<?php

namespace App\Support\Exports;

use App\Models\CpvCode;
use App\Models\Supplier;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use ZipArchive;

class AcnSupplierOdsExporter
{
    private const OFFICE_NS = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const TABLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    private const TEXT_NS = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
    private const MAX_SHEET_ROWS = 1048576;
    private const COUNTRY_ALIASES = [
        'EL' => 'GR',
        'GREAT BRITAIN' => 'GB',
        'GRECIA' => 'GR',
        'GREECE' => 'GR',
        'ITALIA' => 'IT',
        'ITALY' => 'IT',
        'NORTHERN IRELAND' => 'GB',
        'REGNO UNITO' => 'GB',
        'SCOTLAND' => 'GB',
        'STATI UNITI' => 'US',
        'STATI UNITI D AMERICA' => 'US',
        'UK' => 'GB',
        'UNITED KINGDOM' => 'GB',
        'UNITED STATES' => 'US',
        'UNITED STATES OF AMERICA' => 'US',
        'USA' => 'US',
        'WALES' => 'GB',
    ];

    public function buildFromQuery(Builder $query): string
    {
        $templatePath = base_path('docs/ACN_Template_fornitori.ods');

        if (! is_file($templatePath)) {
            throw new RuntimeException('ACN supplier ODS template not found.');
        }

        $exportRows = [];
        $exportRowIndexesByKey = [];
        $query->chunkById(200, function ($suppliers) use (&$exportRows, &$exportRowIndexesByKey) {
            foreach ($suppliers as $supplier) {
                foreach ($this->supplierRows($supplier) as $supplierRow) {
                    $this->appendSupplierRow($exportRows, $exportRowIndexesByKey, $supplierRow);
                }
            }
        }, 'suppliers.id', 'id');

        if ($exportRows === []) {
            $exportRows[] = ['', '', '', '', '', ''];
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'acn-suppliers-');
        if ($temporaryPath === false || ! copy($templatePath, $temporaryPath)) {
            throw new RuntimeException('Unable to prepare ACN supplier ODS export.');
        }

        $zip = new ZipArchive();
        if ($zip->open($temporaryPath) !== true) {
            @unlink($temporaryPath);
            throw new RuntimeException('Unable to open ACN supplier ODS template.');
        }

        $contentXml = $zip->getFromName('content.xml');
        if ($contentXml === false) {
            $zip->close();
            @unlink($temporaryPath);
            throw new RuntimeException('ACN supplier ODS template is missing content.xml.');
        }

        $zip->deleteName('content.xml');
        $zip->addFromString('content.xml', $this->contentXmlWithSupplierRows($contentXml, $exportRows));
        $zip->close();

        return $temporaryPath;
    }

    private function supplierRows(Supplier $supplier): array
    {
        $cpvCodes = Supplier::cpvCodesFromText($supplier->cpv_codes);
        $criteriaByCpvCode = $this->criteriaByCpvCode($supplier, $cpvCodes);

        if ($cpvCodes === []) {
            $cpvCodes = [''];
        }

        return collect($cpvCodes)->map(fn (?string $cpvCode) => [
            $this->normalizeCountry($supplier->country),
            $this->cellText($supplier->tax_code),
            $this->cellText($supplier->name),
            $this->cellText($cpvCode),
            $this->supplierNotes($supplier, $cpvCode, $criteriaByCpvCode),
            $this->acnRelevanceType($supplier->nis_relevance_type),
        ])->all();
    }

    private function appendSupplierRow(array &$exportRows, array &$exportRowIndexesByKey, array $supplierRow): void
    {
        $deduplicationKey = $this->supplierRowDeduplicationKey($supplierRow);

        if ($deduplicationKey && array_key_exists($deduplicationKey, $exportRowIndexesByKey)) {
            $existingIndex = $exportRowIndexesByKey[$deduplicationKey];
            $exportRows[$existingIndex] = $this->mergeSupplierRows($exportRows[$existingIndex], $supplierRow);

            return;
        }

        if ($deduplicationKey) {
            $exportRowIndexesByKey[$deduplicationKey] = count($exportRows);
        }

        $exportRows[] = $supplierRow;
    }

    private function supplierRowDeduplicationKey(array $supplierRow): ?string
    {
        $taxCode = strtoupper($this->cellText($supplierRow[1] ?? ''));
        $cpvCode = strtoupper($this->cellText($supplierRow[3] ?? ''));

        if ($taxCode === '' || $cpvCode === '') {
            return null;
        }

        return $taxCode.'|'.$cpvCode;
    }

