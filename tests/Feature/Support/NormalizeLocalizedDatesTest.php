<?php

namespace Tests\Feature\Support;

use App\Http\Middleware\NormalizeLocalizedDates;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Statuslabel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NormalizeLocalizedDatesTest extends TestCase
{
    private function passThrough(Request $request): Request
    {
        $captured = null;
        (new NormalizeLocalizedDates)->handle($request, function ($r) use (&$captured) {
            $captured = $r;

            return response('ok');
        });

        return $captured;
    }

    public function test_converts_italian_dates_and_leaves_everything_else(): void
    {
        $out = $this->passThrough(Request::create('/hardware', 'POST', [
            'purchase_date' => '23/02/2027',
            'asset_eol_date' => '01-12-2028',
            'iso_already' => '2026-07-02',
            'not_a_date' => '99/99/2020',
            'serial' => 'AB/12/3456',
            'search' => '05/05/2020',
            'nested' => ['d' => '15/06/2026'],
        ]));

        $this->assertSame('2027-02-23', $out->input('purchase_date'));
        $this->assertSame('2028-12-01', $out->input('asset_eol_date'));
        $this->assertSame('2026-07-02', $out->input('iso_already'));   // already ISO → untouched
        $this->assertSame('99/99/2020', $out->input('not_a_date'));    // invalid date → untouched
        $this->assertSame('AB/12/3456', $out->input('serial'));        // not all digits → untouched
        $this->assertSame('05/05/2020', $out->input('search'));        // skip-listed key → untouched
        $this->assertSame('2026-06-15', $out->input('nested.d'));      // nested arrays handled
    }

    public function test_skips_livewire_requests(): void
    {
        $out = $this->passThrough(Request::create('/livewire/update', 'POST', ['x' => '23/02/2027']));

        $this->assertSame('23/02/2027', $out->input('x'));
    }

    public function test_asset_form_accepts_a_pasted_italian_date(): void
    {
        $this->actingAs(User::factory()->superuser()->create());
        $model = AssetModel::factory()->create();

        $this->post(route('hardware.store'), [
            'asset_tags' => ['1' => 'DT-1'],
            'model_id' => $model->id,
            'status_id' => Statuslabel::factory()->create()->id,
            'purchase_date' => '23/02/2027',
        ])->assertSessionHasNoErrors();

        $asset = Asset::where('asset_tag', 'DT-1')->sole();
        $this->assertSame('2027-02-23', Carbon::parse($asset->purchase_date)->format('Y-m-d'));
    }
}
