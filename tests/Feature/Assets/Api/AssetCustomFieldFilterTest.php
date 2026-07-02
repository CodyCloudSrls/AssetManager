<?php

namespace Tests\Feature\Assets\Api;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\CustomField;
use App\Models\User;
use Tests\TestCase;

class AssetCustomFieldFilterTest extends TestCase
{
    public function test_filters_assets_by_a_custom_field_value(): void
    {
        $field = CustomField::factory()->create();
        $model = AssetModel::factory()->create();
        $match = Asset::factory()->create(['model_id' => $model->id]);
        $other = Asset::factory()->create(['model_id' => $model->id]);
        $match->{$field->db_column} = 'hetrix-online';
        $match->save();
        $other->{$field->db_column} = 'something-else';
        $other->save();

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(route('api.assets.index', ['cf_column' => $field->db_column, 'cf_value' => 'hetrix']));

        $response->assertOk();
        $ids = collect($response->json('rows'))->pluck('id')->all();
        $this->assertContains($match->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_a_non_whitelisted_cf_column_is_ignored_and_does_not_break_the_query(): void
    {
        $model = AssetModel::factory()->create();
        Asset::factory()->count(2)->create(['model_id' => $model->id]);

        // A column not in the custom-field whitelist must never reach the query builder.
        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(route('api.assets.index', ['cf_column' => 'deleted_at', 'cf_value' => 'x']));

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, $response->json('total'));
    }
}
