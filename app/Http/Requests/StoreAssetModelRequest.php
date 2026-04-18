<?php

namespace App\Http\Requests;

use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Company;
use App\Models\CustomFieldset;
use App\Models\Depreciation;
use App\Models\Manufacturer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Gate;

class StoreAssetModelRequest extends ImageUploadRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->route('model')) {
            return Gate::allows('update', $this->route('model'));
        }

        return Gate::allows('create', AssetModel::class);
    }

    public function prepareForValidation(): void
    {
        parent::prepareForValidation();

        [$companyId, $visibilityType] = Company::normalizeTemplateOwnership(
            $this->input('company_id'),
            $this->input('visibility_type'),
        );

        $this->merge([
            'company_id' => $companyId,
            'visibility_type' => $visibilityType,
        ]);

        if ($this->category_id) {
            if ($category = Category::find($this->category_id)) {
                $this->merge([
                    'category_type' => $category->category_type ?? null,
                ]);
            }
        }

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(
            [
                'category_type' => 'in:asset',
                'company_id' => 'nullable|integer|exists:companies,id',
                'visibility_type' => 'required|string|in:private,descendants,global',
            ],
            parent::rules(),
        );
    }

    public function messages(): array
    {
        $messages = ['category_type.in' => trans('admin/models/message.invalid_category_type')];

        return $messages;
    }

    public function response(array $errors)
    {
        return $this->redirector->back()->withInput()->withErrors($errors, $this->errorBag);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $modelId = $this->route('model')?->id ?? $this->route('model');
            $companyId = $this->input('company_id');
            $name = $this->input('name');
            $modelNumber = $this->filled('model_number') ? $this->input('model_number') : null;

            $duplicateModelQuery = AssetModel::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('name', $name)
                ->where(function ($query) use ($modelNumber) {
                    if (is_null($modelNumber)) {
                        $query->whereNull('model_number');
                    } else {
                        $query->where('model_number', $modelNumber);
                    }
                })
                ->where(function ($query) use ($companyId) {
                    if (is_null($companyId)) {
                        $query->whereNull('company_id');
                    } else {
                        $query->where('company_id', $companyId);
                    }
                });

            if ($modelId) {
                $duplicateModelQuery->where('id', '!=', $modelId);
            }

            if ($duplicateModelQuery->exists()) {
                $validator->errors()->add('name', trans('validation.unique'));
            }

            if ($this->filled('fieldset_id') && ! CustomFieldset::find($this->input('fieldset_id'))) {
                $validator->errors()->add('fieldset_id', trans('validation.exists', ['attribute' => 'fieldset']));
            }

            if ($this->filled('manufacturer_id') && ! Manufacturer::find($this->input('manufacturer_id'))) {
                $validator->errors()->add('manufacturer_id', trans('validation.exists', ['attribute' => 'manufacturer']));
            }

            if ($this->filled('depreciation_id') && ! Depreciation::find($this->input('depreciation_id'))) {
                $validator->errors()->add('depreciation_id', trans('validation.exists', ['attribute' => 'depreciation']));
            }
        });
    }
}
