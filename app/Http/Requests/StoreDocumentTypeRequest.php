<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\DocumentType;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        [$companyId, $visibilityType] = Company::normalizeTemplateOwnership(
            $this->input('company_id'),
            $this->input('visibility_type'),
        );

        $this->merge([
            'company_id' => $companyId,
            'visibility_type' => $visibilityType,
            'slug' => $this->filled('slug') ? Str::slug($this->input('slug')) : Str::slug((string) $this->input('name')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:65535',
            'sort_order' => 'nullable|integer|min:0|max:65535',
            'is_active' => 'boolean',
            'company_id' => 'nullable|integer|exists:companies,id',
            'visibility_type' => 'required|string|in:private,descendants,global',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('visibility_type') !== DocumentType::VISIBILITY_GLOBAL && is_null($this->input('company_id'))) {
                $validator->errors()->add('company_id', trans('validation.required', ['attribute' => trans('general.company')]));
            }

            if ($this->input('visibility_type') === DocumentType::VISIBILITY_GLOBAL) {
                $canManageGlobalTemplate = Tenant::canCurrentUserUseGlobalTenantContext();

                if (! $canManageGlobalTemplate) {
                    $validator->errors()->add('visibility_type', trans('validation.in', ['attribute' => trans('general.template_visibility.label')]));
                }
            }

            $documentTypeId = $this->route('documenttype')?->id ?? $this->route('documenttype');

            foreach (['name', 'slug'] as $column) {
                if (! $this->filled($column)) {
                    continue;
                }

                $query = DocumentType::withoutGlobalScopes()
                    ->whereNull('deleted_at')
                    ->where($column, $this->input($column))
                    ->where(function ($companyQuery) {
                        if (is_null($this->input('company_id'))) {
                            $companyQuery->whereNull('company_id');
                        } else {
                            $companyQuery->where('company_id', $this->input('company_id'));
                        }
                    });

                if ($documentTypeId) {
                    $query->where('id', '!=', $documentTypeId);
                }

                if ($query->exists()) {
                    $validator->errors()->add($column, trans('validation.unique'));
                }
            }
        });
    }
}
