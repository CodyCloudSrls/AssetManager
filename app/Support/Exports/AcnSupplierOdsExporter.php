<?php

namespace App\Support\Exports;

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

    public function buildFromQuery(Builder $query): string
    {
        $templatePath = base_path('docs/ACN_Template_fornitori.ods');

        if (! is_file($templatePath)) {
            throw new RuntimeException('ACN supplier ODS template not found.');
        }

        $exportRows = [];
        $query->chunkById(200, function ($suppliers) use (&$exportRows) {
            foreach ($suppliers as $supplier) {
                array_push($exportRows, ...$this->supplierRows($supplier));
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

        if ($cpvCodes === []) {
            $cpvCodes = [''];
        }

        return collect($cpvCodes)->map(fn (?string $cpvCode) => [
            $this->normalizeCountry($supplier->country),
            $this->cellText($supplier->tax_code),
            $this->cellText($supplier->name),
            $this->cellText($cpvCode),
            $this->supplierNotes($supplier),
            $this->acnRelevanceType($supplier->nis_relevance_type),
        ])->all();
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
        $country = strtoupper(trim((string) $value));

        return preg_match('/^[A-Z]{2}$/', $country) ? $country : '';
    }

    private function supplierNotes(Supplier $supplier): string
    {
        return collect([
            $supplier->nis_relevance_criteria,
            $supplier->notes,
        ])
            ->map(fn ($value) => $this->cellText($value))
            ->filter()
            ->implode(' | ');
    }

    private function cellText($value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
    }
}
