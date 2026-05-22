<?php

namespace Tests\Feature\Reporting;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
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
}
