<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Statuslabel;
use App\Models\User;
use Tests\TestCase;

class AssetNisInheritanceTest extends TestCase
{
    public function test_new_asset_inherits_nis2_relevance_and_scope_from_its_category(): void
    {
        $this->actingAs(User::factory()->superuser()->create());
        $category = Category::factory()->create(['category_type' => 'asset', 'nis_inventory_required' => true, 'nis_inventory_scope' => 'network']);
        $model = AssetModel::factory()->create(['category_id' => $category->id]);

        // Submit "not relevant" + empty scope: the governing category must override both.
        $this->post(route('hardware.store'), [
            'asset_tags' => ['1' => 'NIS-1'],
            'model_id' => $model->id,
            'status_id' => Statuslabel::factory()->create()->id,
            'nis_relevant' => 0,
            'nis_inventory_scope' => '',
        ])->assertSessionHasNoErrors();

        $asset = Asset::where('asset_tag', 'NIS-1')->sole();
        $this->assertTrue((bool) $asset->nis_relevant);
        $this->assertSame('network', $asset->nis_inventory_scope);
    }

    public function test_asset_in_a_non_nis_category_keeps_its_own_submitted_values(): void
    {
        $this->actingAs(User::factory()->superuser()->create());
        $category = Category::factory()->create(['category_type' => 'asset', 'nis_inventory_required' => false]);
        $model = AssetModel::factory()->create(['category_id' => $category->id]);

        $this->post(route('hardware.store'), [
            'asset_tags' => ['1' => 'NIS-2'],
            'model_id' => $model->id,
            'status_id' => Statuslabel::factory()->create()->id,
            'nis_relevant' => 1,
            'nis_inventory_scope' => 'server',
        ])->assertSessionHasNoErrors();

        $asset = Asset::where('asset_tag', 'NIS-2')->sole();
        $this->assertTrue((bool) $asset->nis_relevant);
        $this->assertSame('server', $asset->nis_inventory_scope);
    }

    public function test_apply_nis_to_assets_button_propagates_to_existing_category_assets(): void
    {
        $this->actingAs(User::factory()->superuser()->create());
        $category = Category::factory()->create(['category_type' => 'asset', 'nis_inventory_required' => true, 'nis_inventory_scope' => 'network']);
        $model = AssetModel::factory()->create(['category_id' => $category->id]);
        $asset = Asset::factory()->create(['model_id' => $model->id, 'nis_relevant' => false, 'nis_inventory_scope' => null]);

        $this->put(route('categories.update', $category), [
            'name' => $category->name, 'category_type' => 'asset', 'visibility_type' => 'global',
            'nis_inventory_required' => 1, 'nis_inventory_scope' => 'network',
            'apply_nis_to_assets' => 1,
        ])->assertRedirect(route('categories.index'));

        $asset->refresh();
        $this->assertTrue((bool) $asset->nis_relevant);
        $this->assertSame('network', $asset->nis_inventory_scope);
    }
}
