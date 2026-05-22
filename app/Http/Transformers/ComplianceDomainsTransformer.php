<?php

namespace App\Http\Transformers;

use App\Helpers\Helper;
use App\Models\ComplianceDomain;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class ComplianceDomainsTransformer
{
    public function transformComplianceDomains(Collection $complianceDomains, int $total): array
    {
        $array = [];

        foreach ($complianceDomains as $complianceDomain) {
            $array[] = $this->transformComplianceDomain($complianceDomain);
        }

        return (new DatatablesTransformer)->transformDatatables($array, $total);
    }

    public function transformComplianceDomain(ComplianceDomain $complianceDomain): array
    {
        return [
            'id' => (int) $complianceDomain->id,
            'key' => e($complianceDomain->key),
            'name' => e($complianceDomain->name),
            'description' => e($complianceDomain->description),
            'is_active' => (bool) $complianceDomain->is_active,
            'is_system' => (bool) $complianceDomain->is_system,
            'sort_order' => (int) $complianceDomain->sort_order,
            'created_by' => $complianceDomain->adminuser ? [
                'id' => (int) $complianceDomain->adminuser->id,
                'name' => e($complianceDomain->adminuser->display_name),
            ] : null,
            'created_at' => Helper::getFormattedDateObject($complianceDomain->created_at, 'datetime'),
            'updated_at' => Helper::getFormattedDateObject($complianceDomain->updated_at, 'datetime'),
            'deleted_at' => Helper::getFormattedDateObject($complianceDomain->deleted_at, 'datetime'),
            'available_actions' => [
                'update' => ($complianceDomain->deleted_at == '' && Gate::allows('update', $complianceDomain)),
                'delete' => ($complianceDomain->deleted_at == '' && Gate::allows('delete', $complianceDomain) && $complianceDomain->isDeletable()),
                'restore' => ($complianceDomain->deleted_at != '' && Gate::allows('restore', $complianceDomain)),
            ],
        ];
    }
}
