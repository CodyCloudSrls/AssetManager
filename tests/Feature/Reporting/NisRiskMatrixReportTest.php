<?php

namespace Tests\Feature\Reporting;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use App\Models\DocumentType;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Reports\NisRealCoverageReport;
use App\Support\Reports\NisRiskMatrixReport;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('reporting')]
class NisRiskMatrixReportTest extends TestCase
{
    public function test_requires_permission()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reports.nis-risk-matrix'))
            ->assertForbidden();
    }

    public function test_can_load_nis_risk_matrix_report_page()
    {
        $this->actingAs(User::factory()->canViewReports()->create())
            ->get(route('reports.nis-risk-matrix'))
            ->assertOk()
            ->assertViewIs('reports.nis_risk_matrix')
            ->assertViewHas(['summary', 'categoryRows', 'rows']);
    }

    public function test_can_load_nis_real_coverage_report_page()
    {
        $this->actingAs(User::factory()->canViewReports()->create())
            ->get(route('reports.nis-real-coverage'))
            ->assertOk()
            ->assertViewIs('reports.nis_real_coverage')
            ->assertViewHas(['summary', 'frameworkRows', 'requirementRows']);
    }

    public function test_nis_real_coverage_page_defaults_to_active_tenant_companies()
    {
        $tenantA = Tenant::createMinimal();
        $tenantB = Tenant::createMinimal();
        $companyA = Company::factory()->create(['name' => 'Tenant Alpha Ltd', 'tenant_id' => $tenantA->id]);
        $companyB = Company::factory()->create(['name' => 'Tenant Beta Ltd', 'tenant_id' => $tenantB->id]);

        $this->createNisFrameworkWithRequirement($companyA, 'NIS2 IT - Allegato 1');
        $this->createNisFrameworkWithRequirement($companyB, 'NIS2 IT - Allegato 2');

        $actor = User::factory()->canViewReports()->create(['company_id' => $companyA->id]);

        $this->actingAs($actor)
            ->get(route('reports.nis-real-coverage'))
            ->assertOk()
            ->assertViewHas('frameworkRows', function ($rows) {
                return $rows->count() === 1
                    && $rows->first()['company_name'] === 'Tenant Alpha Ltd'
                    && $rows->first()['framework']->name === 'NIS2 IT - Allegato 1';
            });
    }

    public function test_nis_real_coverage_excludes_companyless_operational_frameworks()
    {
        $company = Company::factory()->create();
        $this->createNisFrameworkWithRequirement($company, 'NIS2 IT - Allegato 1');

        $globalFramework = DocumentFramework::factory()->create([
            'name' => 'NIS2 IT - Allegato 2',
            'slug' => 'nis2-it-allegato-2',
            'framework_code' => 'NIS2-IT-2',
            'compliance_domain' => 'nis2',
            'company_id' => null,
            'is_system_template' => false,
        ]);
        DocumentFrameworkRequirement::create([
            'document_framework_id' => $globalFramework->id,
            'code' => 'NIS2-GLOBAL-01',
            'title' => 'Global requirement should not be reported',
            'delegation_level' => 'owner_review',
            'risk_level' => 'not_applicable',
        ]);

        $report = (new NisRealCoverageReport)->build();

        $this->assertCount(1, $report['frameworkRows']);
        $this->assertSame('NIS2 IT - Allegato 1', $report['frameworkRows']->first()['framework']->name);
        $this->assertSame(1, $report['summary']['total']);
    }

    public function test_nis_real_coverage_document_type_missing_count_is_not_summed_per_requirement()
    {
        $company = Company::factory()->create();
        $policyType = DocumentType::factory()->create(['name' => 'Policy']);
        $procedureType = DocumentType::factory()->create(['name' => 'Procedure']);
        $framework = DocumentFramework::factory()->for($company)->create([
            'name' => 'NIS2 Tenant Document Types',
            'slug' => 'nis2-tenant-document-types',
            'framework_code' => 'NIS2',
            'compliance_domain' => 'nis2',
        ]);

        foreach ([
            ['NIS2-TYPE-01', $policyType->id],
            ['NIS2-TYPE-02', $policyType->id],
            ['NIS2-TYPE-03', $procedureType->id],
        ] as [$code, $documentTypeId]) {
            DocumentFrameworkRequirement::create([
                'document_framework_id' => $framework->id,
                'code' => $code,
                'title' => $code,
                'delegation_level' => 'owner_review',
                'risk_level' => 'not_applicable',
                'default_document_type_id' => $documentTypeId,
                'minimum_required_documents' => 1,
            ]);
        }

        $report = (new NisRealCoverageReport)->build([$company->id]);

        $this->assertSame(3, $report['summary']['document_shortfall_count']);
        $this->assertSame(2, $report['summary']['required_document_types_count']);
        $this->assertSame(0, $report['summary']['healthy_required_document_types_count']);
        $this->assertSame(2, $report['summary']['missing_required_document_types_count']);
    }

    public function test_nis_real_coverage_counts_distinct_required_documents_not_requirement_links()
    {
        $company = Company::factory()->create();
        $framework = DocumentFramework::factory()->for($company)->create([
            'name' => 'NIS2 Tenant Required Documents',
            'slug' => 'nis2-tenant-required-documents',
            'framework_code' => 'NIS2',
            'compliance_domain' => 'nis2',
        ]);

        $requirements = collect(['NIS2-DOC-01', 'NIS2-DOC-02', 'NIS2-DOC-03'])
            ->map(fn (string $code) => DocumentFrameworkRequirement::create([
                'document_framework_id' => $framework->id,
                'code' => $code,
                'title' => $code,
                'delegation_level' => 'owner_review',
                'risk_level' => 'not_applicable',
                'minimum_required_documents' => 1,
            ]));

        $validDocument = Document::create([
            'name' => 'Shared valid evidence',
            'company_id' => $company->id,
            'status' => Document::STATUS_ACTIVE,
        ]);
        $this->logCoverageUpload($validDocument);

        $draftDocument = Document::create([
            'name' => 'Draft required evidence',
            'company_id' => $company->id,
            'status' => Document::STATUS_DRAFT,
        ]);

        $requirements[0]->documents()->attach($validDocument->id, [
            'coverage_role' => Document::COVERAGE_PRIMARY,
            'covered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $requirements[1]->documents()->attach($validDocument->id, [
            'coverage_role' => Document::COVERAGE_PRIMARY,
            'covered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $requirements[2]->documents()->attach($draftDocument->id, [
            'coverage_role' => Document::COVERAGE_PRIMARY,
            'covered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = (new NisRealCoverageReport)->build([$company->id]);

        $this->assertSame(2, $report['summary']['required_documents_count']);
        $this->assertSame(1, $report['summary']['healthy_required_documents_count']);
        $this->assertSame(1, $report['summary']['missing_required_documents_count']);
        $this->assertSame(1, $report['summary']['document_shortfall_count']);
    }

    public function test_report_is_calculated_from_nis_asset_and_category_fields()
    {
        $category = Category::factory()->create([
            'nis_inventory_required' => true,
            'nis_inventory_scope' => 'network',
        ]);
        Category::factory()->create([
            'nis_inventory_required' => true,
            'nis_inventory_scope' => 'backup',
        ]);

        $model = AssetModel::factory()->for($category, 'category')->create();

        $included = Asset::factory()->for($model, 'model')->create([
            'nis_relevant' => false,
            'nis_service_impact' => 'critical',
        ]);

        Asset::factory()->create([
            'nis_relevant' => false,
            'nis_service_impact' => 'critical',
        ]);

        $report = (new NisRiskMatrixReport)->build();

        $this->assertCount(1, $report['rows']);
        $this->assertSame($included->id, $report['rows']->first()['asset']->id);
        $this->assertSame(NisRiskMatrixReport::RISK_CRITICAL, $report['rows']->first()['risk_level']);
        $this->assertSame(12, $report['rows']->first()['risk_score']);
        $this->assertSame(1, $report['summary'][NisRiskMatrixReport::RISK_CRITICAL]);
        $this->assertCount(2, $report['categoryRows']);
    }

    public function test_nis_real_coverage_uses_minimum_healthy_primary_documents()
    {
        $company = Company::factory()->create();
        $framework = DocumentFramework::factory()->for($company)->create([
            'name' => 'NIS2 Tenant',
            'slug' => 'nis2-tenant-real-coverage',
            'framework_code' => 'NIS2',
            'compliance_domain' => 'nis2',
        ]);

        $requirement = DocumentFrameworkRequirement::create([
            'document_framework_id' => $framework->id,
            'code' => 'NIS2-REQ-01',
            'title' => 'Evidence requirement',
            'delegation_level' => 'owner_review',
            'risk_level' => 'not_applicable',
            'minimum_required_documents' => 2,
        ]);

        $document = Document::create([
            'name' => 'Primary evidence',
            'company_id' => $company->id,
            'status' => Document::STATUS_ACTIVE,
        ]);
        $this->logCoverageUpload($document);

        $requirement->documents()->attach($document->id, [
            'coverage_role' => Document::COVERAGE_PRIMARY,
            'covered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = (new NisRealCoverageReport)->build([$company->id]);

        $this->assertSame(1, $report['summary']['total']);
        $this->assertSame(0, $report['summary']['covered']);
        $this->assertSame(1, $report['summary']['at_risk']);
        $this->assertSame(1, $report['summary']['document_shortfall_count']);
        $this->assertSame(DocumentFrameworkRequirement::COVERAGE_AT_RISK, $report['requirementRows']->first()['requirement']->coverage_status);
    }

    public function test_nis_real_coverage_counts_primary_documents_effective_today()
    {
        $company = Company::factory()->create();
        $framework = DocumentFramework::factory()->for($company)->create([
            'name' => 'NIS2 Tenant Effective',
            'slug' => 'nis2-tenant-effective',
            'framework_code' => 'NIS2',
            'compliance_domain' => 'nis2',
        ]);

        $requirement = DocumentFrameworkRequirement::create([
            'document_framework_id' => $framework->id,
            'code' => 'NIS2-REQ-02',
            'title' => 'Effective evidence requirement',
            'delegation_level' => 'owner_review',
            'risk_level' => 'not_applicable',
            'minimum_required_documents' => 1,
        ]);

        $document = Document::create([
            'name' => 'Effective primary evidence',
            'company_id' => $company->id,
            'status' => Document::STATUS_ACTIVE,
            'effective_at' => now()->toDateString(),
            'next_review_at' => now()->addMonth()->toDateString(),
        ]);
        $this->logCoverageUpload($document);

        $requirement->documents()->attach($document->id, [
            'coverage_role' => Document::COVERAGE_PRIMARY,
            'covered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = (new NisRealCoverageReport)->build([$company->id]);

        $this->assertSame(1, $report['summary']['covered']);
        $this->assertSame(100, $report['summary']['coverage_percent']);
        $this->assertSame(1, $report['summary']['healthy_primary_documents']);
        $this->assertSame(DocumentFrameworkRequirement::COVERAGE_COVERED, $report['requirementRows']->first()['requirement']->coverage_status);
    }

    public function test_nis_real_coverage_excludes_primary_documents_that_are_not_yet_effective()
    {
        $company = Company::factory()->create();
        $framework = DocumentFramework::factory()->for($company)->create([
            'name' => 'NIS2 Tenant Future Effective',
            'slug' => 'nis2-tenant-future-effective',
            'framework_code' => 'NIS2',
            'compliance_domain' => 'nis2',
        ]);

        $requirement = DocumentFrameworkRequirement::create([
            'document_framework_id' => $framework->id,
            'code' => 'NIS2-REQ-03',
            'title' => 'Future effective evidence requirement',
            'delegation_level' => 'owner_review',
            'risk_level' => 'not_applicable',
            'minimum_required_documents' => 2,
        ]);

        $currentDocument = Document::create([
            'name' => 'Current primary evidence',
            'company_id' => $company->id,
            'status' => Document::STATUS_ACTIVE,
            'effective_at' => now()->toDateString(),
            'next_review_at' => now()->addMonth()->toDateString(),
        ]);
        $this->logCoverageUpload($currentDocument);

        $futureDocument = Document::create([
            'name' => 'Future primary evidence',
            'company_id' => $company->id,
            'status' => Document::STATUS_ACTIVE,
            'effective_at' => now()->addDay()->toDateString(),
            'next_review_at' => now()->addMonth()->toDateString(),
        ]);
        $this->logCoverageUpload($futureDocument);

        $requirement->documents()->attach([$currentDocument->id, $futureDocument->id], [
            'coverage_role' => Document::COVERAGE_PRIMARY,
            'covered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = (new NisRealCoverageReport)->build([$company->id]);

        $this->assertSame(0, $report['summary']['covered']);
        $this->assertSame(1, $report['summary']['at_risk']);
        $this->assertSame(1, $report['summary']['healthy_primary_documents']);
        $this->assertSame(1, $report['summary']['document_shortfall_count']);
        $this->assertSame(DocumentFrameworkRequirement::COVERAGE_AT_RISK, $report['requirementRows']->first()['requirement']->coverage_status);
    }

    public function test_nis_real_coverage_excludes_primary_documents_without_uploaded_file()
    {
        $company = Company::factory()->create();
        $framework = DocumentFramework::factory()->for($company)->create([
            'name' => 'NIS2 Tenant Missing Upload',
            'slug' => 'nis2-tenant-missing-upload',
            'framework_code' => 'NIS2',
            'compliance_domain' => 'nis2',
        ]);

        $requirement = DocumentFrameworkRequirement::create([
            'document_framework_id' => $framework->id,
            'code' => 'NIS2-REQ-04',
            'title' => 'Uploaded evidence requirement',
            'delegation_level' => 'owner_review',
            'risk_level' => 'not_applicable',
            'minimum_required_documents' => 1,
        ]);

        $document = Document::create([
            'name' => 'Active evidence without file',
            'company_id' => $company->id,
            'status' => Document::STATUS_ACTIVE,
            'effective_at' => now()->toDateString(),
            'next_review_at' => now()->addMonth()->toDateString(),
        ]);

        $requirement->documents()->attach($document->id, [
            'coverage_role' => Document::COVERAGE_PRIMARY,
            'covered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = (new NisRealCoverageReport)->build([$company->id]);

        $this->assertSame(0, $report['summary']['covered']);
        $this->assertSame(1, $report['summary']['at_risk']);
        $this->assertSame(0, $report['summary']['healthy_primary_documents']);
        $this->assertSame(DocumentFrameworkRequirement::COVERAGE_AT_RISK, $report['requirementRows']->first()['requirement']->coverage_status);
    }

    public function test_nis_real_coverage_excludes_draft_primary_documents_even_with_uploaded_file()
    {
        $company = Company::factory()->create();
        $framework = DocumentFramework::factory()->for($company)->create([
            'name' => 'NIS2 Tenant Draft Document',
            'slug' => 'nis2-tenant-draft-document',
            'framework_code' => 'NIS2',
            'compliance_domain' => 'nis2',
        ]);

        $requirement = DocumentFrameworkRequirement::create([
            'document_framework_id' => $framework->id,
            'code' => 'NIS2-REQ-05',
            'title' => 'Valid evidence requirement',
            'delegation_level' => 'owner_review',
            'risk_level' => 'not_applicable',
            'minimum_required_documents' => 1,
        ]);

        $document = Document::create([
            'name' => 'Draft evidence with file',
            'company_id' => $company->id,
            'status' => Document::STATUS_DRAFT,
            'effective_at' => now()->toDateString(),
            'next_review_at' => now()->addMonth()->toDateString(),
        ]);
        $this->logCoverageUpload($document);

        $requirement->documents()->attach($document->id, [
            'coverage_role' => Document::COVERAGE_PRIMARY,
            'covered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = (new NisRealCoverageReport)->build([$company->id]);

        $this->assertSame(0, $report['summary']['covered']);
        $this->assertSame(1, $report['summary']['at_risk']);
        $this->assertSame(0, $report['summary']['healthy_primary_documents']);
        $this->assertSame(DocumentFrameworkRequirement::COVERAGE_AT_RISK, $report['requirementRows']->first()['requirement']->coverage_status);
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

    private function createNisFrameworkWithRequirement(Company $company, string $name): DocumentFramework
    {
        $framework = DocumentFramework::factory()->for($company)->create([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'framework_code' => 'NIS2',
            'compliance_domain' => 'nis2',
        ]);

        DocumentFrameworkRequirement::create([
            'document_framework_id' => $framework->id,
            'code' => str($name)->slug('-')->upper()->toString().'-REQ-01',
            'title' => 'Requirement for '.$name,
            'delegation_level' => 'owner_review',
            'risk_level' => 'not_applicable',
        ]);

        return $framework;
    }
}
