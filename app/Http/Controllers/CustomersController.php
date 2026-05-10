<?php

namespace App\Http\Controllers;

use App\Actions\Customers\DestroyCustomerAction;
use App\Http\Requests\ImageUploadRequest;
use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class CustomersController extends Controller
{
    public function index(): View
    {
        $this->authorize('view', Customer::class);

        return view('customers.index');
    }

    public function create(): View
    {
        $this->authorize('create', Customer::class);

        return view('customers.edit')->with('item', new Customer);
    }

    public function store(ImageUploadRequest $request): RedirectResponse
    {
        $this->authorize('create', Customer::class);

        $customer = new Customer;
        $this->fillCustomer($customer, $request);
        $customer->created_by = auth()->id();
        $customer = $request->handleImages($customer);

        if ($customer->save()) {
            return redirect()->route('customers.index')->with('success', trans('admin/customers/message.create.success'));
        }

        return redirect()->back()->withInput()->withErrors($customer->getErrors());
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);
        $customer->load([
            'company',
            'contracts.document',
            'contracts.subscriptions.costLines.supplier',
            'documentAssignments.document.type',
            'documentAssignments.document.framework',
            'documentAssignments.issuer',
            'documentAssignments.reviewer',
        ]);

        return view('customers.view', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        return view('customers.edit')->with('item', $customer);
    }

    public function update(ImageUploadRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $this->fillCustomer($customer, $request);
        $customer = $request->handleImages($customer);

        if ($customer->save()) {
            return redirect()->route('customers.index')->with('success', trans('admin/customers/message.update.success'));
        }

        return redirect()->back()->withInput()->withErrors($customer->getErrors());
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        try {
            DestroyCustomerAction::run($customer);
        } catch (RuntimeException $e) {
            return redirect()->route('customers.index')->with('error', $e->getMessage());
        } catch (\Exception $e) {
            report($e);

            return redirect()->route('customers.index')->with('error', trans('admin/customers/message.delete.error'));
        }

        return redirect()->route('customers.index')->with('success', trans('admin/customers/message.delete.success'));
    }

    private function fillCustomer(Customer $customer, ImageUploadRequest $request): void
    {
        $customer->fill($request->only([
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
            'tag_color',
            'notes',
        ]));

        $customer->url = $customer->addhttp($request->input('url'));
    }
}
