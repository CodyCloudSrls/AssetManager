<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Economic forecast per year. EBIT atteso = ricavi − cogs − opex − personale.
 */
class Previsionale extends Model
{
    protected $table = 'previsionali';

    protected $fillable = [
        'company_id', 'anno', 'ricavi', 'ricavi_ricorrente', 'cogs', 'opex', 'personale', 'notes', 'created_by',
    ];

    protected $casts = [
        'anno' => 'integer',
        'ricavi' => 'decimal:2',
        'ricavi_ricorrente' => 'decimal:2',
        'cogs' => 'decimal:2',
        'opex' => 'decimal:2',
        'personale' => 'decimal:2',
    ];

    public function getEbitAttribute(): float
    {
        return round((float) $this->ricavi - (float) $this->cogs - (float) $this->opex - (float) $this->personale, 2);
    }

    public function getMargineLordoAttribute(): float
    {
        return round((float) $this->ricavi - (float) $this->cogs, 2);
    }

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
}
