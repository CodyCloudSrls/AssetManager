<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use App\Models\DocumentType;
use App\Models\User;
use App\Support\Documents\DocumentAssignmentManager;
use Illuminate\Foundation\Http\FormRequest;
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
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('owner_id') && ! User::find($this->input('owner_id'))) {
                $validator->errors()->add('owner_id', trans('validation.exists', ['attribute' => 'owner']));
            }

            if ($this->filled('document_type_id') && ! DocumentType::find($this->input('document_type_id'))) {
                $validator->errors()->add('document_type_id', trans('validation.exists', ['attribute' => 'document type']));
            }

            if ($this->filled('document_framework_id') && ! DocumentFramework::find($this->input('document_framework_id'))) {
                $validator->errors()->add('document_framework_id', trans('validation.exists', ['attribute' => 'document framework']));
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

                $document = $this->route('document');
                $effectiveCompanyId = $this->integer('company_id') ?: ($document?->company_id);

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
}
