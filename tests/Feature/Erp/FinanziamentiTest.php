<?php

namespace Tests\Feature\Erp;

use App\Models\Finanziamento;
use App\Models\User;
use Tests\TestCase;

class FinanziamentiTest extends TestCase
{
    public function test_residuo_and_pagato_computation(): void
    {
        // Telematica: 427/month, 24 installments, 12 paid -> paid 5124, residuo 5124.
        $f = Finanziamento::create(['nome' => 'Telematica', 'rata_mensile' => 427, 'rate_totali' => 24, 'rate_pagate' => 12, 'stato' => 'confermato', 'company_id' => null]);

        $this->assertEqualsWithDelta(5124, $f->pagato, 0.01);
        $this->assertEqualsWithDelta(5124, $f->residuo, 0.01);
        $this->assertEqualsWithDelta(5124, Finanziamento::totalResiduo(null), 0.01);
    }

    public function test_unconfirmed_excluded_from_pfn_total(): void
    {
        Finanziamento::create(['nome' => 'Telematica', 'rata_mensile' => 100, 'rate_totali' => 10, 'rate_pagate' => 0, 'stato' => 'confermato', 'company_id' => null]);
        Finanziamento::create(['nome' => 'AideXa', 'rata_mensile' => 100, 'rate_totali' => 10, 'rate_pagate' => 0, 'stato' => 'da_confermare', 'company_id' => null]);

        $this->assertEqualsWithDelta(1000, Finanziamento::totalResiduo(null, true), 0.01);  // only confirmed
        $this->assertEqualsWithDelta(2000, Finanziamento::totalResiduo(null, false), 0.01); // all
    }

    public function test_index_renders_and_store_persists(): void
    {
        $this->actingAs(User::factory()->superuser()->create());

        $this->get(route('erp.finanziamenti.index'))->assertOk()->assertSee(trans('erp/finanziamenti.title'));
        $this->post(route('erp.finanziamenti.store'), ['nome' => 'Telematica', 'rata_mensile' => 427, 'rate_totali' => 24, 'rate_pagate' => 12, 'stato' => 'confermato'])
            ->assertRedirect(route('erp.finanziamenti.index'));
        $this->assertDatabaseHas('finanziamenti', ['nome' => 'Telematica']);
    }
}
