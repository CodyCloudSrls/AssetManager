<?php

namespace App\Models;

use App\Models\Traits\CompanyableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Watson\Validating\ValidatingTrait;

class ContractCostLine extends SnipeModel
{
    use CompanyableTrait;
    use HasFactory;
    use SoftDeletes;
    use ValidatingTrait;

    protected $table = 'contract_cost_lines';

    protected $fillable = [
        'company_id',
        'contract_subscription_id',
        'supplier_id',
        'description',
        'quantity',
        'unit_cost',
        'cost_frequency',
        'starts_at',
        'ends_at',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'contract_subscription_id' => 'integer',
        'supplier_id' => 'integer',
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    protected $rules = [
        'company_id' => 'required|integer|exists:companies,id',
        'contract_subscription_id' => 'required|integer|exists:contract_subscriptions,id',
        'supplier_id' => 'nullable|integer|exists:suppliers,id',
        'description' => 'required|string|max:255',
        'quantity' => 'required|numeric|gte:0',
        'unit_cost' => 'required|numeric|gte:0',
        'cost_frequency' => 'required|string|in:monthly,quarterly,annual,one_time',
        'starts_at' => 'nullable|date',
        'ends_at' => 'nullable|date|after_or_equal:starts_at',
        'is_active' => 'boolean',
    ];

    public function subscription()
    {
        return $this->belongsTo(ContractSubscription::class, 'contract_subscription_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id')->withTrashed();
    }

    public function adminuser()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function getFrequencyLabelAttribute(): string
    {
        return ContractSubscription::frequencyOptions()[$this->cost_frequency] ?? ucfirst((string) $this->cost_frequency);
    }

    public function getMonthlyCostAttribute(): float
    {
        return ContractSubscription::monthlyAmount((float) $this->quantity * (float) $this->unit_cost, $this->cost_frequency);
    }
}
