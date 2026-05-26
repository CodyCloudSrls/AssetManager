<?php

namespace App\Http\Controllers;

use App\Models\ContractCostLine;
use App\Models\ContractSubscription;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\CustomerContractEvent;
use App\Models\Document;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CustomerContractsController extends Controller
{
    public function index(): View
    {
        $this->authorize('view', CustomerContract::class);

        return view('contracts.index');
    }

    public function create(Request $request): View
    {
        $this->authorize('create', CustomerContract::class);

        $contract = new CustomerContract;
        $contract->company_id = $request->integer('company_id') ?: null;
        $contract->customer_id = $request->integer('customer_id') ?: null;
        $contract->status = CustomerContract::STATUS_DRAFT;
        $contract->currency = 'EUR';

        return view('contracts.edit', $this->formData($contract));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CustomerContract::class);

        $validator = $this->validator($request);
        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator);
        }

        $contract = new CustomerContract;

        DB::transaction(function () use ($request, $contract) {
            $this->fillContract($contract, $request);
            $contract->created_by = auth()->id();
            $contract->save();

            $this->syncSubscriptionRows($contract, $request->input('subscriptions', []));
            $contractForAudit = $this->reloadContractForAudit($contract);

            CustomerContractEvent::log(
                $contractForAudit,
                CustomerContractEvent::EVENT_CREATED,
                [],
                CustomerContractEvent::snapshot($contractForAudit)
            );
        });

        return redirect()->route('contracts.index')->with('success', trans('admin/contracts/general.create_success'));
    }

    public function show(CustomerContract $contract): View
    {
        $this->authorize('view', $contract);
        $contract->load([
            'company',
            'customer',
            'document',
            'owner',
            'subscriptions.costLines.supplier',
            'events.actor',
        ]);

        return view('contracts.view', compact('contract'));
    }

    public function edit(CustomerContract $contract): View
    {
        $this->authorize('update', $contract);
        $contract->load(['subscriptions.costLines']);

        return view('contracts.edit', $this->formData($contract));
    }

    public function update(Request $request, CustomerContract $contract): RedirectResponse
    {
        $this->authorize('update', $contract);

        $validator = $this->validator($request, $contract);
        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator);
        }

        DB::transaction(function () use ($request, $contract) {
            $contract->load('subscriptions.costLines');
            $before = CustomerContractEvent::snapshot($contract);

            $this->fillContract($contract, $request);
            $contract->save();
            $this->syncSubscriptionRows($contract, $request->input('subscriptions', []));

            $afterContract = $this->reloadContractForAudit($contract);
            [$oldValues, $newValues] = CustomerContractEvent::changes(
                $before,
                CustomerContractEvent::snapshot($afterContract)
            );

            if ($oldValues || $newValues) {
                CustomerContractEvent::log(
                    $afterContract,
                    CustomerContractEvent::EVENT_UPDATED,
                    $oldValues,
                    $newValues
                );
            }
        });

        return redirect()->route('contracts.show', $contract)->with('success', trans('admin/contracts/general.update_success'));
    }

    public function destroy(CustomerContract $contract): RedirectResponse
    {
        $this->authorize('delete', $contract);

        DB::transaction(function () use ($contract) {
            $contract->load('subscriptions.costLines');
            CustomerContractEvent::log(
                $contract,
                CustomerContractEvent::EVENT_DELETED,
                CustomerContractEvent::snapshot($contract),
                []
            );

            foreach ($contract->subscriptions as $subscription) {
                $subscription->costLines()->delete();
                $subscription->delete();
            }

            $contract->delete();
        });

        return redirect()->route('contracts.index')->with('success', trans('admin/contracts/general.delete_success'));
    }

    private function formData(CustomerContract $contract): array
    {
        $companyId = old('company_id', $contract->company_id);
        $documents = Document::query()
            ->select(['id', 'name', 'document_number', 'company_id'])
            ->when($companyId, fn ($query) => $query->where('company_id', (int) $companyId))
            ->orderBy('name')
            ->get();

        $suppliers = Supplier::query()
            ->select(['id', 'name', 'company_id'])
            ->when($companyId, fn ($query) => $query->where('company_id', (int) $companyId))
            ->orderBy('name')
            ->get();

        return [
            'item' => $contract,
            'contract' => $contract,
            'documents' => $documents,
            'suppliers' => $suppliers,
            'statusOptions' => CustomerContract::statusOptions(),
            'frequencyOptions' => ContractSubscription::frequencyOptions(),
        ];
    }

    private function validator(Request $request, ?CustomerContract $contract = null)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer|exists:companies,id',
            'customer_id' => 'required|integer|exists:customers,id',
            'document_id' => 'nullable|integer|exists:documents,id',
            'owner_id' => 'nullable|integer|exists:users,id',
            'name' => 'required|string|max:255',
            'contract_number' => 'nullable|string|max:100',
            'status' => ['required', 'string', Rule::in(array_keys(CustomerContract::statusOptions()))],
            'currency' => 'required|string|size:3',
            'signed_at' => 'nullable|date_format:Y-m-d',
            'starts_at' => 'nullable|date_format:Y-m-d',
            'ends_at' => 'nullable|date_format:Y-m-d|after_or_equal:starts_at',
            'renewal_due_at' => 'nullable|date_format:Y-m-d',
            'notice_due_at' => 'nullable|date_format:Y-m-d',
            'scope' => 'nullable|string|max:65535',
            'notes' => 'nullable|string|max:65535',
            'subscriptions' => 'nullable|array',
            'subscriptions.*.name' => 'nullable|string|max:255',
            'subscriptions.*.service_code' => 'nullable|string|max:100',
            'subscriptions.*.description' => 'nullable|string|max:65535',
            'subscriptions.*.quantity' => 'nullable|numeric|gte:0',
            'subscriptions.*.unit_price' => 'nullable|numeric|gte:0',
            'subscriptions.*.billing_frequency' => ['nullable', 'string', Rule::in(array_keys(ContractSubscription::frequencyOptions()))],
            'subscriptions.*.starts_at' => 'nullable|date_format:Y-m-d',
            'subscriptions.*.ends_at' => 'nullable|date_format:Y-m-d',
            'subscriptions.*.cost_supplier_id' => 'nullable|integer|exists:suppliers,id',
            'subscriptions.*.cost_description' => 'nullable|string|max:255',
            'subscriptions.*.cost_quantity' => 'nullable|numeric|gte:0',
            'subscriptions.*.unit_cost' => 'nullable|numeric|gte:0',
            'subscriptions.*.cost_frequency' => ['nullable', 'string', Rule::in(array_keys(ContractSubscription::frequencyOptions()))],
            'subscriptions.*.cost_starts_at' => 'nullable|date_format:Y-m-d',
            'subscriptions.*.cost_ends_at' => 'nullable|date_format:Y-m-d',
        ]);

        $validator->after(function ($validator) use ($request, $contract) {
            $companyId = (int) $request->input('company_id');
            $customer = Customer::withoutGlobalScopes()->find((int) $request->input('customer_id'));

            if ($customer && (int) $customer->company_id !== $companyId) {
                $validator->errors()->add('customer_id', trans('admin/contracts/general.customer_wrong_company'));
            }

            if ($request->filled('document_id')) {
                $document = Document::withoutGlobalScopes()->find((int) $request->input('document_id'));
                if ($document && (int) $document->company_id !== $companyId) {
                    $validator->errors()->add('document_id', trans('admin/contracts/general.document_wrong_company'));
                }
            }

            foreach ($request->input('subscriptions', []) as $key => $row) {
                if ($this->isDeletedRow($row) || $this->isBlankSubscriptionRow($row)) {
                    continue;
                }

                if (blank($row['name'] ?? null)) {
                    $validator->errors()->add("subscriptions.$key.name", trans('validation.required', ['attribute' => trans('admin/contracts/general.subscription_name')]));
                }

                if (blank($row['quantity'] ?? null)) {
                    $validator->errors()->add("subscriptions.$key.quantity", trans('validation.required', ['attribute' => trans('admin/contracts/general.quantity')]));
                }

                if (blank($row['unit_price'] ?? null)) {
                    $validator->errors()->add("subscriptions.$key.unit_price", trans('validation.required', ['attribute' => trans('admin/contracts/general.unit_price')]));
                }

                if ($contract && is_numeric($key)) {
                    $belongs = ContractSubscription::withTrashed()
                        ->where('customer_contract_id', $contract->id)
                        ->where('id', (int) $key)
                        ->exists();

                    if (! $belongs) {
                        $validator->errors()->add("subscriptions.$key.name", trans('admin/contracts/general.subscription_invalid'));
                    }
                }

                if (! empty($row['cost_supplier_id'])) {
                    $supplier = Supplier::withoutGlobalScopes()->find((int) $row['cost_supplier_id']);
                    if ($supplier && (int) $supplier->company_id !== $companyId) {
                        $validator->errors()->add("subscriptions.$key.cost_supplier_id", trans('admin/contracts/general.supplier_wrong_company'));
                    }
                }

                if (! blank($row['starts_at'] ?? null) && ! blank($row['ends_at'] ?? null) && $row['ends_at'] < $row['starts_at']) {
                    $validator->errors()->add("subscriptions.$key.ends_at", trans('validation.after_or_equal', ['attribute' => trans('admin/contracts/general.ends_at'), 'date' => trans('admin/contracts/general.starts_at')]));
                }

                if (! blank($row['cost_starts_at'] ?? null) && ! blank($row['cost_ends_at'] ?? null) && $row['cost_ends_at'] < $row['cost_starts_at']) {
                    $validator->errors()->add("subscriptions.$key.cost_ends_at", trans('validation.after_or_equal', ['attribute' => trans('admin/contracts/general.cost_ends_at'), 'date' => trans('admin/contracts/general.cost_starts_at')]));
                }
            }
        });

        return $validator;
    }

    private function reloadContractForAudit(CustomerContract $contract): CustomerContract
    {
        return CustomerContract::withoutGlobalScopes()
            ->with(['subscriptions.costLines'])
            ->findOrFail($contract->id);
    }

    private function fillContract(CustomerContract $contract, Request $request): void
    {
        $contract->fill($request->only([
            'company_id',
            'customer_id',
            'document_id',
            'owner_id',
            'name',
            'contract_number',
            'status',
            'currency',
            'signed_at',
            'starts_at',
            'ends_at',
            'renewal_due_at',
            'notice_due_at',
            'scope',
            'notes',
        ]));
    }

    private function syncSubscriptionRows(CustomerContract $contract, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $key => $row) {
            if ($this->isDeletedRow($row)) {
                if (is_numeric($key)) {
                    $subscription = ContractSubscription::where('customer_contract_id', $contract->id)->find((int) $key);
                    if ($subscription) {
                        $subscription->costLines()->delete();
                        $subscription->delete();
                    }
                }

                continue;
            }

            if ($this->isBlankSubscriptionRow($row)) {
                continue;
            }

            $subscription = is_numeric($key)
                ? ContractSubscription::where('customer_contract_id', $contract->id)->find((int) $key)
                : null;

            $subscription ??= new ContractSubscription;
            $subscription->company_id = $contract->company_id;
            $subscription->customer_contract_id = $contract->id;
            $subscription->name = trim((string) ($row['name'] ?? ''));
            $subscription->service_code = $this->nullableString($row['service_code'] ?? null);
            $subscription->description = $this->nullableString($row['description'] ?? null);
            $subscription->quantity = $row['quantity'] ?? 1;
            $subscription->unit_price = $row['unit_price'] ?? 0;
            $subscription->billing_frequency = $row['billing_frequency'] ?? ContractSubscription::FREQUENCY_MONTHLY;
            $subscription->starts_at = $row['starts_at'] ?: null;
            $subscription->ends_at = $row['ends_at'] ?: null;
            $subscription->is_active = ! array_key_exists('is_active', $row) || (bool) $row['is_active'];
            $subscription->created_by = $subscription->created_by ?: auth()->id();
            $subscription->save();

            $this->syncSingleCostLine($subscription, $row);
            $keptIds[] = $subscription->id;
        }

        ContractSubscription::where('customer_contract_id', $contract->id)
            ->when(count($keptIds) > 0, fn ($query) => $query->whereNotIn('id', $keptIds))
            ->when(count($keptIds) === 0, fn ($query) => $query)
            ->get()
            ->each(function (ContractSubscription $subscription) {
                $subscription->costLines()->delete();
                $subscription->delete();
            });
    }

    private function syncSingleCostLine(ContractSubscription $subscription, array $row): void
    {
        $hasCost = ! blank($row['cost_description'] ?? null)
            || ! blank($row['cost_supplier_id'] ?? null)
            || ! blank($row['unit_cost'] ?? null);

        $costLine = $subscription->costLines()->first();

        if (! $hasCost) {
            if ($costLine) {
                $costLine->delete();
            }

            return;
        }

        $costLine ??= new ContractCostLine;
        $costLine->company_id = $subscription->company_id;
        $costLine->contract_subscription_id = $subscription->id;
        $costLine->supplier_id = ! empty($row['cost_supplier_id']) ? (int) $row['cost_supplier_id'] : null;
        $costLine->description = $this->nullableString($row['cost_description'] ?? null) ?: $subscription->name;
        $costLine->quantity = $row['cost_quantity'] ?? 1;
        $costLine->unit_cost = $row['unit_cost'] ?? 0;
        $costLine->cost_frequency = $row['cost_frequency'] ?? $subscription->billing_frequency;
        $costLine->starts_at = $row['cost_starts_at'] ?: null;
        $costLine->ends_at = $row['cost_ends_at'] ?: null;
        $costLine->is_active = ! array_key_exists('cost_is_active', $row) || (bool) $row['cost_is_active'];
        $costLine->created_by = $costLine->created_by ?: auth()->id();
        $costLine->save();

        $subscription->costLines()
            ->where('id', '!=', $costLine->id)
            ->delete();
    }

    private function isDeletedRow(array $row): bool
    {
        return (bool) ($row['_delete'] ?? false);
    }

    private function isBlankSubscriptionRow(array $row): bool
    {
        return blank($row['name'] ?? null)
            && blank($row['service_code'] ?? null)
            && blank($row['description'] ?? null)
            && blank($row['unit_price'] ?? null)
            && blank($row['cost_description'] ?? null)
            && blank($row['unit_cost'] ?? null);
    }

    private function nullableString(mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
