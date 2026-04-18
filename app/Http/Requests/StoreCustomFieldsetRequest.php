<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\CustomFieldset;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomFieldsetRequest extends FormRequest
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
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'company_id' => 'nullable|integer|exists:companies,id',
            'visibility_type' => 'required|string|in:private,descendants,global',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $fieldsetId = $this->route('fieldset')?->id ?? $this->route('fieldset');

            $query = CustomFieldset::withoutGlobalScopes()
                ->where('name', $this->input('name'))
                ->where(function ($companyQuery) {
                    if (is_null($this->input('company_id'))) {
                        $companyQuery->whereNull('company_id');
                    } else {
                        $companyQuery->where('company_id', $this->input('company_id'));
                    }
                });

            if ($fieldsetId) {
                $query->where('id', '!=', $fieldsetId);
            }

            if ($query->exists()) {
                $validator->errors()->add('name', trans('validation.unique'));
            }
        });
    }
}
