<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FrancescaTeamsIssuesTest extends TestCase
{
    public function test_document_framework_global_template_validation_is_scoped_to_platform_superadmins(): void
    {
        $request = $this->repoFile('app/Http/Requests/StoreDocumentFrameworkRequest.php');
        $controller = $this->repoFile('app/Http/Controllers/DocumentFrameworksController.php');
        $editView = $this->repoFile('resources/views/documentframeworks/edit.blade.php');
        $importView = $this->repoFile('resources/views/documentframeworks/import.blade.php');

        $this->assertStringContainsString("visibility_type') !== DocumentFramework::VISIBILITY_GLOBAL", $request);
        $this->assertStringContainsString('Tenant::canCurrentUserUseGlobalTenantContext()', $request);
        $this->assertStringContainsString('private function visibilityOptions()', $controller);
        $this->assertStringContainsString('unset($options[DocumentFramework::VISIBILITY_GLOBAL])', $controller);
        $this->assertStringContainsString("withErrors(['visibility_type' => trans('validation.in'", $controller);
        $this->assertStringContainsString("withErrors(['company_id' => trans('validation.required'", $controller);
        $this->assertStringContainsString("'visibilityOptions' => \$visibilityOptions", $editView);
        $this->assertStringContainsString("'visibilityOptions' => \$visibilityOptions", $importView);
    }

    public function test_users_cannot_delete_themselves_from_web_api_or_table_actions(): void
    {
        $model = $this->repoFile('app/Models/User.php');
        $webController = $this->repoFile('app/Http/Controllers/Users/UsersController.php');
        $apiController = $this->repoFile('app/Http/Controllers/Api/UsersController.php');

        $this->assertStringContainsString('auth()->id() !== (int) $this->id', $model);
        $this->assertStringContainsString('auth()->id() === (int) $user->id', $webController);
        $this->assertStringContainsString("trans('admin/users/message.error.cannot_delete_yourself')", $webController);
        $this->assertStringContainsString('auth()->id() === (int) $user->id', $apiController);
        $this->assertStringContainsString("trans('admin/users/message.error.cannot_delete_yourself')", $apiController);
    }

    public function test_user_groups_multi_select_uses_select2_tokens(): void
    {
        $view = $this->repoFile('resources/views/users/edit.blade.php');

        $this->assertStringContainsString('name="groups[]"', $view);
        $this->assertStringContainsString('id="groups"', $view);
        $this->assertStringContainsString('class="form-control select2"', $view);
        $this->assertStringContainsString('style="width: 100%;"', $view);
    }

    public function test_nis2_allegato1_repair_migration_is_targeted_to_pack_generated_frameworks(): void
    {
        $migration = $this->repoFile('database/migrations/2026_05_22_101500_repair_nis2_allegato1_requirement_code.php');

        $this->assertStringContainsString("where('source_pack_key', 'nis2_it_allegato_1')", $migration);
        $this->assertStringContainsString("firstWhere('code', 'ID.RA-08 punto 5')", $migration);
        $this->assertStringContainsString("where('code', 'ID.RA-08 punto 4')", $migration);
        $this->assertStringContainsString("where('title', 'Approvazione del piano di gestione delle vulnerabilità')", $migration);
        $this->assertStringContainsString("'code' => \$expected['code'] ?? 'ID.RA-08 punto 5'", $migration);
        $this->assertStringContainsString("\$updates['default_document_type_id'] = \$documentTypeId", $migration);
    }

    public function test_document_type_templates_are_global_defaults_and_scoped_in_forms(): void
    {
        $request = $this->repoFile('app/Http/Requests/StoreDocumentTypeRequest.php');
        $controller = $this->repoFile('app/Http/Controllers/DocumentTypesController.php');
        $apiController = $this->repoFile('app/Http/Controllers/Api/DocumentTypesController.php');
        $editView = $this->repoFile('resources/views/documenttypes/edit.blade.php');
        $installer = $this->repoFile('app/Support/Compliance/ComplianceFrameworkInstaller.php');
        $packSync = $this->repoFile('app/Support/Compliance/ComplianceFrameworkPackSync.php');
        $migration = $this->repoFile('database/migrations/2026_05_22_090628_seed_global_document_types_and_repair_nis_allegato1_types.php');

        $this->assertStringContainsString("visibility_type') !== DocumentType::VISIBILITY_GLOBAL", $request);
        $this->assertStringContainsString('Tenant::canCurrentUserUseGlobalTenantContext()', $request);
        $this->assertStringContainsString('private function visibilityOptions()', $controller);
        $this->assertStringContainsString('unset($options[DocumentType::VISIBILITY_GLOBAL])', $controller);
        $this->assertStringContainsString('$documentType->fill($request->validated())', $controller);
        $this->assertStringContainsString('$documentType->fill($request->validated())', $apiController);
        $this->assertStringContainsString("'visibilityOptions' => \$visibilityOptions", $editView);
        $this->assertStringContainsString('ensureGlobalDocumentTypesForRequirements', $installer);
        $this->assertStringContainsString('ensureGlobalDocumentTypesForRequirements', $packSync);
        $this->assertStringContainsString('$attributes[\'default_document_type_id\'] = $this->documentTypeIdForRequirement', $packSync);
        $this->assertStringContainsString("where('source_pack_key', self::PACK_KEY)", $migration);
        $this->assertStringContainsString("'visibility_type' => 'global'", $migration);
        $this->assertStringContainsString('repairExistingDocumentTypesFromPrimaryRequirements', $migration);
    }

    public function test_tenant_visibility_permission_does_not_treat_denies_as_superuser_grants(): void
    {
        $company = $this->repoFile('app/Models/Company.php');
        $tenant = $this->repoFile('app/Models/Tenant.php');
        $permissions = $this->repoFile('config/permissions.php');
        $preserveAction = $this->repoFile('app/Actions/Permissions/PreserveUnauthorizedPrivilegedPermissionsAction.php');
        $permissionView = $this->repoFile('resources/views/partials/forms/edit/permissions-base.blade.php');
        $migration = $this->repoFile('database/migrations/2026_05_22_112000_add_view_all_tenants_permission.php');

        $this->assertStringContainsString("'is_superadmin' => false", $company);
        $this->assertStringContainsString("'can_view_all_tenants' => false", $company);
        $this->assertStringContainsString("currentAuthUserHasPermission(\$user, 'superadmin')", $company);
        $this->assertStringContainsString("\$canViewAllTenants = \$isSuperadmin && self::currentAuthUserHasPermission(\$user, 'tenants.view_all')", $company);
        $this->assertStringContainsString("return (string) \$value === '1';", $company);
        $this->assertStringContainsString("return (string) \$value === '-1';", $company);
        $this->assertStringContainsString("->when(! is_null(\$rootTenantId)", $company);
        $this->assertStringContainsString("(int) (\$currentCompany->tenant_id ?? 0) !== \$rootTenantId", $company);
        $this->assertStringContainsString('canCurrentUserUseGlobalTenantContext', $tenant);
        $this->assertStringContainsString("\$authContext['can_view_all_tenants']", $tenant);
        $this->assertStringContainsString("\$authContext['is_superadmin']", $tenant);
        $this->assertStringContainsString('currentUserCompanyTenantId', $tenant);
        $this->assertStringContainsString("'permission' => 'superadmin'", $permissions);
        $this->assertStringContainsString("'permission' => 'tenants.view_all'", $permissions);
        $this->assertStringContainsString("'tenants.view_all'", $preserveAction);
        $this->assertStringContainsString("['superadmin', 'tenants.view_all']", $permissionView);
        $this->assertStringContainsString("private const PERMISSION = 'tenants.view_all';", $migration);
    }

    public function test_tenant_superuser_ui_remains_explicit_and_platform_superadmins_stay_listable(): void
    {
        $permissionsJs = $this->repoFile('resources/assets/js/snipeit.js');
        $apiUsers = $this->repoFile('app/Http/Controllers/Api/UsersController.php');
        $usersTransformer = $this->repoFile('app/Http/Transformers/UsersTransformer.php');
        $snipePolicy = $this->repoFile('app/Policies/SnipePermissionsPolicy.php');

        $this->assertStringContainsString('$("#superadmin_allow").is', $permissionsJs);
        $this->assertStringContainsString('$(".superadmin").change', $permissionsJs);
        $this->assertStringNotContainsString('$("#superuser_allow").is', $permissionsJs);
        $this->assertStringNotContainsString('$(".superuser").change', $permissionsJs);
        $this->assertStringContainsString("if (\$request->boolean('exclude_superusers'))", $apiUsers);
        $this->assertStringNotContainsString("if (! \$authenticatedUser->isSuperAdmin())", $apiUsers);
        $this->assertStringNotContainsString("|| (! \$authenticatedUser->isSuperAdmin())", $apiUsers);
        $this->assertStringContainsString("\$readOnlyUserAbilities = ['view', 'history', 'journal']", $snipePolicy);
        $this->assertStringContainsString("Gate::allows('update', \$user)", $usersTransformer);
    }

    public function test_nis_reports_have_granular_permissions_without_unlocking_all_reports(): void
    {
        $permissions = $this->repoFile('config/permissions.php');
        $authProvider = $this->repoFile('app/Providers/AuthServiceProvider.php');
        $reportsController = $this->repoFile('app/Http/Controllers/ReportsController.php');
        $layout = $this->repoFile('resources/views/layouts/default.blade.php');
        $tenant = $this->repoFile('app/Models/Tenant.php');

        $this->assertStringContainsString("'permission' => 'reports.nis_risk_matrix.view'", $permissions);
        $this->assertStringContainsString("'permission' => 'reports.nis_real_coverage.view'", $permissions);
        $this->assertStringContainsString("Gate::define('reports.nis_risk_matrix.view'", $authProvider);
        $this->assertStringContainsString("Gate::define('reports.nis_real_coverage.view'", $authProvider);
        $this->assertStringContainsString("\$this->authorize('reports.nis_risk_matrix.view')", $reportsController);
        $this->assertStringContainsString("\$this->authorize('reports.nis_real_coverage.view')", $reportsController);
        $this->assertStringContainsString("@canany(['reports.view', 'reports.nis_risk_matrix.view', 'reports.nis_real_coverage.view'])", $layout);
        $this->assertStringContainsString("@can('reports.nis_risk_matrix.view')", $layout);
        $this->assertStringContainsString("@can('reports.nis_real_coverage.view')", $layout);
        $this->assertStringContainsString("'reports.view', 'reports.nis_risk_matrix.view', 'reports.nis_real_coverage.view'", $tenant);
    }

    public function test_document_file_view_permission_is_separate_from_file_management(): void
    {
        $permissions = $this->repoFile('config/permissions.php');
        $policy = $this->repoFile('app/Policies/SnipePermissionsPolicy.php');
        $webController = $this->repoFile('app/Http/Controllers/UploadedFilesController.php');
        $apiController = $this->repoFile('app/Http/Controllers/Api/UploadedFilesController.php');
        $filesTab = $this->repoFile('resources/views/blade/tabs/files-tab.blade.php');
        $filesTable = $this->repoFile('resources/views/blade/table/files.blade.php');
        $transformer = $this->repoFile('app/Http/Transformers/UploadedFilesTransformer.php');

        $this->assertStringContainsString("'permission' => 'documents.files.view'", $permissions);
        $this->assertStringContainsString('public function viewFiles(User $user, $item = null)', $policy);
        $this->assertStringContainsString("\$this->columnName().'.files.view'", $policy);
        $this->assertStringContainsString("\$this->authorize('viewFiles', \$object)", $webController);
        $this->assertStringContainsString("\$this->authorize('viewFiles', \$object)", $apiController);
        $this->assertStringContainsString("@can('viewFiles', \$item)", $filesTab);
        $this->assertStringContainsString("@can('viewFiles', \$object)", $filesTable);
        $this->assertStringContainsString("Gate::allows('files', \$file->item)", $transformer);
    }

    public function test_unused_bootstrap_framework_cleanup_is_available_and_guarded(): void
    {
        $purger = $this->repoFile('app/Support/Compliance/ComplianceFrameworkPackPurger.php');
        $dashboard = $this->repoFile('app/Support/Compliance/ComplianceFrameworkPackDashboard.php');
        $packController = $this->repoFile('app/Http/Controllers/ComplianceFrameworkPacksController.php');
        $frameworkController = $this->repoFile('app/Http/Controllers/DocumentFrameworksController.php');
        $routes = $this->repoFile('routes/web.php');
        $packView = $this->repoFile('resources/views/compliancepacks/show.blade.php');
        $frameworkView = $this->repoFile('resources/views/documentframeworks/view.blade.php');
        $event = $this->repoFile('app/Models/ComplianceFrameworkPackEvent.php');
        $itPackTranslations = $this->repoFile('resources/lang/it-IT/admin/compliancepacks/general.php');
        $itFrameworkMessages = $this->repoFile('resources/lang/it-IT/admin/documentframeworks/message.php');

        $this->assertStringContainsString('class ComplianceFrameworkPackPurger', $purger);
        $this->assertStringContainsString("where('is_system_template', false)", $purger);
        $this->assertStringContainsString("where('source_pack_key', \$packKey)", $purger);
        $this->assertStringContainsString("where('document_framework_id', \$framework->id)", $purger);
        $this->assertStringContainsString('document_framework_requirement_document', $purger);
        $this->assertStringContainsString('forceDelete()', $purger);
        $this->assertStringContainsString('EVENT_TENANT_PURGE', $purger);
        $this->assertStringContainsString("config('compliance_frameworks.packs', [])", $purger);
        $this->assertStringContainsString("\$row['can_apply'] || \$row['can_purge']", $dashboard);
        $this->assertStringContainsString('public const EVENT_TENANT_PURGE', $event);
        $this->assertStringContainsString("\$context['actor_id'] ?? auth()->id()", $event);

        $this->assertStringContainsString('purgeTenant(', $packController);
        $this->assertStringContainsString('confirm_purge_unused_bootstrap', $packController);
        $this->assertStringContainsString('purgeUnusedBootstrap(', $frameworkController);
        $this->assertStringContainsString('canPurgeUnusedBootstrap', $frameworkController);
        $this->assertStringContainsString('purge-unused-bootstrap', $routes);
        $this->assertStringContainsString('settings.compliance_framework_packs.tenants.purge_unused_bootstrap', $routes);
        $this->assertStringContainsString('documentframeworks.purge-unused-bootstrap', $routes);
        $this->assertStringContainsString('purge_unused_bootstrap', $packView);
        $this->assertStringContainsString('canPurgeUnusedBootstrap', $frameworkView);
        $this->assertStringContainsString('purge-unused-bootstrap', $frameworkView);
        $this->assertStringContainsString('tenant_purge', $itPackTranslations);
        $this->assertStringContainsString('purge_unused_bootstrap', $itFrameworkMessages);
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
