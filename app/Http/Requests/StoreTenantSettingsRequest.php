<?php

namespace App\Http\Requests;

use App\Helpers\Helper;
use Illuminate\Foundation\Http\FormRequest;

class StoreTenantSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'default_locale' => 'required|string|in:'.implode(',', Helper::availableLanguageLocales()),
            'bootstrap_compliance_frameworks' => 'nullable|boolean',
        ];
    }
}
