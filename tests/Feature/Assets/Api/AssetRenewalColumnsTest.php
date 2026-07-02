<?php

namespace Tests\Feature\Assets\Api;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\User;
use Tests\TestCase;

class AssetRenewalColumnsTest extends TestCase
{
    public function test_api_exposes_renewal_date_auto_renewal_and_days_left(): void
    {
        $model = AssetModel::factory()->create();
        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            'renewal_date' => now()->addDays(10)->format('Y-m-d'),
            'auto_renewal' => true,
        ]);

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(route('api.assets.index', ['search' => $asset->asset_tag]));

        $response->assertOk();
        $row = collect($response->json('rows'))->firstWhere('id', $asset->id);

        $this->assertNotNull($row);
        $this->assertNotNull($row['renewal_date']);
        $this->assertTrue($row['auto_renewal']);
        // Signed days remaining (10 days ahead → ~10).
        $this->assertGreaterThan(0, $row['renewal_days_left']);
    }
}
