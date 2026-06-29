<?php

namespace Tests\Feature\Erp;

use App\Models\FicDocument;
use App\Models\User;
use Tests\TestCase;

class BilancioSimulatoTest extends TestCase
{
    public function test_maps_real_data_to_italian_income_statement(): void
    {
        FicDocument::create(['fic_company_id' => '1', 'direction' => FicDocument::DIRECTION_ISSUED, 'fic_id' => 1, 'issued_on' => '2026-03-01', 'amount_net' => 10000, 'amount_vat' => 0, 'amount_gross' => 10000, 'paid' => true, 'paid_amount' => 10000, 'company_id' => null]);
        FicDocument::create(['fic_company_id' => '1', 'direction' => FicDocument::DIRECTION_RECEIVED, 'fic_id' => 2, 'issued_on' => '2026-02-01', 'category' => 'Vendita Ingrosso', 'amount_net' => 2000, 'amount_vat' => 0, 'amount_gross' => 2000, 'paid' => true, 'paid_amount' => 2000, 'company_id' => null]); // COGS
        FicDocument::create(['fic_company_id' => '1', 'direction' => FicDocument::DIRECTION_RECEIVED, 'fic_id' => 3, 'issued_on' => '2026-02-01', 'category' => 'Telefono e internet', 'amount_net' => 1000, 'amount_vat' => 0, 'amount_gross' => 1000, 'paid' => true, 'paid_amount' => 1000, 'company_id' => null]); // OPEX

        $response = $this->actingAs(User::factory()->superuser()->create())->get(route('erp.bilancio', ['year' => 2026]));
        $response->assertOk();

        $rows = $response->viewData('rows');
        $this->assertEqualsWithDelta(10000, $rows['valoreProduzione'], 0.01); // A
        $this->assertEqualsWithDelta(2000, $rows['b6'], 0.01);                // COGS
        $this->assertEqualsWithDelta(1000, $rows['b7'], 0.01);                // OPEX
        $this->assertEqualsWithDelta(3000, $rows['costiProduzione'], 0.01);   // B
        $this->assertEqualsWithDelta(7000, $rows['diffAB'], 0.01);            // A-B
        $this->assertEqualsWithDelta(1680, $rows['imposte'], 0.01);           // IRES 24% estimate
        $this->assertEqualsWithDelta(5320, $rows['utile'], 0.01);             // 7000 - 1680
        $this->assertEquals('stima', $response->viewData('impSource'));
    }
}
