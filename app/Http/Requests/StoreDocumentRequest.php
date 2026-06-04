<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use App\Models\DocumentType;
use App\Models\TenantService;
use App\Support\Compliance\ComplianceDomainAccess;
use App\Support\Documents\DocumentAreaAccess;
use App\Support\Documents\DocumentAssignmentManager;
use App\Support\Tenants\TenantRecordGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'company_id' => Company::getIdForCurrentUser($this->input('company_id')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'company_id' => 'nullable|integer|exists:companies,id',
            'owner_id' => 'nullable|integer|exists:users,id',
            'document_type_id' => 'nullable|integer',
            'document_framework_id' => 'nullable|integer',
            'document_number' => 'nullable|string|max:100',
            'reference' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:50',
            'status' => 'required|string|in:'.implode(',', array_keys(Document::getStatusOptions())),
            'document_area' => 'nullable|string|in:'.implode(',', array_keys(Document::documentAreaOptions())),
            'classification' => 'nullable|string|max:100',
            'retention_period' => 'nullable|string|max:100',
            'scope' => 'nullable|string|max:150',
            'issued_at' => 'nullable|date_format:Y-m-d',
            'effective_at' => 'nullable|date_format:Y-m-d',
            'next_review_at' => 'nullable|date_format:Y-m-d',
            'control_url' => 'nullable|url|max:2048',
            'summary' => 'nullable|string|max:65535',
            'notes' => 'nullable|string|max:65535',
            'primary_requirement_ids' => 'nullable|array',
            'primary_requirement_ids.*' => 'integer',
            'supporting_requirement_ids' => 'nullable|array',
            'supporting_requirement_ids.*' => 'integer',
            'requirement_evidence' => 'nullable|array',
            'requirement_evidence.*.covered_at' => 'nullable|date_format:Y-m-d',
            'requirement_evidence.*.notes' => 'nullable|string|max:65535',
            'tenant_service_ids_present' => 'nullable|boolean',
            'tenant_service_ids' => 'nullable|array',
            'tenant_service_ids.*' => 'integer',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $document = $this->route('document');
            $effectiveCompanyId = $this->integer('company_id') ?: ($document?->company_id);
            $effectiveTenantId = TenantRecordGuard::companyTenantId($effectiveCompanyId ? (int) $effectiveCompanyId : null);

            if ($this->filled('owner_id') && ! TenantRecordGuard::userCanBeReferencedByTenant($this->integer('owner_id'), $effectiveTenantId)) {
                $validator->errors()->add('owner_id', trans('validation.exists', ['attribute' => 'owner']));
            }

            if ($this->filled('document_type_id')) {
                $documentType = DocumentType::find($this->input('document_type_id'));
                if (! TenantRecordGuard::templateCanBeAppliedToCompany($documentType, $effectiveCompanyId ? (int) $effectiveCompanyId : null)) {
                    $validator->errors()->add('document_type_id', trans('validation.exists', ['attribute' => 'document type']));
                }
            }

            if ($this->filled('document_framework_id')) {
                $documentFramework = DocumentFramework::find($this->input('document_framework_id'));
                if (
                    ! $documentFramework
                    || $documentFramework->isSystemTemplate()
                    || ! TenantRecordGuard::templateCanBeAppliedToCompany($documentFramework, $effectiveCompanyId ? (int) $effectiveCompanyId : null)
                    || ! ComplianceDomainAccess::canAccessFramework($documentFramework, $this->user())
                ) {
                    $validator->errors()->add('document_framework_id', trans('validation.exists', ['attribute' => 'document framework']));
                }
            }

            if ($this->filled('document_area') && ! DocumentAreaAccess::canSet($this->user(), $this->input('document_area'))) {
                $validator->errors()->add('document_area', trans('validation.exists', ['attribute' => trans('admin/documents/form.document_area')]));
            }

            $tenantServiceIds = $this->normalizedTenantServiceIds();
            if (count($tenantServiceIds) > 0) {
                $validTenantServiceIds = TenantService::validIdsForCompany($tenantServiceIds, $effectiveCompanyId ? (int) $effectiveCompanyId : null);

                if (count($validTenantServiceIds) !== count($tenantServiceIds)) {
                    $validator->errors()->add('tenant_service_ids', trans('admin/tenantservices/general.invalid_for_company'));
                }
            }

            $frameworkId = $this->filled('document_framework_id') ? (int) $this->input('document_framework_id') : null;
            $requirementIds = collect($this->input('primary_requirement_ids', []))
                ->merge($this->input('supporting_requirement_ids', []))
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($requirementIds->count() > 0 && ! $frameworkId) {
                $validator->errors()->add('document_framework_id', trans('admin/documents/message.framework_required_for_requirements'));
            }

            if ($frameworkId && $requirementIds->count() > 0) {
                $validCount = DocumentFrameworkRequirement::query()
                    ->whereIn('id', $requirementIds->all())
                    ->where('document_framework_id', $frameworkId)
                    ->count();

                if ($validCount !== $requirementIds->count()) {
                    $validator->errors()->add('primary_requirement_ids', trans('admin/documents/message.invalid_requirements_for_framework'));
                }
            }

            if ($this->mappingSubmitted() && ! Gate::allows('mapRequirements', $document ?: Document::class)) {
                $validator->errors()->add('primary_requirement_ids', trans('general.insufficient_permissions'));
            }

            if (DocumentAssignmentManager::submissionRequested($this)) {
                $assignmentPayload = DocumentAssignmentManager::normalizedPayload($this);
                $assignmentValidator = Validator::make(
                    $assignmentPayload,
                    DocumentAssignmentManager::rules(),
                    [],
                    DocumentAssignmentManager::attributes()
                );

                foreach ($assignmentValidator->errors()->getMessages() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($field, $message);
                    }
                }

                if (! $document && ! $effectiveCompanyId) {
                    $validator->errors()->add('assignable_type', trans('admin/documents/message.assignment_save_document_first'));

                    return;
                }

                if ($assignmentValidator->errors()->isEmpty()) {
                    DocumentAssignmentManager::validateForDocument(
                        $validator,
                        $document,
                        $effectiveCompanyId,
                        $assignmentPayload
                    );
                }
            }
        });
    }

    public function mappingSubmitted(): bool
    {
        return $this->has('primary_requirement_ids')
            || $this->has('supporting_requirement_ids')
            || $this->has('requirement_evidence');
    }

    public function tenantServicesSubmitted(): bool
    {
        return $this->has('tenant_service_ids_present');
    }

    public function normalizedTenantServiceIds(): array
    {
        return collect($this->input('tenant_service_ids', []))
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
