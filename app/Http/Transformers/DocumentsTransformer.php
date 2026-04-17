<?php

namespace App\Http\Transformers;

use App\Helpers\Helper;
use App\Models\Document;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class DocumentsTransformer
{
    public function transformDocuments(Collection $documents, $total)
    {
        $array = [];

        foreach ($documents as $document) {
            $array[] = $this->transformDocument($document);
        }

        return (new DatatablesTransformer)->transformDatatables($array, $total);
    }

    public function transformDocument(Document $document)
    {
        $array = [
            'id' => (int) $document->id,
            'name' => e($document->name),
            'document_number' => e($document->document_number),
            'document_type' => e($document->type?->name),
            'framework' => e($document->framework?->name),
            'reference' => e($document->reference),
            'version' => e($document->version),
            'status' => e(Document::getStatusOptions()[$document->status] ?? $document->status),
            'classification' => e($document->classification),
            'retention_period' => e($document->retention_period),
            'scope' => e($document->scope),
            'summary' => ($document->summary) ? Helper::parseEscapedMarkedownInline($document->summary) : null,
            'notes' => ($document->notes) ? Helper::parseEscapedMarkedownInline($document->notes) : null,
            'control_url' => e($document->control_url),
            'company' => ($document->company) ? [
                'id' => (int) $document->company->id,
                'name' => e($document->company->name),
                'tag_color' => ($document->company->tag_color) ? e($document->company->tag_color) : null,
            ] : null,
            'owner' => ($document->owner) ? [
                'id' => (int) $document->owner->id,
                'name' => e($document->owner->display_name),
            ] : null,
            'created_by' => ($document->adminuser) ? [
                'id' => (int) $document->adminuser->id,
                'name' => e($document->adminuser->display_name),
            ] : null,
            'created_at' => Helper::getFormattedDateObject($document->created_at, 'datetime'),
            'updated_at' => Helper::getFormattedDateObject($document->updated_at, 'datetime'),
            'deleted_at' => Helper::getFormattedDateObject($document->deleted_at, 'datetime'),
            'issued_at' => Helper::getFormattedDateObject($document->issued_at, 'date'),
            'effective_at' => Helper::getFormattedDateObject($document->effective_at, 'date'),
            'next_review_at' => Helper::getFormattedDateObject($document->next_review_at, 'date'),
        ];

        $array['available_actions'] = [
            'update' => ($document->deleted_at == '' && Gate::allows('update', $document)),
            'delete' => ($document->deleted_at == '' && Gate::allows('delete', $document)),
            'restore' => ($document->deleted_at != '' && Gate::allows('create', Document::class)),
        ];

        return $array;
    }
}
