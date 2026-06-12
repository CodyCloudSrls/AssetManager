<?php

namespace App\Http\Controllers;

use App\Models\CustomerContract;
use App\Models\CustomerContractEvent;
use App\Models\TenantService;
use App\Support\Tenants\TenantRecordGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BulkCustomerContractsController extends Controller
{
    /**
     * Fields that can be changed in bulk, paired with their `apply_<field>` toggle.
     */
    private const EDITABLE_FIELDS = [
        'status',
        'owner_id',
        'currency',
        'signed_at',
        'starts_at',
        'ends_at',
        'renewal_due_at',
        'notice_due_at',
    ];

    public function edit(Request $request): View|RedirectResponse
    {
        $this->authorize('update', CustomerContract::class);

        $contracts = $this->contractsFromRequest($request);

        if ($contracts->isEmpty()) {
            return redirect()->route('contracts.index')->with('error', trans('admin/contracts/general.bulk_nothing_selected'));
        }

        if ($request->input('bulk_actions') !== 'edit') {
            return redirect()->route('contracts.index')->with('error', trans('admin/contracts/general.bulk_nothing_selected'));
        }

        return view('contracts.bulk-edit', [
            'contracts' => $contracts,
            'statusOptions' => CustomerContract::statusOptions(),
            'commonCompanyId' => $this->commonCompanyId($contracts),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('update', CustomerContract::class);

        $contracts = $this->contractsFromRequest($request);

        if ($contracts->isEmpty()) {
            return redirect()->route('contracts.index')->with('error', trans('admin/contracts/general.bulk_nothing_selected'));
        }

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'apply_status' => 'nullable|boolean',
            'status' => ['nullable', Rule::in(array_keys(CustomerContract::statusOptions()))],
            'apply_owner_id' => 'nullable|boolean',
            'owner_id' => 'nullable|integer|exists:users,id',
            'apply_currency' => 'nullable|boolean',
            'currency' => 'nullable|string|size:3',
            'apply_signed_at' => 'nullable|boolean',
            'signed_at' => 'nullable|date',
            'apply_starts_at' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'apply_ends_at' => 'nullable|boolean',
            'ends_at' => 'nullable|date',
            'apply_renewal_due_at' => 'nullable|boolean',
            'renewal_due_at' => 'nullable|date',
            'apply_notice_due_at' => 'nullable|boolean',
            'notice_due_at' => 'nullable|date',
            'apply_tenant_service_ids' => 'nullable|boolean',
            'tenant_service_ids' => 'nullable|array',
            'tenant_service_ids.*' => 'integer',
        ]);

        $validator->after(function ($validator) use ($request, $contracts) {
            if (! $this->hasSelectedFields($request)) {
                $validator->errors()->add('bulk_actions', trans('admin/hardware/message.update.nothing_updated'));
            }

            if ($request->boolean('apply_status') && ! $request->filled('status')) {
                $validator->errors()->add('status', trans('validation.required', ['attribute' => trans('general.status')]));
            }

            if ($request->boolean('apply_currency') && ! $request->filled('currency')) {
                $validator->errors()->add('currency', trans('validation.required', ['attribute' => trans('admin/contracts/general.currency')]));
            }

            if ($request->boolean('apply_owner_id') && $request->filled('owner_id')) {
                foreach ($contracts as $contract) {
                    $tenantId = TenantRecordGuard::companyTenantId($contract->company_id ? (int) $contract->company_id : null);

                    if (! TenantRecordGuard::userCanBeReferencedByTenant($request->integer('owner_id'), $tenantId)) {
                        $validator->errors()->add('owner_id', trans('validation.exists', ['attribute' => trans('admin/contracts/general.owner')]));

                        break;
                    }
                }
            }

            if ($request->boolean('apply_tenant_service_ids')) {
                // Tenant services are tenant-scoped, so bulk assignment is only
                // coherent when every selected contract shares one tenant.
                $tenantIds = $contracts
                    ->map(fn ($contract) => TenantRecordGuard::companyTenantId($contract->company_id ? (int) $contract->company_id : null))
                    ->filter()
                    ->unique();

                if ($tenantIds->count() !== 1) {
                    $validator->errors()->add('tenant_service_ids', trans('admin/contracts/general.bulk_services_single_tenant'));
                } else {
                    $serviceIds = $this->normalizedTenantServiceIds($request);

                    if (count($serviceIds) > 0) {
                        $companyId = (int) $contracts->first()->company_id;
                        $validIds = TenantService::validIdsForCompany($serviceIds, $companyId);

                        if (count($validIds) !== count($serviceIds)) {
                            $validator->errors()->add('tenant_service_ids', trans('admin/tenantservices/general.invalid_for_company'));
                        }
                    }
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator);
        }

        $updates = $this->updatesFromRequest($request);
        $syncServices = $request->boolean('apply_tenant_service_ids');
        $serviceIds = $syncServices ? $this->normalizedTenantServiceIds($request) : [];

        DB::transaction(function () use ($contracts, $updates, $syncServices, $serviceIds) {
            foreach ($contracts as $contract) {
                $this->authorize('update', $contract);

                $before = CustomerContractEvent::snapshot($contract);
                $contract->fill($updates);

                if (! $contract->save()) {
                    $contract->throwValidationException();
                }

                if ($syncServices) {
                    $validIds = TenantService::validIdsForCompany($serviceIds, $contract->company_id ? (int) $contract->company_id : null);
                    $contract->tenantServices()->sync($validIds);
                    $contract->load('tenantServices');
                }

                [$oldValues, $newValues] = CustomerContractEvent::changes(
                    $before,
                    CustomerContractEvent::snapshot($contract)
                );

                if ($oldValues || $newValues) {
                    CustomerContractEvent::log($contract, CustomerContractEvent::EVENT_UPDATED, $oldValues, $newValues);
                }
            }
        });

        return redirect()->route('contracts.index')->with('success', trans('admin/contracts/general.bulk_update_success'));
    }

    /**
     * Load the selected contracts. The Companyable global scope restricts the
     * result to the current user's company, so forged ids simply do not load.
     */
    private function contractsFromRequest(Request $request)
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

        return CustomerContract::query()
            ->whereIn('id', $ids)
            ->with(['customer', 'owner'])
            ->orderBy('name')
            ->get();
    }

    private function hasSelectedFields(Request $request): bool
    {
        foreach (self::EDITABLE_FIELDS as $field) {
            if ($request->boolean('apply_'.$field)) {
                return true;
            }
        }

        return $request->boolean('apply_tenant_service_ids');
    }

    private function commonCompanyId($contracts): ?int
    {
        $companyIds = $contracts->pluck('company_id')->filter()->map(fn ($id) => (int) $id)->unique();

        return $companyIds->count() === 1 ? (int) $companyIds->first() : null;
    }

    private function normalizedTenantServiceIds(Request $request): array
    {
        return collect($request->input('tenant_service_ids', []))
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Build the attribute array applying only the toggled fields. A toggled but
     * empty nullable field (owner / dates) clears the value.
     */
    private function updatesFromRequest(Request $request): array
    {
        $updates = [];

        foreach (self::EDITABLE_FIELDS as $field) {
            if (! $request->boolean('apply_'.$field)) {
                continue;
            }

            $value = $request->input($field);

            if ($field === 'currency' && filled($value)) {
                $value = strtoupper((string) $value);
            }

            $updates[$field] = filled($value) ? $value : null;
        }

        return $updates;
    }
}
