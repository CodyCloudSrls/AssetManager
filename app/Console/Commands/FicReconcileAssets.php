<?php

namespace App\Console\Commands;

use App\Support\Fic\FicReconcileService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 0 of the FiC → Asset reconciliation: a read-only report of how received (purchase) FiC
 * documents match Asset suppliers by Italian VAT, classifying each match as auto-linkable /
 * needs-confirmation / no-asset. Makes ZERO Fatture in Cloud API calls and writes NOTHING — it
 * exists to size the work and inform the invoice→asset mapping decision before any write phase.
 *
 *   php artisan fic:reconcile
 */
class FicReconcileAssets extends Command
{
    protected $signature = 'fic:reconcile {--apply : (non ancora disponibile) scrivere i custom field / allegare i PDF}';

    protected $description = 'Report (dry-run) del match fatture acquisto FiC → asset per P.IVA. Sola lettura, 0 chiamate FiC.';

    public function handle(FicReconcileService $service): int
    {
        // The write phase is intentionally not built yet: it depends on the invoice→asset mapping
        // rule (supplier-level vs pivot), which the team decides using THIS report.
        if ($this->option('apply')) {
            $this->error('La fase di scrittura (--apply) non è ancora implementata: attende la decisione di mapping fattura→asset. Per ora solo report.');

            return self::FAILURE;
        }

        try {
            $r = $service->report();
        } catch (\Throwable $e) {
            $this->error('✘ FiC reconcile report fallito: '.$e->getMessage());
            Log::error('FiC reconcile report failed: '.$e->getMessage(), ['exception' => $e]);

            return self::FAILURE;
        }

        $this->info('FiC → Asset — report riconciliazione (DRY-RUN · sola lettura · 0 chiamate FiC)');
        $this->line('  Documenti ricevuti (acquisti):             '.$r['received_docs']);
        $this->line('  P.IVA distinte (grezze):                   '.$r['distinct_vat_raw']);
        $this->line('  Fatture con P.IVA IT valida:               '.$r['normalizable_invoices']);
        $this->line('  Fatture la cui P.IVA matcha un fornitore:  '.$r['matched_invoices']);
        $this->newLine();
        $this->line('  <info>Auto-collegabili</info> (fornitore con 1 solo asset):   '.count($r['auto_linkable']).' P.IVA');
        $this->line('  <comment>Da confermare</comment> (fornitore con più asset):      '.count($r['candidates']).' P.IVA');
        $this->line('  Match senza asset (fornitore con 0 asset):        '.count($r['matched_no_asset']).' P.IVA');
        $this->line('  P.IVA su più fornitori (da consolidare):          '.count($r['ambiguous_supplier']).' P.IVA');

        if ($r['auto_linkable']) {
            $this->newLine();
            $this->info('Auto-collegabili (scrivibili senza ambiguità):');
            $this->table(['P.IVA', 'Fornitore', 'Asset', '#Fatture'],
                array_map(fn ($x) => [$x['vat'], $x['supplier'], $x['asset_id'], $x['invoices']], $r['auto_linkable']));
        }
        if ($r['candidates']) {
            $this->newLine();
            $this->info('Da confermare a mano (fornitore con più asset):');
            $this->table(['P.IVA', 'Fornitore', '#Asset', '#Fatture'],
                array_map(fn ($x) => [$x['vat'], $x['supplier'], $x['assets'], $x['invoices']], $r['candidates']));
        }
        if ($r['matched_no_asset']) {
            $this->newLine();
            $this->info('Match senza asset (fornitore reale, nessun cespite collegato):');
            $this->table(['P.IVA', 'Fornitore', '#Fatture'],
                array_map(fn ($x) => [$x['vat'], $x['supplier'], $x['invoices']], $r['matched_no_asset']));
        }
        if ($r['ambiguous_supplier']) {
            $this->newLine();
            $this->warn('P.IVA condivisa da più fornitori (da consolidare prima di collegare):');
            $this->table(['P.IVA', 'Fornitori', '#Fatture'],
                array_map(fn ($x) => [$x['vat'], implode(' | ', array_map(fn ($s) => '#'.$s['id'].' '.$s['name'], $x['suppliers'])), $x['invoices']], $r['ambiguous_supplier']));
        }

        return self::SUCCESS;
    }
}
