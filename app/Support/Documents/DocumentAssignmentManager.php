<?php

namespace App\Support\Documents;

use App\Enums\ActionType;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentAssignment;
use App\Models\DocumentAssignmentEvent;
use App\Models\Location;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DocumentAssignmentManager
{
    public static function normalizedPayload(Request $request): array
    {
        $assignableTypeToken = static::assignmentField($request, 'assignment_assignable_type', 'assignable_type');
        $assignableTypeClass = DocumentAssignment::classForAssignableToken($assignableTypeToken);

        $assignableId = match ($assignableTypeToken) {
            DocumentAssignment::ASSIGNABLE_USER => Company::getIdFromInput(static::assignmentField($request, 'assignment_assignable_user_id', 'assignable_user_id')),
            DocumentAssignment::ASSIGNABLE_ASSET => Company::getIdFromInput(static::assignmentField($request, 'assignment_assignable_asset_id', 'assignable_asset_id')),
            DocumentAssignment::ASSIGNABLE_LOCATION => Company::getIdFromInput(static::assignmentField($request, 'assignment_assignable_location_id', 'assignable_location_id')),
            DocumentAssignment::ASSIGNABLE_SUPPLIER => Company::getIdFromInput(static::assignmentField($request, 'assignment_assignable_supplier_id', 'assignable_supplier_id')),
            DocumentAssignment::ASSIGNABLE_CUSTOMER => Company::getIdFromInput(static::assignmentField($request, 'assignment_assignable_customer_id', 'assignable_customer_id')),
            default => null,
        };

        return [
            'assignable_type' => $assignableTypeClass,
            'assignable_id' => $assignableId,
            'relation_type' => static::assignmentField($request, 'assignment_relation_type', 'relation_type'),
            'status' => static::assignmentField($request, 'assignment_status', 'status'),
            'approval_status' => static::assignmentField($request, 'assignment_approval_status', 'approval_status') ?: DocumentAssignment::APPROVAL_PENDING,
            'issuer_id' => Company::getIdFromInput(static::assignmentField($request, 'assignment_issuer_id', 'issuer_id')),
            'reviewer_id' => Company::getIdFromInput(static::assignmentField($request, 'assignment_reviewer_id', 'reviewer_id')),
            'reference_number' => static::assignmentField($request, 'assignment_reference_number', 'reference_number'),
            'issued_at' => static::assignmentField($request, 'assignment_issued_at', 'issued_at'),
            'effective_at' => static::assignmentField($request, 'assignment_effective_at', 'effective_at'),
            'expires_at' => static::assignmentField($request, 'assignment_expires_at', 'expires_at'),
            'renewal_due_at' => static::assignmentField($request, 'assignment_renewal_due_at', 'renewal_due_at'),
            'completed_at' => static::assignmentField($request, 'assignment_completed_at', 'completed_at'),
            'revoked_at' => static::assignmentField($request, 'assignment_revoked_at', 'revoked_at'),
            'reviewed_at' => static::assignmentField($request, 'assignment_reviewed_at', 'reviewed_at'),
            'notes' => static::assignmentField($request, 'assignment_notes', 'notes'),
            'review_notes' => static::assignmentField($request, 'assignment_review_notes', 'review_notes'),
        ];
    }

    public static function submissionRequested(Request $request): bool
    {
        if ($request->boolean('save_assignment')) {
            return true;
        }

        foreach ([
            'assignment_assignable_user_id',
            'assignment_assignable_asset_id',
            'assignment_assignable_location_id',
            'assignment_assignable_supplier_id',
            'assignment_assignable_customer_id',
            'assignment_issuer_id',
            'assignment_reviewer_id',
            'assignment_reference_number',
            'assignment_issued_at',
            'assignment_effective_at',
            'assignment_expires_at',
            'assignment_renewal_due_at',
            'assignment_completed_at',
            'assignment_revoked_at',
            'assignment_reviewed_at',
            'assignment_notes',
            'assignment_review_notes',
        ] as $field) {
            if (static::hasMeaningfulValue($request->input($field))) {
                return true;
            }
        }

        return false;
    }

    public static function rules(): array
    {
        return [
            'assignable_type' => 'required|string|in:'.implode(',', DocumentAssignment::assignableClassMap()),
            'assignable_id' => 'required|integer',
            'relation_type' => 'required|string|in:'.implode(',', array_keys(DocumentAssignment::relationTypeOptions())),
            'status' => 'required|string|in:'.implode(',', array_keys(DocumentAssignment::statusOptions())),
            'approval_status' => 'required|string|in:'.implode(',', array_keys(DocumentAssignment::approvalStatusOptions())),
            'issuer_id' => 'nullable|integer|exists:users,id',
            'reviewer_id' => 'nullable|integer|exists:users,id',
            'reference_number' => 'nullable|string|max:100',
            'issued_at' => 'nullable|date_format:Y-m-d',
            'effective_at' => 'nullable|date_format:Y-m-d',
            'expires_at' => 'nullable|date_format:Y-m-d',
            'renewal_due_at' => 'nullable|date_format:Y-m-d',
            'completed_at' => 'nullable|date_format:Y-m-d',
            'revoked_at' => 'nullable|date_format:Y-m-d',
            'reviewed_at' => 'nullable|date_format:Y-m-d',
            'notes' => 'nullable|string|max:65535',
            'review_notes' => 'nullable|string|max:65535',
        ];
    }

    public static function attributes(): array
    {
        return [
            'assignable_type' => trans('admin/documents/form.assignable_type'),
            'assignable_id' => trans('admin/documents/form.assignable_target'),
            'relation_type' => trans('admin/documents/form.assignment_relation'),
            'status' => trans('admin/documents/form.assignment_status'),
            'approval_status' => trans('admin/documents/form.assignment_approval_status'),
            'issuer_id' => trans('admin/documents/form.assignment_issuer'),
            'reviewer_id' => trans('admin/documents/form.assignment_reviewer'),
            'reference_number' => trans('admin/documents/form.assignment_reference_number'),
            'issued_at' => trans('admin/documents/form.assignment_issued_at'),
            'effective_at' => trans('admin/documents/form.assignment_effective_at'),
            'expires_at' => trans('admin/documents/form.assignment_expires_at'),
            'renewal_due_at' => trans('admin/documents/form.assignment_renewal_due_at'),
            'completed_at' => trans('admin/documents/form.assignment_completed_at'),
            'revoked_at' => trans('admin/documents/form.assignment_revoked_at'),
            'reviewed_at' => trans('admin/documents/form.assignment_reviewed_at'),
            'notes' => trans('admin/documents/form.assignment_notes'),
            'review_notes' => trans('admin/documents/form.assignment_review_notes'),
        ];
    }

    public static function resolvedAssignable(array $payload)
    {
        $className = $payload['assignable_type'] ?? null;
        $assignableId = (int) ($payload['assignable_id'] ?? 0);

        if (! $className || ! $assignableId) {
            return null;
        }

        return match ($className) {
            User::class => User::withoutGlobalScopes()->whereNull('deleted_at')->find($assignableId),
            Asset::class => Asset::withoutGlobalScopes()->whereNull('deleted_at')->find($assignableId),
            Location::class => Location::withoutGlobalScopes()->whereNull('deleted_at')->find($assignableId),
            Supplier::class => Supplier::withoutGlobalScopes()->whereNull('deleted_at')->find($assignableId),
            Customer::class => Customer::withoutGlobalScopes()->whereNull('deleted_at')->find($assignableId),
            default => null,
        };
    }

    public static function validateForDocument($validator, ?Document $document, ?int $documentCompanyId, array $payload): void
    {
        if (! $documentCompanyId) {
            $validator->errors()->add('assignable_type', trans('admin/documents/message.assignment_requires_company'));

            return;
        }

        $assignable = static::resolvedAssignable($payload);

        if (! $assignable) {
            $validator->errors()->add('assignable_type', trans('admin/documents/message.assignment_target_invalid'));

            return;
        }

        $targetCompanyId = (int) ($assignable->company_id ?? 0);
        if ($targetCompanyId === 0) {
            $validator->errors()->add('assignable_type', trans('admin/documents/message.assignment_target_invalid'));

            return;
        }

        $documentTenantId = Company::withoutGlobalScopes()
            ->where('id', $documentCompanyId)
            ->value('tenant_id');
        $targetTenantId = Company::withoutGlobalScopes()
            ->where('id', $targetCompanyId)
            ->value('tenant_id');

        if ((int) ($documentTenantId ?? 0) === 0 || (int) ($documentTenantId ?? 0) !== (int) ($targetTenantId ?? 0)) {
            $validator->errors()->add('assignable_type', trans('admin/documents/message.assignment_target_wrong_tenant'));
        }

        static::validateUserTenant($validator, $payload['issuer_id'] ?? null, 'issuer_id', $documentTenantId, trans('admin/documents/message.assignment_issuer_wrong_tenant'));
        static::validateUserTenant($validator, $payload['reviewer_id'] ?? null, 'reviewer_id', $documentTenantId, trans('admin/documents/message.assignment_reviewer_wrong_tenant'));

        static::validateDateCoherence($validator, $payload);
    }

    public static function fillAssignment(DocumentAssignment $assignment, array $payload, Document $document): void
    {
        $assignable = static::resolvedAssignable($payload);

        $assignment->document_id = $document->id;
        $assignment->company_id = (int) $assignable->company_id;
        $assignment->assignable_type = $payload['assignable_type'];
        $assignment->assignable_id = (int) $payload['assignable_id'];
        $assignment->relation_type = $payload['relation_type'];
        $assignment->status = $payload['status'];
        $assignment->approval_status = $payload['approval_status'] ?? DocumentAssignment::APPROVAL_PENDING;
        $assignment->issuer_id = ! empty($payload['issuer_id']) ? (int) $payload['issuer_id'] : null;
        $assignment->reviewer_id = static::reviewerId($assignment, $payload);
        $assignment->reference_number = static::nullableString($payload['reference_number'] ?? null);
        $assignment->issued_at = $payload['issued_at'] ?: null;
        $assignment->effective_at = $payload['effective_at'] ?: null;
        $assignment->expires_at = $payload['expires_at'] ?: null;
        $assignment->renewal_due_at = $payload['renewal_due_at'] ?: null;
        $assignment->completed_at = $payload['completed_at'] ?: null;
        $assignment->revoked_at = $payload['revoked_at'] ?: null;
        $assignment->reviewed_at = static::reviewedAt($assignment, $payload);
        $assignment->notes = static::nullableString($payload['notes'] ?? null);
        $assignment->review_notes = static::nullableString($payload['review_notes'] ?? null);
    }

    public static function logAssignmentAction(Document $document, DocumentAssignment $assignment, ActionType $actionType): void
    {
        $logAction = new Actionlog;
        $logAction->item_type = Document::class;
        $logAction->item_id = $document->id;
        $logAction->target_type = $assignment->assignable_type;
        $logAction->target_id = $assignment->assignable_id;
        $logAction->created_at = now();
        $logAction->action_date = now();
        $logAction->created_by = auth()->id();
        $logAction->note = implode(' | ', array_filter([
            $assignment->assignable_type_label,
            $assignment->assignable_display_name,
            $assignment->relation_type_label,
            $assignment->status_label,
            $assignment->reference_number ? trans('admin/documents/form.assignment_reference_number').': '.$assignment->reference_number : null,
        ]));
        $logAction->logaction($actionType);
    }

    public static function logAssignmentEvent(Document $document, DocumentAssignment $assignment, string $eventType, array $oldValues = [], array $newValues = [], ?string $note = null): DocumentAssignmentEvent
    {
        $eventData = [
            'document_assignment_id' => $assignment->id,
            'document_id' => $document->id,
            'company_id' => $assignment->company_id,
            'event_type' => $eventType,
            'approval_status' => $assignment->approval_status,
            'actor_id' => auth()->id(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'note' => $note,
            'remote_ip' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
            'created_at' => now(),
        ];

        return DocumentAssignmentEvent::create(array_merge(
            $eventData,
            static::assignmentEventHashes($document, $eventData)
        ));
    }

    public static function auditSnapshot(DocumentAssignment $assignment): array
    {
        return collect([
            'assignable_type' => DocumentAssignment::tokenForAssignableClass($assignment->assignable_type),
            'assignable_id' => $assignment->assignable_id,
            'relation_type' => $assignment->relation_type,
            'status' => $assignment->status,
            'approval_status' => $assignment->approval_status,
            'issuer_id' => $assignment->issuer_id,
            'reviewer_id' => $assignment->reviewer_id,
            'reference_number' => $assignment->reference_number,
            'issued_at' => static::dateForAudit($assignment->issued_at),
            'effective_at' => static::dateForAudit($assignment->effective_at),
            'expires_at' => static::dateForAudit($assignment->expires_at),
            'renewal_due_at' => static::dateForAudit($assignment->renewal_due_at),
            'completed_at' => static::dateForAudit($assignment->completed_at),
            'revoked_at' => static::dateForAudit($assignment->revoked_at),
            'reviewed_at' => static::dateForAudit($assignment->reviewed_at),
            'notes' => $assignment->notes,
            'review_notes' => $assignment->review_notes,
        ])->filter(fn ($value) => ! is_null($value) && $value !== '')->all();
    }

    public static function auditChanges(array $before, array $after): array
    {
        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
        $oldValues = [];
        $newValues = [];

        foreach ($keys as $key) {
            $oldValue = $before[$key] ?? null;
            $newValue = $after[$key] ?? null;

            if ($oldValue === $newValue) {
                continue;
            }

            $oldValues[$key] = $oldValue;
            $newValues[$key] = $newValue;
        }

        return [$oldValues, $newValues];
    }

    private static function assignmentEventHashes(Document $document, array $eventData): array
    {
        $previousHash = DocumentAssignmentEvent::query()
            ->where('document_id', $document->id)
            ->whereNotNull('event_hash')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->value('event_hash');

        $payload = static::canonicalPayload($eventData);
        $payloadHash = hash('sha256', static::stableJson($payload));
        $eventHash = hash('sha256', static::stableJson([
            'algorithm' => 'sha256',
            'payload_hash' => $payloadHash,
            'previous_hash' => $previousHash,
        ]));

        return [
            'hash_algorithm' => 'sha256',
            'previous_hash' => $previousHash,
            'payload_hash' => $payloadHash,
            'event_hash' => $eventHash,
        ];
    }

    private static function canonicalPayload(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $payload[$key] = $value->format(DATE_ATOM);
            } elseif (is_array($value)) {
                $payload[$key] = static::canonicalPayload($value);
            }
        }

        ksort($payload);

        return $payload;
    }

    private static function stableJson(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private static function validateDateCoherence($validator, array $payload): void
    {
        $issuedAt = static::dateValue($payload['issued_at'] ?? null);
        $effectiveAt = static::dateValue($payload['effective_at'] ?? null);
        $expiresAt = static::dateValue($payload['expires_at'] ?? null);
        $renewalDueAt = static::dateValue($payload['renewal_due_at'] ?? null);
        $completedAt = static::dateValue($payload['completed_at'] ?? null);
        $revokedAt = static::dateValue($payload['revoked_at'] ?? null);
        $reviewedAt = static::dateValue($payload['reviewed_at'] ?? null);

        if ($effectiveAt && $issuedAt && $effectiveAt->lt($issuedAt)) {
            $validator->errors()->add('effective_at', trans('validation.after_or_equal', ['attribute' => 'effective_at', 'date' => 'issued_at']));
        }

        if ($expiresAt && $effectiveAt && $expiresAt->lt($effectiveAt)) {
            $validator->errors()->add('expires_at', trans('validation.after_or_equal', ['attribute' => 'expires_at', 'date' => 'effective_at']));
        }

        if ($renewalDueAt && $effectiveAt && $renewalDueAt->lt($effectiveAt)) {
            $validator->errors()->add('renewal_due_at', trans('validation.after_or_equal', ['attribute' => 'renewal_due_at', 'date' => 'effective_at']));
        }

        if ($renewalDueAt && $expiresAt && $renewalDueAt->gt($expiresAt)) {
            $validator->errors()->add('renewal_due_at', trans('validation.before_or_equal', ['attribute' => 'renewal_due_at', 'date' => 'expires_at']));
        }

        if ($completedAt && $effectiveAt && $completedAt->lt($effectiveAt)) {
            $validator->errors()->add('completed_at', trans('validation.after_or_equal', ['attribute' => 'completed_at', 'date' => 'effective_at']));
        }

        if ($revokedAt && $effectiveAt && $revokedAt->lt($effectiveAt)) {
            $validator->errors()->add('revoked_at', trans('validation.after_or_equal', ['attribute' => 'revoked_at', 'date' => 'effective_at']));
        }

        if ($reviewedAt && $effectiveAt && $reviewedAt->lt($effectiveAt)) {
            $validator->errors()->add('reviewed_at', trans('validation.after_or_equal', ['attribute' => 'reviewed_at', 'date' => 'effective_at']));
        }
    }

    private static function dateValue(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $value);
    }

    private static function assignmentField(Request $request, string $prefixedKey, ?string $legacyKey = null): mixed
    {
        if ($request->has($prefixedKey)) {
            return $request->input($prefixedKey);
        }

        return $legacyKey ? $request->input($legacyKey) : null;
    }

    private static function hasMeaningfulValue(mixed $value): bool
    {
        if (is_null($value)) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return (bool) $value;
    }

    private static function nullableString(mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function validateUserTenant($validator, mixed $userId, string $field, int|string|null $documentTenantId, string $tenantErrorMessage): void
    {
        if (empty($userId)) {
            return;
        }

        $user = User::withoutGlobalScopes()->whereNull('deleted_at')->find((int) $userId);

        if (! $user) {
            $validator->errors()->add($field, trans('validation.exists', ['attribute' => $field]));

            return;
        }

        $userTenantId = $user->company_id
            ? Company::withoutGlobalScopes()->where('id', $user->company_id)->value('tenant_id')
            : null;

        if (! $user->isSuperAdmin() && (int) ($userTenantId ?? 0) !== (int) ($documentTenantId ?? 0)) {
            $validator->errors()->add($field, $tenantErrorMessage);
        }
    }

    private static function reviewerId(DocumentAssignment $assignment, array $payload): ?int
    {
        if (! empty($payload['reviewer_id'])) {
            return (int) $payload['reviewer_id'];
        }

        if (
            in_array($assignment->approval_status, [DocumentAssignment::APPROVAL_APPROVED, DocumentAssignment::APPROVAL_REJECTED], true)
            && auth()->id()
        ) {
            return auth()->id();
        }

        return null;
    }

    private static function reviewedAt(DocumentAssignment $assignment, array $payload): mixed
    {
        if (! empty($payload['reviewed_at'])) {
            return $payload['reviewed_at'];
        }

        if (in_array($assignment->approval_status, [DocumentAssignment::APPROVAL_APPROVED, DocumentAssignment::APPROVAL_REJECTED], true)) {
            return $assignment->reviewed_at ?: now();
        }

        return null;
    }

    private static function dateForAudit(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->toDateTimeString();
        }

        return (string) $value;
    }
}
