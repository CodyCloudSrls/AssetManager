<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single FiC cashbook (prima nota) movement: money in/out on a payment channel,
 * optionally tied to the FiC document it settled. Read-only mirror used for the
 * multi-channel incassi reconciliation (TS Pay, Carta, PayPal, banche, ...).
 */
class FicCashbookEntry extends Model
{
    protected $table = 'fic_cashbook_entries';

    public const DIRECTION_IN = 'in';
    public const DIRECTION_OUT = 'out';

    protected $fillable = [
        'fic_company_id', 'fic_id', 'entry_date', 'direction', 'amount',
        'account_name', 'account_id', 'description', 'entity_name', 'kind',
        'document_fic_id', 'document_type', 'company_id', 'synced_at',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount' => 'decimal:2',
        'document_fic_id' => 'integer',
        'synced_at' => 'datetime',
    ];

    /** The FiC document this movement settled (matched by FiC document id). */
    public function document(): BelongsTo
    {
        return $this->belongsTo(FicDocument::class, 'document_fic_id', 'fic_id');
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

    public function scopeIncassi(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_IN);
    }

    public function scopePagamenti(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_OUT);
    }

    /** True when the movement is tied to a FiC document (i.e. already matched to an invoice). */
    public function getIsMatchedAttribute(): bool
    {
        return ! is_null($this->document_fic_id);
    }
}
