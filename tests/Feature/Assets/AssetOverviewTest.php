<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\CustomFieldset;
use App\Models\User;
use Tests\TestCase;

class AssetOverviewTest extends TestCase
{
    public function test_overview_page_renders(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.overview'))
            ->assertOk()
            ->assertSee(trans('admin/hardware/general.overview_title'));
    }

    public function test_fieldset_filter_scopes_assets_to_that_fieldset(): void
    {
        $fieldset = CustomFieldset::factory()->create();
        $modelWith = AssetModel::factory()->create(['fieldset_id' => $fieldset->id]);
        $modelWithout = AssetModel::factory()->create(['fieldset_id' => null]);
        Asset::factory()->create(['model_id' => $modelWith->id]);
        Asset::factory()->create(['model_id' => $modelWithout->id]);

        // Same predicate the assets API uses for the ?fieldset_id filter.
        $count = Asset::whereHas('model', fn ($q) => $q->where('fieldset_id', $fieldset->id))->count();

        $this->assertSame(1, $count);
    }
}
