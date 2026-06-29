<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A bank/cash account with its real balance from the FiC cashbook.
 */
class FicPaymentAccount extends Model
{
    protected $fillable = ['fic_company_id', 'name', 'balance', 'company_id', 'synced_at'];

    protected $casts = [
        'balance' => 'decimal:2',
        'synced_at' => 'datetime',
    ];

    public function scopeForCompanies(Builder $query, ?array $companyIds): Builder
    {
        if (is_null($companyIds)) {
            return $query;
        }
        if ($companyIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('company_id', $companyIds);
    }

    /** Total real cash across all accounts for the scope, or null if none synced. */
    public static function totalBalance(?array $companyIds): ?float
    {
        $query = static::query()->forCompanies($companyIds);
        if (! $query->exists()) {
            return null;
        }

        return round((float) $query->sum('balance'), 2);
    }
}
