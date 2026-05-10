<?php

namespace App\Http\Transformers;

use App\Helpers\Helper;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class CustomersTransformer
{
    public function transformCustomers(Collection $customers, int $total): array
    {
        $array = [];

        foreach ($customers as $customer) {
            $array[] = $this->transformCustomer($customer);
        }

        return (new DatatablesTransformer)->transformDatatables($array, $total);
    }

    public function transformCustomer(Customer $customer): array
    {
        return [
            'id' => (int) $customer->id,
            'name' => e($customer->name),
            'customer_number' => e($customer->customer_number),
            'image' => $customer->image ? Storage::disk('public')->url('customers/'.e($customer->image)) : null,
            'company' => $customer->company ? [
                'id' => (int) $customer->company->id,
                'name' => e($customer->company->name),
                'tag_color' => $customer->company->tag_color ? e($customer->company->tag_color) : null,
            ] : null,
            'status' => e($customer->status),
            'status_label' => e($customer->status_label),
            'vat_number' => e($customer->vat_number),
            'tax_code' => e($customer->tax_code),
            'address' => e($customer->address),
            'address2' => e($customer->address2),
            'city' => e($customer->city),
            'state' => e($customer->state),
            'country' => e($customer->country),
            'zip' => e($customer->zip),
            'contact' => e($customer->contact),
            'phone' => e($customer->phone),
            'email' => e($customer->email),
            'security_contact' => e($customer->security_contact),
            'security_email' => e($customer->security_email),
            'url' => e($customer->url),
            'sector' => e($customer->sector),
            'nis_profile' => e($customer->nis_profile),
            'nis_profile_label' => e($customer->nis_profile_label),
            'nis_service_role' => e($customer->nis_service_role),
            'nis_service_role_label' => e($customer->nis_service_role_label),
            'nis_criticality' => e($customer->nis_criticality),
            'nis_criticality_label' => e($customer->nis_criticality_label),
            'nis_obligations' => e($customer->nis_obligations),
            'incident_notification_terms' => e($customer->incident_notification_terms),
            'sla_terms' => e($customer->sla_terms),
            'audit_rights' => e($customer->audit_rights),
            'nis_last_assessment_at' => Helper::getFormattedDateObject($customer->nis_last_assessment_at, 'date'),
            'nis_next_review_at' => Helper::getFormattedDateObject($customer->nis_next_review_at, 'date'),
            'contracts_count' => (int) ($customer->contracts_count ?? $customer->contracts()->count()),
            'document_assignments_count' => (int) ($customer->document_assignments_count ?? $customer->documentAssignments()->count()),
            'tag_color' => $customer->tag_color ? e($customer->tag_color) : null,
            'notes' => $customer->notes ? Helper::parseEscapedMarkedownInline($customer->notes) : null,
            'created_at' => Helper::getFormattedDateObject($customer->created_at, 'datetime'),
            'updated_at' => Helper::getFormattedDateObject($customer->updated_at, 'datetime'),
            'available_actions' => [
                'update' => Gate::allows('update', $customer),
                'delete' => Gate::allows('delete', $customer) && $customer->isDeletable(),
            ],
        ];
    }
}
