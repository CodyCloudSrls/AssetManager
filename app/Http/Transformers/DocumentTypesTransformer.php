<?php

namespace App\Http\Transformers;

use App\Helpers\Helper;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class DocumentTypesTransformer
{
    public function transformDocumentTypes(Collection $documentTypes, int $total): array
    {
        $array = [];

        foreach ($documentTypes as $documentType) {
            $array[] = $this->transformDocumentType($documentType);
        }

        return (new DatatablesTransformer)->transformDatatables($array, $total);
    }

    public function transformDocumentType(DocumentType $documentType): array
    {
        return [
            'id' => (int) $documentType->id,
            'name' => e($documentType->name),
            'slug' => e($documentType->slug),
            'description' => e($documentType->description),
            'sort_order' => (int) $documentType->sort_order,
            'is_active' => (bool) $documentType->is_active,
            'company' => ($documentType->company) ? [
                'id' => (int) $documentType->company->id,
                'name' => e($documentType->company->name),
            ] : null,
            'visibility_type' => e($documentType->visibility_type),
            'visibility_label' => e($documentType->visibility_label),
            'documents_count' => (int) ($documentType->documents_count ?? 0),
            'created_by' => ($documentType->adminuser) ? [
                'id' => (int) $documentType->adminuser->id,
                'name' => e($documentType->adminuser->display_name),
            ] : null,
            'created_at' => Helper::getFormattedDateObject($documentType->created_at, 'datetime'),
            'updated_at' => Helper::getFormattedDateObject($documentType->updated_at, 'datetime'),
            'deleted_at' => Helper::getFormattedDateObject($documentType->deleted_at, 'datetime'),
            'available_actions' => [
                'update' => ($documentType->deleted_at == '' && Gate::allows('update', $documentType)),
                'delete' => ($documentType->deleted_at == '' && Gate::allows('delete', $documentType) && $documentType->isDeletable()),
                'restore' => ($documentType->deleted_at != '' && Gate::allows('delete', $documentType)),
            ],
        ];
    }
}
