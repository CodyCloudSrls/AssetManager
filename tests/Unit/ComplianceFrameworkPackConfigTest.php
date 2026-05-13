<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ComplianceFrameworkPackConfigTest extends TestCase
{
    private function packs(): array
    {
        return include dirname(__DIR__, 2).'/config/compliance_frameworks.php';
    }

    public function test_ai_act_pack_exists_for_every_supported_locale(): void
    {
        $packs = $this->packs()['packs'];
        $supportedLocales = array_map('basename', glob(dirname(__DIR__, 2).'/resources/lang/*', GLOB_ONLYDIR));

        foreach ($supportedLocales as $locale) {
            $key = $locale === 'it-IT'
                ? 'ai_act_it'
                : ($locale === 'en-US' ? 'ai_act_en' : 'ai_act_'.str_replace('-', '_', strtolower($locale)));

            $this->assertArrayHasKey($key, $packs);
            $this->assertSame($locale, $packs[$key]['locale']);
            $this->assertSame('ai_act', $packs[$key]['framework']['compliance_domain']);
            $this->assertSame('EU', $packs[$key]['framework']['jurisdiction']);
        }
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

    public function test_nis2_and_ai_act_requirements_do_not_store_manual_risk_scores(): void
    {
        foreach ($this->packs()['packs'] as $packKey => $pack) {
            if (! in_array($pack['framework']['compliance_domain'] ?? null, ['nis2', 'ai_act'], true)) {
                continue;
            }

            foreach ($pack['requirements'] as $requirement) {
                $this->assertSame(
                    'not_applicable',
                    $requirement['risk_level'] ?? null,
                    "{$packKey}:{$requirement['code']} must not store a manual risk score"
                );
            }
        }
    }

    public function test_nis2_country_overlay_claims_are_explicit(): void
    {
        $config = $this->packs();
        $packs = $config['packs'];
        $countryOverlays = $config['nis2_country_overlays'];

        $this->assertSame('nis2_it', $packs['nis2_it']['source_register_key']);
        $this->assertSame('national_overlay', $packs['nis2_it']['source_register']['scope']);
        $this->assertSame('implemented', $countryOverlays['IT']['status']);
        $this->assertSame('nis2_it', $countryOverlays['IT']['pack_key']);

        $this->assertSame('nis2_eu', $packs['nis2_eu_it']['source_register_key']);
        $this->assertSame('eu_baseline', $packs['nis2_eu_it']['source_register']['scope']);
        $this->assertSame('it-IT', $packs['nis2_eu_it']['locale']);

        foreach ($packs as $packKey => $pack) {
            if (($pack['framework']['compliance_domain'] ?? null) !== 'nis2' || $packKey === 'nis2_it') {
                continue;
            }

            $this->assertSame('nis2_eu', $pack['source_register_key'], "{$packKey} must remain an EU baseline pack");
            $this->assertStringNotContainsString('ACN', serialize($pack), "{$packKey} must not contain Italian ACN references");
        }
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
                $this->assertSame('nis2_it', $overlay['pack_key']);

                continue;
            }

            $this->assertSame('review_required', $overlay['status'], "{$countryCode} must not claim a national overlay");
            $this->assertNull($overlay['pack_key'], "{$countryCode} must not point to a national pack");
            $this->assertSame('nis2_eu', $overlay['fallback_source_register_key']);
        }
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
}
