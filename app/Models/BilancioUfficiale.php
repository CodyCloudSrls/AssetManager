<?php

namespace App\Models;

use App\Models\Traits\HasUploads;
use App\Models\Traits\Loggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Official deposited yearly accounts (authoritative). See migration for the precedence
 * rule. The model exposes the authoritative payroll/result per year for the cockpit.
 * Supports PDF attachments (the deposited bilancio) via the shared uploads mechanism.
 */
class BilancioUfficiale extends Model
{
    use HasUploads;
    use Loggable;
    use SoftDeletes;

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

    /**
     * Authoritative payroll cost per year (anno => costo_personale) for the given scope.
     *
     * Only *deposited* accounts are authoritative — a provisional estimate must not
     * override the FiC actuals (same gate the imposte precedence uses). Values are summed
     * across all companies in scope so a multi-company tenant consolidates instead of
     * keeping a single company's figure.
     */
    public static function payrollByYear(?array $companyIds): array
    {
        return static::query()->forCompanies($companyIds)
            ->where('is_deposited', true)
            ->get(['anno', 'costo_personale'])
            ->groupBy('anno')
            ->map(fn ($rows) => (float) $rows->sum('costo_personale'))
            ->mapWithKeys(fn ($sum, $anno) => [(int) $anno => (float) $sum])
            ->all();
    }
}
