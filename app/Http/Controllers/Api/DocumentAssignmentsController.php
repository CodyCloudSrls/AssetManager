<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Concerns\AppliesTenantCompanyFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterRequest;
use App\Http\Transformers\DocumentAssignmentsTransformer;
use App\Models\Document;
use App\Models\DocumentAssignment;
use Illuminate\Http\JsonResponse;

class DocumentAssignmentsController extends Controller
{
    use AppliesTenantCompanyFilter;

    public function index(FilterRequest $request): JsonResponse|array
    {
        $this->authorize('index', Document::class);

        $assignments = DocumentAssignment::select('document_assignments.*')
            ->with('document.type', 'company', 'issuer', 'reviewer', 'assignable');

        if ($request->filled('filter') || $request->filled('search')) {
            $assignments->TextSearch($request->input('filter') ?: $request->input('search'));
        }

        if ($request->filled('company_id')) {
            $assignments->where('document_assignments.company_id', '=', $request->input('company_id'));
        }

        $this->applyTenantCompanyFilter($assignments, $request, 'document_assignments.company_id');

        if ($request->boolean('delegated_evidence')) {
            $assignments->whereIn('assignable_type', [
                DocumentAssignment::classForAssignableToken(DocumentAssignment::ASSIGNABLE_USER),
                DocumentAssignment::classForAssignableToken(DocumentAssignment::ASSIGNABLE_SUPPLIER),
            ])->whereIn('relation_type', [
                DocumentAssignment::RELATION_REQUIRED_FOR,
                DocumentAssignment::RELATION_EVIDENCE_FOR,
            ]);

            if (! $request->filled('status') && ! $request->boolean('include_all_statuses')) {
                $assignments->whereIn('status', [
                    DocumentAssignment::STATUS_PLANNED,
                    DocumentAssignment::STATUS_REQUIRED,
                ]);
            }
        }

        if ($request->filled('target_type')) {
            $assignableClass = DocumentAssignment::classForAssignableToken($request->input('target_type'));

            if ($assignableClass) {
                $assignments->where('assignable_type', $assignableClass);
            }
        }

        if ($request->filled('status')) {
            $assignments->where('status', '=', $request->input('status'));
        }

        if ($request->filled('approval_status')) {
            $assignments->where('approval_status', '=', $request->input('approval_status'));
        }

        if ($request->filled('relation_type')) {
            $assignments->where('relation_type', '=', $request->input('relation_type'));
        }

        if ($request->filled('document_id')) {
            $assignments->where('document_id', '=', (int) $request->input('document_id'));
        }

        if ($request->filled('assigned_user_id')) {
            $assignments->where('assignable_type', DocumentAssignment::classForAssignableToken(DocumentAssignment::ASSIGNABLE_USER))
                ->where('assignable_id', '=', (int) $request->input('assigned_user_id'));
        }

        if ($request->filled('assigned_supplier_id')) {
            $assignments->where('assignable_type', DocumentAssignment::classForAssignableToken(DocumentAssignment::ASSIGNABLE_SUPPLIER))
                ->where('assignable_id', '=', (int) $request->input('assigned_supplier_id'));
        }

        if ($request->filled('review_status')) {
            if ($request->input('review_status') === 'due') {
                $reviewWarningDays = $this->tenantFromRequest($request)?->documentReviewWarningDays() ?? 30;

                $assignments->whereNotNull('renewal_due_at')
                    ->whereDate('renewal_due_at', '>=', now()->toDateString())
                    ->whereDate('renewal_due_at', '<=', now()->addDays($reviewWarningDays)->toDateString());
            }

            if ($request->input('review_status') === 'overdue') {
                $assignments->where(function ($query) {
                    $query->whereDate('expires_at', '<', now()->toDateString())
                        ->orWhereDate('renewal_due_at', '<', now()->toDateString());
                });
            }
        }

        $allowedColumns = [
            'id',
            'relation_type',
            'status',
            'approval_status',
            'reference_number',
            'effective_at',
            'expires_at',
            'renewal_due_at',
            'reviewed_at',
            'created_at',
            'updated_at',
        ];

        $limit = app('api_limit_value');
        $offset = Helper::clampPaginationOffset($request->input('offset'), $assignments->count(), $limit);
        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($request->input('sort'), $allowedColumns, true) ? $request->input('sort') : 'renewal_due_at';
        $sortColumn = 'document_assignments.'.$sort;

        $assignments = $assignments
            ->orderByRaw('CASE WHEN '.$sortColumn.' IS NULL THEN 1 ELSE 0 END')
            ->orderBy($sortColumn, $order)
            ->orderByDesc('document_assignments.updated_at');

        $total = $assignments->count();
        $assignments = $assignments->skip($offset)->take($limit)->get();

        return (new DocumentAssignmentsTransformer)->transformDocumentAssignments($assignments, $total);
    }
}
