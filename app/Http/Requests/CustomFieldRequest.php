<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\CustomField;
use App\Models\CustomFieldset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomFieldRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:191',
                Rule::unique('custom_fields', 'name')->ignore($this->fieldId()),
            ],
            'element' => 'required|in:text,listbox,textarea,checkbox,radio',
            'format' => 'nullable|string|max:191',
            'field_values' => 'nullable|string',
            'field_encrypted' => 'nullable|boolean',
            'help_text' => 'nullable|string|max:191',
            'show_in_email' => 'nullable|boolean',
            'show_in_requestable_list' => 'nullable|boolean',
            'is_unique' => 'nullable|boolean',
            'display_in_user_view' => 'nullable|boolean',
            'auto_add_to_fieldsets' => 'nullable|boolean',
            'show_in_listview' => 'nullable|boolean',
            'display_checkin' => 'nullable|boolean',
            'display_checkout' => 'nullable|boolean',
            'display_audit' => 'nullable|boolean',
            'company_id' => 'nullable|integer|exists:companies,id',
            'visibility_type' => 'required|string|in:private,descendants,global',
            'associate_fieldsets.*' => 'nullable|integer|exists:custom_fieldsets,id',
            'custom_format' => 'nullable|valid_regex',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $fieldsetIds = $this->associatedFieldsetIds();

            if (count($fieldsetIds) === 0) {
                return;
            }

            $fieldsets = CustomFieldset::whereIn('id', $fieldsetIds)->get()->keyBy('id');
            $fieldTemplate = (object) [
                'company_id' => $this->input('company_id'),
                'visibility_type' => $this->input('visibility_type'),
            ];

            foreach ($fieldsetIds as $fieldsetId) {
                $fieldset = $fieldsets->get($fieldsetId);

                if (! $fieldset) {
                    $validator->errors()->add('associate_fieldsets.'.$fieldsetId, trans('admin/custom_fields/message/does_not_exist'));

                    continue;
                }

                if (! Company::templateCanBeAppliedToCompany($fieldTemplate, $fieldset->company_id)) {
                    $validator->errors()->add('associate_fieldsets.'.$fieldsetId, trans('validation.exists'));
                }
            }
        });
    }

    public function associatedFieldsetIds(): array
    {
        $submitted = $this->input('associate_fieldsets', []);

        if (! is_array($submitted)) {
            return [];
        }

        $ids = [];

        foreach ($submitted as $key => $value) {
            $candidate = is_numeric($value) ? $value : $key;

            if (is_numeric($candidate)) {
                $ids[] = (int) $candidate;
            }
        }

        return array_values(array_unique($ids));
    }

    public function messages(): array
    {
        return [
            'associate_fieldsets.*.exists' => trans('admin/custom_fields/message/does_not_exist'),
        ];
    }

    private function fieldId(): int|string|null
    {
        $field = $this->route('field');

        if ($field instanceof CustomField) {
            return $field->id;
        }

        $fieldId = $this->route('field_id');

        if ($fieldId instanceof CustomField) {
            return $fieldId->id;
        }

        return $field ?? $fieldId;
    }
}
