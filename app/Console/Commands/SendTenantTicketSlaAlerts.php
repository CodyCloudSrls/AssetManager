<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Support\Tenants\TenantMailNotificationService;
use Illuminate\Console\Command;

class SendTenantTicketSlaAlerts extends Command
{
    protected $signature = 'snipeit:tenant-ticket-sla-alerts {--with-output : Display affected tenant counts in the console}';

    protected $description = 'Send tenant-aware ticket SLA alert digests using the central mail server.';

    public function handle(TenantMailNotificationService $service): int
    {
        $tenants = Tenant::query()->get();
        $sent = 0;

        foreach ($tenants as $tenant) {
            $count = $service->sendTicketSlaDigest($tenant);

            if ($count > 0) {
                $sent++;

                if ($this->option('with-output')) {
                    $this->line($tenant->display_name.': '.$count.' SLA ticket(s) notified');
                }
            }
        }

        $this->info('Tenant ticket SLA digests sent: '.$sent);

        return self::SUCCESS;
    }
}
