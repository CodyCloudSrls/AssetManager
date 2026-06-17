<?php

namespace Tests\Feature\Accessories\Ui;

use App\Models\Accessory;
use App\Models\Category;
use App\Models\User;
use Tests\TestCase;

class BulkEditAccessoriesTest extends TestCase
{
    public function test_bulk_edit_page_requires_permission()
    {
        $accessories = Accessory::factory()->count(2)->create();

        $this->actingAs(User::factory()->create())
            ->post(route('accessories.bulkedit'), [
                'ids' => $accessories->pluck('id')->toArray(),
                'bulk_actions' => 'edit',
            ])->assertForbidden();
    }

    public function test_user_with_permission_sees_bulk_edit_page()
    {
        $accessories = Accessory::factory()->count(2)->create();

        $this->actingAs(User::factory()->editAccessories()->create())
            ->post(route('accessories.bulkedit'), [
                'ids' => $accessories->pluck('id')->toArray(),
                'bulk_actions' => 'edit',
            ])->assertOk();
    }

    public function test_bulk_update_applies_only_checked_fields()
    {
        $oldCategory = Category::factory()->accessoryKeyboardCategory()->create();
        $newCategory = Category::factory()->accessoryMouseCategory()->create();

        $accessories = Accessory::factory()->count(3)->create([
            'category_id' => $oldCategory->id,
            'min_amt' => 1,
        ]);
        $id_array = $accessories->pluck('id')->toArray();

        $this->actingAs(User::factory()->editAccessories()->create())
            ->post(route('accessories.bulkeditsave'), [
                'ids' => $id_array,
                'apply_category_id' => '1',
                'category_id' => $newCategory->id,
                // min_amt is provided but NOT flagged to apply -> must stay unchanged
                'min_amt' => 99,
            ])->assertStatus(302)->assertSessionHasNoErrors();

        Accessory::findMany($id_array)->each(function (Accessory $accessory) use ($newCategory) {
            $this->assertEquals($newCategory->id, $accessory->category_id);
            $this->assertEquals(1, $accessory->min_amt);
        });
    }

    public function test_bulk_update_with_no_fields_selected_does_nothing()
    {
        $accessories = Accessory::factory()->count(2)->create(['min_amt' => 5]);
        $id_array = $accessories->pluck('id')->toArray();

        $this->actingAs(User::factory()->editAccessories()->create())
            ->post(route('accessories.bulkeditsave'), [
                'ids' => $id_array,
                'min_amt' => 42,
            ])->assertStatus(302);

        Accessory::findMany($id_array)->each(function (Accessory $accessory) {
            $this->assertEquals(5, $accessory->min_amt);
        });
    }
}
