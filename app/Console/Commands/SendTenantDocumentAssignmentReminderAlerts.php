<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Support\Tenants\TenantMailNotificationService;
use Illuminate\Console\Command;

class SendTenantDocumentAssignmentReminderAlerts extends Command
{
    protected $signature = 'snipeit:tenant-document-assignment-reminders {--with-output : Display affected tenant counts in the console}';

    protected $description = 'Send tenant-aware delegated document assignment reminder and escalation digests using the central mail server.';

    public function handle(TenantMailNotificationService $service): int
    {
        $tenants = Tenant::query()->get();
        $sent = 0;

        foreach ($tenants as $tenant) {
            $count = $service->sendDocumentAssignmentReminderDigest($tenant);

            if ($count > 0) {
                $sent++;

                if ($this->option('with-output')) {
                    $this->line($tenant->display_name.': '.$count.' assignment(s) notified');
                }
            }
        }

        $this->info('Tenant delegated document assignment digests sent: '.$sent);

        return self::SUCCESS;
    }
}
