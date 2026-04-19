<?php

namespace App\Http\Transformers;

use App\Helpers\Helper;
use App\Models\DocumentFramework;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class DocumentFrameworksTransformer
{
    public function transformDocumentFrameworks(Collection $documentFrameworks, int $total): array
    {
        $array = [];

        foreach ($documentFrameworks as $documentFramework) {
            $array[] = $this->transformDocumentFramework($documentFramework);
        }

        return (new DatatablesTransformer)->transformDatatables($array, $total);
    }

    public function transformDocumentFramework(DocumentFramework $documentFramework): array
    {
        return [
            'id' => (int) $documentFramework->id,
            'name' => e($documentFramework->name),
            'slug' => e($documentFramework->slug),
            'authority_name' => e($documentFramework->authority_name),
            'framework_code' => e($documentFramework->framework_code),
            'framework_type' => e($documentFramework->framework_type),
            'jurisdiction' => e($documentFramework->jurisdiction),
            'version' => e($documentFramework->version),
            'status' => e(\App\Models\DocumentFramework::getStatusOptions()[$documentFramework->status] ?? $documentFramework->status),
            'external_reference_url' => e($documentFramework->external_reference_url),
            'description' => e($documentFramework->description),
            'sort_order' => (int) $documentFramework->sort_order,
            'is_active' => (bool) $documentFramework->is_active,
            'company' => ($documentFramework->company) ? [
                'id' => (int) $documentFramework->company->id,
                'name' => e($documentFramework->company->name),
            ] : null,
            'visibility_type' => e($documentFramework->visibility_type),
            'visibility_label' => e($documentFramework->visibility_label),
            'documents_count' => (int) ($documentFramework->documents_count ?? 0),
            'requirements_count' => (int) ($documentFramework->requirements_count ?? 0),
            'owner' => ($documentFramework->owner) ? [
                'id' => (int) $documentFramework->owner->id,
                'name' => e($documentFramework->owner->display_name),
            ] : null,
            'created_by' => ($documentFramework->adminuser) ? [
                'id' => (int) $documentFramework->adminuser->id,
                'name' => e($documentFramework->adminuser->display_name),
            ] : null,
            'effective_from' => Helper::getFormattedDateObject($documentFramework->effective_from, 'date'),
            'effective_to' => Helper::getFormattedDateObject($documentFramework->effective_to, 'date'),
            'created_at' => Helper::getFormattedDateObject($documentFramework->created_at, 'datetime'),
            'updated_at' => Helper::getFormattedDateObject($documentFramework->updated_at, 'datetime'),
            'deleted_at' => Helper::getFormattedDateObject($documentFramework->deleted_at, 'datetime'),
            'available_actions' => [
                'update' => ($documentFramework->deleted_at == '' && Gate::allows('update', $documentFramework)),
                'delete' => ($documentFramework->deleted_at == '' && Gate::allows('delete', $documentFramework) && $documentFramework->isDeletable()),
                'restore' => ($documentFramework->deleted_at != '' && Gate::allows('delete', $documentFramework)),
            ],
        ];
    }
}
