<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\Location;
use App\Models\User;
use Tests\TestCase;

class AssetLocationClearTest extends TestCase
{
    public function test_default_location_can_be_cleared_on_update(): void
    {
        $location = Location::factory()->create();
        $asset = Asset::factory()->create(['rtd_location_id' => $location->id]);
        $this->assertNotNull($asset->rtd_location_id);

        $this->actingAs(User::factory()->viewAssets()->editAssets()->create())
            ->put(route('hardware.update', $asset), [
                'name' => $asset->name ?: 'Asset',
                'asset_tags' => $asset->asset_tag,
                'status_id' => $asset->status_id,
                'model_id' => $asset->model_id,
                'rtd_location_id' => '',
            ])->assertSessionHasNoErrors();

        $this->assertNull($asset->refresh()->rtd_location_id);
    }
}
