<?php

namespace Tests\Feature\Companies\Ui;

use App\Models\Company;
use App\Models\User;
use Tests\TestCase;

class BulkEditCompaniesTest extends TestCase
{
    public function test_bulk_edit_form_is_shown(): void
    {
        $a = Company::factory()->create();
        $b = Company::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('companies.bulkedit.show'), [
                'ids' => [$a->id, $b->id],
                'bulk_actions' => 'edit',
            ])
            ->assertOk()
            ->assertSee(trans('general.bulk_edit'));
    }

    public function test_bulk_save_applies_only_checked_fields(): void
    {
        $a = Company::factory()->create(['phone' => '111', 'fax' => 'keepfax']);
        $b = Company::factory()->create(['phone' => '222', 'fax' => 'keepfax']);

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('companies.bulksave'), [
                'ids' => [$a->id, $b->id],
                'apply_phone' => '1',
                'phone' => '0550000000',
                // fax NOT applied -> must stay 'keepfax'
                'fax' => 'should-not-write',
            ])
            ->assertRedirect(route('companies.index'));

        foreach ([$a, $b] as $company) {
            $fresh = $company->fresh();
            $this->assertEquals('0550000000', $fresh->phone);
            $this->assertEquals('keepfax', $fresh->fax, 'unchecked field must be untouched');
        }
    }

    public function test_bulk_save_rejects_invalid_email(): void
    {
        $a = Company::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('companies.bulksave'), [
                'ids' => [$a->id],
                'apply_email' => '1',
                'email' => 'not-an-email',
            ])
            ->assertSessionHasErrors('email');
    }
}
