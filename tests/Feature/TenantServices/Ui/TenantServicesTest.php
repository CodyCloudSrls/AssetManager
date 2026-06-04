<?php

namespace Tests\Feature\TenantServices\Ui;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\Document;
use App\Models\Tenant;
use App\Models\TenantService;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class TenantServicesTest extends TestCase
{
    public function test_tenant_service_can_be_created_and_exported_as_acn_xlsx(): void
    {
        [$tenant, $company] = $this->tenantWithCompany();
        $user = User::factory()->superuser()->for($company)->create();
        $this->actingAs($user);

        $this->get(route('tenants.services.create', $tenant))
            ->assertOk()
            ->assertSee('Create service');

        $this->post(route('tenants.services.store', $tenant), [
            'macro_area' => TenantService::MACRO_PRODUCTION_GOODS_SERVICES,
            'name' => 'Managed SOC',
            'description' => 'Security operations service',
            'relevance_override' => TenantService::IMPACT_HIGH,
            'is_active' => '1',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('tenants.services.index', $tenant));

        $this->assertDatabaseHas('tenant_services', [
            'tenant_id' => $tenant->id,
            'macro_area' => TenantService::MACRO_PRODUCTION_GOODS_SERVICES,
            'name' => 'Managed SOC',
            'relevance_override' => TenantService::IMPACT_HIGH,
            'is_active' => 1,
        ]);

        $service = TenantService::where('tenant_id', $tenant->id)->where('name', 'Managed SOC')->firstOrFail();
        $this->get(route('tenants.services.index', $tenant))
            ->assertOk()
            ->assertSee('Managed SOC');
        $this->get(route('tenants.services.edit', [$tenant, $service]))
            ->assertOk()
            ->assertSee('Managed SOC');

        $response = $this->get(route('tenants.services.acn_export', $tenant));
        $response->assertOk();

        $sheetXml = $this->xlsxSheetXml($response->baseResponse->getFile()->getPathname(), 'xl/worksheets/sheet1.xml');

        $this->assertStringContainsString('Macro-area', $sheetXml);
        $this->assertStringContainsString('Denominazione Attivit', $sheetXml);
        $this->assertStringContainsString('Produzione di beni e servizi', $sheetXml);
        $this->assertStringContainsString('Managed SOC', $sheetXml);
        $this->assertStringContainsString('Impatto medio', $sheetXml);
        $this->assertStringContainsString('Impatto alto', $sheetXml);
    }

    public function test_document_can_link_services_from_the_same_tenant(): void
    {
        [$tenant, $company] = $this->tenantWithCompany();
        $user = User::factory()->superuser()->for($company)->create();
        $this->actingAs($user);

        $service = $this->tenantService($tenant, [
            'name' => 'Service linked to document',
        ]);

        $this->get(route('documents.create', ['company_id' => $company->id]))
            ->assertOk()
            ->assertSee('Tenant services');

        $this->post(route('documents.store'), [
            'company_id' => $company->id,
            'name' => 'NIS service document',
            'status' => Document::STATUS_DRAFT,
            'tenant_service_ids_present' => '1',
            'tenant_service_ids' => [$service->id],
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $document = Document::withoutGlobalScopes()
            ->where('name', 'NIS service document')
            ->firstOrFail();

        $this->assertDatabaseHas('document_tenant_service', [
            'document_id' => $document->id,
            'tenant_service_id' => $service->id,
        ]);
    }

    public function test_contract_rejects_services_from_a_different_tenant(): void
    {
        [$tenantA, $companyA] = $this->tenantWithCompany('Tenant A');
        [$tenantB] = $this->tenantWithCompany('Tenant B');
        $user = User::factory()->superuser()->for($companyA)->create();
        $this->actingAs($user);

        $customer = new Customer([
            'company_id' => $companyA->id,
            'name' => 'ACN Customer',
        ]);
        $customer->created_by = $user->id;
        $this->assertTrue($customer->save(), $customer->getErrors()->toJson());

        $foreignService = $this->tenantService($tenantB, [
            'name' => 'Foreign tenant service',
        ]);

        $this->get(route('contracts.create', ['company_id' => $companyA->id]))
            ->assertOk()
            ->assertSee('Tenant services');

        $this->post(route('contracts.store'), [
            'company_id' => $companyA->id,
            'customer_id' => $customer->id,
            'name' => 'Cross tenant service contract',
            'status' => CustomerContract::STATUS_DRAFT,
            'currency' => 'EUR',
            'tenant_service_ids_present' => '1',
            'tenant_service_ids' => [$foreignService->id],
        ])
            ->assertSessionHasErrors('tenant_service_ids');

        $this->assertDatabaseMissing('customer_contracts', [
            'company_id' => $companyA->id,
            'name' => 'Cross tenant service contract',
        ]);
        $this->assertNotEquals($tenantA->id, $foreignService->tenant_id);
    }

    private function tenantWithCompany(string $companyName = 'ACN Tenant Company'): array
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid()]);
        $company = Company::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => $companyName.' '.Str::random(6),
        ]);

        return [$tenant, $company];
    }

    private function tenantService(Tenant $tenant, array $overrides = []): TenantService
    {
        $service = new TenantService(array_merge([
            'tenant_id' => $tenant->id,
            'macro_area' => TenantService::MACRO_PRODUCTION_GOODS_SERVICES,
            'name' => 'Inventory service '.Str::random(6),
            'description' => 'Service description',
            'is_active' => true,
        ], $overrides));

        $this->assertTrue($service->save(), $service->getErrors()->toJson());

        return $service;
    }

    private function xlsxSheetXml(string $path, string $sheetPath): string
    {
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true, 'Unable to open generated XLSX.');

        $xml = $zip->getFromName($sheetPath);
        $zip->close();

        $this->assertIsString($xml, $sheetPath.' was not found in generated XLSX.');

        return $xml;
    }
}
