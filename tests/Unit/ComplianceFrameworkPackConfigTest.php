<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ComplianceFrameworkPackConfigTest extends TestCase
{
    private function packs(): array
    {
        return include dirname(__DIR__, 2).'/config/compliance_frameworks.php';
    }

    public function test_nis2_it_allegato_bootstrap_packs_are_available(): void
    {
        $packs = $this->packs()['packs'];

        $this->assertSame(['nis2_it_allegato_1', 'nis2_it_allegato_2'], array_keys($packs));
        $this->assertCount(87, $packs['nis2_it_allegato_1']['requirements']);
        $this->assertCount(116, $packs['nis2_it_allegato_2']['requirements']);
        $this->assertSame('NIS2 IT - Allegato 1', $packs['nis2_it_allegato_1']['framework']['name']);
        $this->assertSame('NIS2 IT - Allegato 2', $packs['nis2_it_allegato_2']['framework']['name']);
    }

    public function test_unused_bootstrap_tenant_framework_purge_is_guarded(): void
    {
        $migration = $this->doc('database/migrations/2026_05_20_085733_purge_unused_bootstrap_tenant_frameworks.php');

        $this->assertStringContainsString("whereNotNull('document_frameworks.company_id')", $migration);
        $this->assertStringContainsString("whereNotNull('document_frameworks.source_pack_key')", $migration);
        $this->assertStringContainsString("whereColumn('documents.document_framework_id', 'document_frameworks.id')", $migration);
        $this->assertStringContainsString('document_framework_requirement_document', $migration);
        $this->assertStringContainsString("where('document_frameworks.is_system_template', false)", $migration);
    }

    public function test_pack_metadata_and_requirement_references_are_consistent(): void
    {
        $config = $this->packs();

        foreach ($config['packs'] as $packKey => $pack) {
            $this->assertNotEmpty($pack['pack_version'] ?? null, "{$packKey} missing pack_version");
            $this->assertNotEmpty($pack['locale'] ?? null, "{$packKey} missing locale");
            $this->assertNotEmpty($pack['framework']['slug'] ?? null, "{$packKey} missing framework slug");
            $this->assertNotEmpty($pack['framework']['compliance_domain'] ?? null, "{$packKey} missing compliance domain");
            $this->assertNotEmpty($pack['framework']['external_reference_url'] ?? null, "{$packKey} missing reference URL");
            $this->assertNotEmpty($pack['source_register_key'] ?? null, "{$packKey} missing source register key");
            $this->assertArrayHasKey(
                $pack['source_register_key'],
                $config['source_registers'],
                "{$packKey} references an unknown source register"
            );
            $this->assertNotEmpty($pack['source_register']['status'] ?? null, "{$packKey} missing source status");
            $this->assertNotEmpty($pack['source_register']['last_checked_at'] ?? null, "{$packKey} missing source check date");
            $this->assertNotEmpty($pack['source_register']['sources'] ?? [], "{$packKey} missing source URLs");

            $codes = [];
            foreach ($pack['requirements'] as $requirement) {
                $code = $requirement['code'] ?? null;
                $this->assertNotEmpty($code, "{$packKey} has a requirement without code");
                $this->assertArrayNotHasKey($code, $codes, "{$packKey} has duplicate requirement code {$code}");
                $codes[$code] = true;
                $this->assertNotEmpty($requirement['title'] ?? null, "{$packKey}:{$code} missing title");
                $this->assertNotEmpty($requirement['risk_level'] ?? null, "{$packKey}:{$code} missing risk level");
            }

            foreach ($pack['requirements'] as $requirement) {
                foreach ($this->parentCodes($requirement) as $parentCode) {
                    $this->assertArrayHasKey($parentCode, $codes, "{$packKey}:{$requirement['code']} references missing parent {$parentCode}");
                }
            }
        }
    }

    public function test_nis2_and_ai_act_requirement_risk_levels_are_valid(): void
    {
        foreach ($this->packs()['packs'] as $packKey => $pack) {
            if (! in_array($pack['framework']['compliance_domain'] ?? null, ['nis2', 'ai_act'], true)) {
                continue;
            }

            foreach ($pack['requirements'] as $requirement) {
                $this->assertContains(
                    $requirement['risk_level'] ?? null,
                    ['not_applicable', 'low', 'medium', 'high', 'critical'],
                    "{$packKey}:{$requirement['code']} has an invalid risk level"
                );
            }
        }
    }

    public function test_nis2_country_overlay_claims_are_explicit(): void
    {
        $config = $this->packs();
        $countryOverlays = $config['nis2_country_overlays'];

        $this->assertSame('implemented', $countryOverlays['IT']['status']);
        $this->assertSame('nis2_it_allegato_1', $countryOverlays['IT']['pack_key']);
        $this->assertSame(['nis2_it_allegato_1', 'nis2_it_allegato_2'], $countryOverlays['IT']['pack_keys']);
        $this->assertSame('nis2_it', $countryOverlays['IT']['source_register_key']);
        $this->assertSame('national_overlay', $config['source_registers']['nis2_it']['scope']);
    }

    public function test_nis2_country_overlay_matrix_is_complete_and_conservative(): void
    {
        $countryOverlays = $this->packs()['nis2_country_overlays'];
        $expectedJurisdictions = [
            'EU', 'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR',
            'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK',
        ];

        $this->assertSame($expectedJurisdictions, array_keys($countryOverlays));

        foreach ($countryOverlays as $countryCode => $overlay) {
            $this->assertNotEmpty($overlay['status'] ?? null, "{$countryCode} missing overlay status");
            $this->assertNotEmpty($overlay['last_checked_at'] ?? null, "{$countryCode} missing source check date");
            $this->assertNotEmpty($overlay['sources'] ?? [], "{$countryCode} missing source list");

            if ($countryCode === 'EU') {
                $this->assertSame('baseline_only', $overlay['status']);
                $this->assertNull($overlay['pack_key']);

                continue;
            }

            if ($countryCode === 'IT') {
                $this->assertSame('implemented', $overlay['status']);
                $this->assertSame(['nis2_it_allegato_1', 'nis2_it_allegato_2'], $overlay['pack_keys']);
                $this->assertSame('nis2_eu', $overlay['fallback_source_register_key']);

                continue;
            }

            $this->assertSame('review_required', $overlay['status'], "{$countryCode} must not claim a national overlay");
            $this->assertNull($overlay['pack_key'], "{$countryCode} must not point to a national pack");
            $this->assertSame('nis2_eu', $overlay['fallback_source_register_key']);
        }
    }

    public function test_source_register_dates_are_current_for_ai_act_and_nis2(): void
    {
        $config = $this->packs();

        $this->assertSame('2026-05-14', $config['source_checked_at']);

        foreach (['ai_act_eu', 'nis2_eu', 'nis2_it'] as $sourceRegisterKey) {
            $this->assertSame(
                '2026-05-14',
                $config['source_registers'][$sourceRegisterKey]['last_checked_at'] ?? null,
                "{$sourceRegisterKey} source register must record the latest review date"
            );
        }
    }

    public function test_source_register_document_covers_ai_act_and_nis2_pack_keys(): void
    {
        $config = $this->packs();
        $document = $this->doc('docs/compliance-source-register.md');

        foreach ([
            'https://eur-lex.europa.eu/eli/reg/2024/1689/oj',
            'https://digital-strategy.ec.europa.eu/en/policies/regulatory-framework-ai',
            'https://eur-lex.europa.eu/eli/dir/2022/2555/oj',
            'https://digital-strategy.ec.europa.eu/en/policies/nis-transposition',
            'https://www.gazzettaufficiale.it/eli/id/2024/10/01/24G00155/SG',
        ] as $sourceUrl) {
            $this->assertStringContainsString($sourceUrl, $document);
        }

        foreach ($config['source_registers'] as $sourceRegisterKey => $sourceRegister) {
            if (! in_array($sourceRegisterKey, ['ai_act_eu', 'nis2_eu', 'nis2_it'], true)) {
                continue;
            }

            $this->assertStringContainsString("`{$sourceRegisterKey}`", $document);
            $this->assertStringContainsString($sourceRegister['last_checked_at'], $document);
        }

        foreach ($config['packs'] as $packKey => $pack) {
            if (! in_array($pack['framework']['compliance_domain'] ?? null, ['ai_act', 'nis2'], true)) {
                continue;
            }

            $this->assertStringContainsString("`{$packKey}`", $document, "{$packKey} missing from source register document");
        }
    }

    public function test_nis2_pack_audit_document_covers_all_nis2_pack_keys(): void
    {
        $document = $this->doc('docs/nis2-pack-audit.md');

        foreach ($this->packs()['packs'] as $packKey => $pack) {
            if (($pack['framework']['compliance_domain'] ?? null) !== 'nis2') {
                continue;
            }

            $this->assertStringContainsString("`{$packKey}`", $document, "{$packKey} missing from NIS2 audit document");
            $this->assertStringContainsString($pack['pack_version'], $document, "{$packKey} pack version missing from NIS2 audit document");
            $this->assertStringContainsString((string) count($pack['requirements']), $document, "{$packKey} requirement count missing from NIS2 audit document");
        }

        $this->assertStringContainsString('D.Lgs. 138/2024', $document);
        $this->assertStringContainsString('CPV', $document);
        $this->assertStringContainsString('review_required', $document);
        $this->assertStringContainsString('NIS2 Allegato 1 and Allegato 2 are the only shipped Italian bootstrap packs', $document);
    }

    private function parentCodes(array $requirement): array
    {
        $value = $requirement['parent_requirement_codes'] ?? $requirement['parent_requirement_code'] ?? [];

        if (is_string($value)) {
            return array_values(array_filter(array_map('trim', preg_split('/[;,|]+/', $value) ?: [])));
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $value)));
    }

    private function doc(string $relativePath): string
    {
        $path = dirname(__DIR__, 2).'/'.$relativePath;

        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }
}
