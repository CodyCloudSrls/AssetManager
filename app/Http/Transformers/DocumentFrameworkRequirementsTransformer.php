<?php

namespace App\Http\Transformers;

use App\Helpers\Helper;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;

class DocumentFrameworkRequirementsTransformer
{
    public function transformRequirements(Collection $requirements, int $total): array
    {
        $array = [];

        foreach ($requirements as $requirement) {
            $array[] = $this->transformRequirement($requirement);
        }

        return (new DatatablesTransformer)->transformDatatables($array, $total);
    }

    public function transformRequirement(DocumentFrameworkRequirement $requirement): array
    {
        return [
            'id' => (int) $requirement->id,
            'code' => e($requirement->code),
            'title' => e($requirement->title),
            'domain' => e($requirement->domain),
            'obligation_type' => e($requirement->obligation_type),
            'obligation_type_label' => e($requirement->obligation_type_label),
            'evidence_type' => e($requirement->evidence_type),
            'evidence_type_label' => e($requirement->evidence_type_label),
            'delegation_level' => e($requirement->delegation_level),
            'delegation_level_label' => e($requirement->delegation_level_label),
            'risk_level' => e($requirement->risk_level),
            'risk_level_label' => e($requirement->risk_level_label),
            'official_reference' => e($requirement->official_reference),
            'source_url' => e($requirement->source_url),
            'description' => e($requirement->description),
            'coverage_status' => e($requirement->coverage_status),
            'coverage_label' => e($requirement->coverage_label),
            'documents_count' => (int) ($requirement->documents_count ?? 0),
            'owner' => $requirement->owner ? [
                'id' => (int) $requirement->owner->id,
                'name' => e($requirement->owner->display_name),
            ] : null,
            'default_document_type' => e($requirement->defaultDocumentType?->name),
            'review_frequency_months' => $requirement->review_frequency_months,
            'is_mandatory' => (bool) $requirement->is_mandatory,
            'is_active' => (bool) $requirement->is_active,
            'sort_order' => (int) $requirement->sort_order,
            'framework' => $requirement->framework ? [
                'id' => (int) $requirement->framework->id,
                'name' => e($requirement->framework->name),
            ] : null,
            'created_by' => $requirement->adminuser ? [
                'id' => (int) $requirement->adminuser->id,
                'name' => e($requirement->adminuser->display_name),
            ] : null,
            'created_at' => Helper::getFormattedDateObject($requirement->created_at, 'datetime'),
            'updated_at' => Helper::getFormattedDateObject($requirement->updated_at, 'datetime'),
            'available_actions' => [
                'view' => Gate::allows('view', $requirement),
                'update' => ($requirement->deleted_at == '' && Gate::allows('update', $requirement)),
                'delete' => ($requirement->deleted_at == '' && Gate::allows('delete', $requirement) && $requirement->isDeletable()),
                'restore' => ($requirement->deleted_at != '' && Gate::allows('delete', DocumentFramework::class)),
            ],
        ];
    }
}
