<?php

namespace App\Console\Commands;

use App\Support\Fic\FicCashService;
use App\Support\Fic\FicClient;
use Illuminate\Console\Command;

/**
 * Syncs the real bank/cash account balances from the FiC cashbook (conti correnti).
 * Heavier than fic:sync (it scans the whole cashbook), so it runs daily.
 *   php artisan fic:sync-cassa
 */
class FicSyncCash extends Command
{
    protected $signature = 'fic:sync-cassa';

    protected $description = 'Sync real bank/cash balances from the Fatture in Cloud cashbook.';

    public function handle(FicClient $client, FicCashService $cash): int
    {
        if (! $client->isConfigured() || ! $client->hasCompany()) {
            $this->error('FiC is not fully configured (set FIC_API_TOKEN and FIC_COMPANY_ID in .env).');

            return self::FAILURE;
        }

        try {
            $result = $cash->sync();
            $this->info('✔ Cassa sync complete.');
            $this->line('  Accounts: '.$result['accounts']);
            $this->line('  Total cash: '.number_format($result['total'], 2).' EUR');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('✘ Cassa sync failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
