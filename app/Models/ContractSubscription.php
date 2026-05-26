<?php

namespace App\Models;

use App\Models\Traits\CompanyableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Watson\Validating\ValidatingTrait;

class ContractSubscription extends SnipeModel
{
    use CompanyableTrait;
    use HasFactory;
    use SoftDeletes;
    use ValidatingTrait;

    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_QUARTERLY = 'quarterly';
    public const FREQUENCY_ANNUAL = 'annual';
    public const FREQUENCY_ONE_TIME = 'one_time';

    protected $table = 'contract_subscriptions';

    protected $fillable = [
        'company_id',
        'customer_contract_id',
        'name',
        'service_code',
        'description',
        'quantity',
        'unit_price',
        'billing_frequency',
        'starts_at',
        'ends_at',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'customer_contract_id' => 'integer',
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    protected $rules = [
        'company_id' => 'required|integer|exists:companies,id',
        'customer_contract_id' => 'required|integer|exists:customer_contracts,id',
        'name' => 'required|string|max:255',
        'service_code' => 'nullable|string|max:100',
        'description' => 'nullable|string|max:65535',
        'quantity' => 'required|numeric|gte:0',
        'unit_price' => 'required|numeric|gte:0',
        'billing_frequency' => 'required|string|in:monthly,quarterly,annual,one_time',
        'starts_at' => 'nullable|date',
        'ends_at' => 'nullable|date|after_or_equal:starts_at',
        'is_active' => 'boolean',
    ];

    public static function frequencyOptions(): array
    {
        return [
            self::FREQUENCY_MONTHLY => trans('admin/contracts/general.frequency_monthly'),
            self::FREQUENCY_QUARTERLY => trans('admin/contracts/general.frequency_quarterly'),
            self::FREQUENCY_ANNUAL => trans('admin/contracts/general.frequency_annual'),
            self::FREQUENCY_ONE_TIME => trans('admin/contracts/general.frequency_one_time'),
        ];
    }

    public function contract()
    {
        return $this->belongsTo(CustomerContract::class, 'customer_contract_id');
    }

    public function costLines()
    {
        return $this->hasMany(ContractCostLine::class, 'contract_subscription_id')->orderBy('description');
    }

    public function adminuser()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function getFrequencyLabelAttribute(): string
    {
        return self::frequencyOptions()[$this->billing_frequency] ?? ucfirst((string) $this->billing_frequency);
    }

    public function getMonthlyRevenueAttribute(): float
    {
        return self::monthlyAmount((float) $this->quantity * (float) $this->unit_price, $this->billing_frequency);
    }

    public static function monthlyAmount(float $amount, string $frequency): float
    {
        return match ($frequency) {
            self::FREQUENCY_QUARTERLY => $amount / 3,
            self::FREQUENCY_ANNUAL => $amount / 12,
            self::FREQUENCY_ONE_TIME => 0.0,
            default => $amount,
        };
    }
}
