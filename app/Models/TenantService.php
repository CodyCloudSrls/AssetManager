<?php

namespace App\Models;

use App\Support\Tenants\TenantRecordGuard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Watson\Validating\ValidatingTrait;

class TenantService extends SnipeModel
{
    use HasFactory;
    use SoftDeletes;
    use ValidatingTrait;

    public const MACRO_MONITORING_CONTROL = 'monitoring_control';
    public const MACRO_RESEARCH_DESIGN = 'research_design';
    public const MACRO_PRODUCTION_GOODS_SERVICES = 'production_goods_services';
    public const MACRO_FINANCIAL_MANAGEMENT = 'financial_management';
    public const MACRO_CUSTOMER_MANAGEMENT = 'customer_management';
    public const MACRO_HUMAN_RESOURCES = 'human_resources';
    public const MACRO_LOGISTICS = 'logistics';
    public const MACRO_COMMUNICATIONS_MARKETING = 'communications_marketing';
    public const MACRO_ADMINISTRATIVE_MANAGEMENT = 'administrative_management';
    public const MACRO_OTHER_SERVICES_ACTIVITIES = 'other_services_activities';

    public const IMPACT_MINIMAL = 'minimal';
    public const IMPACT_LOW = 'low';
    public const IMPACT_MEDIUM = 'medium';
    public const IMPACT_HIGH = 'high';

    protected $table = 'tenant_services';

    protected $fillable = [
        'tenant_id',
        'macro_area',
        'name',
        'description',
        'relevance_override',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected $rules = [
        'tenant_id' => 'required|integer|exists:tenants,id',
        'macro_area' => 'required|string|max:80',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:65535',
        'relevance_override' => 'nullable|string|max:40',
        'is_active' => 'boolean',
    ];

    public static function macroAreaKeys(): array
    {
        return array_keys(static::acnMacroAreaLabels());
    }

    public static function impactKeys(): array
    {
        return array_keys(static::acnImpactLabels());
    }

    public static function macroAreaOptions(): array
    {
        return collect(static::macroAreaKeys())
            ->mapWithKeys(fn (string $key) => [$key => trans('admin/tenantservices/general.macro_areas.'.$key)])
            ->all();
    }

    public static function impactOptions(): array
    {
        return collect(static::impactKeys())
            ->mapWithKeys(fn (string $key) => [$key => trans('admin/tenantservices/general.impacts.'.$key)])
            ->all();
    }

    public static function acnMacroAreaLabels(): array
    {
        return [
            self::MACRO_MONITORING_CONTROL => 'Monitoraggio e controllo',
            self::MACRO_RESEARCH_DESIGN => 'Ricerca, sviluppo e progettazione',
            self::MACRO_PRODUCTION_GOODS_SERVICES => 'Produzione di beni e servizi',
            self::MACRO_FINANCIAL_MANAGEMENT => 'Gestione finanziaria',
            self::MACRO_CUSTOMER_MANAGEMENT => 'Gestione dei clienti',
            self::MACRO_HUMAN_RESOURCES => 'Gestione delle risorse umane',
            self::MACRO_LOGISTICS => 'Logistica',
            self::MACRO_COMMUNICATIONS_MARKETING => 'Comunicazione e marketing',
            self::MACRO_ADMINISTRATIVE_MANAGEMENT => 'Gestione amministrativa',
            self::MACRO_OTHER_SERVICES_ACTIVITIES => 'Altri servizi e attività',
        ];
    }

    public static function acnImpactLabels(): array
    {
        return [
            self::IMPACT_MINIMAL => 'Impatto minimo',
            self::IMPACT_LOW => 'Impatto basso',
            self::IMPACT_MEDIUM => 'Impatto medio',
            self::IMPACT_HIGH => 'Impatto alto',
        ];
    }

    public static function defaultRelevanceByMacroArea(): array
    {
        return [
            self::MACRO_MONITORING_CONTROL => self::IMPACT_HIGH,
            self::MACRO_RESEARCH_DESIGN => self::IMPACT_MEDIUM,
            self::MACRO_PRODUCTION_GOODS_SERVICES => self::IMPACT_MEDIUM,
            self::MACRO_FINANCIAL_MANAGEMENT => self::IMPACT_LOW,
            self::MACRO_CUSTOMER_MANAGEMENT => self::IMPACT_LOW,
            self::MACRO_HUMAN_RESOURCES => self::IMPACT_LOW,
            self::MACRO_LOGISTICS => self::IMPACT_MINIMAL,
            self::MACRO_COMMUNICATIONS_MARKETING => self::IMPACT_MINIMAL,
            self::MACRO_ADMINISTRATIVE_MANAGEMENT => self::IMPACT_MINIMAL,
            self::MACRO_OTHER_SERVICES_ACTIVITIES => self::IMPACT_MINIMAL,
        ];
    }

    public static function preAssignedRelevanceFor(?string $macroArea): ?string
    {
        return static::defaultRelevanceByMacroArea()[$macroArea] ?? null;
    }

    public static function activeForCompanyId(?int $companyId)
    {
        $tenantId = TenantRecordGuard::companyTenantId($companyId);

        if (! $tenantId) {
            return collect();
        }

        return static::query()
            ->where('tenant_id', $tenantId)
            ->active()
            ->orderBy('macro_area')
            ->orderBy('name')
            ->get();
    }

    public static function validIdsForCompany(array $ids, ?int $companyId): array
    {
        $tenantId = TenantRecordGuard::companyTenantId($companyId);

        if (! $tenantId) {
            return [];
        }

        return static::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_tenant_service')
            ->withTimestamps();
    }

    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(CustomerContract::class, 'customer_contract_tenant_service')
            ->withTimestamps();
    }

    public function adminuser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getMacroAreaLabelAttribute(): string
    {
        return static::macroAreaOptions()[$this->macro_area] ?? (string) $this->macro_area;
    }

    public function getAcnMacroAreaLabelAttribute(): string
    {
        return static::acnMacroAreaLabels()[$this->macro_area] ?? (string) $this->macro_area;
    }

    public function getPreAssignedRelevanceAttribute(): ?string
    {
        return static::preAssignedRelevanceFor($this->macro_area);
    }

    public function getPreAssignedRelevanceLabelAttribute(): ?string
    {
        $impact = $this->pre_assigned_relevance;

        return $impact ? (static::impactOptions()[$impact] ?? $impact) : null;
    }

    public function getAcnPreAssignedRelevanceLabelAttribute(): ?string
    {
        $impact = $this->pre_assigned_relevance;

        return $impact ? (static::acnImpactLabels()[$impact] ?? $impact) : null;
    }

    public function getAssignedRelevanceAttribute(): ?string
    {
        return $this->relevance_override ?: $this->pre_assigned_relevance;
    }

    public function getAssignedRelevanceLabelAttribute(): ?string
    {
        $impact = $this->assigned_relevance;

        return $impact ? (static::impactOptions()[$impact] ?? $impact) : null;
    }

    public function getAcnAssignedRelevanceOverrideLabelAttribute(): string
    {
        return $this->relevance_override
            ? (static::acnImpactLabels()[$this->relevance_override] ?? $this->relevance_override)
            : '';
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    public function setDescriptionAttribute($value): void
    {
        $this->attributes['description'] = $value === '' ? null : $value;
    }

    public function setRelevanceOverrideAttribute($value): void
    {
        $this->attributes['relevance_override'] = $value === '' ? null : $value;
    }
}
