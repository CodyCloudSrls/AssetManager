<?php

namespace App\Support\Documents;

use App\Enums\ActionType;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentAssignment;
use App\Models\Location;
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
            default => null,
        };

        return [
            'assignable_type' => $assignableTypeClass,
            'assignable_id' => $assignableId,
            'relation_type' => static::assignmentField($request, 'assignment_relation_type', 'relation_type'),
            'status' => static::assignmentField($request, 'assignment_status', 'status'),
            'issuer_id' => Company::getIdFromInput(static::assignmentField($request, 'assignment_issuer_id', 'issuer_id')),
            'reference_number' => static::assignmentField($request, 'assignment_reference_number', 'reference_number'),
            'issued_at' => static::assignmentField($request, 'assignment_issued_at', 'issued_at'),
            'effective_at' => static::assignmentField($request, 'assignment_effective_at', 'effective_at'),
            'expires_at' => static::assignmentField($request, 'assignment_expires_at', 'expires_at'),
            'renewal_due_at' => static::assignmentField($request, 'assignment_renewal_due_at', 'renewal_due_at'),
            'completed_at' => static::assignmentField($request, 'assignment_completed_at', 'completed_at'),
            'revoked_at' => static::assignmentField($request, 'assignment_revoked_at', 'revoked_at'),
            'notes' => static::assignmentField($request, 'assignment_notes', 'notes'),
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
            'assignment_issuer_id',
            'assignment_reference_number',
            'assignment_issued_at',
            'assignment_effective_at',
            'assignment_expires_at',
            'assignment_renewal_due_at',
            'assignment_completed_at',
            'assignment_revoked_at',
            'assignment_notes',
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
            'issuer_id' => 'nullable|integer|exists:users,id',
            'reference_number' => 'nullable|string|max:100',
            'issued_at' => 'nullable|date_format:Y-m-d',
            'effective_at' => 'nullable|date_format:Y-m-d',
            'expires_at' => 'nullable|date_format:Y-m-d',
            'renewal_due_at' => 'nullable|date_format:Y-m-d',
            'completed_at' => 'nullable|date_format:Y-m-d',
            'revoked_at' => 'nullable|date_format:Y-m-d',
            'notes' => 'nullable|string|max:65535',
        ];
    }

    public static function attributes(): array
    {
        return [
            'assignable_type' => trans('admin/documents/form.assignable_type'),
            'assignable_id' => trans('admin/documents/form.assignable_target'),
            'relation_type' => trans('admin/documents/form.assignment_relation'),
            'status' => trans('admin/documents/form.assignment_status'),
            'issuer_id' => trans('admin/documents/form.assignment_issuer'),
            'reference_number' => trans('admin/documents/form.assignment_reference_number'),
            'issued_at' => trans('admin/documents/form.assignment_issued_at'),
            'effective_at' => trans('admin/documents/form.assignment_effective_at'),
            'expires_at' => trans('admin/documents/form.assignment_expires_at'),
            'renewal_due_at' => trans('admin/documents/form.assignment_renewal_due_at'),
            'completed_at' => trans('admin/documents/form.assignment_completed_at'),
            'revoked_at' => trans('admin/documents/form.assignment_revoked_at'),
            'notes' => trans('admin/documents/form.assignment_notes'),
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

        if (! empty($payload['issuer_id'])) {
            $issuer = User::withoutGlobalScopes()->whereNull('deleted_at')->find((int) $payload['issuer_id']);

            if (! $issuer) {
                $validator->errors()->add('issuer_id', trans('validation.exists', ['attribute' => 'issuer_id']));
            } else {
                $issuerTenantId = $issuer->company_id
                    ? Company::withoutGlobalScopes()->where('id', $issuer->company_id)->value('tenant_id')
                    : null;

                if (! $issuer->isSuperUser() && (int) ($issuerTenantId ?? 0) !== (int) ($documentTenantId ?? 0)) {
                    $validator->errors()->add('issuer_id', trans('admin/documents/message.assignment_issuer_wrong_tenant'));
                }
            }
        }

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
        $assignment->issuer_id = ! empty($payload['issuer_id']) ? (int) $payload['issuer_id'] : null;
        $assignment->reference_number = static::nullableString($payload['reference_number'] ?? null);
        $assignment->issued_at = $payload['issued_at'] ?: null;
        $assignment->effective_at = $payload['effective_at'] ?: null;
        $assignment->expires_at = $payload['expires_at'] ?: null;
        $assignment->renewal_due_at = $payload['renewal_due_at'] ?: null;
        $assignment->completed_at = $payload['completed_at'] ?: null;
        $assignment->revoked_at = $payload['revoked_at'] ?: null;
        $assignment->notes = static::nullableString($payload['notes'] ?? null);
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

    private static function validateDateCoherence($validator, array $payload): void
    {
        $issuedAt = static::dateValue($payload['issued_at'] ?? null);
        $effectiveAt = static::dateValue($payload['effective_at'] ?? null);
        $expiresAt = static::dateValue($payload['expires_at'] ?? null);
        $renewalDueAt = static::dateValue($payload['renewal_due_at'] ?? null);
        $completedAt = static::dateValue($payload['completed_at'] ?? null);
        $revokedAt = static::dateValue($payload['revoked_at'] ?? null);

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
}
