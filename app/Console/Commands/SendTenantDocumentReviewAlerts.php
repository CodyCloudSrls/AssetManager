<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Support\Tenants\TenantMailNotificationService;
use Illuminate\Console\Command;

class SendTenantDocumentReviewAlerts extends Command
{
    protected $signature = 'snipeit:tenant-document-review-alerts {--with-output : Display affected tenant counts in the console}';

    protected $description = 'Send tenant-aware document review reminder digests using the central mail server.';

    public function handle(TenantMailNotificationService $service): int
    {
        $tenants = Tenant::query()->get();
        $sent = 0;

        foreach ($tenants as $tenant) {
            $count = $service->sendDocumentReviewDigest($tenant);

            if ($count > 0) {
                $sent++;

                if ($this->option('with-output')) {
                    $this->line($tenant->display_name.': '.$count.' document(s) notified');
                }
            }
        }

        $this->info('Tenant document review digests sent: '.$sent);

        return self::SUCCESS;
    }
}
