<?php

namespace App\Models;

use App\Models\Traits\Searchable;
use App\Models\Traits\TenantTemplateTrait;
use App\Presenters\Presentable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Watson\Validating\ValidatingTrait;

class DocumentFramework extends SnipeModel
{
    use HasFactory;
    use Presentable;
    use Searchable;
    use SoftDeletes;
    use TenantTemplateTrait;
    use ValidatingTrait;

    protected $table = 'document_frameworks';

    protected $hidden = ['created_by', 'deleted_at'];

    protected $rules = [
        'name' => 'required|string|max:255',
        'slug' => 'nullable|string|max:255',
        'description' => 'nullable|string|max:65535',
        'authority_name' => 'nullable|string|max:255',
        'framework_code' => 'nullable|string|max:80',
        'framework_type' => 'nullable|string|max:40',
        'compliance_domain' => 'nullable|string|max:40',
        'jurisdiction' => 'nullable|string|max:80',
        'version' => 'nullable|string|max:80',
        'effective_from' => 'nullable|date',
        'effective_to' => 'nullable|date',
        'owner_id' => 'nullable|integer|exists:users,id',
        'review_cadence_months' => 'nullable|integer|min:1|max:120',
        'status' => 'required|string|in:draft,active,superseded,archived',
        'external_reference_url' => 'nullable|url|max:2048',
        'compliance_objective' => 'nullable|string|max:65535',
        'sort_order' => 'nullable|integer|min:0|max:65535',
        'is_active' => 'boolean',
        'company_id' => 'nullable|integer|exists:companies,id',
        'visibility_type' => 'required|string|in:private,descendants,global',
        'is_system_template' => 'boolean',
        'source_framework_id' => 'nullable|integer|exists:document_frameworks,id',
        'source_pack_key' => 'nullable|string|max:80',
        'source_pack_version' => 'nullable|string|max:80',
        'locale' => 'nullable|string|max:20',
    ];

    protected $injectUniqueIdentifier = true;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'authority_name',
        'framework_code',
        'framework_type',
        'compliance_domain',
        'jurisdiction',
        'version',
        'effective_from',
        'effective_to',
        'owner_id',
        'review_cadence_months',
        'status',
        'external_reference_url',
        'compliance_objective',
        'sort_order',
        'is_active',
        'created_by',
        'company_id',
        'visibility_type',
        'is_system_template',
        'source_framework_id',
        'source_pack_key',
        'source_pack_version',
        'locale',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'created_by' => 'integer',
        'company_id' => 'integer',
        'is_system_template' => 'boolean',
        'source_framework_id' => 'integer',
        'owner_id' => 'integer',
        'review_cadence_months' => 'integer',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    protected $searchableAttributes = [
        'name',
        'slug',
        'description',
        'authority_name',
        'framework_code',
        'framework_type',
        'compliance_domain',
        'jurisdiction',
        'version',
        'status',
        'visibility_type',
        'source_pack_key',
        'source_pack_version',
        'locale',
    ];

    protected $searchableRelations = [
        'company' => ['name'],
        'adminuser' => ['first_name', 'last_name', 'display_name'],
        'owner' => ['first_name', 'last_name', 'display_name', 'username'],
    ];

    protected $searchableCounts = [
        'documents_count',
        'requirements_count',
    ];

    public static function getFrameworkTypeOptions(): array
    {
        return [
            'law' => trans('admin/documentframeworks/general.types.law'),
            'regulation' => trans('admin/documentframeworks/general.types.regulation'),
            'standard' => trans('admin/documentframeworks/general.types.standard'),
            'policy' => trans('admin/documentframeworks/general.types.policy'),
            'internal' => trans('admin/documentframeworks/general.types.internal'),
            'custom' => trans('admin/documentframeworks/general.types.custom'),
        ];
    }

    public static function complianceDomainOptions(): array
    {
        return [
            'nis2' => trans('admin/documentframeworks/general.compliance_domains.nis2'),
            'gdpr' => trans('admin/documentframeworks/general.compliance_domains.gdpr'),
            'iso27001' => trans('admin/documentframeworks/general.compliance_domains.iso27001'),
            'supplier_risk' => trans('admin/documentframeworks/general.compliance_domains.supplier_risk'),
            'internal' => trans('admin/documentframeworks/general.compliance_domains.internal'),
            'custom' => trans('admin/documentframeworks/general.compliance_domains.custom'),
        ];
    }

