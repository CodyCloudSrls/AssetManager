<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\DocumentFramework;
use Tests\TestCase;

class DocumentFrameworkTenantIsolationTest extends TestCase
{
    public function test_operational_scope_excludes_system_and_global_non_system_frameworks(): void
    {
        $company = Company::factory()->create();
        $operationalFramework = DocumentFramework::factory()->for($company)->create();
        $globalFramework = DocumentFramework::factory()->create([
            'company_id' => null,
            'visibility_type' => DocumentFramework::VISIBILITY_GLOBAL,
            'is_system_template' => false,
        ]);
        $systemFramework = DocumentFramework::factory()->systemTemplate()->create();

        $frameworkIds = DocumentFramework::withoutGlobalScopes()
            ->operational()
            ->pluck('id')
            ->all();

        $this->assertContains($operationalFramework->id, $frameworkIds);
        $this->assertNotContains($globalFramework->id, $frameworkIds);
        $this->assertNotContains($systemFramework->id, $frameworkIds);
    }

    public function test_system_frameworks_cannot_be_applied_to_tenant_records(): void
    {
        $company = Company::factory()->create();
        $tenantFramework = DocumentFramework::factory()->for($company)->create();
        $globalFramework = DocumentFramework::factory()->create([
            'company_id' => null,
            'visibility_type' => DocumentFramework::VISIBILITY_GLOBAL,
            'is_system_template' => false,
        ]);
        $systemFramework = DocumentFramework::factory()->systemTemplate()->create();

        $this->assertTrue(Company::templateCanBeAppliedToCompany($tenantFramework, $company->id));
        $this->assertFalse(Company::templateCanBeAppliedToCompany($globalFramework, $company->id));
        $this->assertFalse(Company::templateCanBeAppliedToCompany($systemFramework, $company->id));
    }
}
