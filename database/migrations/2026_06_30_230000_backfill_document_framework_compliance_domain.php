<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enriches the compliance_domain on pre-existing document frameworks so they appear
 * under the new per-area compliance menus. NON-DESTRUCTIVE: only fills rows whose
 * compliance_domain is currently NULL/empty — it never overwrites a domain already
 * set, and it touches no requirements, documents or other tenant content. Existing
 * tenant data (e.g. CodyCloud's NIS2) keeps all its records; it only gains its
 * categorisation so it shows in the right place.
 *
 * Mapping order: (1) source_pack_key → its catalog domain (authoritative for
 * bootstrap-derived frameworks), then (2) high-confidence name patterns for the
 * older manually-created frameworks. Unrecognised names are left NULL on purpose.
 */
return new class extends Migration
{
    /** Authoritative pack_key → domain (matches ComplianceFrameworkPackCatalog). */
    private array $packDomains = [
        'nis2_it_allegato_1' => 'nis2',
        'nis2_it_allegato_2' => 'nis2',
        'dl81_it' => 'dl81',
        'iso27001_it' => 'iso27001',
        'iso9001_it' => 'iso9001',
        'gdpr_eu' => 'gdpr',
        'gdpr_it' => 'gdpr',
        'ai_act_it' => 'ai_act',
        'ai_act_eu' => 'ai_act',
    ];

    /** High-confidence name fragment → domain, for legacy manual frameworks. */
    private array $namePatterns = [
        'nis2' => 'nis2',
        'nis 2' => 'nis2',
        'gdpr' => 'gdpr',
        '2016/679' => 'gdpr',
        '81/2008' => 'dl81',
        'd.lgs. 81' => 'dl81',
        'dlgs 81' => 'dl81',
        'sicurezza sul lavoro' => 'dl81',
        'ai act' => 'ai_act',
        'ai-act' => 'ai_act',
        '1689' => 'ai_act',
        '27001' => 'iso27001',
        '9001' => 'iso9001',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('document_frameworks') || ! Schema::hasColumn('document_frameworks', 'compliance_domain')) {
            return;
        }

        $unset = fn ($query) => $query->where(function ($q) {
            $q->whereNull('compliance_domain')->orWhere('compliance_domain', '');
        });

        // (1) Authoritative: derive from the originating pack key.
        foreach ($this->packDomains as $packKey => $domain) {
            $unset(DB::table('document_frameworks')->where('source_pack_key', $packKey))
                ->update(['compliance_domain' => $domain]);
        }

        // (2) Legacy manual frameworks: fill NULL/empty domains from the name.
        foreach ($this->namePatterns as $fragment => $domain) {
            $unset(DB::table('document_frameworks')->whereRaw('LOWER(name) LIKE ?', ['%'.$fragment.'%']))
                ->update(['compliance_domain' => $domain]);
        }
    }

    public function down(): void
    {
        // Non-reversible by design: this only enriched previously-empty categorisation
        // fields. Clearing them again would not restore meaningful prior state, and we
        // must never remove a domain an operator may have set since. No-op.
    }
};
