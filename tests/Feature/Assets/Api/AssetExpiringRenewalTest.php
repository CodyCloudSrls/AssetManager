<?php
namespace Tests\Feature\Assets\Api;
use App\Models\Asset; use App\Models\AssetModel; use App\Models\Statuslabel; use App\Models\User;
use Tests\TestCase;
class AssetExpiringRenewalTest extends TestCase
{
    public function test_expiring_renewal_filter_matches_the_banner_count(): void
    {
        $admin = User::factory()->superuser()->create();
        $status = Statuslabel::factory()->rtd()->create();
        $model = AssetModel::factory()->create();
        // 3 due for renewal within 30 days, 2 not.
        Asset::factory()->count(3)->create(['model_id'=>$model->id,'status_id'=>$status->id,'renewal_date'=>now()->addDays(10)->toDateString()]);
        Asset::factory()->count(2)->create(['model_id'=>$model->id,'status_id'=>$status->id,'renewal_date'=>null]);

        // Banner count.
        $this->actingAs($admin);
        $this->assertSame(3, Asset::expiringRenewal(30)->count());

        // The list API (which JOINs status_labels) must NOT 500 and must return the same 3 —
        // regression for the "ambiguous column deleted_at" crash that made the list show 0.
        $res = $this->actingAs($admin)->getJson(route('api.assets.index', ['expiring_renewal' => 1]));
        $res->assertOk();
        $this->assertSame(3, $res->json('total'));
    }
}
