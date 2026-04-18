<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentFramework;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

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
        });
    }
}
