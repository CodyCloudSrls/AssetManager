<?php

namespace App\Console\Commands;

use App\Support\Fic\FicClient;
use App\Support\Fic\FicSyncService;
use App\Support\Tenants\TenantMailNotificationService;
use Illuminate\Console\Command;

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

        try {
            $result = $sync->syncAll();
            $this->info('✔ FiC sync complete.');
            $this->line('  Issued documents:   '.$result['issued']);
            $this->line('  Received documents: '.$result['received']);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('✘ FiC sync failed: '.$e->getMessage());

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
