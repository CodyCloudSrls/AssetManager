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
    public function test_tenant_switch_from_services_index_redirects_to_selected_tenant_inventory(): void
    {
        [$codyCloudTenant, $codyCloudCompany] = $this->tenantWithCompanyName('CodyCloud');
        [$italwayTenant] = $this->tenantWithCompanyName('Italway Srl');
        $user = User::factory()->superuser()->for($codyCloudCompany)->create();
        $this->actingAs($user);

        $redirectLocation = route('tenants.services.index', $italwayTenant).'?tenant_switched=1';

        $this->post(route('tenants.switch-context'), [
            'tenant_id' => $italwayTenant->id,
            'redirect_to' => route('tenants.services.index', $codyCloudTenant),
        ])
            ->assertSessionHas(Tenant::ACTIVE_TENANT_SESSION_KEY, $italwayTenant->id)
            ->assertRedirect($redirectLocation);

        $this->get($redirectLocation)
            ->assertOk()
            ->assertSee('Services inventory - Italway Srl')
            ->assertDontSee('Services inventory - CodyCloud');
    }

    public function test_tenant_switch_from_old_tenant_service_edit_redirects_to_selected_tenant_inventory(): void
    {
        [$codyCloudTenant, $codyCloudCompany] = $this->tenantWithCompanyName('CodyCloud');
        [$italwayTenant] = $this->tenantWithCompanyName('Italway Srl');
        $service = $this->tenantService($codyCloudTenant, [
            'name' => 'Legacy service',
        ]);
        $user = User::factory()->superuser()->for($codyCloudCompany)->create();
        $this->actingAs($user);

        $this->post(route('tenants.switch-context'), [
            'tenant_id' => $italwayTenant->id,
            'redirect_to' => route('tenants.services.edit', [$codyCloudTenant, $service]),
        ])
            ->assertSessionHas(Tenant::ACTIVE_TENANT_SESSION_KEY, $italwayTenant->id)
            ->assertRedirect(route('tenants.services.index', $italwayTenant).'?tenant_switched=1');
    }

    public function test_tenant_switch_rejects_protocol_relative_redirects(): void
    {
        [$codyCloudTenant, $codyCloudCompany] = $this->tenantWithCompanyName('CodyCloud');
        [$italwayTenant] = $this->tenantWithCompanyName('Italway Srl');
        $user = User::factory()->superuser()->for($codyCloudCompany)->create();
        $this->actingAs($user);

        $this->post(route('tenants.switch-context'), [
            'tenant_id' => $italwayTenant->id,
            'redirect_to' => '//evil.example/admin/tenants/'.$codyCloudTenant->id.'/services',
        ])
            ->assertSessionHas(Tenant::ACTIVE_TENANT_SESSION_KEY, $italwayTenant->id)
            ->assertRedirect(route('home').'?tenant_switched=1');
    }

    public function test_tenant_service_can_be_created_and_exported_as_acn_xlsx(): void
    {
        [$tenant, $company] = $this->tenantWithCompany();
        $user = User::factory()->superuser()->for($company)->create();
        $this->actingAs($user);

        $this->get(route('tenants.services.create', $tenant))
            ->assertOk()
            ->assertSee('Create service')
            ->assertSee('Services')
            ->assertSee('Produzione di beni e servizi - Infrastrutture digitali')
            ->assertSee('Produzione di beni e servizi - Gestione dei servizi TIC')
            ->assertSee('Produzione di beni e servizi - Fornitori di servizi digitali')
            ->assertDontSee('value="'.TenantService::MACRO_PRODUCTION_GOODS_SERVICES.'"', false)
            ->assertSee(route('tenants.services.index', $tenant), false);

        $this->post(route('tenants.services.store', $tenant), [
            'macro_area' => TenantService::MACRO_PRODUCTION_DIGITAL_INFRASTRUCTURES,
            'name' => 'Managed SOC',
            'description' => 'Security operations service',
            'acn_subject_basis' => 'DNISA: Infrastrutture digitali - DNS',
            'relevance_override' => TenantService::IMPACT_HIGH,
            'is_active' => '1',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('tenants.services.index', $tenant));

        $this->assertDatabaseHas('tenant_services', [
            'tenant_id' => $tenant->id,
            'macro_area' => TenantService::MACRO_PRODUCTION_DIGITAL_INFRASTRUCTURES,
            'name' => 'Managed SOC',
            'acn_subject_basis' => 'DNISA: Infrastrutture digitali - DNS',
            'relevance_override' => TenantService::IMPACT_HIGH,
            'is_active' => 1,
        ]);

        $service = TenantService::where('tenant_id', $tenant->id)->where('name', 'Managed SOC')->firstOrFail();
        $this->get(route('tenants.services.index', $tenant))
            ->assertOk()
            ->assertSee('Managed SOC')
            ->assertSee('DNISA: Infrastrutture digitali - DNS');
        $this->get(route('tenants.services.edit', [$tenant, $service]))
            ->assertOk()
            ->assertSee('Managed SOC');

        $response = $this->get(route('tenants.services.acn_export', $tenant));
        $response->assertOk();

        $sheetXml = $this->xlsxSheetXml($response->baseResponse->getFile()->getPathname(), 'xl/worksheets/sheet1.xml');

        $this->assertStringContainsString('Macro-area', $sheetXml);
        $this->assertStringContainsString('Denominazione Attivit', $sheetXml);
        $this->assertStringContainsString('Produzione di beni e servizi - Infrastrutture digitali', $sheetXml);
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

        $this->getJson(route('api.documents.index', [
            'tenant_id' => $tenant->id,
            'tenant_service_id' => $service->id,
        ]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.name', 'NIS service document');

        $this->get(route('tenants.services.index', $tenant))
            ->assertOk()
            ->assertSee(route('documents.index', ['tenant_id' => $tenant->id, 'tenant_service_id' => $service->id]));
    }

    public function test_contracts_can_be_filtered_by_linked_tenant_service(): void
    {
        [$tenant, $company] = $this->tenantWithCompany();
        $user = User::factory()->superuser()->for($company)->create();
        $this->actingAs($user);

        $service = $this->tenantService($tenant, [
            'name' => 'Service linked to contract',
        ]);

        $customer = new Customer([
            'company_id' => $company->id,
            'name' => 'ACN Customer',
        ]);
        $customer->created_by = $user->id;
        $this->assertTrue($customer->save(), $customer->getErrors()->toJson());

        $contract = new CustomerContract([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'name' => 'Service delivery contract',
            'status' => CustomerContract::STATUS_DRAFT,
            'currency' => 'EUR',
        ]);
        $contract->created_by = $user->id;
        $this->assertTrue($contract->save(), $contract->getErrors()->toJson());
        $contract->tenantServices()->sync([$service->id]);

        $this->getJson(route('api.contracts.index', [
            'tenant_id' => $tenant->id,
            'tenant_service_id' => $service->id,
        ]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.name', 'Service delivery contract');

        $this->get(route('tenants.services.index', $tenant))
            ->assertOk()
            ->assertSee(route('contracts.index', ['tenant_id' => $tenant->id, 'tenant_service_id' => $service->id]));
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

    public function test_same_service_name_and_macro_area_allowed_for_different_companies(): void
    {
        [$tenant, $companyA] = $this->tenantWithCompanyName('Logica 2.0');
        $companyB = Company::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Iblue']);
        $this->actingAs(User::factory()->superuser()->for($companyA)->create());

        $macro = TenantService::MACRO_PRODUCTION_DIGITAL_INFRASTRUCTURES;
        $payload = fn ($companyId) => [
            'macro_area' => $macro,
            'company_id' => $companyId,
            'name' => 'Servizio identico',
            'is_active' => '1',
        ];

        // Company A
        $this->post(route('tenants.services.store', $tenant), $payload($companyA->id))->assertSessionHasNoErrors();
        // Company B — same name + macro-area must be allowed (two companies, same service)
        $this->post(route('tenants.services.store', $tenant), $payload($companyB->id))->assertSessionHasNoErrors();

        $this->assertEquals(2, TenantService::where('tenant_id', $tenant->id)->where('name', 'Servizio identico')->count());

        // Same company + same name + macro-area → still rejected as a duplicate
        $this->post(route('tenants.services.store', $tenant), $payload($companyA->id))->assertSessionHasErrors('name');
    }

    public function test_services_index_filters_by_macro_area_status_and_search(): void
    {
        [$tenant, $company] = $this->tenantWithCompanyName('Filter Co');
        $this->actingAs(User::factory()->superuser()->for($company)->create());

        $keys = array_keys(TenantService::macroAreaOptions());
        $macroA = $keys[0];
        $macroB = $keys[1] ?? $keys[0];

        $this->tenantService($tenant, ['name' => 'Alphaaa Svc', 'macro_area' => $macroA, 'is_active' => true]);
        $this->tenantService($tenant, ['name' => 'Bettaaa Svc', 'macro_area' => $macroB, 'is_active' => true]);
        $this->tenantService($tenant, ['name' => 'Gammaaa Svc', 'macro_area' => $macroA, 'is_active' => false]);

        if ($macroA !== $macroB) {
            $this->get(route('tenants.services.index', ['tenant' => $tenant, 'macro_area' => $macroA]))
                ->assertOk()->assertSee('Alphaaa Svc')->assertSee('Gammaaa Svc')->assertDontSee('Bettaaa Svc');
        }

        $this->get(route('tenants.services.index', ['tenant' => $tenant, 'status' => 'active']))
            ->assertOk()->assertSee('Alphaaa Svc')->assertDontSee('Gammaaa Svc');

        $this->get(route('tenants.services.index', ['tenant' => $tenant, 'q' => 'Bettaaa']))
            ->assertOk()->assertSee('Bettaaa Svc')->assertDontSee('Alphaaa Svc');
    }

    public function test_bulk_update_can_change_macro_area(): void
    {
        [$tenant, $company] = $this->tenantWithCompanyName('Macro Co');
        $this->actingAs(User::factory()->superuser()->for($company)->create());

        $keys = array_keys(TenantService::macroAreaOptions());
        $from = $keys[0];
        $to = $keys[1] ?? $keys[0];

        $s1 = $this->tenantService($tenant, ['name' => 'Macro svc 1', 'macro_area' => $from]);
        $s2 = $this->tenantService($tenant, ['name' => 'Macro svc 2', 'macro_area' => $from]);

        $this->post(route('tenants.services.bulkeditsave', $tenant), [
            'ids' => [$s1->id, $s2->id],
            'apply_macro_area' => '1',
            'macro_area' => $to,
        ])->assertRedirect(route('tenants.services.index', $tenant));

        $this->assertEquals($to, $s1->fresh()->macro_area);
        $this->assertEquals($to, $s2->fresh()->macro_area);
    }

    public function test_acn_export_can_be_scoped_to_a_company(): void
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid()]);
        $companyA = Company::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Exp A']);
        Company::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Exp B']);
        $this->actingAs(User::factory()->superuser()->for($companyA)->create());

        $this->tenantService($tenant, ['name' => 'OnlyA', 'company_id' => $companyA->id]);

        $this->get(route('tenants.services.acn_export', ['tenant' => $tenant, 'company_id' => $companyA->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // A company from another tenant is rejected.
        $foreign = Company::factory()->create(['name' => 'Foreign']);
        $this->get(route('tenants.services.acn_export', ['tenant' => $tenant, 'company_id' => $foreign->id]))
            ->assertNotFound();
    }

    public function test_bulk_update_can_change_company_and_back_to_tenant_wide(): void
    {
        [$tenant, $company] = $this->tenantWithCompanyName('Suez Italy');
        $user = User::factory()->superuser()->for($company)->create();
        $this->actingAs($user);

        $s1 = $this->tenantService($tenant, ['name' => 'Bulk svc 1', 'company_id' => null]);
        $s2 = $this->tenantService($tenant, ['name' => 'Bulk svc 2', 'company_id' => null]);

        // Assign both services to a single company.
        $this->post(route('tenants.services.bulkeditsave', $tenant), [
            'ids' => [$s1->id, $s2->id],
            'apply_company_id' => '1',
            'company_id' => (string) $company->id,
        ])->assertRedirect(route('tenants.services.index', $tenant));

        $this->assertEquals($company->id, $s1->fresh()->company_id);
        $this->assertEquals($company->id, $s2->fresh()->company_id);

        // Move them back to tenant-wide (NULL) via the empty company value.
        $this->post(route('tenants.services.bulkeditsave', $tenant), [
            'ids' => [$s1->id, $s2->id],
            'apply_company_id' => '1',
            'company_id' => '',
        ])->assertRedirect(route('tenants.services.index', $tenant));

        $this->assertNull($s1->fresh()->company_id);
        $this->assertNull($s2->fresh()->company_id);
    }

    public function test_bulk_update_rejects_company_from_another_tenant(): void
    {
        [$tenant] = $this->tenantWithCompanyName('Tenant A');
        [, $foreignCompany] = $this->tenantWithCompanyName('Tenant B');
        $user = User::factory()->superuser()->create();
        $this->actingAs($user);

        $service = $this->tenantService($tenant, ['name' => 'Svc', 'company_id' => null]);

        $this->post(route('tenants.services.bulkeditsave', $tenant), [
            'ids' => [$service->id],
            'apply_company_id' => '1',
            'company_id' => (string) $foreignCompany->id,
        ])->assertSessionHasErrors('company_id');

        $this->assertNull($service->fresh()->company_id);
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

    private function tenantWithCompanyName(string $companyName): array
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid()]);
        $company = Company::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => $companyName,
        ]);

        return [$tenant, $company];
    }

    private function tenantService(Tenant $tenant, array $overrides = []): TenantService
    {
        $service = new TenantService(array_merge([
            'tenant_id' => $tenant->id,
            'macro_area' => TenantService::MACRO_PRODUCTION_DIGITAL_INFRASTRUCTURES,
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
