<?php

namespace Tests\Unit;

use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class DocumentFrameworkRequirementRiskTest extends TestCase
{
    public function test_legacy_nis2_frameworks_are_detected_without_compliance_domain(): void
    {
        $framework = new DocumentFramework([
            'name' => 'NIS2',
            'framework_code' => null,
            'compliance_domain' => null,
        ]);

        $this->assertTrue($framework->isNis2Domain());
    }

    public function test_nis2_requirement_risk_is_displayed_as_not_applicable(): void
    {
        $framework = new DocumentFramework([
            'name' => 'NIS2',
            'framework_code' => null,
            'compliance_domain' => null,
        ]);

        $requirement = new DocumentFrameworkRequirement([
            'risk_level' => 'medium',
        ]);
        $requirement->setRelation('framework', $framework);

        $this->assertSame('not_applicable', $requirement->effective_risk_level);
    }

    public function test_requirement_can_expose_multiple_parent_codes(): void
    {
        $firstParent = new DocumentFrameworkRequirement([
            'id' => 10,
            'code' => 'NIS2-GOV-01',
        ]);
        $firstParent->forceFill(['id' => 10]);

        $secondParent = new DocumentFrameworkRequirement([
            'id' => 11,
            'code' => 'NIS2-RISK-01',
        ]);
        $secondParent->forceFill(['id' => 11]);

        $requirement = new DocumentFrameworkRequirement([
            'id' => 12,
            'code' => 'NIS2-CTRL-01',
        ]);
        $requirement->forceFill(['id' => 12]);
        $requirement->exists = true;
        $requirement->setRelation('parents', new Collection([$firstParent, $secondParent]));

        $this->assertSame('NIS2-GOV-01, NIS2-RISK-01', $requirement->parent_requirement_codes);
    }

    public function test_requirement_coverage_requires_minimum_healthy_primary_documents(): void
    {
        $requirement = new DocumentFrameworkRequirement([
            'minimum_required_documents' => 2,
        ]);
        $requirement->forceFill([
            'documents_count' => 2,
            'primary_documents_count' => 2,
            'healthy_primary_documents_count' => 1,
        ]);

        $this->assertSame(DocumentFrameworkRequirement::COVERAGE_AT_RISK, $requirement->coverage_status);
        $this->assertFalse($requirement->document_minimum_satisfied);
        $this->assertSame(1, $requirement->document_shortfall_count);
    }

    public function test_zero_minimum_documents_is_covered_without_evidence(): void
    {
        $requirement = new DocumentFrameworkRequirement([
            'minimum_required_documents' => 0,
        ]);
        $requirement->forceFill([
            'documents_count' => 0,
            'primary_documents_count' => 0,
            'healthy_primary_documents_count' => 0,
        ]);

        $this->assertSame(DocumentFrameworkRequirement::COVERAGE_COVERED, $requirement->coverage_status);
        $this->assertTrue($requirement->document_minimum_satisfied);
        $this->assertSame(0, $requirement->document_shortfall_count);
    }
}
