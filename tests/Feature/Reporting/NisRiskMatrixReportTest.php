<?php

namespace Tests\Feature\Reporting;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\User;
use App\Support\Reports\NisRiskMatrixReport;
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
}
