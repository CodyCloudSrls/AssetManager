<?php

namespace Tests\Feature\Tenants;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantConfigPageTest extends TestCase
{
    private function tenantWithRoot(): array
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid()]);
        $rootCompany = Company::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => null,
            'name' => 'Acme Root '.Str::random(5),
        ]);

        return [$tenant, $rootCompany];
    }

    public function test_config_page_renders_with_all_sections(): void
    {
        [$tenant] = $this->tenantWithRoot();
        $this->actingAs(User::factory()->superuser()->create());

        $this->get(route('tenants.config.edit', $tenant))
            ->assertOk()
            ->assertSee(trans('admin/tenants/general.config.title'))
            ->assertSee(trans('admin/tenants/general.branding'))
            ->assertSee(trans('admin/tenants/general.helpdesk.title'))
            ->assertSee(trans('admin/tenants/general.mail.title'));
    }

    public function test_config_save_updates_tenant_and_root_company(): void
    {
        [$tenant, $rootCompany] = $this->tenantWithRoot();
        $this->actingAs(User::factory()->superuser()->create());

        $event = array_key_first(Tenant::mailNotificationEventOptions());

        $this->put(route('tenants.config.update', $tenant), [
            'default_locale' => 'en-US',
            'default_compliance_jurisdiction' => 'IT',
            'brand' => '2',
            'header_color' => '#123456',
            'helpdesk_enabled' => '0',
            'helpdesk_allow_attachments' => '1',
            'helpdesk_slug' => 'Acme Helpdesk',
            'helpdesk_contact_email' => 'help@acme.test',
            'helpdesk_intro' => 'Welcome to our helpdesk.',
            'tenant_notification_email' => 'notify@acme.test',
            'tenant_mail_from_name' => 'Acme Tenant',
            'tenant_document_review_warning_days' => '45',
            'tenant_mail_notification_events' => [$event],
        ])->assertRedirect(route('tenants.config.edit', $tenant));

        $tenant->refresh();
        $rootCompany->refresh();

        $this->assertEquals('en-US', $tenant->default_locale);
        $this->assertEquals('IT', $tenant->default_compliance_jurisdiction);
        $this->assertEquals(2, $rootCompany->brand);
        $this->assertEquals('#123456', $rootCompany->header_color);
        $this->assertStringContainsString('acme-helpdesk', $rootCompany->helpdesk_slug);
        $this->assertEquals('help@acme.test', $rootCompany->helpdesk_contact_email);
        $this->assertEquals('notify@acme.test', $rootCompany->tenant_notification_email);
        $this->assertEquals(45, $rootCompany->tenant_document_review_warning_days);
        $this->assertContains($event, $rootCompany->tenant_mail_notification_events);
    }

    public function test_config_save_validates(): void
    {
        [$tenant] = $this->tenantWithRoot();
        $this->actingAs(User::factory()->superuser()->create());

        $this->put(route('tenants.config.update', $tenant), [
            'default_locale' => 'en-US',
            'default_compliance_jurisdiction' => 'NOPE', // invalid
            'tenant_document_review_warning_days' => '9999', // > 365
        ])->assertSessionHasErrors(['default_compliance_jurisdiction', 'tenant_document_review_warning_days']);
    }

    public function test_config_requires_manage_permission(): void
    {
        [$tenant] = $this->tenantWithRoot();
        // A plain user who cannot manage the tenant.
        $this->actingAs(User::factory()->create());

        $this->get(route('tenants.config.edit', $tenant))->assertForbidden();
    }
}
