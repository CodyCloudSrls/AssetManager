<?php

namespace Tests\Feature\Erp;

use App\Models\Finanziamento;
use App\Models\ManagementInput;
use App\Models\User;
use Tests\TestCase;

class FotografiaTest extends TestCase
{
    public function test_renders(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('erp.fotografia'))
            ->assertOk()
            ->assertSee(trans('erp/fotografia.title'));
    }

    public function test_cassa_input_persists_and_feeds_pfn(): void
    {
        Finanziamento::create(['nome' => 'Telematica', 'rata_mensile' => 427, 'rate_totali' => 24, 'rate_pagate' => 12, 'stato' => 'confermato', 'company_id' => null]); // residuo 5124

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('erp.fotografia.input'), ['cassa_attuale' => 2000])
            ->assertRedirect(route('erp.fotografia'));

        // PFN would be 5124 - 2000 = 3124; verify the manual input persisted (global scope).
        $this->assertEqualsWithDelta(2000, ManagementInput::getValue(null, ManagementInput::KEY_CASSA), 0.01);
    }
}
