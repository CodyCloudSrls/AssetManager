<?php

namespace Tests\Feature\Suppliers\Ui;

use App\Models\Supplier;
use App\Models\User;
use Tests\TestCase;

class BulkEditSuppliersTest extends TestCase
{
    public function test_bulk_edit_form_is_shown_for_edit_action(): void
    {
        $s1 = Supplier::factory()->create();
        $s2 = Supplier::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('suppliers.bulkedit.show'), [
                'ids' => [$s1->id, $s2->id],
                'bulk_actions' => 'edit',
            ])
            ->assertOk()
            ->assertSee(trans('general.bulk_edit'));
    }

    public function test_bulk_save_applies_only_checked_fields(): void
    {
        $s1 = Supplier::factory()->create(['nis_relevant' => false, 'notes' => 'keep']);
        $s2 = Supplier::factory()->create(['nis_relevant' => false, 'notes' => 'keep']);

        $criticality = array_key_first(Supplier::nisCriticalityOptions());

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('suppliers.bulksave'), [
                'ids' => [$s1->id, $s2->id],
                'apply_nis_relevant' => '1',
                'nis_relevant_value' => '1',
                'apply_nis_criticality' => '1',
                'nis_criticality' => $criticality,
                // notes NOT applied -> must stay 'keep'
                'notes' => 'should not be written',
            ])
            ->assertRedirect(route('suppliers.index'));

        foreach ([$s1, $s2] as $supplier) {
            $fresh = $supplier->fresh();
            $this->assertTrue((bool) $fresh->nis_relevant);
            $this->assertEquals($criticality, $fresh->nis_criticality);
            $this->assertEquals('keep', $fresh->notes, 'unchecked field must be untouched');
        }
    }

    public function test_bulk_save_rejects_invalid_enum_value(): void
    {
        $s1 = Supplier::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('suppliers.bulksave'), [
                'ids' => [$s1->id],
                'apply_nis_criticality' => '1',
                'nis_criticality' => 'not-a-real-value',
            ])
            ->assertSessionHasErrors('nis_criticality');
    }
}
