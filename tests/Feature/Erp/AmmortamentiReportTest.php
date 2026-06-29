<?php

namespace Tests\Feature\Erp;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Depreciation;
use App\Models\User;
use App\Support\Reports\AmmortamentiReport;
use Tests\TestCase;

class AmmortamentiReportTest extends TestCase
{
    private function makeAsset(array $depr = [], array $asset = []): Asset
    {
        $depreciation = Depreciation::factory()->create(array_merge(['months' => 60], $depr));
        $model = AssetModel::factory()->create(['depreciation_id' => $depreciation->id]);

        return Asset::factory()->create(array_merge([
            'model_id' => $model->id,
            'purchase_cost' => 1000,
            'purchase_date' => '2024-03-01',
        ], $asset));
    }

    public function test_first_year_is_halved_then_full_rate(): void
    {
        $this->actingAs(User::factory()->superuser()->create());
        $asset = $this->makeAsset(['coefficiente_annuo' => 20]); // 20% on 1000 -> full quota 200

        $row2024 = (new AmmortamentiReport())->build(null, 2024)['rows']->firstWhere('asset.id', $asset->id);
        $this->assertEqualsWithDelta(20, $row2024['coefficiente'], 0.01);
        $this->assertEqualsWithDelta(100, $row2024['quota_year'], 0.01); // prima quota = 200/2
        $this->assertEqualsWithDelta(100, $row2024['fondo'], 0.01);
        $this->assertEqualsWithDelta(900, $row2024['residuo'], 0.01);

        $row2026 = (new AmmortamentiReport())->build(null, 2026)['rows']->firstWhere('asset.id', $asset->id);
        $this->assertEqualsWithDelta(200, $row2026['quota_year'], 0.01); // full quota
        $this->assertEqualsWithDelta(500, $row2026['fondo'], 0.01);      // 100 + 200 + 200
        $this->assertEqualsWithDelta(500, $row2026['residuo'], 0.01);
    }

    public function test_fund_never_exceeds_cost(): void
    {
        $this->actingAs(User::factory()->superuser()->create());
        $asset = $this->makeAsset(['coefficiente_annuo' => 20]);

        $row = (new AmmortamentiReport())->build(null, 2040)['rows']->firstWhere('asset.id', $asset->id);
        $this->assertEqualsWithDelta(1000, $row['fondo'], 0.01);
        $this->assertEqualsWithDelta(0, $row['residuo'], 0.01);
        $this->assertTrue($row['fully_depreciated']);
    }

    public function test_rate_derived_from_months_when_no_coefficient(): void
    {
        $this->actingAs(User::factory()->superuser()->create());
        $asset = $this->makeAsset(['months' => 48, 'coefficiente_annuo' => null]); // 12/48 = 25%

        $row = (new AmmortamentiReport())->build(null, 2025)['rows']->firstWhere('asset.id', $asset->id);
        $this->assertEqualsWithDelta(25, $row['coefficiente'], 0.01);
    }

    public function test_libro_cespiti_page_renders(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('erp.ammortamenti'))
            ->assertOk()
            ->assertSee(trans('erp/general.ammortamenti.title'));
    }
}
