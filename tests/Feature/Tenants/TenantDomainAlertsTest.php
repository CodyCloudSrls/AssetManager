<?php

namespace Tests\Feature\Tenants;

use App\Mail\TenantContractExpiryDigestMail;
use App\Mail\TenantFicSyncErrorMail;
use App\Mail\TenantFrameworkReviewDigestMail;
use App\Mail\TenantNotuleUnpaidDigestMail;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\DocumentFramework;
use App\Models\Notula;
use App\Models\Tenant;
use App\Support\Tenants\TenantMailNotificationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantDomainAlertsTest extends TestCase
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
            'tenant_id' => $tenant->id, 'parent_id' => null,
            'tenant_notification_email' => 'ops@tenant.test',
            'tenant_mail_notification_events' => $events,
        ]);

        return [$tenant, $company];
    }

    private function foreignCompany(): Company
    {
        return Company::factory()->create(['tenant_id' => null, 'parent_id' => null]);
    }

    private function contract(Company $company, $renewalDueAt): CustomerContract
    {
        $customer = new Customer;
        $customer->company_id = $company->id;
        $customer->name = 'Cliente '.Str::random(4);
        $customer->save();

        $contract = new CustomerContract;
        $contract->company_id = $company->id;
        $contract->customer_id = $customer->id;
        $contract->name = 'CTR '.Str::random(4);
        $contract->status = CustomerContract::STATUS_ACTIVE;
        $contract->renewal_due_at = $renewalDueAt;
        $contract->save();

        return $contract;
    }

    private function notula(Company $company, float $amount): Notula
    {
        $n = new Notula;
        $n->company_id = $company->id;
        $n->professional_name = 'Studio '.Str::random(4);
        $n->amount = $amount;
        $n->paid_amount = 0;
        $n->status = Notula::STATUS_UNPAID;
        $n->save();

        return $n;
    }

    private function framework(Company $company, int $cadence, $createdAt): DocumentFramework
    {
        $fw = new DocumentFramework;
        $fw->name = 'FW '.Str::random(4);
        $fw->slug = 'fw-'.Str::random(6);
        $fw->company_id = $company->id;
        $fw->is_system_template = false;
        $fw->is_active = true;
        $fw->status = 'active';
        $fw->visibility_type = 'private';
        $fw->review_cadence_months = $cadence;
        $fw->save();
        $fw->created_at = $createdAt;
        $fw->save();

        return $fw;
    }

    public function test_contract_expiry_digest_is_scoped_and_localized(): void
    {
        Mail::fake();
        [$tenant, $company] = $this->tenant();
        $this->contract($company, now()->addDays(10));
        $this->contract($this->foreignCompany(), now()->addDays(10));

        $count = $this->service()->sendContractExpiryDigest($tenant);

        $this->assertSame(1, $count);
        Mail::assertSent(TenantContractExpiryDigestMail::class, fn ($m) => $m->hasTo('ops@tenant.test') && $m->locale === 'it-IT' && strlen($m->render()) > 0);
    }

    public function test_notule_unpaid_digest_is_scoped(): void
    {
        Mail::fake();
        [$tenant, $company] = $this->tenant();
        $this->notula($company, 500);
        $this->notula($this->foreignCompany(), 500);

        $count = $this->service()->sendNotuleUnpaidDigest($tenant);

        $this->assertSame(1, $count);
        Mail::assertSent(TenantNotuleUnpaidDigestMail::class, fn ($m) => strlen($m->render()) > 0);
    }

    public function test_framework_review_digest_is_scoped(): void
    {
        Mail::fake();
        [$tenant, $company] = $this->tenant();
        $this->framework($company, 12, now()->subYears(2));     // due
        $this->framework($this->foreignCompany(), 12, now()->subYears(2));

        $count = $this->service()->sendFrameworkReviewDigest($tenant);

        $this->assertSame(1, $count);
        Mail::assertSent(TenantFrameworkReviewDigestMail::class, fn ($m) => strlen($m->render()) > 0);
    }

    public function test_recently_reviewed_framework_is_not_due(): void
    {
        Mail::fake();
        [$tenant, $company] = $this->tenant();
        $fw = $this->framework($company, 12, now()->subYears(2));
        $fw->last_reviewed_at = now();   // just reviewed → not due for another cadence
        $fw->save();

        $this->assertSame(0, $this->service()->sendFrameworkReviewDigest($tenant));
        Mail::assertNothingSent();
    }

    public function test_fic_sync_error_is_sent_to_the_tenant(): void
    {
        Mail::fake();
        [$tenant] = $this->tenant();

        $count = $this->service()->sendFicSyncError($tenant, 'HTTP 401 Unauthorized', '2026-07-02 02:32:00');

        $this->assertSame(1, $count);
        Mail::assertSent(TenantFicSyncErrorMail::class, fn ($m) => strlen($m->render()) > 0);
    }

    public function test_digests_respect_the_event_toggle(): void
    {
        Mail::fake();
        [$tenant, $company] = $this->tenant(['document_review_due']); // none of the C events enabled
        $this->contract($company, now()->addDays(5));
        $this->notula($company, 100);

        $this->assertSame(0, $this->service()->sendContractExpiryDigest($tenant));
        $this->assertSame(0, $this->service()->sendNotuleUnpaidDigest($tenant));
        $this->assertSame(0, $this->service()->sendFicSyncError($tenant, 'x', 'y'));

        Mail::assertNothingSent();
    }
}
