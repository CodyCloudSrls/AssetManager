<?php

namespace App\Models;

use App\Models\Traits\CompanyableTrait;
use App\Models\Traits\Loggable;
use App\Models\Traits\Searchable;
use App\Presenters\CustomerContractPresenter;
use App\Presenters\Presentable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Gate;
use Watson\Validating\ValidatingTrait;

class CustomerContract extends SnipeModel
{
    use CompanyableTrait;
    use HasFactory;
    use Loggable;
    use Presentable;
    use Searchable;
    use SoftDeletes;
    use ValidatingTrait;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_TERMINATED = 'terminated';

    protected $presenter = CustomerContractPresenter::class;

    protected $table = 'customer_contracts';

    protected $fillable = [
        'company_id',
        'customer_id',
        'document_id',
        'owner_id',
        'name',
        'contract_number',
        'status',
        'currency',
        'signed_at',
        'starts_at',
        'ends_at',
        'renewal_due_at',
        'notice_due_at',
        'scope',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'customer_id' => 'integer',
        'document_id' => 'integer',
        'owner_id' => 'integer',
        'created_by' => 'integer',
        'signed_at' => 'date',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'renewal_due_at' => 'date',
        'notice_due_at' => 'date',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'currency' => 'EUR',
    ];

    protected $searchableAttributes = [
        'name',
        'contract_number',
        'status',
        'currency',
        'scope',
        'notes',
    ];

    protected $searchableRelations = [
        'customer' => ['name', 'customer_number', 'vat_number'],
        'company' => ['name'],
        'document' => ['name', 'document_number', 'reference'],
        'owner' => ['first_name', 'last_name', 'display_name'],
    ];

    protected $rules = [
        'company_id' => 'required|integer|exists:companies,id',
        'customer_id' => 'required|integer|exists:customers,id',
        'document_id' => 'nullable|integer|exists:documents,id',
        'owner_id' => 'nullable|integer|exists:users,id',
        'name' => 'required|string|max:255',
        'contract_number' => 'nullable|string|max:100',
        'status' => 'required|string|in:draft,active,suspended,expired,terminated',
        'currency' => 'required|string|size:3',
        'signed_at' => 'nullable|date_format:Y-m-d',
        'starts_at' => 'nullable|date_format:Y-m-d',
        'ends_at' => 'nullable|date_format:Y-m-d|after_or_equal:starts_at',
        'renewal_due_at' => 'nullable|date_format:Y-m-d',
        'notice_due_at' => 'nullable|date_format:Y-m-d',
        'scope' => 'nullable|string|max:65535',
        'notes' => 'nullable|string|max:65535',
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => trans('admin/contracts/general.status_draft'),
            self::STATUS_ACTIVE => trans('admin/contracts/general.status_active'),
            self::STATUS_SUSPENDED => trans('admin/contracts/general.status_suspended'),
            self::STATUS_EXPIRED => trans('admin/contracts/general.status_expired'),
            self::STATUS_TERMINATED => trans('admin/contracts/general.status_terminated'),
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id')->withTrashed();
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id')->withTrashed();
    }

    public function adminuser()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function subscriptions()
    {
        return $this->hasMany(ContractSubscription::class, 'customer_contract_id')->orderBy('name');
    }

    public function events()
    {
        return $this->hasMany(CustomerContractEvent::class, 'customer_contract_id')->orderByDesc('created_at')->orderByDesc('id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusOptions()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    public function getIsExpiringAttribute(): bool
    {
        return $this->renewal_due_at
            && $this->renewal_due_at->isFuture()
            && $this->renewal_due_at->lte(Carbon::today()->addDays(30));
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->ends_at && $this->ends_at->lt(Carbon::today());
    }

    public function isDeletable(): bool
    {
        return Gate::allows('delete', $this) && ($this->deleted_at == '');
    }
}
