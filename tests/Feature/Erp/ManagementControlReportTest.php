<?php

namespace Tests\Feature\Erp;

use App\Models\FicDocument;
use App\Models\User;
use App\Support\Reports\ManagementControlReport;
use Tests\TestCase;

class ManagementControlReportTest extends TestCase
{
    private function issued(int $year, float $net, float $vat, array $extra = []): void
    {
        FicDocument::create(array_merge([
            'fic_company_id' => '1', 'direction' => FicDocument::DIRECTION_ISSUED, 'fic_id' => random_int(1, 1_000_000),
            'issued_on' => "$year-03-10", 'amount_net' => $net, 'amount_vat' => $vat, 'amount_gross' => $net + $vat,
            'paid' => true, 'paid_amount' => $net + $vat, 'company_id' => null,
        ], $extra));
    }

    private function received(int $year, float $net, float $vat, string $category, array $extra = []): void
    {
        FicDocument::create(array_merge([
            'fic_company_id' => '1', 'direction' => FicDocument::DIRECTION_RECEIVED, 'fic_id' => random_int(1, 1_000_000),
            'issued_on' => "$year-02-10", 'category' => $category, 'amount_net' => $net, 'amount_vat' => $vat, 'amount_gross' => $net + $vat,
            'paid' => true, 'paid_amount' => $net + $vat, 'company_id' => null,
        ], $extra));
    }

    public function test_reclassified_income_statement_buckets_costs(): void
    {
        $this->issued(2026, 10000, 2200);
        $this->received(2026, 3000, 660, 'Vendita Ingrosso');   // COGS
        $this->received(2026, 1000, 220, 'Telefono e internet'); // OPEX
        $this->received(2026, 2000, 440, 'Stipendi e salari');   // LABOR
        $this->received(2026, 1000, 220, 'Spese materiali');     // MIXED 70/30 -> 700 COGS, 300 OPEX

        $ce = (new ManagementControlReport())->contoEconomico(null, [2026])[2026];

        $this->assertEqualsWithDelta(10000, $ce['ricavi'], 0.01);
        $this->assertEqualsWithDelta(3700, $ce['cogs'], 0.01);          // 3000 + 700
        $this->assertEqualsWithDelta(6300, $ce['margine_lordo'], 0.01); // 10000 - 3700
        $this->assertEqualsWithDelta(1300, $ce['opex'], 0.01);          // 1000 + 300
        $this->assertEqualsWithDelta(2000, $ce['personale'], 0.01);
        $this->assertEqualsWithDelta(3000, $ce['ebit'], 0.01);          // 6300 - 1300 - 2000
    }

    public function test_iva_balance(): void
    {
        $this->issued(2026, 10000, 2200);
        $this->received(2026, 3000, 660, 'Vendita Ingrosso');

        $iva = (new ManagementControlReport())->iva(null, [2026])[2026];
        $this->assertEqualsWithDelta(2200, $iva['debito'], 0.01);
        $this->assertEqualsWithDelta(660, $iva['credito'], 0.01);
        $this->assertEqualsWithDelta(1540, $iva['saldo'], 0.01);
    }

    public function test_crediti_deduped_by_vat_across_name_variants(): void
    {
        FicDocument::create(['fic_company_id' => '1', 'direction' => FicDocument::DIRECTION_ISSUED, 'fic_id' => 1, 'entity_name' => 'ITALWAY S.R.L.', 'entity_vat' => 'IT123', 'issued_on' => '2026-01-01', 'amount_net' => 100, 'amount_vat' => 0, 'amount_gross' => 100, 'paid' => false, 'paid_amount' => 0, 'company_id' => null]);
        FicDocument::create(['fic_company_id' => '1', 'direction' => FicDocument::DIRECTION_ISSUED, 'fic_id' => 2, 'entity_name' => 'Italway S.r.l.', 'entity_vat' => 'IT123', 'issued_on' => '2026-02-01', 'amount_net' => 50, 'amount_vat' => 0, 'amount_gross' => 50, 'paid' => false, 'paid_amount' => 0, 'company_id' => null]);

        $crediti = (new ManagementControlReport())->creditiDebiti(null)['crediti'];

        $this->assertCount(1, $crediti); // same VAT -> one row, not two
        $this->assertEqualsWithDelta(150, (float) $crediti->first()->aperto, 0.01);
    }

    public function test_page_renders(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('erp.controllo'))
            ->assertOk()
            ->assertSee(trans('erp/controllo.ce_title'));
    }
}
