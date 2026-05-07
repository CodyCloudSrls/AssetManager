<?php

namespace App\Http\Transformers;

use App\Helpers\Helper;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SuppliersTransformer
{
    public function transformSuppliers(Collection $suppliers, $total)
    {
        $array = [];
        foreach ($suppliers as $supplier) {
            $array[] = self::transformSupplier($supplier);
        }

        return (new DatatablesTransformer)->transformDatatables($array, $total);
    }

    public function transformSupplier(?Supplier $supplier = null)
    {
        if ($supplier) {
            $array = [
                'id' => (int) $supplier->id,
                'name' => e($supplier->name),
                'image' => ($supplier->image) ? Storage::disk('public')->url('suppliers/'.e($supplier->image)) : null,
                'url' => e($supplier->url),
                'address' => e($supplier->address),
                'address2' => e($supplier->address2),
                'city' => e($supplier->city),
                'state' => e($supplier->state),
                'country' => e($supplier->country),
                'zip' => e($supplier->zip),
                'fax' => e($supplier->fax),
                'phone' => e($supplier->phone),
                'email' => e($supplier->email),
                'contact' => e($supplier->contact),
                'company' => $supplier->company ? [
                    'id' => (int) $supplier->company->id,
                    'name' => e($supplier->company->name),
                ] : null,
                'visibility_type' => $supplier->visibility_type,
                'visibility_label' => e($supplier->visibility_label),
                'nis_relevant' => (bool) $supplier->nis_relevant,
                'nis_relevance_type' => e($supplier->nis_relevance_type),
                'nis_relevance_type_label' => e($supplier->nis_relevance_type_label),
                'nis_criticality' => e($supplier->nis_criticality),
                'nis_criticality_label' => e($supplier->nis_criticality_label),
                'nis_assessment_status' => e($supplier->nis_assessment_status),
                'nis_assessment_status_label' => e($supplier->nis_assessment_status_label),
                'nis_relevance_criteria' => e($supplier->nis_relevance_criteria),
                'cpv_codes' => e($supplier->cpv_codes),
                'nis_last_assessment_at' => Helper::getFormattedDateObject($supplier->nis_last_assessment_at, 'date'),
                'nis_next_review_at' => Helper::getFormattedDateObject($supplier->nis_next_review_at, 'date'),
                'assets_count' => (int) $supplier->assets_count,
                'accessories_count' => (int) $supplier->accessories_count,
                'licenses_count' => (int) $supplier->licenses_count,
                'consumables_count' => (int) $supplier->consumables_count,
                'components_count' => (int) $supplier->components_count,
                'tag_color' => $supplier->tag_color ? e($supplier->tag_color) : null,
                'notes' => ($supplier->notes) ? Helper::parseEscapedMarkedownInline($supplier->notes) : null,
                'created_at' => Helper::getFormattedDateObject($supplier->created_at, 'datetime'),
                'created_by' => $supplier->adminuser ? [
                    'id' => (int) $supplier->adminuser->id,
                    'name' => e($supplier->adminuser->present()->fullName),
                ] : null,
                'updated_at' => Helper::getFormattedDateObject($supplier->updated_at, 'datetime'),

            ];

            $permissions_array['available_actions'] = [
                'update' => Gate::allows('update', $supplier),
                'delete' => (Gate::allows('delete', $supplier) && ($supplier->isDeletable())),
            ];

            $array += $permissions_array;

            return $array;
        }
    }
}
