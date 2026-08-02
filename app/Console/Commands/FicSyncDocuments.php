<?php

namespace App\Console\Commands;

use App\Support\Fic\FicClient;
use App\Support\Fic\FicRateGuard;
use App\Support\Fic\FicRateLimitException;
use App\Support\Fic\FicSyncService;
use App\Support\Tenants\TenantMailNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Syncs Fatture in Cloud documents into the read-only ERP mirror (fic_documents).
 * Idempotent — safe to schedule. Run in the target environment:
 *   php artisan fic:sync
 */
class FicSyncDocuments extends Command
{
    protected $signature = 'fic:sync';

    protected $description = 'Sync Fatture in Cloud documents into the read-only ERP mirror.';

    public function handle(FicClient $client, FicSyncService $sync, TenantMailNotificationService $mail): int
    {
        if (! $client->isConfigured() || ! $client->hasCompany()) {
            $this->error('FiC is not fully configured. Set FIC_API_TOKEN and FIC_COMPANY_ID in .env (verify with "php artisan fic:test").');

            return self::FAILURE;
        }

        // Skip the run entirely while a rate-limit cooldown is open — do not even open the
        // connection. This is a normal, self-healing state, not a failure.
        if (FicRateGuard::isCoolingDown()) {
            $this->info('FiC sync skipped: rate-limit cooldown active until '.FicRateGuard::cooldownUntil().'.');

            return self::SUCCESS;
        }

        try {
            $result = $sync->syncAll();
            $this->info('✔ FiC sync complete.');
            $this->line('  Issued documents:   '.$result['issued']);
            $this->line('  Received documents: '.$result['received']);

            return self::SUCCESS;
        } catch (FicRateLimitException $e) {
            // The guard tripped mid-run (near the quota): back off quietly, don't alert.
            $this->warn('FiC sync paused to protect the quota: '.$e->getMessage());

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('✘ FiC sync failed: '.$e->getMessage());

            // Always leave a grep-able ERROR in the log — the tenant e-mail alert goes to
            // MAIL_MAILER=log (i.e. nobody), so without this a failure is completely silent
            // (this is what let a dead token go unnoticed for ~10 days).
            Log::error('FiC document sync failed: '.$e->getMessage(), ['exception' => $e]);

            // Notify the tenant that owns the FiC local company (best-effort; never masks the failure).
            try {
                $tenant = $mail->tenantFromCompanyId(config('services.fic.local_company_id') ? (int) config('services.fic.local_company_id') : null);
                if ($tenant) {
                    $mail->sendFicSyncError($tenant, $e->getMessage(), now()->toDateTimeString());
                }
            } catch (\Throwable $notifyError) {
                $this->warn('  (could not send FiC sync failure notification: '.$notifyError->getMessage().')');
            }

            return self::FAILURE;
        }
    }
}
