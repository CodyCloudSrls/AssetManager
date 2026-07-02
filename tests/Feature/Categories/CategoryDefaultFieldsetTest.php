<?php

namespace Tests\Feature\Categories;

use App\Models\AssetModel;
use App\Models\Category;
use App\Models\CustomFieldset;
use App\Models\Manufacturer;
use App\Models\User;
use Tests\TestCase;

class CategoryDefaultFieldsetTest extends TestCase
{
    public function test_asset_category_stores_the_default_fieldset(): void
    {
        $fieldset = CustomFieldset::factory()->create();
        $this->actingAs(User::factory()->superuser()->create());

        $this->post(route('categories.store'), [
            'name' => 'Indirizzo IP', 'category_type' => 'asset', 'visibility_type' => 'global', 'fieldset_id' => $fieldset->id,
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', ['name' => 'Indirizzo IP', 'fieldset_id' => $fieldset->id]);
    }

    public function test_non_asset_category_never_gets_a_fieldset(): void
    {
        $fieldset = CustomFieldset::factory()->create();
        $this->actingAs(User::factory()->superuser()->create());

        $this->post(route('categories.store'), [
            'name' => 'Licenze cat', 'category_type' => 'license', 'visibility_type' => 'global', 'fieldset_id' => $fieldset->id,
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', ['name' => 'Licenze cat', 'fieldset_id' => null]);
    }

    public function test_new_model_inherits_the_category_default_fieldset(): void
    {
        $fieldset = CustomFieldset::factory()->create();
        $category = Category::factory()->create(['category_type' => 'asset', 'fieldset_id' => $fieldset->id]);
        $manufacturer = Manufacturer::factory()->create();
        $this->actingAs(User::factory()->superuser()->create());

        // No fieldset chosen for the model → it inherits the category's default.
        $this->post(route('models.store'), [
            'name' => '.com', 'category_id' => $category->id, 'manufacturer_id' => $manufacturer->id,
            'visibility_type' => 'global', 'fieldset_id' => '',
        ])->assertRedirect();

        $this->assertSame($fieldset->id, (int) AssetModel::where('name', '.com')->firstOrFail()->fieldset_id);
    }

    public function test_apply_to_models_fills_only_models_without_a_fieldset(): void
    {
        $default = CustomFieldset::factory()->create();
        $other = CustomFieldset::factory()->create();
        $category = Category::factory()->create(['category_type' => 'asset', 'fieldset_id' => $default->id]);
        $withNull = AssetModel::factory()->create(['category_id' => $category->id, 'fieldset_id' => null]);
        $withOther = AssetModel::factory()->create(['category_id' => $category->id, 'fieldset_id' => $other->id]);

        $this->actingAs(User::factory()->superuser()->create());
        $this->put(route('categories.update', $category), [
            'name' => $category->name, 'category_type' => 'asset', 'visibility_type' => 'global',
            'fieldset_id' => $default->id, 'apply_fieldset_to_models' => '1',
        ])->assertRedirect(route('categories.index'));

        $this->assertSame($default->id, (int) $withNull->fresh()->fieldset_id);  // filled
        $this->assertSame($other->id, (int) $withOther->fresh()->fieldset_id);   // preserved
    }
}
