<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Concerns\AppliesTenantCompanyFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterRequest;
use App\Http\Transformers\CustomerContractsTransformer;
use App\Models\CustomerContract;
use App\Models\CustomerContractEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CustomerContractsController extends Controller
{
    use AppliesTenantCompanyFilter;

    public function index(FilterRequest $request): array
    {
        $this->authorize('view', CustomerContract::class);

        $allowedColumns = [
            'id',
            'name',
            'contract_number',
            'company_id',
            'customer_id',
            'status',
            'currency',
            'starts_at',
            'ends_at',
            'renewal_due_at',
            'notice_due_at',
            'updated_at',
            'created_at',
        ];

        $contracts = CustomerContract::query()
            ->select([
                'id',
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
                'created_at',
                'updated_at',
                'deleted_at',
                'created_by',
            ])
            ->with(['company', 'customer', 'document', 'subscriptions.costLines']);

        if ($request->filled('filter') || $request->filled('search')) {
            $contracts->TextSearch($request->input('filter') ?: $request->input('search'));
        }

        foreach (['name', 'contract_number', 'status', 'currency'] as $filter) {
            if ($request->filled($filter)) {
                $contracts->where($filter, '=', $request->input($filter));
            }
        }

        if ($request->filled('customer_id')) {
            $contracts->where('customer_id', (int) $request->input('customer_id'));
        }

        if ($request->filled('company_id')) {
            $contracts->where('company_id', (int) $request->input('company_id'));
        }

        if ($request->filled('renewal_status')) {
            if ($request->input('renewal_status') === 'due') {
                $contracts->whereNotNull('renewal_due_at')
                    ->whereDate('renewal_due_at', '<=', now()->addDays(30)->toDateString());
            }

            if ($request->input('renewal_status') === 'missing') {
                $contracts->whereNull('renewal_due_at');
            }
        }

        $this->applyTenantCompanyFilter($contracts, $request, 'company_id');

        $limit = app('api_limit_value');
        $offset = Helper::clampPaginationOffset($request->input('offset'), $contracts->count(), $limit);
        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($request->input('sort'), $allowedColumns, true) ? $request->input('sort') : 'created_at';

        $contracts->orderBy($sort, $order);

        $total = $contracts->count();
        $contracts = $contracts->skip($offset)->take($limit)->get();

        return (new CustomerContractsTransformer)->transformContracts($contracts, $total);
    }

    public function show(CustomerContract $contract): array
    {
        $this->authorize('view', $contract);
        $contract->load(['company', 'customer', 'document', 'subscriptions.costLines']);

        return (new CustomerContractsTransformer)->transformContract($contract);
    }

    public function destroy(CustomerContract $contract): JsonResponse
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

        return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/contracts/general.delete_success')));
    }
}
