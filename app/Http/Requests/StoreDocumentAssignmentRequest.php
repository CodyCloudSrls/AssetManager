<?php

namespace App\Http\Requests;

use App\Models\Document;
use App\Support\Documents\DocumentAssignmentManager;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->merge(DocumentAssignmentManager::normalizedPayload($this));
    }

    public function rules(): array
    {
        return DocumentAssignmentManager::rules();
    }

    public function attributes(): array
    {
        return DocumentAssignmentManager::attributes();
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $document = $this->route('document');

            if (! $document instanceof Document) {
                $validator->errors()->add('assignable_type', trans('admin/documents/message.assignment_document_missing'));

                return;
            }

            DocumentAssignmentManager::validateForDocument(
                $validator,
                $document,
                $document->company_id,
                $this->only(array_keys(DocumentAssignmentManager::rules()))
            );
        });
    }

    public function resolvedAssignable()
    {
        return DocumentAssignmentManager::resolvedAssignable(
            $this->only(array_keys(DocumentAssignmentManager::rules()))
        );
    }
}
