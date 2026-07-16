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

        if ($request->filled('tenant_service_id')) {
            $contracts->whereHas('tenantServices', function ($query) use ($request) {
                $query->where('tenant_services.id', '=', (int) $request->input('tenant_service_id'));
            });
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
        $requestedSort = $request->input('sort');

        // Count BEFORE applying any ORDER BY, so the count query never carries the computed-sort
        // subqueries (portable; and Laravel strips orders on aggregates anyway).
        $total = $contracts->count();

        // customer + monthly_* are NOT real DB columns: `customer` is a belongsTo and the monthly_*
        // values are computed in CustomerContractsTransformer from subscriptions/costLines. To sort
        // the server-side page by exactly what the table DISPLAYS, reproduce each value as a
        // correlated subquery. The CASE divisors mirror ContractSubscription::monthlyAmount()
        // (one_time => 0, else raw = monthly); deleted_at IS NULL matches the SoftDeletes eager
        // loads; is_active is intentionally NOT filtered (the transformer sums active + inactive).
        $revenueSub = '(SELECT COALESCE(SUM(CASE cs.billing_frequency '
            ."WHEN 'one_time' THEN 0 "
            ."WHEN 'bimonthly' THEN cs.quantity * cs.unit_price / 2 "
            ."WHEN 'quarterly' THEN cs.quantity * cs.unit_price / 3 "
            ."WHEN 'quadrimester' THEN cs.quantity * cs.unit_price / 4 "
            ."WHEN 'semiannual' THEN cs.quantity * cs.unit_price / 6 "
            ."WHEN 'annual' THEN cs.quantity * cs.unit_price / 12 "
            .'ELSE cs.quantity * cs.unit_price END), 0) '
            .'FROM contract_subscriptions cs '
            .'WHERE cs.customer_contract_id = customer_contracts.id AND cs.deleted_at IS NULL)';

        $costSub = '(SELECT COALESCE(SUM(CASE ccl.cost_frequency '
            ."WHEN 'one_time' THEN 0 "
            ."WHEN 'bimonthly' THEN ccl.quantity * ccl.unit_cost / 2 "
            ."WHEN 'quarterly' THEN ccl.quantity * ccl.unit_cost / 3 "
            ."WHEN 'quadrimester' THEN ccl.quantity * ccl.unit_cost / 4 "
            ."WHEN 'semiannual' THEN ccl.quantity * ccl.unit_cost / 6 "
            ."WHEN 'annual' THEN ccl.quantity * ccl.unit_cost / 12 "
            .'ELSE ccl.quantity * ccl.unit_cost END), 0) '
            .'FROM contract_cost_lines ccl '
            .'JOIN contract_subscriptions cs2 ON cs2.id = ccl.contract_subscription_id '
            .'WHERE cs2.customer_contract_id = customer_contracts.id '
            .'AND cs2.deleted_at IS NULL AND ccl.deleted_at IS NULL)';

        $computedSort = [
            'customer' => '(SELECT c.name FROM customers c WHERE c.id = customer_contracts.customer_id AND c.deleted_at IS NULL)',
            'monthly_revenue' => $revenueSub,
            'monthly_cost' => $costSub,
            'monthly_net' => $revenueSub.' - '.$costSub,
        ];

        // $order is sanitized to the literal 'asc'/'desc' above and the subquery SQL is fully
        // static (no request data), so appending $order to orderByRaw is injection-safe.
        if (array_key_exists($requestedSort, $computedSort)) {
            $contracts->orderByRaw($computedSort[$requestedSort].' '.$order);
        } else {
            $sort = in_array($requestedSort, $allowedColumns, true) ? $requestedSort : 'created_at';
            $contracts->orderBy($sort, $order);
        }

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
