<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\User;
use Tests\TestCase;

/**
 * Guards against Blade compile/render regressions on the asset and model forms
 * (a broken directive there returns a 500 that POST-only tests never catch).
 */
class AssetFormRendersTest extends TestCase
{
    public function test_asset_create_form_renders(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.create'))
            ->assertOk();
    }

    public function test_asset_edit_form_renders(): void
    {
        $asset = Asset::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.edit', $asset))
            ->assertOk();
    }

    public function test_asset_clone_form_renders(): void
    {
        $asset = Asset::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('clone/hardware', $asset))
            ->assertOk();
    }

    public function test_model_create_and_edit_forms_render(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('models.create'))
            ->assertOk();

        $model = AssetModel::factory()->create();
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('models.edit', $model))
            ->assertOk();
    }
}
