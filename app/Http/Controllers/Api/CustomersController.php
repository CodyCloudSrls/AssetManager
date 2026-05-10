<?php

namespace App\Http\Controllers\Api;

use App\Actions\Customers\DestroyCustomerAction;
use App\Helpers\Helper;
use App\Http\Controllers\Concerns\AppliesTenantCompanyFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\ImageUploadRequest;
use App\Http\Transformers\CustomersTransformer;
use App\Http\Transformers\SelectlistTransformer;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CustomersController extends Controller
{
    use AppliesTenantCompanyFilter;

    public function index(FilterRequest $request): array
    {
        $this->authorize('view', Customer::class);

        $allowedColumns = [
            'id',
            'name',
            'customer_number',
            'company_id',
            'status',
            'vat_number',
            'tax_code',
            'city',
            'state',
            'country',
            'zip',
            'contact',
            'phone',
            'email',
            'security_contact',
            'security_email',
            'sector',
            'nis_profile',
            'nis_service_role',
            'nis_criticality',
            'nis_next_review_at',
            'contracts_count',
            'document_assignments_count',
            'updated_at',
            'created_at',
        ];

        $customers = Customer::query()
            ->select([
                'id',
                'company_id',
                'name',
                'customer_number',
                'status',
                'vat_number',
                'tax_code',
                'address',
                'address2',
                'city',
                'state',
                'country',
                'zip',
                'contact',
                'phone',
                'email',
                'security_contact',
                'security_email',
                'url',
                'sector',
                'nis_profile',
                'nis_service_role',
                'nis_criticality',
                'nis_obligations',
                'incident_notification_terms',
                'sla_terms',
                'audit_rights',
                'nis_last_assessment_at',
                'nis_next_review_at',
                'image',
                'tag_color',
                'notes',
                'created_at',
                'updated_at',
                'deleted_at',
                'created_by',
            ])
            ->with(['company', 'adminuser'])
            ->withCount(['contracts', 'documentAssignments']);

        if ($request->filled('filter') || $request->filled('search')) {
            $customers->TextSearch($request->input('filter') ?: $request->input('search'));
        }

        foreach (['name', 'customer_number', 'status', 'city', 'state', 'country', 'zip', 'email', 'nis_profile', 'nis_service_role', 'nis_criticality'] as $filter) {
            if ($request->filled($filter)) {
                $customers->where($filter, '=', $request->input($filter));
            }
        }

        if ($request->filled('company_id')) {
            $customers->where('company_id', '=', (int) $request->input('company_id'));
        }

        if ($request->filled('nis_review_status')) {
            if ($request->input('nis_review_status') === 'due') {
                $reviewWarningDays = $this->tenantFromRequest($request)?->documentReviewWarningDays() ?? 0;

                $customers->whereNotNull('nis_next_review_at')
                    ->whereDate('nis_next_review_at', '<=', now()->addDays($reviewWarningDays)->toDateString());
            }

            if ($request->input('nis_review_status') === 'missing') {
                $customers->whereNull('nis_next_review_at');
            }
        }

        $this->applyTenantCompanyFilter($customers, $request, 'company_id');

        $limit = app('api_limit_value');
        $offset = Helper::clampPaginationOffset($request->input('offset'), $customers->count(), $limit);
        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($request->input('sort'), $allowedColumns, true) ? $request->input('sort') : 'created_at';

        $customers->orderBy($sort, $order);

        $total = $customers->count();
        $customers = $customers->skip($offset)->take($limit)->get();

        return (new CustomersTransformer)->transformCustomers($customers, $total);
    }

    public function store(ImageUploadRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $customer = new Customer;
        $customer->fill($request->all());
        $customer->created_by = auth()->id();
        $customer->url = $customer->addhttp($request->input('url'));
        $customer = $request->handleImages($customer);

        if ($customer->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', $customer, trans('admin/customers/message.create.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $customer->getErrors()));
    }

    public function show(Customer $customer): array
    {
        $this->authorize('view', $customer);
        $customer->load(['company', 'adminuser'])->loadCount(['contracts', 'documentAssignments']);

        return (new CustomersTransformer)->transformCustomer($customer);
    }

    public function update(ImageUploadRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $customer->fill($request->all());
        $customer->url = $customer->addhttp($request->input('url'));
        $customer = $request->handleImages($customer);

        if ($customer->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', $customer, trans('admin/customers/message.update.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $customer->getErrors()));
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);

        try {
            DestroyCustomerAction::run($customer);
        } catch (RuntimeException $e) {
            return response()->json(Helper::formatStandardApiResponse('error', null, $e->getMessage()));
        } catch (\Exception $e) {
            report($e);

            return response()->json(Helper::formatStandardApiResponse('error', null, trans('general.something_went_wrong')));
        }

        return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/customers/message.delete.success')));
    }

    public function selectlist(Request $request): array
    {
        $this->authorize('view.selectlists');

        $customers = Customer::query()
            ->select(['id', 'name', 'customer_number', 'image', 'tag_color', 'company_id'])
            ->when($request->filled('company_id'), fn ($query) => $query->where('company_id', (int) $request->input('company_id')))
            ->when($request->filled('companyId'), fn ($query) => $query->where('company_id', (int) $request->input('companyId')));

        if ($request->filled('search')) {
            $customers->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%'.$request->input('search').'%')
                    ->orWhere('customer_number', 'LIKE', '%'.$request->input('search').'%')
                    ->orWhere('vat_number', 'LIKE', '%'.$request->input('search').'%');
            });
        }

        $customers = $customers->orderBy('name')->paginate(50);

        foreach ($customers as $customer) {
            $customer->use_text = trim($customer->name.($customer->customer_number ? ' ('.$customer->customer_number.')' : ''));
            $customer->use_image = $customer->image ? Storage::disk('public')->url('customers/'.$customer->image) : null;
        }

        return (new SelectlistTransformer)->transformSelectlist($customers);
    }
}
