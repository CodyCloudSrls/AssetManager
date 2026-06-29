<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * An installment loan / financing. Residuo = (rate_totali - rate_pagate) × rata_mensile.
 */
class Finanziamento extends Model
{
    protected $table = 'finanziamenti';

    public const STATO_CONFERMATO = 'confermato';
    public const STATO_DA_CONFERMARE = 'da_confermare';

    protected $fillable = [
        'company_id', 'nome', 'rata_mensile', 'rate_totali', 'rate_pagate', 'stato', 'notes', 'created_by',
    ];

    protected $casts = [
        'rata_mensile' => 'decimal:2',
        'rate_totali' => 'integer',
        'rate_pagate' => 'integer',
    ];

    public function getResiduoAttribute(): float
    {
        return round(max(0, $this->rate_totali - $this->rate_pagate) * (float) $this->rata_mensile, 2);
    }

    public function getPagatoAttribute(): float
    {
        return round(min($this->rate_pagate, $this->rate_totali) * (float) $this->rata_mensile, 2);
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

    /** Total confirmed financial debt (residuo) for the scope, for the PFN. */
    public static function totalResiduo(?array $companyIds, bool $confirmedOnly = true): float
    {
        $query = static::query()->forCompanies($companyIds);
        if ($confirmedOnly) {
            $query->where('stato', self::STATO_CONFERMATO);
        }

        return round((float) $query->get()->sum('residuo'), 2);
    }
}
