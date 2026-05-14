<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ComplianceFrameworkPackOperationsWbsTest extends TestCase
{
    public function test_operations_wbs_is_closed_with_safe_update_constraints(): void
    {
        $document = $this->repoFile('docs/compliance-framework-pack-operations-wbs.md');

        $this->assertStringContainsString('complete and closed', $document);
        $this->assertStringContainsString('tenant manager self-service', $document);
        $this->assertStringContainsString('selected-row superadmin safe update', $document);
        $this->assertStringContainsString('No background all-tenant propagation exists', $document);
        $this->assertStringNotContainsString('intentionally deferred', $document);
    }

    public function test_bulk_pack_route_is_registered_before_single_tenant_route(): void
    {
        $routes = $this->repoFile('routes/web.php');
        $bulkRoute = strpos($routes, "compliance-framework-packs/{packKey}/tenants/bulk");
        $singleTenantRoute = strpos($routes, "compliance-framework-packs/{packKey}/tenants/{tenant}");

        $this->assertIsInt($bulkRoute);
        $this->assertIsInt($singleTenantRoute);
        $this->assertLessThan($singleTenantRoute, $bulkRoute);
    }

    public function test_pack_operation_language_files_cover_safe_bulk_keys(): void
    {
        foreach (glob($this->repoPath('resources/lang/*/admin/compliancepacks/general.php')) as $file) {
            $translations = include $file;

            foreach (['bulk_apply_tenants', 'bulk_apply_help', 'bulk_select_tenant'] as $key) {
                $this->assertNotEmpty($translations[$key] ?? null, "{$file} missing {$key}");
            }

            foreach (['tenant_current', 'tenant_skipped', 'bulk_no_tenants', 'bulk_tenant_applied'] as $messageKey) {
                $this->assertNotEmpty($translations['messages'][$messageKey] ?? null, "{$file} missing messages.{$messageKey}");
            }
        }
    }

    public function test_tenant_language_files_cover_safe_self_service_keys(): void
    {
        foreach (glob($this->repoPath('resources/lang/*/admin/tenants/general.php')) as $file) {
            $translations = include $file;

            $this->assertNotEmpty($translations['settings']['bootstrap_compliance_frameworks'] ?? null, "{$file} missing bootstrap label");
            $this->assertNotEmpty($translations['settings']['bootstrap_compliance_frameworks_help'] ?? null, "{$file} missing bootstrap help");
        }

        foreach (glob($this->repoPath('resources/lang/*/admin/tenants/message.php')) as $file) {
            $translations = include $file;

            $this->assertNotEmpty($translations['settings']['bootstrap']['safe_update_success'] ?? null, "{$file} missing safe update message");
        }
    }

    private function repoPath(string $relativePath): string
    {
        return dirname(__DIR__, 2).'/'.$relativePath;
    }

    private function repoFile(string $relativePath): string
    {
        $path = $this->repoPath($relativePath);

        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }
}