    public static function looksLikeNis2Domain(?string $complianceDomain, array $metadata = []): bool
    {
        if ($complianceDomain === 'nis2') {
            return true;
        }

        foreach (['framework_code', 'slug', 'name'] as $field) {
            $value = $metadata[$field] ?? null;

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $normalized = Str::lower(Str::ascii($value));

            if (preg_match('/\bnis[\s_-]*2\b/', $normalized) === 1) {
                return true;
            }
        }

        return false;
    }

    public static function getStatusOptions(): array
    {
        return [
            'draft' => trans('admin/documentframeworks/general.statuses.draft'),
            'active' => trans('admin/documentframeworks/general.statuses.active'),
            'superseded' => trans('admin/documentframeworks/general.statuses.superseded'),
            'archived' => trans('admin/documentframeworks/general.statuses.archived'),
        ];
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'document_framework_id');
    }

    public function requirements()
    {
        return $this->hasMany(DocumentFrameworkRequirement::class, 'document_framework_id');
    }

    public function sourceFramework()
    {
        return $this->belongsTo(self::class, 'source_framework_id')->withTrashed();
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id')->withTrashed();
    }

    public function activeRequirements()
    {
        return $this->requirements()->where('is_active', true)->whereNull('deleted_at');
    }

    public function getCoverageSummaryAttribute(): array
    {
        $requirements = $this->relationLoaded('requirements')
            ? $this->requirements
            : $this->requirements()
                ->withCount('documents')
                ->get();

        $total = $requirements->count();
        $covered = 0;
        $atRisk = 0;
        $supportingOnly = 0;
        $missing = 0;

        foreach ($requirements as $requirement) {
            $status = $requirement->coverage_status;

            if ($status === DocumentFrameworkRequirement::COVERAGE_COVERED) {
                $covered++;
            } elseif ($status === DocumentFrameworkRequirement::COVERAGE_AT_RISK) {
                $atRisk++;
            } elseif ($status === DocumentFrameworkRequirement::COVERAGE_SUPPORTING_ONLY) {
                $supportingOnly++;
            } else {
                $missing++;
            }
        }

        return [
            'total' => $total,
            'covered' => $covered,
            'at_risk' => $atRisk,
            'supporting_only' => $supportingOnly,
            'missing' => $missing,
            'coverage_percent' => $total > 0 ? (int) floor(($covered / $total) * 100) : 0,
        ];
    }

    public function isDeletable()
    {
        return Gate::allows('delete', $this)
            && (($this->documents_count ?? $this->documents()->count()) === 0)
            && (($this->requirements_count ?? $this->requirements()->count()) === 0)
            && ($this->deleted_at == '');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeOperational($query)
    {
        return $query
            ->where($this->getTable().'.is_system_template', false)
            ->whereNotNull($this->getTable().'.company_id');
    }

    public function scopeSystemTemplates($query)
    {
        return $query->where($this->getTable().'.is_system_template', true);
    }

    public function scopeOrderByCreatedBy($query, $order)
    {
        return $query->leftJoin('users as admin_sort', 'document_frameworks.created_by', '=', 'admin_sort.id')
            ->select('document_frameworks.*')
            ->orderBy('admin_sort.first_name', $order)
            ->orderBy('admin_sort.last_name', $order);
    }

    public function setSlugAttribute($value)
    {
        $source = $value ?: $this->attributes['name'] ?? null;
        $this->attributes['slug'] = $source ? Str::slug($source) : null;
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;

        if (! array_key_exists('slug', $this->attributes) || blank($this->attributes['slug'])) {
            $this->attributes['slug'] = $value ? Str::slug($value) : null;
        }
    }

    public function setEffectiveFromAttribute($value)
    {
        $this->attributes['effective_from'] = ($value === '' ? null : $value);
    }

    public function setEffectiveToAttribute($value)
    {
        $this->attributes['effective_to'] = ($value === '' ? null : $value);
    }

    public function setExternalReferenceUrlAttribute($value)
    {
        $this->attributes['external_reference_url'] = ($value === '' ? null : $value);
    }

    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = ($value === '' ? null : $value);
    }

    public function setComplianceObjectiveAttribute($value)
    {
        $this->attributes['compliance_objective'] = ($value === '' ? null : $value);
    }

    public function isCurrent(): bool
    {
        return $this->status === 'active'
            && ($this->effective_from === null || $this->effective_from->lte(Carbon::today()))
            && ($this->effective_to === null || $this->effective_to->gte(Carbon::today()));
    }

    public function isSystemTemplate(): bool
    {
        return (bool) $this->is_system_template;
    }

    public function isNis2Domain(): bool
    {
        return self::looksLikeNis2Domain($this->compliance_domain, [
            'framework_code' => $this->framework_code,
            'slug' => $this->slug,
            'name' => $this->name,
        ]);
    }
}
