<?php

namespace App\Http\Transformers;

use App\Helpers\Helper;
use App\Models\DocumentAssignment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class DocumentAssignmentsTransformer
{
    public function transformDocumentAssignments(Collection $assignments, int $total): array
    {
        $rows = [];

        foreach ($assignments as $assignment) {
            $rows[] = $this->transformDocumentAssignment($assignment);
        }

        return (new DatatablesTransformer)->transformDatatables($rows, $total);
    }

    public function transformDocumentAssignment(DocumentAssignment $assignment): array
    {
        $assignment->loadMissing('document', 'document.type', 'company', 'issuer', 'reviewer', 'assignable');

        return [
            'id' => (int) $assignment->id,
            'document' => $assignment->document ? [
                'id' => (int) $assignment->document->id,
                'name' => e($assignment->document->name),
            ] : null,
            'document_number' => e($assignment->document?->document_number),
            'document_type' => e($assignment->document?->type?->name),
            'target' => [
                'id' => (int) $assignment->assignable_id,
                'name' => e($assignment->assignable_display_name),
                'url' => $assignment->assignable_url,
                'type' => e($assignment->assignable_type_label),
            ],
            'assignable_type' => e(DocumentAssignment::tokenForAssignableClass($assignment->assignable_type)),
            'assignable_type_label' => e($assignment->assignable_type_label),
            'relation_type' => e($assignment->relation_type),
            'relation_type_label' => e($assignment->relation_type_label),
            'status' => e($assignment->status),
            'status_label' => e($assignment->status_label),
            'approval_status' => e($assignment->approval_status),
            'approval_status_label' => e($assignment->approval_status_label),
            'company' => $assignment->company ? [
                'id' => (int) $assignment->company->id,
                'name' => e($assignment->company->name),
                'tag_color' => $assignment->company->tag_color ? e($assignment->company->tag_color) : null,
            ] : null,
            'issuer' => $assignment->issuer ? [
                'id' => (int) $assignment->issuer->id,
                'name' => e($assignment->issuer->display_name),
            ] : null,
            'reviewer' => $assignment->reviewer ? [
                'id' => (int) $assignment->reviewer->id,
                'name' => e($assignment->reviewer->display_name),
            ] : null,
            'reference_number' => e($assignment->reference_number),
            'effective_at' => Helper::getFormattedDateObject($assignment->effective_at, 'date'),
            'expires_at' => Helper::getFormattedDateObject($assignment->expires_at, 'date'),
            'renewal_due_at' => Helper::getFormattedDateObject($assignment->renewal_due_at, 'date'),
            'completed_at' => Helper::getFormattedDateObject($assignment->completed_at, 'date'),
            'reviewed_at' => Helper::getFormattedDateObject($assignment->reviewed_at, 'datetime'),
            'notes' => e(Str::limit(trim(strip_tags((string) $assignment->notes)), 160)),
            'review_notes' => e(Str::limit(trim(strip_tags((string) $assignment->review_notes)), 160)),
            'is_expiring' => $assignment->is_expiring,
            'is_expired' => $assignment->is_expired,
            'created_at' => Helper::getFormattedDateObject($assignment->created_at, 'datetime'),
            'updated_at' => Helper::getFormattedDateObject($assignment->updated_at, 'datetime'),
            'available_actions' => [
                'view' => $assignment->document && Gate::allows('view', $assignment->document),
                'update' => $assignment->document && Gate::allows('update', $assignment->document),
            ],
        ];
    }
}
