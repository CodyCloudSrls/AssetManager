<?php

namespace Tests\Unit\Fic;

use App\Support\Fic\FicClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FicClientTest extends TestCase
{
    public function test_is_configured_requires_base_url_and_token(): void
    {
        $this->assertFalse((new FicClient('', '', ''))->isConfigured());
        $this->assertFalse((new FicClient('https://api.test', '', ''))->isConfigured());
        $this->assertTrue((new FicClient('https://api.test', 'tok', '1'))->isConfigured());
    }

    public function test_user_info_sends_bearer_token(): void
    {
        Http::fake(['*/user/info' => Http::response(['data' => ['name' => 'CodyCloud']], 200)]);

        $client = new FicClient('https://api.test', 'mytoken', '99');

        $this->assertEquals('CodyCloud', $client->userInfo()['data']['name']);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/user/info')
                && $request->hasHeader('Authorization', 'Bearer mytoken');
        });
    }

    public function test_issued_documents_targets_the_company(): void
    {
        Http::fake(['*' => Http::response(['data' => []], 200)]);

        (new FicClient('https://api.test', 'tok', '42'))->issuedDocuments('invoice');

        Http::assertSent(fn ($r) => str_contains($r->url(), '/c/42/issued_documents'));
    }

    public function test_read_only_client_exposes_no_write_methods(): void
    {
        // Guard against accidentally adding POST/PUT/DELETE helpers — FiC stays the
        // fiscal source of truth and this connector must remain read-only.
        $methods = get_class_methods(FicClient::class);
        foreach (['post', 'put', 'patch', 'delete', 'create', 'store', 'update'] as $forbidden) {
            foreach ($methods as $m) {
                $this->assertStringNotContainsStringIgnoringCase($forbidden, $m, "FicClient must stay read-only ({$m})");
            }
        }
    }
}
