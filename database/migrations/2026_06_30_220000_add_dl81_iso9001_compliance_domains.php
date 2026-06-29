<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the D.Lgs. 81/2008 (salute e sicurezza sul lavoro) and ISO 9001 (qualità)
 * thematic compliance domains, so the per-area framework filter and the
 * activatable nav modules can scope to them. Idempotent.
 */
return new class extends Migration
{
    private array $domains = [
        ['key' => 'dl81', 'name' => 'D.Lgs. 81/2008', 'description' => 'Salute e sicurezza sul lavoro (Testo Unico Sicurezza).', 'sort_order' => 13],
        ['key' => 'iso9001', 'name' => 'ISO 9001', 'description' => 'Sistema di gestione per la qualità (ISO 9001:2015).', 'sort_order' => 14],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('compliance_domains')) {
            return;
        }

        $now = now();

        foreach ($this->domains as $domain) {
            $payload = [
                'name' => $domain['name'],
                'description' => $domain['description'],
                'is_active' => true,
                'is_system' => true,
                'sort_order' => $domain['sort_order'],
                'updated_at' => $now,
            ];

            if (DB::table('compliance_domains')->where('key', $domain['key'])->exists()) {
                DB::table('compliance_domains')->where('key', $domain['key'])->update($payload);
            } else {
                DB::table('compliance_domains')->insert($payload + ['key' => $domain['key'], 'created_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('compliance_domains')) {
            return;
        }

        DB::table('compliance_domains')
            ->whereIn('key', ['dl81', 'iso9001'])
            ->where('is_system', true)
            ->delete();
    }
};
