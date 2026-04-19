<?php

namespace App\Http\Requests;

use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentFrameworkRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_framework_id' => 'required|integer|exists:document_frameworks,id',
            'parent_id' => 'nullable|integer|exists:document_framework_requirements,id',
            'code' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'domain' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:65535',
            'evidence_guidance' => 'nullable|string|max:65535',
            'applicability_notes' => 'nullable|string|max:65535',
            'owner_id' => 'nullable|integer|exists:users,id',
            'default_document_type_id' => 'nullable|integer|exists:document_types,id',
            'review_frequency_months' => 'nullable|integer|min:1|max:120',
            'sort_order' => 'nullable|integer|min:0|max:65535',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function prepareForValidation(): void
    {
        if (! $this->filled('document_framework_id') && $this->route('documentframework')) {
            $framework = $this->route('documentframework');
            $this->merge([
                'document_framework_id' => $framework?->id ?? $framework,
            ]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $requirementId = $this->route('documentframeworkrequirement')?->id ?? $this->route('documentframeworkrequirement');
            $frameworkId = (int) $this->input('document_framework_id');

            $query = DocumentFrameworkRequirement::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('document_framework_id', $frameworkId)
                ->where('code', $this->input('code'));

            if ($requirementId) {
                $query->where('id', '!=', $requirementId);
            }

            if ($query->exists()) {
                $validator->errors()->add('code', trans('validation.unique'));
            }

            if ($this->filled('parent_id')) {
                $parent = DocumentFrameworkRequirement::withoutGlobalScopes()->find($this->input('parent_id'));

                if (! $parent || (int) $parent->document_framework_id !== $frameworkId) {
                    $validator->errors()->add('parent_id', trans('validation.exists', ['attribute' => 'parent requirement']));
                }
            }

            if ($frameworkId > 0 && ! DocumentFramework::find($frameworkId)) {
                $validator->errors()->add('document_framework_id', trans('validation.exists', ['attribute' => 'document framework']));
            }
        });
    }
}
