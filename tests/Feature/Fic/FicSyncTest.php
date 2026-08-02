<?php

namespace Tests\Feature\Fic;

use App\Models\FicDocument;
use App\Support\Fic\FicClient;
use App\Support\Fic\FicSyncService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FicSyncTest extends TestCase
{
    private function fakeFic(): void
    {
        config()->set('services.fic.base_url', 'https://api.test');
        config()->set('services.fic.token', 'test-token');
        config()->set('services.fic.company_id', '42');
        config()->set('services.fic.local_company_id', null);

        $empty = ['data' => [], 'current_page' => 1, 'last_page' => 1];
        Http::fake(function ($request) use ($empty) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);
            $type = $q['type'] ?? '';
            $url = $request->url();

            if (str_contains($url, '/issued_documents') && $type === 'invoice') {
                return Http::response(['data' => [[
                    'id' => 1001, 'type' => 'invoice', 'number' => '1/2026', 'date' => '2026-01-15',
                    'amount_net' => 1000, 'amount_vat' => 220, 'amount_gross' => 1220, 'currency' => 'EUR',
                    'entity' => ['name' => 'Acme Srl', 'vat_number' => 'IT123'],
                    'payments_list' => [['amount' => 1220, 'status' => 'not_paid', 'due_date' => '2026-02-15']],
                ]], 'current_page' => 1, 'last_page' => 1], 200);
            }
            if (str_contains($url, '/received_documents') && $type === 'expense') {
                return Http::response(['data' => [[
                    'id' => 2002, 'type' => 'expense', 'category' => 'Spese Immateriali', 'number' => 'F-9', 'date' => '2026-01-10',
                    'amount_net' => 500, 'amount_vat' => 110, 'amount_gross' => 610, 'currency' => 'EUR',
                    'entity' => ['name' => 'Supplier Spa', 'vat_number' => 'IT999'],
                    'payments_list' => [['amount' => 610, 'status' => 'paid', 'due_date' => '2026-01-31', 'paid_date' => '2026-02-05']],
                ]], 'current_page' => 1, 'last_page' => 1], 200);
            }
            // Supplier credit note: FiC reports POSITIVE amounts; it must be stored NEGATIVE so it
            // reduces costs in the controllo di gestione.
            if (str_contains($url, '/received_documents') && $type === 'passive_credit_note') {
                return Http::response(['data' => [[
                    'id' => 3003, 'type' => 'passive_credit_note', 'category' => 'Spese Immateriali', 'number' => 'NC-2', 'date' => '2026-01-20',
                    'amount_net' => 200, 'amount_vat' => 44, 'amount_gross' => 244, 'currency' => 'EUR',
                    'entity' => ['name' => 'Supplier Spa', 'vat_number' => 'IT999'],
                    'payments_list' => [['amount' => 244, 'status' => 'paid', 'due_date' => '2026-01-20', 'paid_date' => '2026-01-20']],
                ]], 'current_page' => 1, 'last_page' => 1], 200);
            }

            return Http::response($empty, 200);
        });
    }

    public function test_sync_imports_and_maps_documents(): void
    {
        $this->fakeFic();

        $result = (new FicSyncService(new FicClient()))->syncAll();

        $this->assertEquals(1, $result['issued']);
        $this->assertEquals(2, $result['received'], 'expense + passive_credit_note');

        // Supplier credit note stored NEGATIVE so it nets against costs.
        $creditNote = FicDocument::received()->where('doc_type', 'passive_credit_note')->first();
        $this->assertNotNull($creditNote, 'passive_credit_note must be synced');
        $this->assertEqualsWithDelta(-200, (float) $creditNote->amount_net, 0.01);
        $this->assertEqualsWithDelta(-244, (float) $creditNote->amount_gross, 0.01);
        // Received net = expense(+500) + credit note(-200) = 300 (costs correctly reduced).
        $this->assertEqualsWithDelta(300, (float) FicDocument::received()->sum('amount_net'), 0.01);

        $issued = FicDocument::issued()->first();
        $this->assertEquals(1001, $issued->fic_id);
        $this->assertEquals('Acme Srl', $issued->entity_name);
        $this->assertEqualsWithDelta(1220, (float) $issued->amount_gross, 0.01);
        $this->assertFalse($issued->paid);
        $this->assertEquals('2026-02-15', $issued->due_on->format('Y-m-d'));
        $this->assertEqualsWithDelta(1220, (float) $issued->outstanding, 0.01);

        $received = FicDocument::received()->first();
        $this->assertTrue($received->paid);
        $this->assertEqualsWithDelta(610, (float) $received->paid_amount, 0.01);
        $this->assertEquals('Spese Immateriali', $received->category);
        $this->assertEquals('2026-02-05', $received->paid_on->format('Y-m-d'));
    }

    public function test_sync_is_idempotent(): void
    {
        $this->fakeFic();

        $sync = new FicSyncService(new FicClient());
        $sync->syncAll();
        $sync->syncAll();

        $this->assertEquals(1, FicDocument::issued()->count());
        $this->assertEquals(2, FicDocument::received()->count()); // expense + passive_credit_note, not duplicated
    }
}
