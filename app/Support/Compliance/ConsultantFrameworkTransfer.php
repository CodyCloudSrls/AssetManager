<?php

namespace App\Support\Compliance;

use App\Models\Company;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use League\Csv\EscapeFormula;
use League\Csv\Reader;
use League\Csv\Writer;
use RuntimeException;
use ZipArchive;

class ConsultantFrameworkTransfer
{
    public const FORMAT_CSV = 'csv';
    public const FORMAT_XLSX = 'xlsx';
    public const FORMAT_DOCX = 'docx';

    private const FRAMEWORK_COLUMNS = [
        'framework_name',
        'framework_slug',
        'framework_code',
        'authority_name',
        'framework_type',
        'compliance_domain',
        'jurisdiction',
        'framework_version',
        'framework_locale',
        'status',
        'review_cadence_months',
        'effective_from',
        'effective_to',
        'external_reference_url',
        'compliance_objective',
        'framework_description',
        'sort_order',
        'is_active',
    ];

    private const REQUIREMENT_COLUMNS = [
        'parent_requirement_code',
        'requirement_code',
        'requirement_title',
        'requirement_domain',
        'obligation_type',
        'evidence_type',
        'delegation_level',
        'risk_level',
        'official_reference',
        'source_url',
        'review_frequency_months',
        'minimum_required_documents',
        'requirement_description',
        'evidence_guidance',
        'applicability_notes',
        'is_mandatory',
        'requirement_is_active',
        'requirement_sort_order',
    ];

    private const HEADER_ALIASES = [
        'framework' => 'framework_name',
        'framework_title' => 'framework_name',
        'name' => 'framework_name',
        'nome_framework' => 'framework_name',
        'nome_del_framework' => 'framework_name',
        'framework_slug' => 'framework_slug',
        'slug' => 'framework_slug',
        'codice_framework' => 'framework_code',
        'authority' => 'authority_name',
        'autorita' => 'authority_name',
        'tipo_framework' => 'framework_type',
        'dominio_compliance' => 'compliance_domain',
        'giurisdizione' => 'jurisdiction',
        'version' => 'framework_version',
        'versione' => 'framework_version',
        'documenti_minimi' => 'minimum_required_documents',
        'min_documenti' => 'minimum_required_documents',
        'minimum_documents' => 'minimum_required_documents',
        'minimum_required_documents' => 'minimum_required_documents',
        'locale' => 'framework_locale',
        'stato' => 'status',
        'review_months' => 'review_cadence_months',
        'intervallo_revisione_mesi' => 'review_cadence_months',
        'valido_da' => 'effective_from',
        'valido_a' => 'effective_to',
        'url_riferimento' => 'external_reference_url',
        'obiettivo_compliance' => 'compliance_objective',
        'description' => 'framework_description',
        'descrizione_framework' => 'framework_description',
        'ordinamento' => 'sort_order',
        'attivo' => 'is_active',
        'parent_code' => 'parent_requirement_code',
        'codice_padre' => 'parent_requirement_code',
        'parent_requirement' => 'parent_requirement_code',
        'code' => 'requirement_code',
        'codice' => 'requirement_code',
        'codice_requisito' => 'requirement_code',
        'title' => 'requirement_title',
        'titolo' => 'requirement_title',
        'titolo_requisito' => 'requirement_title',
        'domain' => 'requirement_domain',
        'dominio_requisito' => 'requirement_domain',
        'tipo_obbligo' => 'obligation_type',
        'tipo_evidenza' => 'evidence_type',
        'livello_delega' => 'delegation_level',
        'livello_rischio' => 'risk_level',
        'riferimento_ufficiale' => 'official_reference',
        'url_fonte' => 'source_url',
        'frequenza_revisione_mesi' => 'review_frequency_months',
        'descrizione_requisito' => 'requirement_description',
        'guida_evidenza' => 'evidence_guidance',
        'note_applicabilita' => 'applicability_notes',
        'obbligatorio' => 'is_mandatory',
        'requisito_attivo' => 'requirement_is_active',
        'ordinamento_requisito' => 'requirement_sort_order',
    ];

