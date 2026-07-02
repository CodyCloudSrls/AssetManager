<?php

namespace Tests\Feature\Tenants;

use App\Mail\TenantAuditDueDigestMail;
use App\Mail\TenantExpectedCheckinDigestMail;
use App\Mail\TenantInventoryLowDigestMail;
use App\Mail\TenantLicenseExpiryDigestMail;
use App\Mail\TenantWarrantyDigestMail;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Company;
use App\Models\Consumable;
use App\Models\License;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenants\TenantMailNotificationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantStockAlertsTest extends TestCase
{
    private function service(): TenantMailNotificationService
    {
        return app(TenantMailNotificationService::class);
    }

    /** @return array{0: Tenant, 1: Company} */
    private function tenant(?array $events = null): array
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid(), 'default_locale' => 'it-IT']);
        $company = Company::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => null,
            'tenant_notification_email' => 'ops@tenant.test',
            'tenant_mail_notification_events' => $events,
        ]);

        return [$tenant, $company];
    }

    /** A company that belongs to no tenant — its records must never reach a tenant digest. */
    private function foreignCompany(): Company
    {
        return Company::factory()->create(['tenant_id' => null, 'parent_id' => null]);
    }

    public function test_warranty_digest_is_scoped_to_the_tenant_and_localized(): void
    {
        Mail::fake();
        [$tenant, $company] = $this->tenant();
        $model = AssetModel::factory()->create();
        // Drive the warranty path (purchase_date + warranty_months): the factory recomputes
        // asset_eol_date after create, so warranty expiry is the deterministic signal here.
        $warranty = ['purchase_date' => now()->format('Y-m-d'), 'warranty_months' => 1];
        Asset::factory()->create(['company_id' => $company->id, 'model_id' => $model->id] + $warranty);
        Asset::factory()->create(['company_id' => $this->foreignCompany()->id, 'model_id' => $model->id] + $warranty);

        $count = $this->service()->sendWarrantyDigest($tenant, 60);

        $this->assertSame(1, $count);
        Mail::assertSent(TenantWarrantyDigestMail::class, fn ($m) => $m->hasTo('ops@tenant.test') && $m->locale === 'it-IT' && strlen($m->render()) > 0);
    }

    public function test_license_expiry_digest_is_scoped_to_the_tenant(): void
    {
        Mail::fake();
        [$tenant, $company] = $this->tenant();
        License::factory()->create(['company_id' => $company->id, 'expiration_date' => now()->addDays(10)->format('Y-m-d'), 'termination_date' => null]);
        License::factory()->create(['company_id' => $this->foreignCompany()->id, 'expiration_date' => now()->addDays(10)->format('Y-m-d'), 'termination_date' => null]);

        $count = $this->service()->sendLicenseExpiryDigest($tenant);

        $this->assertSame(1, $count);
        Mail::assertSent(TenantLicenseExpiryDigestMail::class, fn ($m) => strlen($m->render()) > 0);
    }

    public function test_inventory_low_digest_is_scoped_to_the_tenant(): void
    {
        Mail::fake();
        [$tenant, $company] = $this->tenant();
        Consumable::factory()->create(['company_id' => $company->id, 'min_amt' => 5, 'qty' => 1]);
        Consumable::factory()->create(['company_id' => $this->foreignCompany()->id, 'min_amt' => 5, 'qty' => 1]);

        $count = $this->service()->sendInventoryLowDigest($tenant);

        $this->assertSame(1, $count);
        Mail::assertSent(TenantInventoryLowDigestMail::class, fn ($m) => strlen($m->render()) > 0);
    }

    public function test_expected_checkin_digest_is_scoped_to_the_tenant(): void
    {
        Mail::fake();
        [$tenant, $company] = $this->tenant();
        $user = User::factory()->create();
        $model = AssetModel::factory()->create();
        Asset::factory()->create([
            'company_id' => $company->id, 'model_id' => $model->id,
            'assigned_to' => $user->id, 'assigned_type' => User::class,
            'expected_checkin' => now()->subDays(2)->format('Y-m-d'),
        ]);
        Asset::factory()->create([
            'company_id' => $this->foreignCompany()->id, 'model_id' => $model->id,
            'assigned_to' => $user->id, 'assigned_type' => User::class,
            'expected_checkin' => now()->subDays(2)->format('Y-m-d'),
        ]);

        $count = $this->service()->sendExpectedCheckinDigest($tenant);

        $this->assertSame(1, $count);
        Mail::assertSent(TenantExpectedCheckinDigestMail::class, fn ($m) => strlen($m->render()) > 0);
    }

    public function test_audit_due_digest_is_scoped_to_the_tenant(): void
    {
        Mail::fake();
        [$tenant, $company] = $this->tenant();
        $model = AssetModel::factory()->create();
        Asset::factory()->create(['company_id' => $company->id, 'model_id' => $model->id, 'next_audit_date' => now()->subDay()->format('Y-m-d')]);
        Asset::factory()->create(['company_id' => $this->foreignCompany()->id, 'model_id' => $model->id, 'next_audit_date' => now()->subDay()->format('Y-m-d')]);

        $count = $this->service()->sendAuditDueDigest($tenant);

        $this->assertSame(1, $count);
        Mail::assertSent(TenantAuditDueDigestMail::class, fn ($m) => strlen($m->render()) > 0);
    }

    public function test_digests_are_skipped_when_the_event_is_disabled(): void
    {
        Mail::fake();
        // Events configured but WITHOUT any stock alert → all skipped.
        [$tenant, $company] = $this->tenant(['document_review_due']);
        $model = AssetModel::factory()->create();
        Asset::factory()->create(['company_id' => $company->id, 'model_id' => $model->id, 'asset_eol_date' => now()->addDays(10)->format('Y-m-d')]);
        Consumable::factory()->create(['company_id' => $company->id, 'min_amt' => 5, 'qty' => 1]);

        $this->assertSame(0, $this->service()->sendWarrantyDigest($tenant));
        $this->assertSame(0, $this->service()->sendInventoryLowDigest($tenant));

        Mail::assertNothingSent();
    }
}
