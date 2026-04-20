<?php

namespace App\Http\Requests;

use App\Models\Asset;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentAssignment;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $assignableTypeToken = $this->assignmentField('assignment_assignable_type', 'assignable_type');
        $assignableTypeClass = DocumentAssignment::classForAssignableToken($assignableTypeToken);

        $assignableId = match ($assignableTypeToken) {
            DocumentAssignment::ASSIGNABLE_USER => Company::getIdFromInput($this->assignmentField('assignment_assignable_user_id', 'assignable_user_id')),
            DocumentAssignment::ASSIGNABLE_ASSET => Company::getIdFromInput($this->assignmentField('assignment_assignable_asset_id', 'assignable_asset_id')),
            DocumentAssignment::ASSIGNABLE_LOCATION => Company::getIdFromInput($this->assignmentField('assignment_assignable_location_id', 'assignable_location_id')),
            default => null,
        };

        $this->merge([
            'assignable_type' => $assignableTypeClass,
            'assignable_id' => $assignableId,
            'relation_type' => $this->assignmentField('assignment_relation_type', 'relation_type'),
            'status' => $this->assignmentField('assignment_status', 'status'),
            'issuer_id' => Company::getIdFromInput($this->assignmentField('assignment_issuer_id', 'issuer_id')),
            'reference_number' => $this->assignmentField('assignment_reference_number', 'reference_number'),
            'issued_at' => $this->assignmentField('assignment_issued_at', 'issued_at'),
            'effective_at' => $this->assignmentField('assignment_effective_at', 'effective_at'),
            'expires_at' => $this->assignmentField('assignment_expires_at', 'expires_at'),
            'renewal_due_at' => $this->assignmentField('assignment_renewal_due_at', 'renewal_due_at'),
            'completed_at' => $this->assignmentField('assignment_completed_at', 'completed_at'),
            'revoked_at' => $this->assignmentField('assignment_revoked_at', 'revoked_at'),
            'notes' => $this->assignmentField('assignment_notes', 'notes'),
        ]);
    }

    public function rules(): array
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

    public function attributes(): array
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $document = $this->route('document');

            if (! $document instanceof Document) {
                $validator->errors()->add('assignable_type', trans('admin/documents/message.assignment_document_missing'));

                return;
            }

            if (is_null($document->company_id)) {
                $validator->errors()->add('assignable_type', trans('admin/documents/message.assignment_requires_company'));

                return;
            }

            $assignable = $this->resolveAssignable();

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
                ->where('id', $document->company_id)
                ->value('tenant_id');
            $targetTenantId = Company::withoutGlobalScopes()
                ->where('id', $targetCompanyId)
                ->value('tenant_id');

            if ((int) ($documentTenantId ?? 0) === 0 || (int) ($documentTenantId ?? 0) !== (int) ($targetTenantId ?? 0)) {
                $validator->errors()->add('assignable_type', trans('admin/documents/message.assignment_target_wrong_tenant'));
            }

            if ($this->filled('issuer_id')) {
                $issuer = User::withoutGlobalScopes()->whereNull('deleted_at')->find($this->integer('issuer_id'));

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

            $this->validateDateCoherence($validator);
        });
    }

    public function resolvedAssignable()
    {
        return $this->resolveAssignable();
    }

    private function resolveAssignable()
    {
        $className = $this->input('assignable_type');
        $assignableId = $this->integer('assignable_id');

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

    private function validateDateCoherence($validator): void
    {
        $issuedAt = $this->dateValue('issued_at');
        $effectiveAt = $this->dateValue('effective_at');
        $expiresAt = $this->dateValue('expires_at');
        $renewalDueAt = $this->dateValue('renewal_due_at');
        $completedAt = $this->dateValue('completed_at');
        $revokedAt = $this->dateValue('revoked_at');

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

    private function dateValue(string $field): ?\Carbon\Carbon
    {
        if (! $this->filled($field)) {
            return null;
        }

        return \Carbon\Carbon::createFromFormat('Y-m-d', (string) $this->input($field));
    }

    private function assignmentField(string $prefixedKey, ?string $legacyKey = null): mixed
    {
        if ($this->has($prefixedKey)) {
            return $this->input($prefixedKey);
        }

        return $legacyKey ? $this->input($legacyKey) : null;
    }
}