    public function import(UploadedFile $file, array $ownership, ?int $createdBy): array
    {
        $extension = $this->uploadedExtension($file);

        if (! in_array($extension, $this->supportedImportExtensions(), true)) {
            $this->fail(trans('admin/documentframeworks/message.import.unsupported_file_type', ['type' => $extension ?: $file->getClientOriginalExtension()]));
        }

        [$companyId, $visibilityType] = Company::normalizeTemplateOwnership(
            $ownership['company_id'] ?? null,
            $ownership['visibility_type'] ?? DocumentFramework::VISIBILITY_PRIVATE,
        );

        if (is_null($companyId)) {
            $this->fail(trans('validation.required', ['attribute' => trans('general.company')]));
        }

        try {
            $records = $this->recordsFromRows(
                $this->readRows($file->getRealPath(), $extension)
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            $this->fail(trans('admin/documentframeworks/message.import.parse_error'));
        }

        $frameworkRecord = $this->frameworkRecord($records);
        $frameworkData = $this->frameworkData($frameworkRecord, $companyId, $visibilityType, $createdBy);
        $requirementData = $this->requirementsData($records, $createdBy, DocumentFramework::looksLikeNis2Domain(
            $frameworkData['compliance_domain'] ?? null,
            $frameworkData
        ));

        $this->assertFrameworkDoesNotExist($frameworkData);

        return DB::transaction(function () use ($frameworkData, $requirementData) {
            $framework = new DocumentFramework;
            $framework->fill($frameworkData);

            if (! $framework->save()) {
                $this->fail(trans('admin/documentframeworks/message.import.save_failed', ['error' => $framework->getErrors()->first()]));
            }

            $requirementsByCode = [];

            foreach ($requirementData as $row) {
                $requirement = new DocumentFrameworkRequirement;
                $requirement->fill($row['attributes']);
                $requirement->document_framework_id = $framework->id;

                if (! $requirement->save()) {
                    $this->fail(trans('admin/documentframeworks/message.import.save_failed', ['error' => $requirement->getErrors()->first()]));
                }

                $requirementsByCode[$requirement->code] = $requirement;
            }

            foreach ($requirementData as $row) {
                $parentCodes = $row['parent_requirement_codes'];

                if ($parentCodes === []) {
                    continue;
                }

                $requirement = $requirementsByCode[$row['attributes']['code']];
                $parentIds = collect($parentCodes)
                    ->map(fn (string $parentCode) => (int) $requirementsByCode[$parentCode]->id)
                    ->values()
                    ->all();
                $requirement->parent_id = $parentIds[0] ?? null;

                if (! $requirement->save()) {
                    $this->fail(trans('admin/documentframeworks/message.import.save_failed', ['error' => $requirement->getErrors()->first()]));
                }

                if (DocumentFrameworkRequirement::parentPivotTableExists()) {
                    $requirement->parents()->sync($parentIds);
                }
            }

            return [
                'framework' => $framework,
                'requirements_count' => count($requirementData),
            ];
        });
    }

    public function export(DocumentFramework $framework, string $format): array
    {
        $format = strtolower($format);

        if (! in_array($format, $this->supportedExportFormats(), true)) {
            throw new RuntimeException('Unsupported export format.');
        }

        $rows = $this->exportRows($framework);
        $path = match ($format) {
            self::FORMAT_CSV => $this->writeCsv($rows),
            self::FORMAT_XLSX => $this->writeXlsx($rows),
            self::FORMAT_DOCX => $this->writeDocx($rows),
        };

        return [
            'path' => $path,
            'filename' => Str::slug($framework->name ?: 'document-framework').'-'.now()->format('Y-m-d').'.'.$format,
            'mime' => $this->mimeType($format),
        ];
    }

    public function supportedImportExtensions(): array
    {
        return [
            self::FORMAT_CSV,
            'tsv',
            'txt',
            self::FORMAT_XLSX,
            self::FORMAT_DOCX,
        ];
    }

    public function supportedExportFormats(): array
    {
        return [
            self::FORMAT_CSV,
            self::FORMAT_XLSX,
            self::FORMAT_DOCX,
        ];
    }

    private function uploadedExtension(UploadedFile $file): string
    {
        return strtolower($file->getClientOriginalExtension() ?: pathinfo((string) $file->getClientOriginalName(), PATHINFO_EXTENSION));
    }

    private function recordsFromRows(array $rows): array
    {
        $rows = array_values(array_filter($rows, fn ($row) => ! $this->rowIsBlank($row)));

        if (count($rows) < 2) {
            $this->fail(trans('admin/documentframeworks/message.import.no_rows'));
        }

        $rawHeaders = array_shift($rows);
        $headerMap = [];
        $seenHeaders = [];

        foreach ($rawHeaders as $index => $header) {
            $key = $this->canonicalHeader($header);

            if ($key === null) {
                continue;
            }

            if (isset($seenHeaders[$key])) {
                $this->fail(trans('admin/documentframeworks/message.import.duplicate_columns', ['column' => $key]));
            }

            $seenHeaders[$key] = true;
            $headerMap[$index] = $key;
        }

        $missingColumns = array_diff(['framework_name', 'requirement_code', 'requirement_title'], array_values($headerMap));

        if ($missingColumns !== []) {
            $this->fail(trans('admin/documentframeworks/message.import.missing_columns', ['columns' => implode(', ', $missingColumns)]));
        }

        $records = [];

        foreach ($rows as $offset => $row) {
            $record = ['_row_number' => $offset + 2];

            foreach ($headerMap as $index => $key) {
                $record[$key] = $this->cleanCell($row[$index] ?? null);
            }

            if (! $this->rowIsBlank($record)) {
                $records[] = $record;
            }
        }

        if ($records === []) {
            $this->fail(trans('admin/documentframeworks/message.import.no_rows'));
        }

        return $records;
    }

    private function frameworkRecord(array $records): array
    {
        $frameworkRecord = $records[0];

        foreach ($records as $record) {
            foreach (self::FRAMEWORK_COLUMNS as $column) {
                $currentValue = $this->cleanCell($record[$column] ?? null);
                $seedValue = $this->cleanCell($frameworkRecord[$column] ?? null);

                if (($currentValue !== '') && ($seedValue !== '') && ($currentValue !== $seedValue)) {
                    $this->fail(trans('admin/documentframeworks/message.import.mixed_framework', ['row' => $record['_row_number'], 'column' => $column]));
                }
            }
        }

        return $frameworkRecord;
    }

    private function frameworkData(array $record, ?int $companyId, string $visibilityType, ?int $createdBy): array
    {
        $name = $this->requiredString($record, 'framework_name', 255);
        $slug = $this->cleanCell($record['framework_slug'] ?? null);
        $effectiveFrom = $this->dateValue($record, 'effective_from');
        $effectiveTo = $this->dateValue($record, 'effective_to');

        if ($effectiveFrom && $effectiveTo && $effectiveTo < $effectiveFrom) {
            $this->fail(trans('admin/documentframeworks/message.import.invalid_date_range'));
        }

        return [
            'name' => $name,
            'slug' => Str::slug($slug !== '' ? $slug : $name),
            'description' => $this->nullableString($record, 'framework_description', 65535),
            'authority_name' => $this->nullableString($record, 'authority_name', 255),
            'framework_code' => $this->nullableString($record, 'framework_code', 80),
            'framework_type' => $this->enumValue($record, 'framework_type', DocumentFramework::getFrameworkTypeOptions(), null),
            'compliance_domain' => $this->enumValue($record, 'compliance_domain', DocumentFramework::complianceDomainOptions(), null),
            'jurisdiction' => $this->nullableString($record, 'jurisdiction', 80),
            'version' => $this->nullableString($record, 'framework_version', 80),
            'locale' => $this->nullableString($record, 'framework_locale', 20),
            'effective_from' => $effectiveFrom,
            'effective_to' => $effectiveTo,
            'owner_id' => null,
            'review_cadence_months' => $this->integerValue($record, 'review_cadence_months', 1, 120, null),
            'status' => $this->enumValue($record, 'status', DocumentFramework::getStatusOptions(), 'active'),
            'external_reference_url' => $this->urlValue($record, 'external_reference_url'),
            'compliance_objective' => $this->nullableString($record, 'compliance_objective', 65535),
            'sort_order' => $this->integerValue($record, 'sort_order', 0, 65535, 0),
            'is_active' => $this->booleanValue($record, 'is_active', true),
            'created_by' => $createdBy,
            'company_id' => $companyId,
            'visibility_type' => $visibilityType,
            'is_system_template' => false,
            'source_framework_id' => null,
            'source_pack_key' => null,
            'source_pack_version' => null,
        ];
    }

    private function requirementsData(array $records, ?int $createdBy, bool $forceRiskNotApplicable): array
    {
        $requirements = [];
        $codes = [];

        foreach ($records as $index => $record) {
            $code = $this->requiredString($record, 'requirement_code', 100);

            if (isset($codes[$code])) {
                $this->fail(trans('admin/documentframeworks/message.import.duplicate_requirement', ['code' => $code]));
            }

            $codes[$code] = true;
            $parentCodes = $this->parentRequirementCodesFromRecord($record);

            $requirements[] = [
                'parent_requirement_codes' => $parentCodes,
                'attributes' => [
                    'parent_id' => null,
                    'code' => $code,
                    'title' => $this->requiredString($record, 'requirement_title', 255),
                    'domain' => $this->nullableString($record, 'requirement_domain', 120),
                    'obligation_type' => $this->enumValue($record, 'obligation_type', DocumentFrameworkRequirement::obligationTypeOptions(), null),
                    'is_mandatory' => $this->booleanValue($record, 'is_mandatory', true),
                    'is_active' => $this->booleanValue($record, 'requirement_is_active', true),
                    'owner_id' => null,
                    'default_document_type_id' => null,
                    'evidence_type' => $this->enumValue($record, 'evidence_type', DocumentFrameworkRequirement::evidenceTypeOptions(), null),
                    'delegation_level' => $this->enumValue($record, 'delegation_level', DocumentFrameworkRequirement::delegationLevelOptions(), 'owner_review'),
                    'risk_level' => $forceRiskNotApplicable
                        ? 'not_applicable'
                        : $this->enumValue($record, 'risk_level', DocumentFrameworkRequirement::riskLevelOptions(), 'medium'),
                    'official_reference' => $this->nullableString($record, 'official_reference', 255),
                    'source_url' => $this->urlValue($record, 'source_url'),
                    'review_frequency_months' => $this->integerValue($record, 'review_frequency_months', 1, 120, null),
                    'minimum_required_documents' => $this->integerValue($record, 'minimum_required_documents', 0, 65535, 1),
                    'sort_order' => $this->integerValue($record, 'requirement_sort_order', 0, 65535, ($index + 1) * 10),
                    'description' => $this->nullableString($record, 'requirement_description', 65535),
                    'evidence_guidance' => $this->nullableString($record, 'evidence_guidance', 65535),
                    'applicability_notes' => $this->nullableString($record, 'applicability_notes', 65535),
                    'created_by' => $createdBy,
                ],
            ];
        }

        foreach ($requirements as $row) {
            $parentCodes = $row['parent_requirement_codes'];

            if ($parentCodes === []) {
                continue;
            }

            foreach ($parentCodes as $parentCode) {
                if ($parentCode === $row['attributes']['code'] || ! isset($codes[$parentCode])) {
                    $this->fail(trans('admin/documentframeworks/message.import.invalid_parent', ['code' => $parentCode]));
                }
            }
        }

        return $requirements;
    }

    private function assertFrameworkDoesNotExist(array $frameworkData): void
    {
        foreach (['name', 'slug'] as $column) {
            $exists = DocumentFramework::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where($column, $frameworkData[$column])
                ->where(function ($query) use ($frameworkData) {
                    if ($frameworkData['company_id'] === null) {
                        $query->whereNull('company_id');
                    } else {
                        $query->where('company_id', $frameworkData['company_id']);
                    }
                })
                ->exists();

            if ($exists) {
                $this->fail(trans('admin/documentframeworks/message.import.duplicate_framework', ['column' => $column, 'value' => $frameworkData[$column]]));
            }
        }
    }

    private function exportRows(DocumentFramework $framework): array
    {
        $requirements = $framework->requirements()
            ->with(DocumentFrameworkRequirement::parentPivotTableExists() ? ['parent', 'parents'] : ['parent'])
            ->ordered()
            ->get();

        $headers = array_merge(self::FRAMEWORK_COLUMNS, self::REQUIREMENT_COLUMNS);
        $frameworkRow = [
            'framework_name' => $framework->name,
            'framework_slug' => $framework->slug,
            'framework_code' => $framework->framework_code,
            'authority_name' => $framework->authority_name,
            'framework_type' => $framework->framework_type,
            'compliance_domain' => $framework->compliance_domain,
            'jurisdiction' => $framework->jurisdiction,
            'framework_version' => $framework->version,
            'framework_locale' => $framework->locale,
            'status' => $framework->status,
            'review_cadence_months' => $framework->review_cadence_months,
            'effective_from' => optional($framework->effective_from)->format('Y-m-d'),
            'effective_to' => optional($framework->effective_to)->format('Y-m-d'),
            'external_reference_url' => $framework->external_reference_url,
            'compliance_objective' => $framework->compliance_objective,
            'framework_description' => $framework->description,
            'sort_order' => $framework->sort_order,
            'is_active' => $framework->is_active ? '1' : '0',
        ];

        if ($requirements->isEmpty()) {
            return [
                $headers,
                array_values(array_merge($frameworkRow, array_fill_keys(self::REQUIREMENT_COLUMNS, ''))),
            ];
        }

        $rows = [$headers];

        foreach ($requirements as $requirement) {
            $requirementRow = [
                'parent_requirement_code' => $requirement->parent_requirement_codes,
                'requirement_code' => $requirement->code,
                'requirement_title' => $requirement->title,
                'requirement_domain' => $requirement->domain,
                'obligation_type' => $requirement->obligation_type,
                'evidence_type' => $requirement->evidence_type,
                'delegation_level' => $requirement->delegation_level,
                'risk_level' => $requirement->effective_risk_level,
                'official_reference' => $requirement->official_reference,
                'source_url' => $requirement->source_url,
                'review_frequency_months' => $requirement->review_frequency_months,
                'minimum_required_documents' => $requirement->minimum_required_documents,
                'requirement_description' => $requirement->description,
                'evidence_guidance' => $requirement->evidence_guidance,
                'applicability_notes' => $requirement->applicability_notes,
                'is_mandatory' => $requirement->is_mandatory ? '1' : '0',
                'requirement_is_active' => $requirement->is_active ? '1' : '0',
                'requirement_sort_order' => $requirement->sort_order,
            ];

            $rows[] = array_values(array_merge($frameworkRow, $requirementRow));
        }

        return $rows;
    }

    private function writeCsv(array $rows): string
    {
        $path = $this->temporaryPath('csv');
        $writer = Writer::createFromPath($path, 'w+');
        $formatter = new EscapeFormula('`');

        foreach ($rows as $row) {
            $writer->insertOne($formatter->escapeRecord($row));
        }

        return $path;
    }

    private function writeXlsx(array $rows): string
    {
        $path = $this->temporaryPath('xlsx');
        $zip = $this->openZipForWrite($path);

        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypes());
        $zip->addFromString('_rels/.rels', $this->packageRelationships('xl/workbook.xml'));
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRelationships());
        $zip->addFromString('xl/styles.xml', $this->xlsxStyles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->xlsxWorksheet($rows));
        $zip->addFromString('docProps/core.xml', $this->coreProperties());
        $zip->addFromString('docProps/app.xml', $this->appProperties('Worksheet'));
        $zip->close();

