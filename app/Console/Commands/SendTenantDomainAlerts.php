<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Support\Tenants\TenantMailNotificationService;
use Illuminate\Console\Command;

class SendTenantDomainAlerts extends Command
{
    protected $signature = 'snipeit:tenant-domain-alerts {--with-output : Display affected tenant counts in the console}';

    protected $description = 'Send tenant-aware domain digests (contract expiry, unpaid notule, compliance framework review) using each tenant\'s recipients, language and SMTP.';

    public function handle(TenantMailNotificationService $service): int
    {
        $digests = [
            'contract expiry' => fn (Tenant $tenant) => $service->sendContractExpiryDigest($tenant),
            'unpaid notule' => fn (Tenant $tenant) => $service->sendNotuleUnpaidDigest($tenant),
            'framework review' => fn (Tenant $tenant) => $service->sendFrameworkReviewDigest($tenant),
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

        $this->info('Tenant domain alert digests sent: '.$sent);

        return self::SUCCESS;
    }
}
