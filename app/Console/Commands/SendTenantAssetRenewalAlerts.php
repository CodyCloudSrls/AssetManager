<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Support\Tenants\TenantMailNotificationService;
use Illuminate\Console\Command;

class SendTenantAssetRenewalAlerts extends Command
{
    protected $signature = 'snipeit:tenant-asset-renewal-alerts {--days=30 : Renewal horizon in days} {--with-output : Display affected tenant counts in the console}';

    protected $description = 'Send tenant-aware asset renewal/expiry reminder digests using the central mail server.';

    public function handle(TenantMailNotificationService $service): int
    {
        $days = max(1, (int) $this->option('days'));
        $tenants = Tenant::query()->get();
        $sent = 0;

        foreach ($tenants as $tenant) {
            $count = $service->sendAssetRenewalDigest($tenant, $days);

            if ($count > 0) {
                $sent++;

                if ($this->option('with-output')) {
                    $this->line($tenant->display_name.': '.$count.' asset(s) notified');
                }
            }
        }

        $this->info('Tenant asset renewal digests sent: '.$sent);

        return self::SUCCESS;
    }
}
