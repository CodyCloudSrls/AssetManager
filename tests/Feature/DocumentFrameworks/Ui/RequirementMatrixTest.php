<?php

namespace Tests\Feature\DocumentFrameworks\Ui;

use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use App\Models\User;
use Tests\TestCase;

class RequirementMatrixTest extends TestCase
{
    public function test_framework_requirement_matrix_displays_coverage_owner_review_and_linked_evidence(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->create(['company_id' => $company->id]);
        $admin = User::factory()->superuser()->create();

        $framework = DocumentFramework::factory()->for($company)->create([
            'name' => 'NIS2 Evidence Framework',
            'compliance_domain' => 'nis2',
        ]);

        $requirement = DocumentFrameworkRequirement::create([
            'document_framework_id' => $framework->id,
            'code' => 'NIS-TEST-01',
            'title' => 'Maintain auditable supplier evidence',
            'domain' => 'Supply Chain',
            'delegation_level' => 'owner_review',
            'risk_level' => 'not_applicable',
            'owner_id' => $owner->id,
            'evidence_guidance' => 'Linked contracts and supplier assessments.',
            'is_active' => true,
            'is_mandatory' => true,
            'created_by' => $admin->id,
        ]);

        $document = Document::create([
            'name' => 'Supplier Security Assessment',
            'company_id' => $company->id,
            'owner_id' => $owner->id,
            'document_framework_id' => $framework->id,
            'status' => Document::STATUS_ACTIVE,
            'version' => '1.0',
            'next_review_at' => now()->addDays(10)->format('Y-m-d'),
        ]);

        $requirement->documents()->attach($document->id, [
            'coverage_role' => Document::COVERAGE_PRIMARY,
            'covered_at' => now()->format('Y-m-d'),
            'notes' => 'Primary supplier evidence.',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('documentframeworks.requirements.matrix', $framework))
            ->assertOk()
            ->assertSee('NIS-TEST-01')
            ->assertSee('Maintain auditable supplier evidence')
            ->assertSee('Supplier Security Assessment')
            ->assertSee('Primary supplier evidence.')
            ->assertSee($owner->display_name);
    }
}
