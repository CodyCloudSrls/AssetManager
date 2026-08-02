<?php
namespace Tests\Feature\Licenses\Ui;
use App\Models\License; use App\Models\Manufacturer; use App\Models\User;
use Tests\TestCase;
class LicenseFilterTest extends TestCase
{
    public function test_index_renders_and_api_filters(): void
    {
        $admin = User::factory()->superuser()->create();
        $m1 = Manufacturer::factory()->create();
        $m2 = Manufacturer::factory()->create();
        $a = License::factory()->create(['manufacturer_id'=>$m1->id, 'license_email'=>'a@x.it']);
        $b = License::factory()->create(['manufacturer_id'=>$m2->id, 'license_email'=>'b@x.it']);

        // index page renders with the filter selects
        $this->actingAs($admin)->get(route('licenses.index'))->assertOk()
            ->assertSee('license_manufacturer_filter', false)
            ->assertSee('license_email_filter', false)
            ->assertSee($m1->name, false);

        // API filters by manufacturer_id
        $r1 = $this->actingAs($admin)->getJson(route('api.licenses.index', ['manufacturer_id'=>$m1->id]));
        $r1->assertOk();
        $ids = collect($r1->json('rows'))->pluck('id')->all();
        $this->assertContains($a->id, $ids);
        $this->assertNotContains($b->id, $ids);

        // API filters by license_email
        $r2 = $this->actingAs($admin)->getJson(route('api.licenses.index', ['license_email'=>'b@x.it']));
        $ids2 = collect($r2->json('rows'))->pluck('id')->all();
        $this->assertContains($b->id, $ids2);
        $this->assertNotContains($a->id, $ids2);
    }
}
