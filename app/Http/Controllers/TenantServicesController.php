<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantService;
use App\Support\Exports\AcnTenantServicesXlsxExporter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TenantServicesController extends Controller
{
    public function index(Tenant $tenant, Request $request): View
    {
        abort_unless(Tenant::canCurrentUserViewTenant($tenant), 403);

        $companyIds = $tenant->activeCompanyIds();

        // Optional filter by company: a numeric id scopes to that company,
        // the literal 'tenant' scopes to tenant-wide (company_id IS NULL).
        $companyFilter = $request->input('company_id', '');
        $servicesQuery = $tenant->services()
            ->withCount([
                'documents' => fn ($query) => count($companyIds) === 0
                    ? $query->whereRaw('1 = 0')
                    : $query->withoutGlobalScopes()->whereIn('documents.company_id', $companyIds),
                'contracts' => fn ($query) => count($companyIds) === 0
                    ? $query->whereRaw('1 = 0')
                    : $query->withoutGlobalScopes()->whereIn('customer_contracts.company_id', $companyIds),
            ])
            ->with('company');

        if ($companyFilter === 'tenant') {
            $servicesQuery->whereNull('company_id');
        } elseif (is_numeric($companyFilter) && in_array((int) $companyFilter, $companyIds, true)) {
            $servicesQuery->where('company_id', (int) $companyFilter);
        } else {
            $companyFilter = '';
        }

        // Group the list by company (tenant-wide first), then macro-area, then name —
        // so a tenant like "Suez International" no longer shows everything mixed up.
        $services = $servicesQuery->get()
            ->sortBy([
                fn ($a, $b) => strcasecmp($a->company?->name ?? '', $b->company?->name ?? ''),
                fn ($a, $b) => strcasecmp($a->macro_area ?? '', $b->macro_area ?? ''),
                fn ($a, $b) => strcasecmp($a->name ?? '', $b->name ?? ''),
            ])
            ->values();

        $companyOptions = \App\Models\Company::where('tenant_id', $tenant->id)
            ->orderBy('name')->pluck('name', 'id')->all();
        $canManageTenant = $this->canManageServices($tenant);

        return view('tenantservices.index', compact('tenant', 'services', 'canManageTenant', 'companyOptions', 'companyFilter'));
    }

    public function create(Tenant $tenant): View
    {
        abort_unless($this->canManageServices($tenant), 403);

        $service = new TenantService([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        return view('tenantservices.edit', $this->formData($tenant, $service));
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless($this->canManageServices($tenant), 403);

        $validator = $this->validator($request, $tenant);
        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator);
        }

        $service = new TenantService;
        $this->fillService($service, $tenant, $request);
        $service->created_by = auth()->id();

        if (! $service->save()) {
            return redirect()->back()->withInput()->withErrors($service->getErrors());
        }

        return redirect()->route('tenants.services.index', $tenant)
            ->with('success', trans('admin/tenantservices/message.create.success'));
    }

    public function edit(Tenant $tenant, TenantService $tenantService): View
    {
        abort_unless($this->canManageServices($tenant), 403);
        $tenantService = $this->serviceForTenant($tenant, $tenantService);

        return view('tenantservices.edit', $this->formData($tenant, $tenantService));
    }

    public function update(Request $request, Tenant $tenant, TenantService $tenantService): RedirectResponse
    {
        abort_unless($this->canManageServices($tenant), 403);
        $tenantService = $this->serviceForTenant($tenant, $tenantService);

        $validator = $this->validator($request, $tenant, $tenantService);
        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator);
        }

        $this->fillService($tenantService, $tenant, $request);

        if (! $tenantService->save()) {
            return redirect()->back()->withInput()->withErrors($tenantService->getErrors());
        }

        return redirect()->route('tenants.services.index', $tenant)
            ->with('success', trans('admin/tenantservices/message.update.success'));
    }

    public function destroy(Tenant $tenant, TenantService $tenantService): RedirectResponse
    {
        abort_unless($this->canManageServices($tenant), 403);
        $tenantService = $this->serviceForTenant($tenant, $tenantService);

        if ($tenantService->documents()->exists() || $tenantService->contracts()->exists()) {
            return redirect()->route('tenants.services.index', $tenant)
                ->with('error', trans('admin/tenantservices/message.delete.linked'));
        }

        $tenantService->delete();

        return redirect()->route('tenants.services.index', $tenant)
            ->with('success', trans('admin/tenantservices/message.delete.success'));
    }

    public function exportAcn(Tenant $tenant, AcnTenantServicesXlsxExporter $exporter): BinaryFileResponse
    {
        abort_unless(Tenant::canCurrentUserViewTenant($tenant), 403);

        $path = $exporter->build($tenant);
        $filename = 'Elenco_categorizzato_TENANT'.$tenant->id.'_T'.now()->format('Ymd_His').'.xlsx';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function bulkEdit(Request $request, Tenant $tenant): View|RedirectResponse
    {
        abort_unless($this->canManageServices($tenant), 403);

        $services = $this->servicesFromRequest($request, $tenant);

        if ($services->isEmpty()) {
            return redirect()->route('tenants.services.index', $tenant)
                ->with('error', trans('admin/tenantservices/message.bulk.nothing_selected'));
        }

        return view('tenantservices.bulk-edit', [
            'tenant' => $tenant,
            'services' => $services,
            'impactOptions' => TenantService::impactOptions(),
        ]);
    }

    public function bulkUpdate(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless($this->canManageServices($tenant), 403);

        $services = $this->servicesFromRequest($request, $tenant);

        if ($services->isEmpty()) {
            return redirect()->route('tenants.services.index', $tenant)
                ->with('error', trans('admin/tenantservices/message.bulk.nothing_selected'));
        }

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'apply_relevance_override' => 'nullable|boolean',
            'relevance_override' => ['nullable', Rule::in(TenantService::impactKeys())],
            'apply_is_active' => 'nullable|boolean',
            'is_active_state' => ['nullable', Rule::in(['0', '1'])],
        ]);

        $validator->after(function ($validator) use ($request) {
            if (! $this->bulkHasSelectedFields($request)) {
                $validator->errors()->add('bulk_actions', trans('admin/hardware/message.update.nothing_updated'));
            }

            if ($request->boolean('apply_is_active') && ! $request->filled('is_active_state')) {
                $validator->errors()->add('is_active_state', trans('validation.required', ['attribute' => trans('general.status')]));
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator);
        }

        $updates = [];

        if ($request->boolean('apply_relevance_override')) {
            $updates['relevance_override'] = $request->filled('relevance_override') ? $request->input('relevance_override') : null;
        }

        if ($request->boolean('apply_is_active')) {
            $updates['is_active'] = $request->input('is_active_state') === '1';
        }

        DB::transaction(function () use ($services, $updates) {
            foreach ($services as $service) {
                $service->fill($updates);

                if (! $service->save()) {
                    $service->throwValidationException();
                }
            }
        });

        return redirect()->route('tenants.services.index', $tenant)
            ->with('success', trans('admin/tenantservices/message.bulk.success'));
    }

    private function servicesFromRequest(Request $request, Tenant $tenant)
    {
        $ids = collect($request->input('ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return collect();
        }

        return TenantService::query()
            ->whereIn('id', $ids)
            ->where('tenant_id', $tenant->id)
            ->orderBy('macro_area')
            ->orderBy('name')
            ->get();
    }

    private function bulkHasSelectedFields(Request $request): bool
    {
        return $request->boolean('apply_relevance_override')
            || $request->boolean('apply_is_active');
    }

    private function formData(Tenant $tenant, TenantService $service): array
    {
        return [
            'tenant' => $tenant,
            'service' => $service,
            'item' => $service,
            'macroAreaOptions' => TenantService::macroAreaOptions($service->macro_area),
            'impactOptions' => TenantService::impactOptions(),
            'companyOptions' => \App\Models\Company::where('tenant_id', $tenant->id)->orderBy('name')->pluck('name', 'id')->all(),
        ];
    }

    private function validator(Request $request, Tenant $tenant, ?TenantService $service = null)
    {
        $macroAreaKeys = TenantService::selectableMacroAreaKeys();
        if ($service?->macro_area && in_array($service->macro_area, TenantService::macroAreaKeys(), true)) {
            $macroAreaKeys[] = $service->macro_area;
            $macroAreaKeys = array_values(array_unique($macroAreaKeys));
        }

        $tenantCompanyIds = \App\Models\Company::where('tenant_id', $tenant->id)->pluck('id')->all();

        return Validator::make($request->all(), [
            'macro_area' => ['required', 'string', Rule::in($macroAreaKeys)],
            'company_id' => ['nullable', Rule::in($tenantCompanyIds)],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tenant_services', 'name')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenant->id)
                        ->where('macro_area', $request->input('macro_area'))
                        ->whereNull('deleted_at'))
                    ->ignore($service?->id),
            ],
            'description' => 'nullable|string|max:65535',
            'acn_subject_basis' => 'nullable|string|max:65535',
            'relevance_override' => ['nullable', 'string', Rule::in(TenantService::impactKeys())],
            'is_active' => 'nullable|boolean',
        ]);
    }

    private function fillService(TenantService $service, Tenant $tenant, Request $request): void
    {
        $service->tenant_id = $tenant->id;
        $service->company_id = $request->filled('company_id') ? (int) $request->input('company_id') : null;
        $service->macro_area = $request->input('macro_area');
        $service->name = trim((string) $request->input('name'));
        $service->description = $request->input('description');
        $service->acn_subject_basis = $request->input('acn_subject_basis');
        $service->relevance_override = $request->input('relevance_override');
        $service->is_active = $request->boolean('is_active');
    }

    private function serviceForTenant(Tenant $tenant, TenantService $service): TenantService
    {
        abort_unless((int) $service->tenant_id === (int) $tenant->id, 404);

        return $service;
    }

    /**
     * Tenant services can be managed either by a tenant admin (existing behaviour)
     * or by a user holding the global `tenants.services.manage` granular permission,
     * but only for tenants they can already reach (tenant isolation preserved).
     */
    private function canManageServices(Tenant $tenant): bool
    {
        $user = auth()->user();

        return $user->canManageTenant($tenant)
            || (Tenant::canCurrentUserViewTenant($tenant) && $user->hasAccess('tenants.services.manage'));
    }
}
