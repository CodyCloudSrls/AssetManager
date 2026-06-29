<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Official deposited yearly accounts (authoritative). See migration for the precedence
 * rule. The model exposes the authoritative payroll/result per year for the cockpit.
 */
class BilancioUfficiale extends Model
{
    protected $table = 'bilanci_ufficiali';

    protected $fillable = [
        'company_id', 'anno', 'ricavi', 'costi', 'costo_personale',
        'ammortamenti', 'utile', 'imposte', 'is_deposited', 'notes', 'created_by',
    ];

    protected $casts = [
        'anno' => 'integer',
        'ricavi' => 'decimal:2',
        'costi' => 'decimal:2',
        'costo_personale' => 'decimal:2',
        'ammortamenti' => 'decimal:2',
        'utile' => 'decimal:2',
        'imposte' => 'decimal:2',
        'is_deposited' => 'boolean',
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

    /** Authoritative payroll cost per year (anno => costo_personale) for the given scope. */
    public static function payrollByYear(?array $companyIds): array
    {
        return static::query()->forCompanies($companyIds)
            ->get(['anno', 'costo_personale'])
            ->mapWithKeys(fn ($b) => [(int) $b->anno => (float) $b->costo_personale])
            ->all();
    }
}
