<?php

namespace App\Http\Requests;

use App\Helpers\Helper;
use App\Models\Tenant;
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
            'default_compliance_jurisdiction' => 'required|string|in:'.implode(',', Tenant::complianceJurisdictionValues()),
            'bootstrap_compliance_frameworks' => 'nullable|boolean',
        ];
    }
}
