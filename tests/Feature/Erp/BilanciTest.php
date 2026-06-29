<?php

namespace Tests\Feature\Erp;

use App\Models\BilancioUfficiale;
use App\Models\FicDocument;
use App\Models\User;
use App\Support\Reports\ManagementControlReport;
use Tests\TestCase;

class BilanciTest extends TestCase
{
    public function test_official_payroll_overrides_fic_in_income_statement(): void
    {
        // FiC labour for 2024 = 5000, but the deposited bilancio says 8006 -> CE uses 8006.
        FicDocument::create(['fic_company_id' => '1', 'direction' => FicDocument::DIRECTION_RECEIVED, 'fic_id' => 1, 'issued_on' => '2024-02-01', 'category' => 'Stipendi e salari', 'amount_net' => 5000, 'amount_vat' => 0, 'amount_gross' => 5000, 'paid' => true, 'paid_amount' => 5000, 'company_id' => null]);
        FicDocument::create(['fic_company_id' => '1', 'direction' => FicDocument::DIRECTION_ISSUED, 'fic_id' => 2, 'issued_on' => '2024-03-01', 'amount_net' => 20000, 'amount_vat' => 0, 'amount_gross' => 20000, 'paid' => true, 'paid_amount' => 20000, 'company_id' => null]);
        BilancioUfficiale::create(['anno' => 2024, 'costo_personale' => 8006, 'company_id' => null]);

        $ce = (new ManagementControlReport())->contoEconomico(null, [2024])[2024];

        $this->assertEqualsWithDelta(8006, $ce['personale'], 0.01);
        $this->assertEquals('reale', $ce['personale_source']);
        $this->assertEqualsWithDelta(11994, $ce['ebit'], 0.01); // 20000 - 0 - 8006
    }

    public function test_index_renders_and_store_persists(): void
    {
        $this->actingAs(User::factory()->superuser()->create());

        $this->get(route('erp.bilanci.index'))->assertOk()->assertSee(trans('erp/bilanci.title'));

        $this->post(route('erp.bilanci.store'), [
            'anno' => 2024, 'ricavi' => 130005, 'costi' => 125215,
            'costo_personale' => 8006, 'utile' => 1840, 'imposte' => 2886, 'is_deposited' => 1,
        ])->assertRedirect(route('erp.bilanci.index'));

        $this->assertDatabaseHas('bilanci_ufficiali', ['anno' => 2024, 'is_deposited' => 1]);
    }
}
