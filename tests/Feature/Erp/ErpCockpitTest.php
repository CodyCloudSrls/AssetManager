<?php

namespace Tests\Feature\Erp;

use App\Models\FicDocument;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ErpCockpitTest extends TestCase
{
    public function test_cockpit_renders_for_superadmin(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('erp.index'))
            ->assertOk()
            ->assertSee(trans('erp/general.title'));
    }

    public function test_cockpit_shows_active_module_hub_and_no_planned_placeholders(): void
    {
        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('erp.index'))
            ->assertOk();

        // The module hub links to real pages, all marked Active…
        $response->assertSee(trans('erp/general.hub_title'))
            ->assertSee(trans('erp/general.hub.controllo'))
            ->assertSee(trans('erp/general.hub.fotografia'))
            ->assertSee(route('erp.controllo'))
            ->assertSee(route('erp.riconciliazione'));

        // …and the stale "coming soon" roadmap is gone.
        $response->assertDontSee(trans('erp/general.status_planned'))
            ->assertDontSee(trans('erp/general.roadmap_title'));
    }

    public function test_cockpit_shows_fiscal_data_and_scadenzario_from_mirror(): void
    {
        FicDocument::create([
            'fic_company_id' => '42',
            'direction' => FicDocument::DIRECTION_ISSUED,
            'fic_id' => 1,
            'doc_type' => 'invoice',
            'number' => 'INV-7',
            'issued_on' => Carbon::now()->subDays(3),
            'due_on' => Carbon::now()->addDays(5),
            'entity_name' => 'Acme Cockpit Srl',
            'amount_net' => 1000,
            'amount_vat' => 220,
            'amount_gross' => 1220,
            'paid' => false,
            'paid_amount' => 0,
            'company_id' => null,
            'synced_at' => Carbon::now(),
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('erp.index'))
            ->assertOk()
            ->assertSee(trans('erp/general.scadenzario.title'))
            ->assertSee('Acme Cockpit Srl')
            ->assertSee('INV-7');
    }
}
