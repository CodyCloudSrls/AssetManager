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
    public const MACRO_PRODUCTION_GOODS_SERVICES = 'production_goods_services';
    public const MACRO_PRODUCTION_DIGITAL_INFRASTRUCTURES = 'production_digital_infrastructures';
    public const MACRO_PRODUCTION_ICT_SERVICE_MANAGEMENT = 'production_ict_service_management';
    public const MACRO_PRODUCTION_DIGITAL_SERVICE_PROVIDERS = 'production_digital_service_providers';
    public const MACRO_RESEARCH_DESIGN = 'research_design';
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
        'acn_subject_basis',
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
        'acn_subject_basis' => 'nullable|string|max:65535',
        'relevance_override' => 'nullable|string|max:40',
        'is_active' => 'boolean',
    ];

    public static function macroAreaKeys(): array
    {
        return array_keys(static::acnMacroAreaLabels());
    }

    public static function selectableMacroAreaKeys(): array
    {
        return array_keys(static::selectableAcnMacroAreaLabels());
    }

    public static function impactKeys(): array
    {
        return array_keys(static::acnImpactLabels());
    }

    public static function macroAreaOptions(?string $currentMacroArea = null): array
    {
        $keys = static::selectableMacroAreaKeys();

        if ($currentMacroArea && in_array($currentMacroArea, static::macroAreaKeys(), true) && ! in_array($currentMacroArea, $keys, true)) {
            $keys[] = $currentMacroArea;
        }

        return collect($keys)
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
            self::MACRO_PRODUCTION_GOODS_SERVICES => 'Produzione di beni e servizi',
            'production_drinking_water' => 'Produzione di beni e servizi - Acqua potabile',
            'production_wastewater' => 'Produzione di beni e servizi - Acque reflue',
            'production_public_administration' => 'Produzione di beni e servizi - Amministrazioni centrali, regionali, locali e di altro tipo (allegato III)',
            'production_art_3_comma_10' => 'Produzione di beni e servizi - Art. 3 comma 10',
            'production_banking' => 'Produzione di beni e servizi - Bancario',
            'production_electricity' => 'Produzione di beni e servizi - Energia elettrica',
            'production_other_transport_equipment_manufacturing' => 'Produzione di beni e servizi - Fabbricazione di altri mezzi di trasporto',
            'production_electrical_equipment_manufacturing' => 'Produzione di beni e servizi - Fabbricazione di apparecchiature elettriche',
            'production_motor_vehicle_manufacturing' => 'Produzione di beni e servizi - Fabbricazione di autoveicoli, rimorchi e semirimorchi',
            'production_computer_electronics_optics_manufacturing' => 'Produzione di beni e servizi - Fabbricazione di computer e prodotti di elettronica e ottica',
            'production_medical_devices_manufacturing' => 'Produzione di beni e servizi - Fabbricazione di dispositivi medici e di dispositivi medico-diagnostici in vitro',
            'production_machinery_equipment_manufacturing' => 'Produzione di beni e servizi - Fabbricazione di macchinari e apparecchiature n.c.a.',
            'production_chemicals' => 'Produzione di beni e servizi - Fabbricazione, produzione e distribuzione di sostanze chimiche',
            self::MACRO_PRODUCTION_DIGITAL_SERVICE_PROVIDERS => 'Produzione di beni e servizi - Fornitori di servizi digitali',
            'production_gas' => 'Produzione di beni e servizi - Gas',
            'production_waste_management' => 'Produzione di beni e servizi - Gestione dei rifiuti',
            self::MACRO_PRODUCTION_ICT_SERVICE_MANAGEMENT => 'Produzione di beni e servizi - Gestione dei servizi TIC',
            'production_hydrogen' => 'Produzione di beni e servizi - Idrogeno',
            'production_financial_market_infrastructures' => 'Produzione di beni e servizi - Infrastrutture dei mercati finanziari',
            self::MACRO_PRODUCTION_DIGITAL_INFRASTRUCTURES => 'Produzione di beni e servizi - Infrastrutture digitali',
            'production_research_education_institutions' => 'Produzione di beni e servizi - Istituti di istruzione che svolgono attività di ricerca',
            'production_oil' => 'Produzione di beni e servizi - Petrolio',
            'production_food' => 'Produzione di beni e servizi - Produzione, trasformazione e distribuzione di alimenti',
            'production_research' => 'Produzione di beni e servizi - Ricerca',
            'production_healthcare' => 'Produzione di beni e servizi - Sanitario',
            'production_postal_courier' => 'Produzione di beni e servizi - Servizi postali e di corriere',
            'production_public_owned_companies' => 'Produzione di beni e servizi - Società in house, partecipate e a controllo pubblico',
            'production_local_public_transport_services' => 'Produzione di beni e servizi - Soggetti che forniscono servizi di trasporto pubblico locale',
            'production_cultural_interest_activities' => 'Produzione di beni e servizi - Soggetti che svolgono attività di interesse culturale',
            'production_space' => 'Produzione di beni e servizi - Spazio',
            'production_district_heating_cooling' => 'Produzione di beni e servizi - Teleriscaldamento e teleraffrescamento',
            'production_transport' => 'Produzione di beni e servizi - Trasporti',
            'production_air_transport' => 'Produzione di beni e servizi - Trasporto aereo',
            'production_rail_transport' => 'Produzione di beni e servizi - Trasporto ferroviario',
            'production_water_transport' => 'Produzione di beni e servizi - Trasporto per vie d\'acqua',
            'production_road_transport' => 'Produzione di beni e servizi - Trasporto su strada',
            self::MACRO_RESEARCH_DESIGN => 'Ricerca, sviluppo e progettazione',
            self::MACRO_FINANCIAL_MANAGEMENT => 'Gestione finanziaria',
            self::MACRO_CUSTOMER_MANAGEMENT => 'Gestione dei clienti',
            self::MACRO_HUMAN_RESOURCES => 'Gestione delle risorse umane',
            self::MACRO_LOGISTICS => 'Logistica',
            self::MACRO_COMMUNICATIONS_MARKETING => 'Comunicazione e marketing',
            self::MACRO_ADMINISTRATIVE_MANAGEMENT => 'Gestione amministrativa',
            self::MACRO_OTHER_SERVICES_ACTIVITIES => 'Altri servizi e attività',
        ];
    }

    public static function selectableAcnMacroAreaLabels(): array
    {
        return collect(static::acnMacroAreaLabels())
            ->except([self::MACRO_PRODUCTION_GOODS_SERVICES])
            ->all();
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
            'production_drinking_water' => self::IMPACT_MEDIUM,
            'production_wastewater' => self::IMPACT_MEDIUM,
            'production_public_administration' => self::IMPACT_MEDIUM,
            'production_art_3_comma_10' => self::IMPACT_MEDIUM,
            'production_banking' => self::IMPACT_MEDIUM,
            'production_electricity' => self::IMPACT_MEDIUM,
            'production_other_transport_equipment_manufacturing' => self::IMPACT_MEDIUM,
            'production_electrical_equipment_manufacturing' => self::IMPACT_MEDIUM,
            'production_motor_vehicle_manufacturing' => self::IMPACT_MEDIUM,
            'production_computer_electronics_optics_manufacturing' => self::IMPACT_MEDIUM,
            'production_medical_devices_manufacturing' => self::IMPACT_MEDIUM,
            'production_machinery_equipment_manufacturing' => self::IMPACT_MEDIUM,
            'production_chemicals' => self::IMPACT_MEDIUM,
            self::MACRO_PRODUCTION_DIGITAL_SERVICE_PROVIDERS => self::IMPACT_MEDIUM,
            'production_gas' => self::IMPACT_MEDIUM,
            'production_waste_management' => self::IMPACT_MEDIUM,
            self::MACRO_PRODUCTION_ICT_SERVICE_MANAGEMENT => self::IMPACT_MEDIUM,
            'production_hydrogen' => self::IMPACT_MEDIUM,
            'production_financial_market_infrastructures' => self::IMPACT_MEDIUM,
            self::MACRO_PRODUCTION_DIGITAL_INFRASTRUCTURES => self::IMPACT_MEDIUM,
            'production_research_education_institutions' => self::IMPACT_MEDIUM,
            'production_oil' => self::IMPACT_MEDIUM,
            'production_food' => self::IMPACT_MEDIUM,
            'production_research' => self::IMPACT_MEDIUM,
            'production_healthcare' => self::IMPACT_MEDIUM,
            'production_postal_courier' => self::IMPACT_MEDIUM,
            'production_public_owned_companies' => self::IMPACT_MEDIUM,
            'production_local_public_transport_services' => self::IMPACT_MEDIUM,
            'production_cultural_interest_activities' => self::IMPACT_MEDIUM,
            'production_space' => self::IMPACT_MEDIUM,
            'production_district_heating_cooling' => self::IMPACT_MEDIUM,
            'production_transport' => self::IMPACT_MEDIUM,
            'production_air_transport' => self::IMPACT_MEDIUM,
            'production_rail_transport' => self::IMPACT_MEDIUM,
            'production_water_transport' => self::IMPACT_MEDIUM,
            'production_road_transport' => self::IMPACT_MEDIUM,
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

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'asset_tenant_service')
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
        return static::macroAreaOptions($this->macro_area)[$this->macro_area] ?? (string) $this->macro_area;
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

    public function setAcnSubjectBasisAttribute($value): void
    {
        $this->attributes['acn_subject_basis'] = $value === '' ? null : $value;
    }

    public function setRelevanceOverrideAttribute($value): void
    {
        $this->attributes['relevance_override'] = $value === '' ? null : $value;
    }
}
