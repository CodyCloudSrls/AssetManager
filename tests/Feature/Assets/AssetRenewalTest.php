<?php

namespace Tests\Feature\Assets;

use App\Mail\TenantAssetRenewalDigestMail;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Company;
use App\Models\Tenant;
use App\Support\Tenants\TenantMailNotificationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssetRenewalTest extends TestCase
{
    public function test_expiring_renewal_scope_includes_soon_and_overdue_excludes_far(): void
    {
        $model = AssetModel::factory()->create();
        $soon = Asset::factory()->create(['model_id' => $model->id, 'renewal_date' => now()->addDays(10)->format('Y-m-d')]);
        $overdue = Asset::factory()->create(['model_id' => $model->id, 'renewal_date' => now()->subDays(5)->format('Y-m-d')]);
        $far = Asset::factory()->create(['model_id' => $model->id, 'renewal_date' => now()->addDays(200)->format('Y-m-d')]);
        $none = Asset::factory()->create(['model_id' => $model->id, 'renewal_date' => null]);

        $ids = Asset::expiringRenewal(30)->pluck('assets.id')->all();

        $this->assertContains($soon->id, $ids);
        $this->assertContains($overdue->id, $ids);
        $this->assertNotContains($far->id, $ids);
        $this->assertNotContains($none->id, $ids);
    }

    private function tenantWithRenewingAsset(?string $locale = 'it-IT', $events = null): Tenant
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid(), 'default_locale' => $locale]);
        $company = Company::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => null,
            'tenant_notification_email' => 'ops@tenant.test',
            'tenant_mail_notification_events' => $events,
        ]);
        $model = AssetModel::factory()->create();
        Asset::factory()->create(['company_id' => $company->id, 'model_id' => $model->id, 'renewal_date' => now()->addDays(5)->format('Y-m-d')]);

        return $tenant;
    }

    public function test_renewal_digest_is_sent_in_the_tenant_language(): void
    {
        Mail::fake();
        $tenant = $this->tenantWithRenewingAsset('it-IT');

        $count = app(TenantMailNotificationService::class)->sendAssetRenewalDigest($tenant);

        $this->assertSame(1, $count);
        Mail::assertSent(TenantAssetRenewalDigestMail::class, fn ($mail) => $mail->hasTo('ops@tenant.test') && $mail->locale === 'it-IT');
    }

    public function test_renewal_digest_is_skipped_when_the_event_is_disabled(): void
    {
        Mail::fake();
        // Events configured but WITHOUT asset_renewal_due → skipped.
        $tenant = $this->tenantWithRenewingAsset('it-IT', ['document_review_due']);

        $count = app(TenantMailNotificationService::class)->sendAssetRenewalDigest($tenant);

        $this->assertSame(0, $count);
        Mail::assertNotSent(TenantAssetRenewalDigestMail::class);
    }
}
