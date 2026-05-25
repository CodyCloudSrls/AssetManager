<?php

namespace Tests\Feature\Suppliers\Ui;

use App\Models\Supplier;
use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Tests\TestCase;
use ZipArchive;

class AcnExportSuppliersTest extends TestCase
{
    public function test_acn_export_can_be_limited_to_selected_suppliers()
    {
        $selectedRelevantSupplier = Supplier::factory()->create([
            'name' => 'Selected ICT Supplier S.p.A.',
            'tax_code' => '00000000001',
            'nis_relevant' => true,
            'nis_relevance_type' => 'ict_supply',
            'cpv_codes' => "72000000-5\n72200000-7",
            'country' => 'IT',
            'nis_relevance_criteria' => "Servizi di connettivita\nServizi applicativi",
            'notes' => null,
        ]);
        $selectedNonRelevantSupplier = Supplier::factory()->create([
            'name' => 'Selected Non Relevant Supplier Srl',
            'tax_code' => '00000000002',
            'nis_relevant' => false,
            'nis_relevance_type' => 'ict_and_non_fungible',
            'cpv_codes' => '72200000-7',
            'country' => 'IT',
        ]);
        Supplier::factory()->create([
            'name' => 'Unselected Relevant Supplier',
            'nis_relevant' => true,
            'nis_relevance_type' => 'ict_supply',
            'cpv_codes' => '72000000-5',
            'country' => 'IT',
        ]);

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('suppliers.acn_export', [
                'ids' => [
                    $selectedRelevantSupplier->id,
                    $selectedNonRelevantSupplier->id,
                ],
                'nis_relevant' => 1,
            ]))
            ->assertOk();

        $this->assertSame('application/vnd.oasis.opendocument.spreadsheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.ods', $response->headers->get('Content-Disposition'));

        $records = collect($this->odsRows($response->streamedContent(), 'Fornitori'));
        $header = $records->first();
        $rows = $records->slice(1)->filter(fn (array $row) => collect($row)->filter()->isNotEmpty())->values();
        $names = $rows->map(fn (array $row) => $row[2])->values();
        $taxCodes = $rows->map(fn (array $row) => $row[1])->values();
        $cpvCodes = $rows->map(fn (array $row) => $row[3])->values();
        $relevanceTypes = $rows->map(fn (array $row) => $row[5])->values();
        $selectedSupplierNotes = $rows->filter(fn (array $row) => $row[2] === 'Selected ICT Supplier S.p.A.')
            ->map(fn (array $row) => $row[4])
            ->values();

        $this->assertSame([
            'Paese',
            'Codice fiscale/Codice IPA',
            'Ragione sociale',
            'CPV',
            'Note',
            'Criterio di rilevanza',
        ], array_slice($header, 0, 6));
        $this->assertCount(3, $names);
        $this->assertContains('Selected ICT Supplier S.p.A.', $names);
        $this->assertContains('Selected Non Relevant Supplier Srl', $names);
        $this->assertNotContains('Unselected Relevant Supplier', $names);
        $this->assertContains('00000000001', $taxCodes);
        $this->assertContains('00000000002', $taxCodes);
        $this->assertContains('72000000-5', $cpvCodes);
        $this->assertContains('72200000-7', $cpvCodes);
        $this->assertSame(['Servizi di connettivita', 'Servizi applicativi'], $selectedSupplierNotes->all());
        $this->assertContains('Fornitura ICT', $relevanceTypes);
        $this->assertContains('Fornitura ICT non fungibile', $relevanceTypes);
    }

    private function odsRows(string $content, string $sheetName): array
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'acn-export-test-');
        file_put_contents($temporaryPath, $content);

        $zip = new ZipArchive();
        $zip->open($temporaryPath);
        $contentXml = $zip->getFromName('content.xml');
        $zip->close();
        @unlink($temporaryPath);

        $dom = new DOMDocument();
        $dom->loadXML($contentXml);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('table', 'urn:oasis:names:tc:opendocument:xmlns:table:1.0');

        $table = $xpath->query('//table:table[@table:name="'.$sheetName.'"]')->item(0);

        return collect($xpath->query('table:table-row', $table))
            ->map(fn (DOMElement $row) => $this->odsRowCells($xpath, $row))
            ->takeUntil(fn (array $row) => count($row) === 0)
            ->values()
            ->all();
    }

    private function odsRowCells(DOMXPath $xpath, DOMElement $row): array
    {
        $cells = [];

        foreach ($xpath->query('table:table-cell|table:covered-table-cell', $row) as $cell) {
            $repeat = (int) ($cell->getAttribute('table:number-columns-repeated') ?: 1);
            $value = trim(preg_replace('/\s+/u', ' ', $cell->textContent) ?? '');

            for ($index = 0; $index < $repeat && count($cells) < 6; $index++) {
                $cells[] = $value;
            }

            if (count($cells) >= 6) {
                break;
            }
        }

        return $cells;
    }
}
