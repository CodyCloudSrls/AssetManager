<?php

namespace Tests\Feature\Suppliers\Ui;

use App\Models\Supplier;
use App\Models\User;
use League\Csv\Reader;
use Tests\TestCase;

class AcnExportSuppliersTest extends TestCase
{
    public function test_acn_export_can_be_limited_to_selected_suppliers()
    {
        $selectedRelevantSupplier = Supplier::factory()->create([
            'name' => 'TIM S.p.A.',
            'tax_code' => '00488410010',
            'nis_relevant' => true,
        ]);
        $selectedNonRelevantSupplier = Supplier::factory()->create([
            'name' => 'CodyCloud Srl',
            'nis_relevant' => false,
        ]);
        Supplier::factory()->create([
            'name' => 'Unselected Relevant Supplier',
            'nis_relevant' => true,
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

        $records = collect(Reader::createFromString($response->streamedContent())->getRecords())
            ->values();
        $header = $records->first();
        $names = $records->slice(1)->map(fn (array $row) => $row[2])->values();
        $taxCodes = $records->slice(1)->map(fn (array $row) => $row[3])->values();

        $this->assertSame(trans('admin/suppliers/table.tax_code'), $header[3]);
        $this->assertCount(2, $names);
        $this->assertContains('TIM S.p.A.', $names);
        $this->assertContains('CodyCloud Srl', $names);
        $this->assertNotContains('Unselected Relevant Supplier', $names);
        $this->assertContains('00488410010', $taxCodes);
    }
}