        return $path;
    }

    private function writeDocx(array $rows): string
    {
        $path = $this->temporaryPath('docx');
        $zip = $this->openZipForWrite($path);

        $zip->addFromString('[Content_Types].xml', $this->docxContentTypes());
        $zip->addFromString('_rels/.rels', $this->packageRelationships('word/document.xml'));
        $zip->addFromString('word/document.xml', $this->docxDocument($rows));
        $zip->addFromString('docProps/core.xml', $this->coreProperties());
        $zip->addFromString('docProps/app.xml', $this->appProperties('Document'));
        $zip->close();

        return $path;
    }

    private function readRows(string|false $path, string $extension): array
    {
        if (! is_string($path) || ! is_readable($path)) {
            throw new RuntimeException('Upload is not readable.');
        }

        return match ($extension) {
            self::FORMAT_XLSX => $this->readXlsxRows($path),
            self::FORMAT_DOCX => $this->readDocxRows($path),
            'tsv' => $this->readDelimitedRows($path, "\t"),
            default => $this->readDelimitedRows($path, $this->detectDelimiter($path)),
        };
    }

    private function readDelimitedRows(string $path, string $delimiter): array
    {
        $reader = Reader::createFromPath($path, 'r');
        $reader->setDelimiter($delimiter);

        $rows = [];

        foreach ($reader as $row) {
            $rows[] = array_map(fn ($value) => $this->cleanCell($value), $row);
        }

        return $rows;
    }

    private function readXlsxRows(string $path): array
    {
        $zip = $this->openZipForRead($path);
        $sheetPath = $this->firstWorksheetPath($zip);
        $sheetXml = $zip->getFromName($sheetPath);

        if ($sheetXml === false) {
            $zip->close();
            throw new RuntimeException('Worksheet not found.');
        }

        $sharedStrings = $this->xlsxSharedStrings($zip);
        $zip->close();

        $dom = $this->loadXml($sheetXml);
        $xpath = new \DOMXPath($dom);
        $rows = [];

        foreach ($xpath->query('//*[local-name()="sheetData"]/*[local-name()="row"]') as $rowNode) {
            $row = [];
            $sequentialIndex = 0;

            foreach ($xpath->query('./*[local-name()="c"]', $rowNode) as $cellNode) {
                $cellReference = $cellNode->attributes?->getNamedItem('r')?->nodeValue;
                $columnIndex = $cellReference ? $this->columnIndexFromCellReference($cellReference) : $sequentialIndex;
                $row[$columnIndex] = $this->xlsxCellValue($xpath, $cellNode, $sharedStrings);
                $sequentialIndex = $columnIndex + 1;
            }

            if ($row !== []) {
                ksort($row);
                $rows[] = $this->denseRow($row);
            }
        }

        return $rows;
    }

    private function readDocxRows(string $path): array
    {
        $zip = $this->openZipForRead($path);
        $documentXml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($documentXml === false) {
            throw new RuntimeException('Document body not found.');
        }

        $dom = $this->loadXml($documentXml);
        $xpath = new \DOMXPath($dom);
        $table = $xpath->query('//*[local-name()="tbl"]')->item(0);

        if ($table === null) {
            throw new RuntimeException('Document table not found.');
        }

        $rows = [];

        foreach ($xpath->query('./*[local-name()="tr"]', $table) as $rowNode) {
            $row = [];

            foreach ($xpath->query('./*[local-name()="tc"]', $rowNode) as $cellNode) {
                $row[] = $this->wordCellText($xpath, $cellNode);
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function wordCellText(\DOMXPath $xpath, \DOMNode $cellNode): string
    {
        $paragraphs = $xpath->query('./*[local-name()="p"]', $cellNode);
        $parts = [];

        if ($paragraphs->length === 0) {
            $paragraphs = $xpath->query('.//*[local-name()="p"]', $cellNode);
        }

        foreach ($paragraphs as $paragraph) {
            $parts[] = $this->wordNodeText($xpath, $paragraph);
        }

        if ($parts === []) {
            return $this->wordNodeText($xpath, $cellNode);
        }

        return $this->cleanCell(implode("\n", $parts));
    }

    private function wordNodeText(\DOMXPath $xpath, \DOMNode $node): string
    {
        $parts = [];

        foreach ($xpath->query('.//*[local-name()="t" or local-name()="tab" or local-name()="br"]', $node) as $childNode) {
            if ($childNode->localName === 'tab') {
                $parts[] = "\t";
            } elseif ($childNode->localName === 'br') {
                $parts[] = "\n";
            } else {
                $parts[] = $childNode->textContent;
            }
        }

        return implode('', $parts);
    }

    private function firstWorksheetPath(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = $this->loadXml($workbookXml);
        $workbookPath = new \DOMXPath($workbook);
        $sheet = $workbookPath->query('//*[local-name()="sheet"]')->item(0);
        $relationshipId = $sheet?->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id')
            ?: $sheet?->attributes?->getNamedItem('r:id')?->nodeValue;

        if (! $relationshipId) {
            return 'xl/worksheets/sheet1.xml';
        }

        $rels = $this->loadXml($relsXml);
        $relsPath = new \DOMXPath($rels);

        foreach ($relsPath->query('//*[local-name()="Relationship"]') as $relationship) {
            if ($relationship->attributes?->getNamedItem('Id')?->nodeValue !== $relationshipId) {
                continue;
            }

            $target = $relationship->attributes?->getNamedItem('Target')?->nodeValue ?: 'worksheets/sheet1.xml';
            $target = ltrim($target, '/');

            return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function xlsxSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $dom = $this->loadXml($xml);
        $xpath = new \DOMXPath($dom);
        $strings = [];

        foreach ($xpath->query('//*[local-name()="si"]') as $node) {
            $parts = [];

            foreach ($xpath->query('.//*[local-name()="t"]', $node) as $textNode) {
                $parts[] = $textNode->textContent;
            }

            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    private function xlsxCellValue(\DOMXPath $xpath, \DOMNode $cellNode, array $sharedStrings): string
    {
        $type = $cellNode->attributes?->getNamedItem('t')?->nodeValue;

        if ($type === 'inlineStr') {
            $parts = [];

            foreach ($xpath->query('.//*[local-name()="is"]//*[local-name()="t"]', $cellNode) as $textNode) {
                $parts[] = $textNode->textContent;
            }

            return $this->cleanCell(implode('', $parts));
        }

        $valueNode = $xpath->query('./*[local-name()="v"]', $cellNode)->item(0);
        $value = $valueNode?->textContent ?? '';

        if ($type === 's') {
            return $this->cleanCell($sharedStrings[(int) $value] ?? '');
        }

        return $this->cleanCell($value);
    }

    private function denseRow(array $row): array
    {
        $max = max(array_keys($row));
        $dense = [];

        for ($index = 0; $index <= $max; $index++) {
            $dense[] = $row[$index] ?? '';
        }

        return $dense;
    }

    private function canonicalHeader(?string $header): ?string
    {
        $normalized = $this->normalizeToken($header);

        if ($normalized === '') {
            return null;
        }

        $columns = array_merge(self::FRAMEWORK_COLUMNS, self::REQUIREMENT_COLUMNS);

        if (in_array($normalized, $columns, true)) {
            return $normalized;
        }

        return self::HEADER_ALIASES[$normalized] ?? null;
    }

    private function enumValue(array $record, string $column, array $options, ?string $default): ?string
    {
        $value = $this->cleanCell($record[$column] ?? null);

        if ($value === '') {
            return $default;
        }

        if (array_key_exists($value, $options)) {
            return $value;
        }

        $normalized = $this->normalizeToken($value);

        foreach ($options as $key => $label) {
            if ($normalized === $this->normalizeToken($key) || $normalized === $this->normalizeToken($label)) {
                return $key;
            }
        }

        $this->fail(trans('admin/documentframeworks/message.import.invalid_enum', [
            'column' => $column,
            'row' => $record['_row_number'] ?? 1,
        ]));
    }

    private function booleanValue(array $record, string $column, bool $default): bool
    {
        $value = $this->cleanCell($record[$column] ?? null);

        if ($value === '') {
            return $default;
        }

        $normalized = $this->normalizeToken($value);

        if (in_array($normalized, ['1', 'true', 'yes', 'y', 'si', 'oui', 'ja', 'on'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'n', 'non', 'nein', 'off'], true)) {
            return false;
        }

        $this->fail(trans('admin/documentframeworks/message.import.invalid_boolean', [
            'column' => $column,
            'row' => $record['_row_number'] ?? 1,
        ]));
    }

    private function integerValue(array $record, string $column, int $min, int $max, ?int $default): ?int
    {
        $value = $this->cleanCell($record[$column] ?? null);

        if ($value === '') {
            return $default;
        }

        if (! preg_match('/^-?\d+$/', $value)) {
            $this->fail(trans('admin/documentframeworks/message.import.invalid_number', [
                'column' => $column,
                'row' => $record['_row_number'] ?? 1,
            ]));
        }

        $integer = (int) $value;

        if ($integer < $min || $integer > $max) {
            $this->fail(trans('admin/documentframeworks/message.import.invalid_number', [
                'column' => $column,
                'row' => $record['_row_number'] ?? 1,
            ]));
        }

        return $integer;
    }

    private function dateValue(array $record, string $column): ?string
    {
        $value = $this->cleanCell($record[$column] ?? null);

        if ($value === '') {
            return null;
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $this->fail(trans('admin/documentframeworks/message.import.invalid_date', [
                'column' => $column,
                'row' => $record['_row_number'] ?? 1,
            ]));
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        if (! checkdate($month, $day, $year)) {
            $this->fail(trans('admin/documentframeworks/message.import.invalid_date', [
                'column' => $column,
                'row' => $record['_row_number'] ?? 1,
            ]));
        }

        return $value;
    }

    private function urlValue(array $record, string $column): ?string
    {
        $value = $this->cleanCell($record[$column] ?? null);

        if ($value === '') {
            return null;
        }

        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            $this->fail(trans('admin/documentframeworks/message.import.invalid_url', [
                'column' => $column,
                'row' => $record['_row_number'] ?? 1,
            ]));
        }

        return $value;
    }

    private function requiredString(array $record, string $column, int $max): string
    {
        $value = $this->cleanCell($record[$column] ?? null);

        if ($value === '' || mb_strlen($value) > $max) {
            $this->fail(trans('admin/documentframeworks/message.import.invalid_required', [
                'column' => $column,
                'row' => $record['_row_number'] ?? 1,
            ]));
        }

        return $value;
    }

    private function nullableString(array $record, string $column, int $max): ?string
    {
        $value = $this->cleanCell($record[$column] ?? null);

        if ($value === '') {
            return null;
        }

        if (mb_strlen($value) > $max) {
            $this->fail(trans('admin/documentframeworks/message.import.invalid_required', [
                'column' => $column,
                'row' => $record['_row_number'] ?? 1,
            ]));
        }

        return $value;
    }

    private function parentRequirementCodesFromRecord(array $record): array
    {
        $value = $this->cleanCell($record['parent_requirement_code'] ?? null);

        if ($value === '') {
            return [];
        }

        if (mb_strlen($value) > 1000) {
            $this->fail(trans('admin/documentframeworks/message.import.invalid_required', [
                'column' => 'parent_requirement_code',
                'row' => $record['_row_number'] ?? 1,
            ]));
        }

        return collect(preg_split('/[;,|]+/', $value) ?: [])
            ->map(fn ($code) => trim((string) $code))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function rowIsBlank(array $row): bool
    {
        foreach ($row as $key => $value) {
            if ($key === '_row_number') {
                continue;
            }

            if ($this->cleanCell($value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function cleanCell($value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", (string) $value);

        if (str_starts_with($value, "\xEF\xBB\xBF")) {
            $value = substr($value, 3);
        }

        return trim($value);
    }

    private function normalizeToken($value): string
    {
        $value = Str::ascii(Str::lower($this->cleanCell($value)));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?: '';

        return trim($value, '_');
    }

    private function detectDelimiter(string $path): string
    {
        $line = '';
        $handle = fopen($path, 'r');

        if (is_resource($handle)) {
            $line = (string) fgets($handle);
            fclose($handle);
        }

        $delimiters = [',' => substr_count($line, ','), ';' => substr_count($line, ';'), "\t" => substr_count($line, "\t")];
        arsort($delimiters);

        return (string) array_key_first($delimiters);
    }

    private function openZipForRead(string $path): ZipArchive
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Unable to open zip package.');
        }

        return $zip;
    }

    private function openZipForWrite(string $path): ZipArchive
    {
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create zip package.');
        }

        return $zip;
    }

    private function loadXml(string $xml): \DOMDocument
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument;
        $loaded = $dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new RuntimeException('Invalid XML.');
        }

        return $dom;
    }

    private function columnIndexFromCellReference(string $reference): int
    {
        preg_match('/^([A-Z]+)/i', $reference, $matches);
        $letters = strtoupper($matches[1] ?? 'A');
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    private function cellReference(int $columnIndex, int $rowIndex): string
    {
        $column = '';
        $index = $columnIndex + 1;

        while ($index > 0) {
            $modulo = ($index - 1) % 26;
            $column = chr(65 + $modulo).$column;
            $index = intdiv($index - $modulo, 26);
        }

        return $column.$rowIndex;
    }

    private function xlsxWorksheet(array $rows): string
    {
        $sheetRows = '';

        foreach ($rows as $rowIndex => $row) {
            $cells = '';
            $excelRow = $rowIndex + 1;

            foreach ($row as $columnIndex => $value) {
                $reference = $this->cellReference($columnIndex, $excelRow);
                $cells .= '<c r="'.$reference.'" t="inlineStr"><is><t xml:space="preserve">'.$this->xml($value).'</t></is></c>';
            }

            $sheetRows .= '<row r="'.$excelRow.'">'.$cells.'</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheetData>'.$sheetRows.'</sheetData>'
            .'</worksheet>';
    }

    private function docxDocument(array $rows): string
    {
        $tableRows = '';

        foreach ($rows as $row) {
            $cells = '';

            foreach ($row as $value) {
                $cells .= '<w:tc><w:tcPr><w:tcW w:w="2400" w:type="dxa"/></w:tcPr><w:p><w:r>'.$this->wordText($value).'</w:r></w:p></w:tc>';
            }

            $tableRows .= '<w:tr>'.$cells.'</w:tr>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:body><w:tbl><w:tblPr><w:tblW w:w="0" w:type="auto"/></w:tblPr>'.$tableRows.'</w:tbl>'
            .'<w:sectPr><w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/><w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>'
            .'</w:body></w:document>';
    }

    private function wordText($value): string
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", (string) $value));
        $parts = [];

        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $parts[] = '<w:br/>';
            }

            $parts[] = '<w:t xml:space="preserve">'.$this->xml($line).'</w:t>';
        }

        return implode('', $parts);
    }

    private function xlsxContentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .'</Types>';
    }

    private function docxContentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .'</Types>';
    }

    private function packageRelationships(string $officeDocumentTarget): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="'.$officeDocumentTarget.'"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
    }

    private function xlsxWorkbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Framework" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function xlsxWorkbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function xlsxStyles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            .'</styleSheet>';
    }

    private function coreProperties(): string
    {
        $timestamp = now()->toIso8601String();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:creator>Snipe-IT</dc:creator><cp:lastModifiedBy>Snipe-IT</cp:lastModifiedBy>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$timestamp.'</dcterms:created>'
            .'<dcterms:modified xsi:type="dcterms:W3CDTF">'.$timestamp.'</dcterms:modified>'
            .'</cp:coreProperties>';
    }

    private function appProperties(string $application): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Application>'.$application.'</Application>'
            .'</Properties>';
    }

    private function temporaryPath(string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'framework-transfer-');

        if ($path === false) {
            throw new RuntimeException('Unable to create temporary file.');
        }

        $target = $path.'.'.$extension;
        rename($path, $target);

        return $target;
    }

    private function mimeType(string $format): string
    {
        return match ($format) {
            self::FORMAT_CSV => 'text/csv',
            self::FORMAT_XLSX => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::FORMAT_DOCX => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        };
    }

    private function xml($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function fail(string $message): void
    {
        throw ValidationException::withMessages(['file' => [$message]]);
    }
}
