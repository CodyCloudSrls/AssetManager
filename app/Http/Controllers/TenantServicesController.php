<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantService;
use App\Support\Exports\AcnTenantServicesXlsxExporter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TenantServicesController extends Controller
{
    public function index(Tenant $tenant): View
    {
        abort_unless(Tenant::canCurrentUserViewTenant($tenant), 403);

        $services = $tenant->services()
            ->withCount(['documents', 'contracts'])
            ->orderBy('macro_area')
            ->orderBy('name')
            ->get();
        $canManageTenant = auth()->user()->canManageTenant($tenant);

        return view('tenantservices.index', compact('tenant', 'services', 'canManageTenant'));
    }

    public function create(Tenant $tenant): View
    {
        abort_unless(auth()->user()->canManageTenant($tenant), 403);

        $service = new TenantService([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        return view('tenantservices.edit', $this->formData($tenant, $service));
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless(auth()->user()->canManageTenant($tenant), 403);

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
        abort_unless(auth()->user()->canManageTenant($tenant), 403);
        $tenantService = $this->serviceForTenant($tenant, $tenantService);

        return view('tenantservices.edit', $this->formData($tenant, $tenantService));
    }

    public function update(Request $request, Tenant $tenant, TenantService $tenantService): RedirectResponse
    {
        abort_unless(auth()->user()->canManageTenant($tenant), 403);
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
        abort_unless(auth()->user()->canManageTenant($tenant), 403);
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

    private function formData(Tenant $tenant, TenantService $service): array
    {
        return [
            'tenant' => $tenant,
            'service' => $service,
            'item' => $service,
            'macroAreaOptions' => TenantService::macroAreaOptions(),
            'impactOptions' => TenantService::impactOptions(),
        ];
    }

    private function validator(Request $request, Tenant $tenant, ?TenantService $service = null)
    {
        return Validator::make($request->all(), [
            'macro_area' => ['required', 'string', Rule::in(TenantService::macroAreaKeys())],
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
            'relevance_override' => ['nullable', 'string', Rule::in(TenantService::impactKeys())],
            'is_active' => 'nullable|boolean',
        ]);
    }

    private function fillService(TenantService $service, Tenant $tenant, Request $request): void
    {
        $service->tenant_id = $tenant->id;
        $service->macro_area = $request->input('macro_area');
        $service->name = trim((string) $request->input('name'));
        $service->description = $request->input('description');
        $service->relevance_override = $request->input('relevance_override');
        $service->is_active = $request->boolean('is_active');
    }

    private function serviceForTenant(Tenant $tenant, TenantService $service): TenantService
    {
        abort_unless((int) $service->tenant_id === (int) $tenant->id, 404);

        return $service;
    }
}
