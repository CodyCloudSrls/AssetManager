<?php

namespace Tests\Feature\Licenses\Ui;

use App\Models\Company;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use Tests\TestCase;

class BulkLicensesTest extends TestCase
{
    public function test_requires_permission(): void
    {
        $license = License::factory()->create();

        $this->actingAs(User::factory()->create()) // no license perms
            ->post(route('licenses.bulkedit'), ['ids' => [$license->id], 'bulk_actions' => 'edit'])
            ->assertForbidden();
    }

    public function test_bulk_edit_form_renders_for_selected_licenses(): void
    {
        $licenses = License::factory()->count(2)->create();

        $this->actingAs(User::factory()->editLicenses()->create())
            ->post(route('licenses.bulkedit'), [
                'ids' => $licenses->pluck('id')->all(),
                'bulk_actions' => 'edit',
            ])
            ->assertOk()
            ->assertSee(trans('general.bulk_edit'), false);
    }

    public function test_bulk_update_applies_only_ticked_fields(): void
    {
        $licenses = License::factory()->count(2)->create(['license_email' => 'old@example.com', 'notes' => 'keep']);

        $this->actingAs(User::factory()->editLicenses()->create())
            ->post(route('licenses.bulkeditsave'), [
                'ids' => $licenses->pluck('id')->all(),
                'apply_license_email' => '1',
                'license_email' => 'new@example.com',
                // notes is submitted but NOT ticked -> must stay unchanged
                'notes' => 'should-not-apply',
            ])
            ->assertRedirect(route('licenses.index'));

        foreach ($licenses as $license) {
            $license->refresh();
            $this->assertSame('new@example.com', $license->license_email);
            $this->assertSame('keep', $license->notes, 'un-ticked field must not change');
        }
    }

    public function test_blank_company_with_apply_does_not_rehome_licenses(): void
    {
        $company = Company::factory()->create();
        $license = License::factory()->create(['company_id' => $company->id]);

        $this->actingAs(User::factory()->editLicenses()->create())
            ->post(route('licenses.bulkeditsave'), [
                'ids' => [$license->id],
                'apply_company_id' => '1',
                'company_id' => '', // blank + ticked must NOT move it
            ])
            ->assertRedirect(route('licenses.index'));

        $this->assertSame($company->id, $license->refresh()->company_id, 'blank company must leave company unchanged');
    }

    public function test_bulk_delete_confirm_renders(): void
    {
        $licenses = License::factory()->count(2)->create();

        $this->actingAs(User::factory()->deleteLicenses()->create())
            ->post(route('licenses.bulkedit'), [
                'ids' => $licenses->pluck('id')->all(),
                'bulk_actions' => 'delete',
            ])
            ->assertOk()
            ->assertSee(trans('general.bulk_delete'), false);
    }

    public function test_bulk_delete_removes_unassigned_and_skips_assigned(): void
    {
        $free = License::factory()->create(['seats' => 1]);
        $inUse = License::factory()->create(['seats' => 1]);

        // Assign a seat on $inUse so it must be skipped.
        $seat = LicenseSeat::where('license_id', $inUse->id)->first();
        $seat->assigned_to = User::factory()->create()->id;
        $seat->save();

        $this->assertSame(0, (int) $free->refresh()->assigned_seats_count);
        $this->assertSame(1, (int) $inUse->refresh()->assigned_seats_count);

        $this->actingAs(User::factory()->deleteLicenses()->create())
            ->post(route('licenses.bulkdelete'), [
                'ids' => [$free->id, $inUse->id],
            ])
            ->assertRedirect(route('licenses.index'));

        $this->assertNull(License::find($free->id), 'unassigned license should be deleted');
        $this->assertNotNull(License::find($inUse->id), 'license with an assigned seat must be kept');
    }
}
