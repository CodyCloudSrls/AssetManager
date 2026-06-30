<?php

namespace Tests\Feature\TenantServices\Ui;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\TenantService;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantServiceBulkDeleteTest extends TestCase
{
    /** @return array{0: Tenant, 1: Company, 2: User} */
    private function tenantCompanyAndManager(): array
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid()]);
        $company = Company::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Bulk Co '.Str::random(6)]);
        $manager = User::factory()->for($company)->create([
            'permissions' => json_encode(['tenants.services.manage' => '1']),
        ]);

        return [$tenant, $company, $manager];
    }

    private function service(Tenant $tenant, ?int $companyId, string $name): TenantService
    {
        return TenantService::create([
            'tenant_id' => $tenant->id,
            'company_id' => $companyId,
            'macro_area' => 'customer_management',
            'name' => $name,
            'is_active' => true,
        ]);
    }

    public function test_bulk_delete_removes_selected_services_and_returns_to_the_filtered_list(): void
    {
        [$tenant, $company, $manager] = $this->tenantCompanyAndManager();
        $a = $this->service($tenant, $company->id, 'Servizio A');
        $b = $this->service($tenant, $company->id, 'Servizio B');
        $keep = $this->service($tenant, $company->id, 'Servizio C');

        $this->actingAs($manager)
            ->post(route('tenants.services.bulkdelete', ['tenant' => $tenant, 'status' => 'active', 'q' => 'Serv']), [
                'ids' => [$a->id, $b->id],
            ])
            // Returns to the SAME filtered view, not the unfiltered index.
            ->assertRedirect(route('tenants.services.index', ['tenant' => $tenant->id, 'status' => 'active', 'q' => 'Serv']));

        $this->assertSoftDeleted('tenant_services', ['id' => $a->id]);
        $this->assertSoftDeleted('tenant_services', ['id' => $b->id]);
        $this->assertDatabaseHas('tenant_services', ['id' => $keep->id, 'deleted_at' => null]);
    }

    public function test_bulk_delete_is_scoped_to_the_tenant(): void
    {
        [$tenant, $company, $manager] = $this->tenantCompanyAndManager();
        $mine = $this->service($tenant, $company->id, 'Mine');

        $otherTenant = Tenant::create(['uuid' => (string) Str::uuid()]);
        $otherCompany = Company::factory()->create(['tenant_id' => $otherTenant->id]);
        $foreign = $this->service($otherTenant, $otherCompany->id, 'Foreign');

        $this->actingAs($manager)
            ->post(route('tenants.services.bulkdelete', $tenant), ['ids' => [$mine->id, $foreign->id]]);

        $this->assertSoftDeleted('tenant_services', ['id' => $mine->id]);
        // servicesFromRequest scopes by tenant_id, so the other tenant's service is untouched.
        $this->assertDatabaseHas('tenant_services', ['id' => $foreign->id, 'deleted_at' => null]);
    }

    public function test_bulk_delete_without_permission_is_forbidden(): void
    {
        [$tenant, $company] = $this->tenantCompanyAndManager();
        $viewer = User::factory()->for($company)->create([
            'permissions' => json_encode(['tenants.services.view' => '1']),
        ]);
        $svc = $this->service($tenant, $company->id, 'Protected');

        $this->actingAs($viewer)
            ->post(route('tenants.services.bulkdelete', $tenant), ['ids' => [$svc->id]])
            ->assertForbidden();

        $this->assertDatabaseHas('tenant_services', ['id' => $svc->id, 'deleted_at' => null]);
    }
}
