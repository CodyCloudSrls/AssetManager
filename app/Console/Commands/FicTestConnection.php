<?php

namespace App\Console\Commands;

use App\Support\Fic\FicClient;
use Illuminate\Console\Command;

/**
 * Verifies the Fatture in Cloud API connection using the configured token.
 * Run this in the target environment (where .env holds FIC_API_TOKEN):
 *   php artisan fic:test
 * Read-only — it only fetches the authenticated user and accessible companies.
 */
class FicTestConnection extends Command
{
    protected $signature = 'fic:test';

    protected $description = 'Verify the Fatture in Cloud API connection (read-only).';

    public function handle(FicClient $client): int
    {
        if (! $client->isConfigured()) {
            $this->error('Fatture in Cloud is not configured. Set FIC_API_TOKEN in .env.');

            return self::FAILURE;
        }

        try {
            $user = $client->userInfo();
            $this->info('✔ FiC token is valid.');
            $this->line('  User: '.($user['data']['info']['email'] ?? $user['data']['name'] ?? 'n/a'));

            $companies = $client->companies()['data']['companies'] ?? [];
            $this->line('  Accessible companies: '.count($companies));
            foreach (array_slice($companies, 0, 10) as $c) {
                $this->line('    - ['.($c['id'] ?? '?').'] '.($c['name'] ?? 'n/a'));
            }

            if (! $client->hasCompany()) {
                $this->warn('  Set FIC_COMPANY_ID in .env to the company id you want to sync.');
            }

            // Show the remaining call budget the guard just captured from the response headers.
            $budget = \App\Support\Fic\FicRateGuard::snapshot();
            $this->line('  Budget chiamate — orario: '.($budget['hourly_remaining'] ?? '?').'/1000, mensile: '.($budget['monthly_remaining'] ?? '?').'/40000');
            if ($budget['cooldown_until']) {
                $this->warn('  Cooldown attivo fino a: '.$budget['cooldown_until']);
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('✘ FiC connection failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
