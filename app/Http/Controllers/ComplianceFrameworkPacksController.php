<?php

namespace App\Http\Controllers;

use App\Models\ComplianceFrameworkPackEvent;
use App\Models\Tenant;
use App\Support\Compliance\ComplianceFrameworkInstaller;
use App\Support\Compliance\ComplianceFrameworkPackDashboard;
use App\Support\Compliance\ComplianceFrameworkPackPurger;
use App\Support\Compliance\ComplianceFrameworkPackSync;
use App\Support\Compliance\ComplianceFrameworkPackTenantUpdater;
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
        ComplianceFrameworkPackTenantUpdater $tenantUpdater
    ): RedirectResponse {
        $this->authorizeGlobalPackManagement();

        $pack = $this->packOrAbort($dashboard, $packKey);
        $result = $tenantUpdater->applyPack($tenant, $packKey, $pack, auth()->id());

        if (($result['status'] ?? null) === 'skipped') {
            $message = match ($result['reason'] ?? null) {
                'locale_mismatch' => trans('admin/compliancepacks/general.messages.locale_mismatch'),
                'jurisdiction_mismatch' => trans('admin/compliancepacks/general.messages.jurisdiction_mismatch'),
                default => trans('admin/compliancepacks/general.messages.tenant_skipped'),
            };

            return redirect()
                ->route('settings.compliance_framework_packs.show', $packKey)
                ->with('error', $message);
        }

        if (($result['status'] ?? null) === 'manual_review') {
            return redirect()
                ->route('settings.compliance_framework_packs.show', $packKey)
                ->with('error', trans('admin/compliancepacks/general.messages.manual_review_required'));
        }

        if (($result['status'] ?? null) === 'current') {
            return redirect()
                ->route('settings.compliance_framework_packs.show', $packKey)
                ->with('success', trans('admin/compliancepacks/general.messages.tenant_current'));
        }

        return redirect()
            ->route('settings.compliance_framework_packs.show', $packKey)
            ->with('success', trans('admin/compliancepacks/general.messages.tenant_applied'));
    }

    public function purgeTenant(
        Request $request,
        string $packKey,
        Tenant $tenant,
        ComplianceFrameworkPackDashboard $dashboard,
        ComplianceFrameworkPackPurger $purger
    ): RedirectResponse {
        $this->authorizeGlobalPackManagement();

        $request->validate([
            'confirm_purge_unused_bootstrap' => 'accepted',
        ]);

        $pack = $this->packOrAbort($dashboard, $packKey);
        $result = $purger->purgeTenantPack($tenant, $packKey, $pack, auth()->id());

        if (($result['status'] ?? null) === 'purged') {
            return redirect()
                ->route('settings.compliance_framework_packs.show', $packKey)
                ->with('success', trans('admin/compliancepacks/general.messages.tenant_purged', [
                    'frameworks' => data_get($result, 'summary.frameworks_purged', 0),
                    'requirements' => data_get($result, 'summary.requirements_purged', 0),
                ]));
        }

        if (($result['status'] ?? null) === 'blocked') {
            return redirect()
                ->route('settings.compliance_framework_packs.show', $packKey)
                ->with('error', trans('admin/compliancepacks/general.messages.tenant_purge_blocked'));
        }

        return redirect()
            ->route('settings.compliance_framework_packs.show', $packKey)
            ->with('error', trans('admin/compliancepacks/general.messages.tenant_purge_skipped'));
    }

    public function applyTenantsBulk(
        Request $request,
        string $packKey,
        ComplianceFrameworkPackDashboard $dashboard,
        ComplianceFrameworkPackTenantUpdater $tenantUpdater
    ): RedirectResponse {
        $this->authorizeGlobalPackManagement();

        $request->validate([
            'tenant_ids' => 'required|array|min:1',
            'tenant_ids.*' => 'integer',
            'confirm_bulk_safe_update' => 'accepted',
        ]);

        $pack = $this->packOrAbort($dashboard, $packKey);
        $tenantIds = collect($request->input('tenant_ids', []))
            ->map(fn ($tenantId) => (int) $tenantId)
            ->filter()
            ->unique()
            ->values();

        if ($tenantIds->isEmpty()) {
            return redirect()
                ->route('settings.compliance_framework_packs.show', $packKey)
                ->with('error', trans('admin/compliancepacks/general.messages.bulk_no_tenants'));
        }

        $tenants = Tenant::query()
            ->whereIn('id', $tenantIds->all())
            ->get()
            ->keyBy('id');

        $summary = $tenantUpdater->emptySummary();

        foreach ($tenantIds as $tenantId) {
            $tenant = $tenants->get($tenantId);

            if (! $tenant) {
                $tenantUpdater->countResult($summary, [
                    'status' => 'skipped',
                    'reason' => 'missing_tenant',
                    'tenant_id' => $tenantId,
                    'pack_key' => $packKey,
                ]);

                continue;
            }

            $tenantUpdater->countResult($summary, $tenantUpdater->applyPack($tenant, $packKey, $pack, auth()->id()));
        }

        return redirect()
            ->route('settings.compliance_framework_packs.show', $packKey)
            ->with('success', trans('admin/compliancepacks/general.messages.bulk_tenant_applied', [
                'applied' => $summary['applied'],
                'current' => $summary['current'],
                'manual_review' => $summary['manual_review'],
                'skipped' => $summary['skipped'],
            ]));
    }

    private function authorizeGlobalPackManagement(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
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
