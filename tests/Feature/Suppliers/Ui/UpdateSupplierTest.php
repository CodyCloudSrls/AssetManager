<?php

namespace Tests\Feature\Suppliers\Ui;

use App\Models\Supplier;
use App\Models\User;
use Tests\TestCase;

class UpdateSupplierTest extends TestCase
{
    public function test_page_renders()
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('suppliers.edit', Supplier::factory()->create()->id))
            ->assertOk();
    }

    public function test_supplier_tax_code_can_be_updated()
    {
        $supplier = Supplier::factory()->create(['tax_code' => null]);

        $this->actingAs(User::factory()->superuser()->create())
            ->put(route('suppliers.update', $supplier), [
                'name' => $supplier->name,
                'tax_code' => 'RSSMRA80A01H501U',
                'company_id' => $supplier->company_id,
                'visibility_type' => $supplier->visibility_type,
                'phone' => $supplier->phone,
                'fax' => $supplier->fax,
                'email' => $supplier->email,
            ])
            ->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'tax_code' => 'RSSMRA80A01H501U',
        ]);
    }
}
