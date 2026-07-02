<?php

namespace Tests\Feature\Erp;

use App\Models\BilancioUfficiale;
use App\Models\User;
use App\Support\Bilanci\BilancioPdfExtractor;
use Tests\TestCase;

class BilancioPdfExtractorTest extends TestCase
{
    /** Condensed but faithful to the Registro Imprese "itcc" income-statement layout. */
    private string $sample = <<<'TXT'
        Conto economico micro
                                                          31-12-2022 31-12-2021
        A) Valore della produzione
          1) ricavi delle vendite e delle prestazioni          4.079            0
          Totale valore della produzione                       4.085            0
        B) Costi della produzione
          Totale costi della produzione                        3.679            0
          10) ammortamenti e svalutazioni
            Totale ammortamenti e svalutazioni                    25            0
          Totale delle imposte sul reddito dell'esercizio, correnti, differite e anticipate   112   0
        21) Utile (perdita) dell'esercizio                       294            0
        Bilancio di esercizio al 31-12-2022
        TXT;

    public function test_parses_the_conto_economico_figures(): void
    {
        $data = (new BilancioPdfExtractor)->parseText($this->sample);

        $this->assertSame(2022, $data['anno']);
        $this->assertSame(4085, $data['ricavi']);
        $this->assertSame(3679, $data['costi']);
        $this->assertNull($data['costo_personale']);   // no personnel line → absent
        $this->assertSame(25, $data['ammortamenti']);
        $this->assertSame(112, $data['imposte']);
        $this->assertSame(294, $data['utile']);
    }

    public function test_parses_personnel_and_a_loss_with_thousands_and_decimals(): void
    {
        $text = <<<'TXT'
            31-12-2023 31-12-2022
            Totale valore della produzione        1.250.000,50    900.000
            Totale costi della produzione         1.300.000,00    850.000
            Totale costi per il personale           420.000       390.000
            Totale ammortamenti e svalutazioni       15.000        12.000
            Totale delle imposte sul reddito dell'esercizio, correnti      0    5.000
            21) Utile (perdita) dell'esercizio      (50.000)      45.000
            Bilancio di esercizio al 31-12-2023
            TXT;

        $data = (new BilancioPdfExtractor)->parseText($text);

        $this->assertSame(2023, $data['anno']);
        $this->assertEqualsWithDelta(1250000.50, $data['ricavi'], 0.001);
        $this->assertSame(1300000, $data['costi']);
        $this->assertSame(420000, $data['costo_personale']);
        $this->assertSame(15000, $data['ammortamenti']);
        $this->assertSame(0, $data['imposte']);
        $this->assertSame(-50000, $data['utile']);   // (perdita) → negative
    }

    public function test_extract_route_reports_when_no_pdf_is_attached(): void
    {
        $bilancio = BilancioUfficiale::create(['anno' => 2024, 'is_deposited' => true]);

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('erp.bilanci.extract', $bilancio))
            ->assertRedirect(route('erp.bilanci.edit', $bilancio))
            ->assertSessionHas('error');
    }
}
