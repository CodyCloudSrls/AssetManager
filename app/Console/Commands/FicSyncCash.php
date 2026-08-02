<?php

namespace App\Console\Commands;

use App\Support\Fic\FicCashService;
use App\Support\Fic\FicClient;
use App\Support\Fic\FicRateGuard;
use App\Support\Fic\FicRateLimitException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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

        if (FicRateGuard::isCoolingDown()) {
            $this->info('Cassa sync skipped: rate-limit cooldown active until '.FicRateGuard::cooldownUntil().'.');

            return self::SUCCESS;
        }

        try {
            $result = $cash->sync();
            $this->info('✔ Cassa sync complete.');
            $this->line('  Accounts: '.$result['accounts']);
            $this->line('  Total cash: '.number_format($result['total'], 2).' EUR');

            return self::SUCCESS;
        } catch (FicRateLimitException $e) {
            $this->warn('Cassa sync paused to protect the quota: '.$e->getMessage());

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('✘ Cassa sync failed: '.$e->getMessage());
            Log::error('FiC cassa sync failed: '.$e->getMessage(), ['exception' => $e]);

            return self::FAILURE;
        }
    }
}
