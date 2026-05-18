<?php

namespace App\Models;

use App\Http\Traits\UniqueUndeletedTrait;
use App\Models\Traits\CompanyableTrait;
use App\Models\Traits\HasUploads;
use App\Models\Traits\Loggable;
use App\Models\Traits\Searchable;
use App\Presenters\CustomerPresenter;
use App\Presenters\Presentable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Watson\Validating\ValidatingTrait;

class Customer extends SnipeModel
{
    use CompanyableTrait;
    use HasFactory;
    use HasUploads;
    use Loggable;
    use Presentable;
    use Searchable;
    use SoftDeletes;
    use UniqueUndeletedTrait;
    use ValidatingTrait;

    public const STATUS_PROSPECT = 'prospect';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_ENDED = 'ended';

    public const NIS_PROFILE_NOT_ASSESSED = 'not_assessed';
    public const NIS_PROFILE_OUT_OF_SCOPE = 'out_of_scope';
    public const NIS_PROFILE_NIS_SUBJECT = 'nis_subject';
    public const NIS_PROFILE_IMPORTANT = 'important';
    public const NIS_PROFILE_ESSENTIAL = 'essential';

    public const NIS_ROLE_NOT_ASSESSED = 'not_assessed';
    public const NIS_ROLE_SERVICE_PROVIDER = 'service_provider';
    public const NIS_ROLE_ICT_SUPPLIER = 'ict_supplier';
    public const NIS_ROLE_MANAGED_SERVICE = 'managed_service';
    public const NIS_ROLE_SECURITY_SERVICE = 'security_service';

    protected $presenter = CustomerPresenter::class;

    protected $table = 'customers';

    protected $fillable = [
        'company_id',
        'name',
        'customer_number',
        'status',
        'vat_number',
        'tax_code',
        'address',
        'address2',
        'city',
        'state',
        'country',
        'zip',
        'contact',
        'phone',
        'email',
        'security_contact',
        'security_email',
        'url',
        'sector',
        'nis_profile',
        'nis_service_role',
        'nis_criticality',
        'nis_obligations',
        'incident_notification_terms',
        'sla_terms',
        'audit_rights',
        'nis_last_assessment_at',
        'nis_next_review_at',
        'image',
        'tag_color',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'created_by' => 'integer',
        'nis_last_assessment_at' => 'date',
        'nis_next_review_at' => 'date',
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
        'nis_profile' => self::NIS_PROFILE_NOT_ASSESSED,
        'nis_service_role' => self::NIS_ROLE_NOT_ASSESSED,
        'nis_criticality' => 'not_assessed',
    ];

    protected $searchableAttributes = [
        'name',
        'customer_number',
        'status',
        'vat_number',
        'tax_code',
        'contact',
        'phone',
        'email',
        'security_contact',
        'security_email',
        'sector',
        'nis_profile',
        'nis_service_role',
        'nis_criticality',
        'nis_obligations',
        'incident_notification_terms',
        'sla_terms',
        'audit_rights',
        'notes',
    ];

    protected $searchableRelations = [
        'company' => ['name'],
        'adminuser' => ['first_name', 'last_name', 'display_name'],
    ];

    public function getRules(): array
    {
        return [
            'company_id' => 'required|integer|exists:companies,id',
            'name' => [
                'required',
                'max:255',
                Rule::unique('customers', 'name')
                    ->ignore($this->getKey())
                    ->where(fn ($query) => $query->whereNull('deleted_at')->where('company_id', $this->company_id)),
            ],
            'customer_number' => 'nullable|string|max:100',
            'status' => 'required|string|in:'.implode(',', array_keys(static::statusOptions())),
            'vat_number' => 'nullable|string|max:50',
            'tax_code' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:191',
            'state' => 'nullable|string|max:191',
            'country' => 'nullable|string|max:191',
            'zip' => 'nullable|string|max:20',
            'contact' => 'nullable|string|max:100',
            'phone' => 'nullable|string|min:3|max:35',
            'email' => 'nullable|email|max:150',
            'security_contact' => 'nullable|string|max:150',
            'security_email' => 'nullable|email|max:150',
            'url' => 'nullable|url|max:250',
            'sector' => 'nullable|string|max:150',
            'nis_profile' => 'required|string|in:'.implode(',', array_keys(static::nisProfileOptions())),
            'nis_service_role' => 'required|string|in:'.implode(',', array_keys(static::nisServiceRoleOptions())),
            'nis_criticality' => 'required|string|in:'.implode(',', array_keys(static::nisCriticalityOptions())),
            'nis_obligations' => 'nullable|string|max:65535',
            'incident_notification_terms' => 'nullable|string|max:65535',
            'sla_terms' => 'nullable|string|max:65535',
            'audit_rights' => 'nullable|string|max:65535',
            'nis_last_assessment_at' => 'nullable|date',
            'nis_next_review_at' => 'nullable|date',
            'tag_color' => 'nullable|string|max:10',
            'notes' => 'nullable|string|max:65535',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PROSPECT => trans('admin/customers/table.status_prospect'),
            self::STATUS_ACTIVE => trans('admin/customers/table.status_active'),
            self::STATUS_SUSPENDED => trans('admin/customers/table.status_suspended'),
            self::STATUS_ENDED => trans('admin/customers/table.status_ended'),
        ];
    }

