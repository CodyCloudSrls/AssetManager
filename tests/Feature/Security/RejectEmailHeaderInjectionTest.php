<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\RejectEmailHeaderInjection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RejectEmailHeaderInjectionTest extends TestCase
{
    public function test_email_like_request_fields_reject_crlf_sequences(): void
    {
        $request = Request::create('/_test/email-crlf', 'POST', [
            'guest_email' => "guest@example.com\r\nBcc: attacker@example.com",
        ]);

        try {
            (new RejectEmailHeaderInjection)->handle($request, fn () => new JsonResponse(['ok' => true]));
            $this->fail('Expected email CRLF validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('guest_email', $exception->errors());
        }
    }

    public function test_regular_multiline_request_fields_are_not_rejected(): void
    {
        $request = Request::create('/_test/email-crlf-ok', 'POST', [
            'description' => "Line one\nLine two",
        ]);

        $response = (new RejectEmailHeaderInjection)->handle($request, fn () => new JsonResponse(['ok' => true]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"ok":true}', $response->getContent());
    }
}
