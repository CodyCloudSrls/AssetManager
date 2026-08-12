<?php

namespace Tests\Feature\Erp;

use App\Models\Asset;
use App\Models\FicDocument;
use App\Models\Supplier;
use App\Support\Fic\FicReconcileService;
use Tests\TestCase;

/**
 * Phase-0 FiC→Asset reconciliation report: the VAT normalisation + supplier/asset classification
 * are the load-bearing logic. This pins them, since the (future) write phase keys off exactly
 * these buckets.
 */
class FicReconcileReportTest extends TestCase
{
    public function test_vat_normalisation_accepts_it_vat_and_rejects_noise(): void
    {
        $this->assertSame('12345678901', FicReconcileService::normalizeVat('  12345678901 '));
        $this->assertSame('12345678901', FicReconcileService::normalizeVat('IT12345678901'));
        $this->assertNull(FicReconcileService::normalizeVat(''));
        $this->assertNull(FicReconcileService::normalizeVat('ESTERO'));
        $this->assertNull(FicReconcileService::normalizeVat('EU826002156'));   // foreign VAT
        $this->assertNull(FicReconcileService::normalizeVat('B83357863'));     // ES VAT
        $this->assertNull(FicReconcileService::normalizeVat('XXXXXXX1'));      // placeholder
        $this->assertNull(FicReconcileService::normalizeVat('1234567890'));    // 10 digits, not a P.IVA
    }

    private function supplier(string $name, string $taxCode): Supplier
    {
        return Supplier::create(['name' => $name, 'tax_code' => $taxCode, 'visibility_type' => 'global']);
    }

    private function receivedDoc(string $ficId, string $vat, string $name): void
    {
        FicDocument::create([
            'fic_company_id' => '999',
            'direction' => FicDocument::DIRECTION_RECEIVED,
            'fic_id' => $ficId,
            'doc_type' => 'expense',
            'number' => $ficId,
            'entity_vat' => $vat,
            'entity_name' => $name,
            'amount_net' => 100,
            'amount_vat' => 22,
            'amount_gross' => 122,
            'currency' => 'EUR',
        ]);
    }

    public function test_report_classifies_matches_into_the_right_buckets(): void
    {
        // A: 1 asset -> auto-linkable
        $a = $this->supplier('Vendor A', '11111111111');
        Asset::factory()->create(['supplier_id' => $a->id]);
        $this->receivedDoc('1001', '11111111111', 'Vendor A');
        $this->receivedDoc('1002', '11111111111', 'Vendor A'); // 2 invoices, same supplier

        // B: 2 assets -> candidate (needs confirmation). VAT arrives with IT prefix + spaces.
        $b = $this->supplier('Vendor B', '22222222222');
        Asset::factory()->count(2)->create(['supplier_id' => $b->id]);
        $this->receivedDoc('1003', ' IT22222222222 ', 'Vendor B');

        // C: 0 assets -> matched_no_asset
        $c = $this->supplier('Service C', '33333333333');
        $this->receivedDoc('1004', '33333333333', 'Service C');

        // Noise: foreign / ESTERO -> skipped entirely
        $this->receivedDoc('1005', 'ESTERO', 'Foreign Co');
        $this->receivedDoc('1006', 'EU826002156', 'BlueSnap');

        $r = app(FicReconcileService::class)->report();

        $this->assertCount(1, $r['auto_linkable']);
        $this->assertSame('11111111111', $r['auto_linkable'][0]['vat']);
        $this->assertSame(2, $r['auto_linkable'][0]['invoices']);

        $this->assertCount(1, $r['candidates']);
        $this->assertSame('22222222222', $r['candidates'][0]['vat']);   // IT prefix + spaces normalised
        $this->assertSame(2, $r['candidates'][0]['assets']);

        $this->assertCount(1, $r['matched_no_asset']);
        $this->assertSame('33333333333', $r['matched_no_asset'][0]['vat']);

        // Noise (ESTERO / EU VAT) never becomes a match; A(2)+B(1)+C(1) invoices matched, 3 VATs.
        $this->assertSame(4, $r['matched_invoices']);
        $this->assertSame(3, $r['matched_vat']);
    }

    public function test_duplicate_supplier_vat_is_flagged_ambiguous(): void
    {
        $this->supplier('Dup One', '44444444444');
        $this->supplier('Dup Two', '44444444444');
        $this->receivedDoc('2001', '44444444444', 'Dup');

        $r = app(FicReconcileService::class)->report();

        $this->assertCount(1, $r['ambiguous_supplier']);
        $this->assertSame('44444444444', $r['ambiguous_supplier'][0]['vat']);
        $this->assertCount(2, $r['ambiguous_supplier'][0]['suppliers']);
        $this->assertCount(0, $r['auto_linkable']);
    }
}
