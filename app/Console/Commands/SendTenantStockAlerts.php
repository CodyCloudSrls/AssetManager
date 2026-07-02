<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Support\Tenants\TenantMailNotificationService;
use Illuminate\Console\Command;

class SendTenantStockAlerts extends Command
{
    protected $signature = 'snipeit:tenant-stock-alerts {--with-output : Display affected tenant counts in the console}';

    protected $description = 'Send tenant-aware digests of the stock asset alerts (warranty/EOL, license expiry, low inventory, expected checkin, upcoming audit) using each tenant\'s recipients, language and SMTP.';

    /**
     * Each digest is gated by its own per-tenant notification event toggle and reuses the
     * platform alert thresholds (alert_interval, alert_threshold, due_checkin_days,
     * audit_warning_days) as the default horizon.
     */
    public function handle(TenantMailNotificationService $service): int
    {
        $digests = [
            'warranty/EOL' => fn (Tenant $tenant) => $service->sendWarrantyDigest($tenant),
            'license expiry' => fn (Tenant $tenant) => $service->sendLicenseExpiryDigest($tenant),
            'low inventory' => fn (Tenant $tenant) => $service->sendInventoryLowDigest($tenant),
            'expected checkin' => fn (Tenant $tenant) => $service->sendExpectedCheckinDigest($tenant),
            'upcoming audit' => fn (Tenant $tenant) => $service->sendAuditDueDigest($tenant),
        ];

        $sent = 0;

        foreach (Tenant::query()->get() as $tenant) {
            foreach ($digests as $label => $digest) {
                $count = $digest($tenant);

                if ($count > 0) {
                    $sent++;

                    if ($this->option('with-output')) {
                        $this->line($tenant->display_name.' — '.$label.': '.$count.' item(s) notified');
                    }
                }
            }
        }

        $this->info('Tenant stock alert digests sent: '.$sent);

        return self::SUCCESS;
    }
}
