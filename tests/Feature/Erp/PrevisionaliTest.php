<?php

namespace Tests\Feature\Erp;

use App\Models\Previsionale;
use App\Models\User;
use Tests\TestCase;

class PrevisionaliTest extends TestCase
{
    public function test_expected_ebit_is_computed(): void
    {
        $p = Previsionale::create(['anno' => 2027, 'ricavi' => 150000, 'cogs' => 48000, 'opex' => 52000, 'personale' => 45000, 'company_id' => null]);

        $this->assertEqualsWithDelta(5000, $p->ebit, 0.01); // 150000 - 48000 - 52000 - 45000
        $this->assertEqualsWithDelta(102000, $p->margine_lordo, 0.01);
    }

    public function test_index_renders_and_store_persists(): void
    {
        $this->actingAs(User::factory()->superuser()->create());

        $this->get(route('erp.previsionali.index'))->assertOk()->assertSee(trans('erp/previsionali.title'));
        $this->post(route('erp.previsionali.store'), ['anno' => 2027, 'ricavi' => 150000, 'cogs' => 48000, 'opex' => 52000, 'personale' => 45000])
            ->assertRedirect(route('erp.previsionali.index'));
        $this->assertDatabaseHas('previsionali', ['anno' => 2027]);
    }
}
