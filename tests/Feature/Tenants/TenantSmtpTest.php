<?php

namespace Tests\Feature\Tenants;

use App\Mail\TenantTestMail;
use App\Models\Company;
use App\Models\Tenant;
use App\Support\Tenants\TenantMailNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantSmtpTest extends TestCase
{
    private function tenant(array $companyAttrs = []): Tenant
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid(), 'default_locale' => 'it-IT']);
        Company::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'parent_id' => null,
            'tenant_notification_email' => 'ops@tenant.test',
        ], $companyAttrs));

        return $tenant;
    }

    public function test_smtp_config_and_from_email_resolve_from_the_root_company(): void
    {
        $tenant = $this->tenant([
            'tenant_mail_host' => 'smtp.tenant.test', 'tenant_mail_port' => 587,
            'tenant_mail_username' => 'user', 'tenant_mail_password' => 'secret',
            'tenant_mail_encryption' => 'tls', 'tenant_mail_from_email' => 'noreply@tenant.test',
        ]);

        $this->assertTrue($tenant->hasCustomSmtp());
        $config = $tenant->customMailerConfig();
        $this->assertSame('smtp.tenant.test', $config['host']);
        $this->assertSame(587, $config['port']);
        $this->assertSame('tls', $config['encryption']);
        $this->assertSame('noreply@tenant.test', $tenant->notificationFromEmail());
    }

    public function test_password_is_stored_encrypted(): void
    {
        $tenant = $this->tenant(['tenant_mail_host' => 'smtp.tenant.test', 'tenant_mail_port' => 587, 'tenant_mail_password' => 'secret']);
        $company = $tenant->rootCompany();

        // Raw column is ciphertext, the model decrypts it back.
        $raw = DB::table('companies')->where('id', $company->id)->value('tenant_mail_password');
        $this->assertNotSame('secret', $raw);
        $this->assertNotEmpty($raw);
        $this->assertSame('secret', $company->fresh()->tenant_mail_password);
    }

    public function test_sending_builds_a_runtime_mailer_when_smtp_is_configured(): void
    {
        Mail::fake();
        $tenant = $this->tenant(['tenant_mail_host' => 'smtp.tenant.test', 'tenant_mail_port' => 587]);

        $count = app(TenantMailNotificationService::class)->sendTestEmail($tenant);

        $this->assertSame(1, $count);
        $this->assertSame('smtp.tenant.test', config('mail.mailers.tenant_'.$tenant->id.'.host'));
        Mail::assertSent(TenantTestMail::class);
    }

    public function test_falls_back_to_platform_mailer_when_no_smtp(): void
    {
        Mail::fake();
        $tenant = $this->tenant(); // no SMTP columns

        $count = app(TenantMailNotificationService::class)->sendTestEmail($tenant);

        $this->assertSame(1, $count);
        // No per-tenant runtime mailer registered → platform default used.
        $this->assertNull(config('mail.mailers.tenant_'.$tenant->id));
        Mail::assertSent(TenantTestMail::class);
    }
}
