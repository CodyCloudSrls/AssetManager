<?php

namespace Tests\Feature\Erp;

use App\Models\Notula;
use App\Models\User;
use Tests\TestCase;

class NotuleTest extends TestCase
{
    public function test_only_pending_notule_are_accruable(): void
    {
        Notula::create(['professional_name' => 'Avv. Rossi', 'amount' => 1000, 'status' => Notula::STATUS_PENDING]);
        Notula::create(['professional_name' => 'Dott. Bianchi', 'amount' => 500, 'status' => Notula::STATUS_INVOICED]);

        // Invoiced notula drop out of the accrual (the real FiC invoice carries the cost).
        $this->assertEqualsWithDelta(1000, (float) Notula::accruable()->sum('amount'), 0.01);
    }

    public function test_index_renders_and_lists_notule(): void
    {
        Notula::create(['professional_name' => 'Avv. Verdi', 'amount' => 1200, 'status' => Notula::STATUS_PENDING]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('erp.notule.index'))
            ->assertOk()
            ->assertSee('Avv. Verdi');
    }

    public function test_store_creates_a_notula(): void
    {
        $this->actingAs(User::factory()->superuser()->create());

        $this->post(route('erp.notule.store'), [
            'professional_name' => 'Geom. Neri',
            'amount' => 800,
            'status' => Notula::STATUS_PENDING,
        ])->assertRedirect(route('erp.notule.index'));

        $this->assertDatabaseHas('notule', ['professional_name' => 'Geom. Neri']);
    }

    public function test_paid_amount_persists_and_residuo_is_computed(): void
    {
        $this->actingAs(User::factory()->superuser()->create());

        $this->post(route('erp.notule.store'), [
            'professional_name' => 'Dott. Verdi', 'amount' => 1000, 'paid_amount' => 400, 'status' => Notula::STATUS_PENDING,
        ])->assertRedirect(route('erp.notule.index'));

        $n = Notula::where('professional_name', 'Dott. Verdi')->firstOrFail();
        $this->assertEqualsWithDelta(400, (float) $n->paid_amount, 0.01);
        $this->assertEqualsWithDelta(600, $n->residuo, 0.01);
        $this->assertEqualsWithDelta(600, Notula::outstandingTotal(null), 0.01); // residuo, not full amount
    }

    public function test_paid_amount_cannot_exceed_amount(): void
    {
        $this->actingAs(User::factory()->superuser()->create());

        $this->from(route('erp.notule.create'))
            ->post(route('erp.notule.store'), ['professional_name' => 'X', 'amount' => 100, 'paid_amount' => 250, 'status' => Notula::STATUS_PENDING])
            ->assertSessionHasErrors('paid_amount');
    }

    public function test_professional_or_supplier_required(): void
    {
        $this->actingAs(User::factory()->superuser()->create());

        $this->from(route('erp.notule.create'))
            ->post(route('erp.notule.store'), ['amount' => 100, 'status' => Notula::STATUS_PENDING])
            ->assertSessionHasErrors('professional_name');
    }
}