    public static function nisProfileOptions(): array
    {
        return [
            self::NIS_PROFILE_NOT_ASSESSED => trans('admin/customers/table.nis_profile_not_assessed'),
            self::NIS_PROFILE_OUT_OF_SCOPE => trans('admin/customers/table.nis_profile_out_of_scope'),
            self::NIS_PROFILE_NIS_SUBJECT => trans('admin/customers/table.nis_profile_nis_subject'),
            self::NIS_PROFILE_IMPORTANT => trans('admin/customers/table.nis_profile_important'),
            self::NIS_PROFILE_ESSENTIAL => trans('admin/customers/table.nis_profile_essential'),
        ];
    }

    public static function nisServiceRoleOptions(): array
    {
        return [
            self::NIS_ROLE_NOT_ASSESSED => trans('admin/customers/table.nis_service_role_not_assessed'),
            self::NIS_ROLE_SERVICE_PROVIDER => trans('admin/customers/table.nis_service_role_service_provider'),
            self::NIS_ROLE_ICT_SUPPLIER => trans('admin/customers/table.nis_service_role_ict_supplier'),
            self::NIS_ROLE_MANAGED_SERVICE => trans('admin/customers/table.nis_service_role_managed_service'),
            self::NIS_ROLE_SECURITY_SERVICE => trans('admin/customers/table.nis_service_role_security_service'),
        ];
    }

    public static function nisCriticalityOptions(): array
    {
        return [
            'not_assessed' => trans('admin/customers/table.nis_criticality_not_assessed'),
            'low' => trans('admin/customers/table.nis_criticality_low'),
            'medium' => trans('admin/customers/table.nis_criticality_medium'),
            'high' => trans('admin/customers/table.nis_criticality_high'),
            'critical' => trans('admin/customers/table.nis_criticality_critical'),
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function adminuser()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function contracts()
    {
        return $this->hasMany(CustomerContract::class, 'customer_id')->orderByDesc('starts_at')->orderByDesc('created_at');
    }

    public function documentAssignments(): MorphMany
    {
        return $this->morphMany(DocumentAssignment::class, 'assignable')
            ->with(['document.type', 'issuer', 'reviewer'])
            ->orderBy('renewal_due_at')
            ->orderBy('expires_at');
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusOptions()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getNisProfileLabelAttribute(): string
    {
        return static::nisProfileOptions()[$this->nis_profile] ?? ucfirst(str_replace('_', ' ', (string) $this->nis_profile));
    }

    public function getNisServiceRoleLabelAttribute(): string
    {
        return static::nisServiceRoleOptions()[$this->nis_service_role] ?? ucfirst(str_replace('_', ' ', (string) $this->nis_service_role));
    }

    public function getNisCriticalityLabelAttribute(): string
    {
        return static::nisCriticalityOptions()[$this->nis_criticality] ?? ucfirst(str_replace('_', ' ', (string) $this->nis_criticality));
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    public function isDeletable(): bool
    {
        return Gate::allows('delete', $this)
            && (($this->contracts_count ?? $this->contracts()->count()) === 0)
            && (($this->document_assignments_count ?? $this->documentAssignments()->count()) === 0)
            && ($this->deleted_at == '');
    }

    public function addhttp($url)
    {
        if (($url != '') && (! preg_match('~^(?:f|ht)tps?://~i', $url))) {
            $url = 'http://'.$url;
        }

        return $url;
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = $value ?: self::STATUS_ACTIVE;
    }

    public function setNisProfileAttribute($value): void
    {
        $this->attributes['nis_profile'] = $value ?: self::NIS_PROFILE_NOT_ASSESSED;
    }

    public function setNisServiceRoleAttribute($value): void
    {
        $this->attributes['nis_service_role'] = $value ?: self::NIS_ROLE_NOT_ASSESSED;
    }

    public function setNisCriticalityAttribute($value): void
    {
        $this->attributes['nis_criticality'] = $value ?: 'not_assessed';
    }
}
