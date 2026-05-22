<?php

namespace Tests\Feature\Documents\Api;

use App\Models\Company;
use App\Models\ComplianceDomain;
use App\Models\Document;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use App\Models\DocumentType;
use App\Models\User;
use App\Support\Compliance\ComplianceDomainAccess;
use App\Support\Reports\NisRealCoverageReport;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DocumentAccessScopeTest extends TestCase
{
    public function test_restricted_user_sees_only_selected_domain_and_allowed_document_area(): void
    {
        $company = Company::factory()->create();
        $type = DocumentType::factory()->create();
        $actor = $this->restrictedDocumentUser($company, ['nis2'], [
            'documents.view' => '1',
            'documents.area.cybersecurity.view' => '1',
        ]);

        $nis2Framework = $this->framework($company, 'nis2', 'nis2-scope-test');
        $gdprFramework = $this->framework($company, 'gdpr', 'gdpr-scope-test');

        $visible = $this->document($company, $type, $nis2Framework, 'Visible cyber NIS2', 'cybersecurity');
        $this->document($company, $type, $nis2Framework, 'Hidden admin NIS2', 'administration');
        $this->document($company, $type, $gdprFramework, 'Hidden cyber GDPR', 'cybersecurity');
        $this->document($company, $type, null, 'Hidden unclassified', null);

        $this->actingAsForApi($actor)
            ->getJson(route('api.documents.index'))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.id', $visible->id)
            ->assertJsonPath('rows.0.name', 'Visible cyber NIS2');
    }

    public function test_requirement_document_counts_use_document_visibility_scope(): void
    {
        $company = Company::factory()->create();
        $type = DocumentType::factory()->create();
        $actor = $this->restrictedDocumentUser($company, ['nis2'], [
            'documentframeworks.view' => '1',
            'documents.view' => '1',
            'documents.area.cybersecurity.view' => '1',
        ]);

        $framework = $this->framework($company, 'nis2', 'nis2-requirement-count-test');
        $requirement = DocumentFrameworkRequirement::query()->create([
            'document_framework_id' => $framework->id,
            'code' => 'GV-TEST-01',
            'title' => 'Scoped evidence count',
            'minimum_required_documents' => 1,
            'delegation_level' => 'owner_review',
            'risk_level' => 'medium',
            'is_active' => true,
            'is_mandatory' => true,
            'created_by' => $actor->id,
        ]);

        $visible = $this->document($company, $type, $framework, 'Visible evidence', 'cybersecurity');
        $hidden = $this->document($company, $type, $framework, 'Hidden evidence', 'administration');

        $requirement->documents()->attach($visible->id, [
            'coverage_role' => Document::COVERAGE_PRIMARY,
            'covered_at' => now(),
            'created_by' => $actor->id,
        ]);
        $requirement->documents()->attach($hidden->id, [
            'coverage_role' => Document::COVERAGE_PRIMARY,
            'covered_at' => now(),
            'created_by' => $actor->id,
        ]);

        $this->actingAsForApi($actor)
            ->getJson(route('api.documentframeworkrequirements.index', ['document_framework_id' => $framework->id]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.documents_count', 1)
            ->assertJsonPath('rows.0.primary_documents_count', 1);
    }

    public function test_compliance_domain_mutation_requires_platform_superadmin(): void
    {
        $tenantAdmin = User::factory()->admin()->create([
            'permissions' => json_encode([
                'admin' => '1',
                'compliancedomains.view' => '1',
                'compliancedomains.create' => '1',
                'compliancedomains.edit' => '1',
                'compliancedomains.delete' => '1',
            ]),
        ]);

        $this->actingAsForApi($tenantAdmin)
            ->postJson(route('api.compliancedomains.store'), [
                'key' => 'custom_scope_from_tenant',
                'name' => 'Custom Scope From Tenant',
                'is_active' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('compliance_domains', [
            'key' => 'custom_scope_from_tenant',
        ]);
    }

    public function test_nis_real_coverage_report_respects_restricted_compliance_domains(): void
    {
        $company = Company::factory()->create();
        $actor = $this->restrictedDocumentUser($company, ['gdpr'], [
            'reports.nis_real_coverage.view' => '1',
            'documents.view' => '1',
            'documents.area.cybersecurity.view' => '1',
        ]);

        $framework = $this->framework($company, 'nis2', 'nis2-hidden-from-gdpr-user');
        DocumentFrameworkRequirement::query()->create([
            'document_framework_id' => $framework->id,
            'code' => 'NIS2-DOMAIN-01',
            'title' => 'Hidden NIS2 requirement',
            'minimum_required_documents' => 1,
            'delegation_level' => 'owner_review',
            'risk_level' => 'medium',
            'is_active' => true,
            'is_mandatory' => true,
            'created_by' => $actor->id,
        ]);

        $report = (new NisRealCoverageReport)->build([$company->id], $actor);

        $this->assertSame(0, $report['summary']['total']);
        $this->assertCount(0, $report['frameworkRows']);
        $this->assertCount(0, $report['requirementRows']);
    }

    public function test_nis_real_coverage_report_counts_only_visible_document_areas(): void
    {
        $company = Company::factory()->create();
        $type = DocumentType::factory()->create();
        $actor = $this->restrictedDocumentUser($company, ['nis2'], [
            'reports.nis_real_coverage.view' => '1',
            'documents.view' => '1',
            'documents.area.cybersecurity.view' => '1',
        ]);

        $framework = $this->framework($company, 'nis2', 'nis2-hidden-area-evidence');
        $requirement = DocumentFrameworkRequirement::query()->create([
            'document_framework_id' => $framework->id,
            'code' => 'NIS2-AREA-01',
            'title' => 'Requirement with hidden area evidence',
            'minimum_required_documents' => 1,
            'delegation_level' => 'owner_review',
            'risk_level' => 'medium',
            'is_active' => true,
            'is_mandatory' => true,
            'created_by' => $actor->id,
        ]);

        $hidden = $this->document($company, $type, $framework, 'Hidden administration evidence', 'administration');
        $this->logCoverageUpload($hidden);
        $requirement->documents()->attach($hidden->id, [
            'coverage_role' => Document::COVERAGE_PRIMARY,
            'covered_at' => now(),
            'created_by' => $actor->id,
        ]);

        $report = (new NisRealCoverageReport)->build([$company->id], $actor);

        $this->assertSame(1, $report['summary']['total']);
        $this->assertSame(0, $report['summary']['covered']);
        $this->assertSame(1, $report['summary']['missing']);
        $this->assertSame(0, $report['summary']['healthy_primary_documents']);
        $this->assertSame(1, $report['summary']['document_shortfall_count']);
    }

    public function test_restricted_user_cannot_access_inactive_loaded_compliance_domain(): void
    {
        $company = Company::factory()->create();
        $domain = ComplianceDomain::query()->create([
            'key' => 'inactive_scope_test',
            'name' => 'Inactive Scope Test',
            'is_active' => false,
            'is_system' => false,
        ]);
        $actor = $this->restrictedDocumentUser($company, [], [
            'documents.view' => '1',
            'documents.area.cybersecurity.view' => '1',
        ]);
        $actor->complianceDomains()->sync([$domain->id]);
        $actor->load('complianceDomains');

        $framework = $this->framework($company, 'inactive_scope_test', 'inactive-scope-test');

        $this->assertSame([], ComplianceDomainAccess::allowedDomainKeys($actor));
        $this->assertFalse(ComplianceDomainAccess::canAccessFramework($framework, $actor));
    }

    private function restrictedDocumentUser(Company $company, array $domains, array $permissions): User
    {
        $user = User::factory()->create([
            'company_id' => $company->id,
            'permissions' => json_encode($permissions),
            'compliance_scope_restricted' => true,
        ]);

        $domainIds = ComplianceDomain::query()
            ->whereIn('key', $domains)
            ->pluck('id')
            ->all();

        $user->complianceDomains()->sync($domainIds);

        return $user;
    }

    private function framework(Company $company, string $domain, string $slug): DocumentFramework
    {
        return DocumentFramework::factory()->create([
            'company_id' => $company->id,
            'name' => strtoupper($slug),
            'slug' => $slug,
            'compliance_domain' => $domain,
            'status' => 'active',
            'is_active' => true,
            'is_system_template' => false,
        ]);
    }

    private function document(
        Company $company,
        DocumentType $type,
        ?DocumentFramework $framework,
        string $name,
        ?string $area
    ): Document {
        return Document::query()->create([
            'name' => $name,
            'company_id' => $company->id,
            'status' => Document::STATUS_ACTIVE,
            'document_area' => $area,
            'document_type_id' => $type->id,
            'document_framework_id' => $framework?->id,
        ]);
    }

    private function logCoverageUpload(Document $document): void
    {
        DB::table('action_logs')->insert([
            'item_type' => Document::class,
            'item_id' => $document->id,
            'action_type' => 'uploaded',
            'filename' => 'evidence-'.$document->id.'.pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
