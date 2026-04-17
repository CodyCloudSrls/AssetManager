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
            'description' => e($documentFramework->description),
            'sort_order' => (int) $documentFramework->sort_order,
            'is_active' => (bool) $documentFramework->is_active,
            'documents_count' => (int) ($documentFramework->documents_count ?? 0),
            'created_by' => ($documentFramework->adminuser) ? [
                'id' => (int) $documentFramework->adminuser->id,
                'name' => e($documentFramework->adminuser->display_name),
            ] : null,
            'created_at' => Helper::getFormattedDateObject($documentFramework->created_at, 'datetime'),
            'updated_at' => Helper::getFormattedDateObject($documentFramework->updated_at, 'datetime'),
            'deleted_at' => Helper::getFormattedDateObject($documentFramework->deleted_at, 'datetime'),
            'available_actions' => [
                'update' => ($documentFramework->deleted_at == '' && Gate::allows('update', $documentFramework)),
                'delete' => ($documentFramework->deleted_at == '' && Gate::allows('delete', $documentFramework) && $documentFramework->isDeletable()),
                'restore' => ($documentFramework->deleted_at != '' && Gate::allows('delete', DocumentFramework::class)),
            ],
        ];
    }
}