    private function mergeSupplierRows(array $existingRow, array $duplicateRow): array
    {
        foreach ([0, 2, 5] as $index) {
            if (($existingRow[$index] ?? '') === '' && ($duplicateRow[$index] ?? '') !== '') {
                $existingRow[$index] = $duplicateRow[$index];
            }
        }

        $existingRow[4] = $this->mergeNotes($existingRow[4] ?? '', $duplicateRow[4] ?? '');

        return $existingRow;
    }

    private function contentXmlWithSupplierRows(string $contentXml, array $exportRows): string
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($contentXml);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('table', self::TABLE_NS);

        $table = $xpath->query('//table:table[@table:name="Fornitori"]')->item(0);
        if (! $table instanceof DOMElement) {
            throw new RuntimeException('ACN supplier ODS template is missing the Fornitori sheet.');
        }

        $existingRows = iterator_to_array($xpath->query('table:table-row', $table));
        $headerRow = $existingRows[0] ?? null;
        $blankTailRow = end($existingRows);

        if (! $headerRow instanceof DOMElement || ! $blankTailRow instanceof DOMElement) {
            throw new RuntimeException('ACN supplier ODS template has an unexpected Fornitori sheet layout.');
        }

        foreach (array_slice($existingRows, 1) as $row) {
            $table->removeChild($row);
        }

        $lastDataRow = count($exportRows) - 1;
        foreach ($exportRows as $index => $row) {
            $table->appendChild($this->createSupplierRow($dom, $row, $this->rowPosition($index, $lastDataRow)));
        }

        $remainingRows = self::MAX_SHEET_ROWS - 1 - count($exportRows);
        if ($remainingRows > 0) {
            $tail = $blankTailRow->cloneNode(true);
            $tail->setAttributeNS(self::TABLE_NS, 'table:number-rows-repeated', (string) $remainingRows);
            $table->appendChild($tail);
        }

