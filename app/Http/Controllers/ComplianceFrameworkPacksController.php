<?php

namespace App\Http\Controllers;

use App\Models\ComplianceFrameworkPackEvent;
use App\Models\Tenant;
use App\Support\Compliance\ComplianceFrameworkInstaller;
use App\Support\Compliance\ComplianceFrameworkPackDashboard;
use App\Support\Compliance\ComplianceFrameworkPackSync;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ComplianceFrameworkPacksController extends Controller
{
    public function index(Request $request, ComplianceFrameworkPackDashboard $dashboard): View
    {
        $this->authorizeGlobalPackManagement();
        $allRows = $dashboard->packRows();
        $filters = $dashboard->filtersFromInput($request->only([
            'domain',
            'locale',
            'jurisdiction',
            'source_status',
            'system_status',
            'tenant_status',
        ]));

        return view('compliancepacks.index', [
            'packRows' => $dashboard->filterPackRows($allRows, $filters),
            'filterOptions' => $dashboard->filterOptions($allRows),
            'filters' => $filters,
            'dashboard' => $dashboard,
            'latestEvents' => $dashboard->latestEvents(null, 10),
        ]);
    }

    public function show(string $packKey, ComplianceFrameworkPackDashboard $dashboard): View
    {
        $this->authorizeGlobalPackManagement();

        $pack = $this->packOrAbort($dashboard, $packKey);

        return view('compliancepacks.show', [
            'packKey' => $packKey,
            'pack' => $pack,
            'checksum' => $dashboard->checksum($pack),
            'systemDiff' => $dashboard->systemDiff($packKey, $pack),
            'tenantRows' => $dashboard->tenantRows($packKey, $pack),
            'latestEvents' => $dashboard->latestEvents($packKey, 25),
            'dashboard' => $dashboard,
        ]);
    }

    public function applySystem(
        string $packKey,
        ComplianceFrameworkPackDashboard $dashboard,
        ComplianceFrameworkInstaller $installer,
        ComplianceFrameworkPackSync $sync
    ): RedirectResponse {
        $this->authorizeGlobalPackManagement();

        $pack = $this->packOrAbort($dashboard, $packKey);
        $before = $dashboard->systemDiff($packKey, $pack);
        $summary = $installer->installSystemPack($packKey, $pack, true, auth()->id());
        $framework = $sync->systemFramework($packKey, $pack);
        $after = $sync->diff($framework, $packKey, $pack);

        ComplianceFrameworkPackEvent::record(
            ComplianceFrameworkPackEvent::EVENT_SYSTEM_SYNC,
            ComplianceFrameworkPackEvent::SCOPE_SYSTEM,
            $packKey,
            $pack,
            [
                'document_framework_id' => $framework?->id,
                'diff_before' => $before,
                'diff_after' => $after,
                'result_summary' => $summary,
            ],
        );

        return redirect()
            ->route('settings.compliance_framework_packs.show', $packKey)
            ->with('success', trans('admin/compliancepacks/general.messages.system_applied'));
    }

    public function applyTenant(
        string $packKey,
        Tenant $tenant,
        ComplianceFrameworkPackDashboard $dashboard,
        ComplianceFrameworkInstaller $installer,
        ComplianceFrameworkPackSync $sync
    ): RedirectResponse {
        $this->authorizeGlobalPackManagement();

        $pack = $this->packOrAbort($dashboard, $packKey);
        $packLocale = $pack['locale'] ?? null;
        $tenantLocale = $installer->bootstrapLocale($tenant->defaultLocale());
        $compatiblePackKeys = $installer->availablePackKeys($tenantLocale, $tenant->defaultComplianceJurisdiction());

        if ($packLocale !== $tenantLocale) {
            return redirect()
                ->route('settings.compliance_framework_packs.show', $packKey)
                ->with('error', trans('admin/compliancepacks/general.messages.locale_mismatch'));
        }

        if (! in_array($packKey, $compatiblePackKeys, true)) {
            return redirect()
                ->route('settings.compliance_framework_packs.show', $packKey)
                ->with('error', trans('admin/compliancepacks/general.messages.jurisdiction_mismatch'));
        }

        $framework = $sync->tenantFramework($tenant, $packKey, $pack);
        $before = $sync->diff($framework, $packKey, $pack);

        if (! $dashboard->canApplyTenantDiff($before)) {
            return redirect()
                ->route('settings.compliance_framework_packs.show', $packKey)
                ->with('error', trans('admin/compliancepacks/general.messages.manual_review_required'));
        }

        if ($before['framework_missing']) {
            $summary = $installer->bootstrapTenant($tenant, $tenantLocale, [$packKey], false, auth()->id());
            $framework = $sync->tenantFramework($tenant, $packKey, $pack);
            $after = $sync->diff($framework, $packKey, $pack);
            $eventType = ComplianceFrameworkPackEvent::EVENT_TENANT_BOOTSTRAP;
        } else {
            $merge = $sync->mergeMissingRequirements($framework, $packKey, $pack, auth()->id());
            $summary = [
                'requirements_created' => $merge['requirements_created'],
                'metadata_updated' => $merge['metadata_updated'],
                'conflicts_count' => $merge['conflicts_count'],
            ];
            $framework->refresh();
            $after = $sync->diff($framework, $packKey, $pack);
            $eventType = ComplianceFrameworkPackEvent::EVENT_TENANT_SYNC;
        }

        $rootCompany = $tenant->rootCompany();
        ComplianceFrameworkPackEvent::record(
            $eventType,
            ComplianceFrameworkPackEvent::SCOPE_TENANT,
            $packKey,
            $pack,
            [
                'tenant_id' => $tenant->id,
                'company_id' => $rootCompany?->id,
                'document_framework_id' => $framework?->id,
                'diff_before' => $before,
                'diff_after' => $after,
                'result_summary' => $summary,
            ],
        );

        return redirect()
            ->route('settings.compliance_framework_packs.show', $packKey)
            ->with('success', trans('admin/compliancepacks/general.messages.tenant_applied'));
    }

    private function authorizeGlobalPackManagement(): void
    {
        abort_unless(auth()->user()?->isSuperUser(), 403);
    }

    private function packOrAbort(ComplianceFrameworkPackDashboard $dashboard, string $packKey): array
    {
        try {
            return $dashboard->packOrFail($packKey);
        } catch (InvalidArgumentException) {
            abort(404);
        }
    }
}
