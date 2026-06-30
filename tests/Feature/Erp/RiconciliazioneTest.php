<?php

namespace Tests\Feature\Erp;

use App\Models\FicCashbookEntry;
use App\Models\User;
use Tests\TestCase;

class RiconciliazioneTest extends TestCase
{
    private function entry(array $attrs): FicCashbookEntry
    {
        return FicCashbookEntry::create(array_merge([
            'fic_company_id' => '1',
            'entry_date' => '2026-05-10',
            'direction' => 'in',
            'amount' => 100,
            'account_name' => 'TS Pay',
        ], $attrs));
    }

    public function test_reconciliation_groups_incassi_by_channel_and_lists_unmatched(): void
    {
        $this->entry(['fic_id' => 'E1', 'account_name' => 'TS Pay', 'entity_name' => 'Cliente A', 'document_fic_id' => 999]);
        $this->entry(['fic_id' => 'E2', 'account_name' => 'PayPal', 'amount' => 50, 'entity_name' => 'Cliente B', 'document_fic_id' => null]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('erp.riconciliazione', ['year' => 2026]))
            ->assertOk()
            ->assertSee('TS Pay')
            ->assertSee('PayPal')
            ->assertSee('Cliente B'); // the unmatched incasso shows in "Da riconciliare"
    }

    public function test_outflows_are_not_counted_as_incassi(): void
    {
        $this->entry(['fic_id' => 'IN', 'account_name' => 'TS Pay', 'amount' => 100, 'direction' => 'in', 'document_fic_id' => 1]);
        $this->entry(['fic_id' => 'OUT', 'account_name' => 'TS Pay', 'amount' => 999, 'direction' => 'out', 'document_fic_id' => null]);

        $this->assertSame(100.0, (float) FicCashbookEntry::incassi()->sum('amount'));
        $this->assertSame(1, FicCashbookEntry::incassi()->count());
    }

    public function test_channel_detail_filter_renders(): void
    {
        $this->entry(['fic_id' => 'E1', 'account_name' => 'TS Pay', 'entity_name' => 'Cliente TSPay', 'document_fic_id' => 7]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('erp.riconciliazione', ['year' => 2026, 'channel' => 'TS Pay']))
            ->assertOk()
            ->assertSee('Cliente TSPay');
    }
}