        return $dom->saveXML();
    }

    private function createSupplierRow(DOMDocument $dom, array $values, string $position): DOMElement
    {
        $styles = match ($position) {
            'first' => ['ce6', 'ce7', 'ce7', 'ce7', 'ce7', 'ce8'],
            'last' => ['ce12', 'ce13', 'ce13', 'ce13', 'ce13', 'ce14'],
            default => ['ce9', 'ce10', 'ce10', 'ce10', 'ce10', 'ce11'],
        };

        $validations = ['val3', null, null, 'val2', null, 'val1'];

        $row = $dom->createElementNS(self::TABLE_NS, 'table:table-row');
        $row->setAttributeNS(self::TABLE_NS, 'table:style-name', 'ro1');

        foreach (array_values($values) as $index => $value) {
            $row->appendChild($this->createStringCell(
                $dom,
                $styles[$index],
                $this->cellText($value),
                $validations[$index]
            ));
        }

        $emptyCells = $dom->createElementNS(self::TABLE_NS, 'table:table-cell');
        $emptyCells->setAttributeNS(self::TABLE_NS, 'table:number-columns-repeated', '16378');
        $row->appendChild($emptyCells);

        return $row;
    }

    private function createStringCell(DOMDocument $dom, string $style, string $value, ?string $validation): DOMElement
    {
        $cell = $dom->createElementNS(self::TABLE_NS, 'table:table-cell');
        $cell->setAttributeNS(self::TABLE_NS, 'table:style-name', $style);
        $cell->setAttributeNS(self::OFFICE_NS, 'office:value-type', 'string');

        if ($validation) {
            $cell->setAttributeNS(self::TABLE_NS, 'table:content-validation-name', $validation);
        }

        $paragraph = $dom->createElementNS(self::TEXT_NS, 'text:p');
        $paragraph->appendChild($dom->createTextNode($value));
        $cell->appendChild($paragraph);

        return $cell;
    }

    private function rowPosition(int $index, int $lastDataRow): string
    {
        if ($index === 0 && $lastDataRow > 0) {
            return 'first';
        }

        if ($index === $lastDataRow) {
            return 'last';
        }

        return 'middle';
    }

    private function acnRelevanceType(?string $value): string
    {
        return match ($value) {
            'ict_supply' => 'Fornitura ICT',
            'non_fungible' => 'Fornitura non fungibile',
            'ict_and_non_fungible' => 'Fornitura ICT non fungibile',
            default => '',
        };
    }

    private function normalizeCountry(?string $value): string
    {
        $country = $this->countryLookupKey($value);

        if ($country === '') {
            return '';
        }

        $country = self::COUNTRY_ALIASES[$country] ?? $country;
        $knownCountryCodes = $this->knownCountryCodes();

        if (preg_match('/^[A-Z]{2}$/', $country)) {
            return $knownCountryCodes === [] || in_array($country, $knownCountryCodes, true) ? $country : '';
        }

        return $this->countryCodeFromName($country, $knownCountryCodes);
    }

    private function supplierNotes(Supplier $supplier, ?string $cpvCode = null, array $criteriaByCpvCode = []): string
    {
        $criteria = $cpvCode && array_key_exists($cpvCode, $criteriaByCpvCode)
            ? $criteriaByCpvCode[$cpvCode]
            : $supplier->nis_relevance_criteria;

        return collect([
            $criteria,
            $supplier->notes,
        ])
            ->map(fn ($value) => $this->cellText($value))
            ->filter()
            ->implode(' | ');
    }

    private function mergeNotes(string $existingNotes, string $duplicateNotes): string
    {
        return collect([$existingNotes, $duplicateNotes])
            ->flatMap(fn ($notes) => explode(' | ', $notes))
            ->map(fn ($notes) => $this->cellText($notes))
            ->filter()
            ->unique()
            ->values()
            ->implode(' | ');
    }

    private function knownCountryCodes(): array
    {
        static $countryCodes = null;

        if (is_array($countryCodes)) {
            return $countryCodes;
        }

        $templatePath = base_path('docs/ACN_Template_fornitori.ods');
        if (! is_file($templatePath)) {
            return $countryCodes = [];
        }

        $zip = new ZipArchive();
        if ($zip->open($templatePath) !== true) {
            return $countryCodes = [];
        }

        $contentXml = $zip->getFromName('content.xml');
        $zip->close();

        if ($contentXml === false) {
            return $countryCodes = [];
        }

        $dom = new DOMDocument();
        $dom->loadXML($contentXml);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('table', self::TABLE_NS);

        $countryCodes = [];
        $rows = $xpath->query('//table:table[@table:name="DATA_VALIDATION"]/table:table-row');

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }

            $cell = $xpath->query('table:table-cell', $row)->item(0);
            $code = $cell ? $this->cellText($cell->textContent) : '';

            if (preg_match('/^[A-Z]{2}$/', $code)) {
                $countryCodes[] = $code;
            }
        }

        return $countryCodes = array_values(array_unique($countryCodes));
    }

    private function countryCodeFromName(string $country, array $knownCountryCodes): string
    {
        $countries = trans('localizations.countries');

        if (! is_array($countries)) {
            return '';
        }

        foreach ($countries as $code => $name) {
            $code = self::COUNTRY_ALIASES[$this->countryLookupKey($code)] ?? (string) $code;

            if ($knownCountryCodes !== [] && ! in_array($code, $knownCountryCodes, true)) {
                continue;
            }

            if ($this->countryLookupKey($name) === $country) {
                return $code;
            }
        }

        return '';
    }

    private function criteriaByCpvCode(Supplier $supplier, array $cpvCodes): array
    {
        $criteriaLines = $this->nonEmptyLines($supplier->nis_relevance_criteria);

        if ($criteriaLines === []) {
            return [];
        }

        $rawCodes = $this->cpvCodesPreservingOrder($supplier->cpv_codes);
        $mapped = [];

        if (count($rawCodes) === count($criteriaLines)) {
            foreach ($rawCodes as $index => $code) {
                $mapped[$code] ??= $criteriaLines[$index];
            }

            return $mapped;
        }

        if (count($cpvCodes) === count($criteriaLines)) {
            foreach (array_values($cpvCodes) as $index => $code) {
                $mapped[$code] ??= $criteriaLines[$index];
            }
        }

        return $mapped;
    }

    private function cpvCodesPreservingOrder(?string $value): array
    {
        preg_match_all('/\b\d{8}-?\d\b/', (string) $value, $matches);

        return collect($matches[0] ?? [])
            ->map(fn ($code) => CpvCode::normalizeCode($code))
            ->filter(fn ($code) => is_string($code) && preg_match('/^\d{8}-\d$/', $code))
            ->values()
            ->all();
    }

    private function nonEmptyLines(?string $value): array
    {
        return collect(preg_split('/\R/u', (string) $value) ?: [])
            ->map(fn ($line) => $this->cellText($line))
            ->filter()
            ->values()
            ->all();
    }

    private function countryLookupKey($value): string
    {
        $country = strtoupper($this->cellText($value));
        $country = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $country) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $country) ?? '');
    }

    private function cellText($value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
    }
}
