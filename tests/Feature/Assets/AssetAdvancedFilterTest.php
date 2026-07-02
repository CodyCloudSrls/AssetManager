<?php

namespace Tests\Feature\Assets;

use App\Models\User;
use Tests\TestCase;

class AssetAdvancedFilterTest extends TestCase
{
    public function test_asset_index_shows_advanced_filters_panel(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.index'))
            ->assertOk()
            ->assertSee(trans('admin/hardware/general.advanced_filters'));
    }

    public function test_asset_index_accepts_structured_filters(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.index', ['category_id' => 1, 'status_id' => 1]))
            ->assertOk();
    }
}
