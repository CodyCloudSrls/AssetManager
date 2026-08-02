<?php

namespace App\Console;

use App\Models\Setting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        if (Setting::getSettings()?->alerts_enabled === 1) {
            $schedule->command('snipeit:inventory-alerts')->daily();
            $schedule->command('snipeit:expiring-alerts')->daily();
            $schedule->command('snipeit:expected-checkin')->daily();
            $schedule->command('snipeit:upcoming-audits')->daily();
        }
        $schedule->command('snipeit:tenant-ticket-sla-alerts')->daily();
        $schedule->command('snipeit:tenant-document-review-alerts')->daily();
        $schedule->command('snipeit:tenant-document-assignment-reminders')->daily();
        $schedule->command('snipeit:tenant-asset-renewal-alerts')->daily();
        $schedule->command('snipeit:tenant-stock-alerts')->daily();
        $schedule->command('snipeit:tenant-domain-alerts')->daily();
        $schedule->command('snipeit:backup')->weekly();
        $schedule->command('backup:clean')->daily();
        $schedule->command('auth:clear-resets')->everyFifteenMinutes();
        $schedule->command('saml:clear_expired_nonces')->weekly();

        // ERP: keep the read-only Fatture in Cloud mirror fresh (no-op if FiC unconfigured).
        // Hourly, not every 10 min: one full re-sync is ~27 API calls (the received-documents
        // page count dominates), so every-10-min = ~3,888/day ≈ 117k/month, ~3x FiC's 40,000
        // monthly cap. Hourly = ~648/day ≈ 19k/month, comfortably under; FicRateGuard is the
        // hard backstop. Documents are entered manually in FiC, so hourly freshness is ample.
        $schedule->command('fic:sync')->hourly()->withoutOverlapping();
        // Real bank/cash balances from the cashbook — heavier, so once a day.
        $schedule->command('fic:sync-cassa')->dailyAt('02:30')->withoutOverlapping();
    }

    /**
     * This method is required by Laravel to handle any console routes
     * that are defined in routes/console.php.
     */
    protected function commands()
    {
        require base_path('routes/console.php');
        $this->load(__DIR__.'/Commands');
    }
}
