<?php

namespace Tests\Feature\Contracts\Ui;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\Tenant;
use App\Models\TenantService;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class BulkEditContractTenantServicesTest extends TestCase
{
    private function tenantWithCompany(): array
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid()]);
        $company = Company::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Svc Co '.Str::random(6),
        ]);

        return [$tenant, $company];
    }

    private function service(Tenant $tenant, string $name): TenantService
    {
        $service = new TenantService([
            'tenant_id' => $tenant->id,
            'macro_area' => TenantService::MACRO_PRODUCTION_DIGITAL_INFRASTRUCTURES,
            'name' => $name,
            'is_active' => true,
        ]);
        $this->assertTrue($service->save(), $service->getErrors()->toJson());

        return $service;
    }

    private function contract(Company $company, User $creator, string $name): CustomerContract
    {
        $customer = new Customer(['company_id' => $company->id, 'name' => $name.' Customer']);
        $customer->created_by = $creator->id;
        $this->assertTrue($customer->save(), $customer->getErrors()->toJson());

        $contract = new CustomerContract([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'name' => $name,
            'status' => CustomerContract::STATUS_DRAFT,
            'currency' => 'EUR',
        ]);
        $contract->created_by = $creator->id;
        $this->assertTrue($contract->save(), $contract->getErrors()->toJson());

        return $contract;
    }

    public function test_bulk_assigns_tenant_services_to_same_tenant_contracts(): void
    {
        [$tenant, $company] = $this->tenantWithCompany();
        $user = User::factory()->superuser()->for($company)->create();
        $a = $this->contract($company, $user, 'Bulk Svc A');
        $b = $this->contract($company, $user, 'Bulk Svc B');
        $s1 = $this->service($tenant, 'Hosting');
        $s2 = $this->service($tenant, 'Backup');

        $this->actingAs($user)
            ->post(route('contracts.bulkeditsave'), [
                'ids' => [$a->id, $b->id],
                'apply_tenant_service_ids' => '1',
                'tenant_service_ids_present' => '1',
                'tenant_service_ids' => [$s1->id, $s2->id],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('contracts.index'));

        foreach ([$a, $b] as $contract) {
            $this->assertEqualsCanonicalizing(
                [$s1->id, $s2->id],
                $contract->fresh()->tenantServices()->pluck('tenant_services.id')->all()
            );
            $this->assertDatabaseHas('customer_contract_events', [
                'customer_contract_id' => $contract->id,
                'event_type' => 'updated',
            ]);
        }
    }

    public function test_service_from_a_different_tenant_is_rejected(): void
    {
        [$tenant, $company] = $this->tenantWithCompany();
        [$otherTenant] = $this->tenantWithCompany();
        $user = User::factory()->superuser()->for($company)->create();
        $contract = $this->contract($company, $user, 'Bulk Svc C');
        $foreignService = $this->service($otherTenant, 'Foreign');

        $this->actingAs($user)
            ->post(route('contracts.bulkeditsave'), [
                'ids' => [$contract->id],
                'apply_tenant_service_ids' => '1',
                'tenant_service_ids_present' => '1',
                'tenant_service_ids' => [$foreignService->id],
            ])
            ->assertSessionHasErrors('tenant_service_ids');

        $this->assertSame(0, $contract->fresh()->tenantServices()->count());
    }

    public function test_contracts_spanning_two_tenants_cannot_receive_services(): void
    {
        [$tenantA, $companyA] = $this->tenantWithCompany();
        [$tenantB, $companyB] = $this->tenantWithCompany();
        $user = User::factory()->superuser()->create();
        $contractA = $this->contract($companyA, $user, 'Span A');
        $contractB = $this->contract($companyB, $user, 'Span B');
        $service = $this->service($tenantA, 'Hosting');

        $this->actingAs($user)
            ->post(route('contracts.bulkeditsave'), [
                'ids' => [$contractA->id, $contractB->id],
                'apply_tenant_service_ids' => '1',
                'tenant_service_ids_present' => '1',
                'tenant_service_ids' => [$service->id],
            ])
            ->assertSessionHasErrors('tenant_service_ids');

        $this->assertSame(0, $contractA->fresh()->tenantServices()->count());
    }
}
