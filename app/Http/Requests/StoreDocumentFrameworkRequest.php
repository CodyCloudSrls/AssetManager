<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\ComplianceDomain;
use App\Models\DocumentFramework;
use App\Models\Tenant;
use App\Support\Compliance\ComplianceDomainAccess;
use App\Support\Tenants\TenantRecordGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreDocumentFrameworkRequest extends FormRequest
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
            'compliance_domain' => ComplianceDomain::normalizeKey($this->input('compliance_domain')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:65535',
            'authority_name' => 'nullable|string|max:255',
            'framework_code' => 'nullable|string|max:80',
            'framework_type' => 'nullable|string|max:40',
            'compliance_domain' => 'nullable|string|max:80',
            'jurisdiction' => 'nullable|string|max:80',
            'version' => 'nullable|string|max:80',
            'effective_from' => 'nullable|date_format:Y-m-d',
            'effective_to' => 'nullable|date_format:Y-m-d|after_or_equal:effective_from',
            'owner_id' => 'nullable|integer|exists:users,id',
            'review_cadence_months' => 'nullable|integer|min:1|max:120',
            'status' => 'required|string|in:'.implode(',', array_keys(DocumentFramework::getStatusOptions())),
            'external_reference_url' => 'nullable|url|max:2048',
            'compliance_objective' => 'nullable|string|max:65535',
            'sort_order' => 'nullable|integer|min:0|max:65535',
            'is_active' => 'boolean',
            'company_id' => 'nullable|integer|exists:companies,id',
            'visibility_type' => 'required|string|in:private,descendants,global',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $documentFrameworkId = $this->route('documentframework')?->id ?? $this->route('documentframework');

            if (is_null($this->input('company_id')) && $this->input('visibility_type') !== DocumentFramework::VISIBILITY_GLOBAL) {
                $validator->errors()->add('company_id', trans('validation.required', ['attribute' => trans('general.company')]));
            }

            if ($this->input('visibility_type') === DocumentFramework::VISIBILITY_GLOBAL) {
                $canManageGlobalFramework = Tenant::canCurrentUserUseGlobalTenantContext();

                if (! $canManageGlobalFramework) {
                    $validator->errors()->add('visibility_type', trans('validation.in', ['attribute' => trans('general.template_visibility.label')]));
                }
            }

            if ($this->filled('compliance_domain')) {
                $domainKey = ComplianceDomain::normalizeKey($this->input('compliance_domain'));
                $this->merge(['compliance_domain' => $domainKey]);

                if (! ComplianceDomain::isValidKey($domainKey)) {
                    $validator->errors()->add('compliance_domain', trans('validation.exists', ['attribute' => trans('admin/documentframeworks/table.compliance_domain')]));
                } elseif (! ComplianceDomainAccess::canAccessDomain($domainKey, $this->user())) {
                    $validator->errors()->add('compliance_domain', trans('validation.exists', ['attribute' => trans('admin/documentframeworks/table.compliance_domain')]));
                }
            }

            foreach (['name', 'slug'] as $column) {
                if (! $this->filled($column)) {
                    continue;
                }

                $query = DocumentFramework::withoutGlobalScopes()
                    ->whereNull('deleted_at')
                    ->where($column, $this->input($column))
                    ->where(function ($companyQuery) {
                        if (is_null($this->input('company_id'))) {
                            $companyQuery->whereNull('company_id');
                        } else {
                            $companyQuery->where('company_id', $this->input('company_id'));
                        }
                    });

                if ($documentFrameworkId) {
                    $query->where('id', '!=', $documentFrameworkId);
                }

                if ($query->exists()) {
                    $validator->errors()->add($column, trans('validation.unique'));
                }
            }

            if ($this->filled('owner_id')) {
                $tenantId = TenantRecordGuard::companyTenantId($this->integer('company_id') ?: null);

                if (! TenantRecordGuard::userCanBeReferencedByTenant($this->integer('owner_id'), $tenantId)) {
                    $validator->errors()->add('owner_id', trans('validation.exists', ['attribute' => 'owner']));
                }
            }
        });
    }
}
