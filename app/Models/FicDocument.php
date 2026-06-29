<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only mirror of a Fatture in Cloud document (issued or received).
 * Never written to FiC — populated by the idempotent FicSyncService.
 */
class FicDocument extends Model
{
    public const DIRECTION_ISSUED = 'issued';

    public const DIRECTION_RECEIVED = 'received';

    protected $fillable = [
        'fic_company_id', 'direction', 'fic_id', 'doc_type', 'number',
        'issued_on', 'due_on', 'entity_name', 'entity_vat',
        'amount_net', 'amount_vat', 'amount_gross', 'currency',
        'paid', 'paid_amount', 'company_id', 'synced_at',
    ];

    protected $casts = [
        'issued_on' => 'date',
        'due_on' => 'date',
        'amount_net' => 'decimal:2',
        'amount_vat' => 'decimal:2',
        'amount_gross' => 'decimal:2',
        'paid' => 'boolean',
        'paid_amount' => 'decimal:2',
        'synced_at' => 'datetime',
    ];

    public function scopeIssued(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_ISSUED);
    }

    public function scopeReceived(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_RECEIVED);
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('paid', false);
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

    /** Outstanding amount on an unpaid document. */
    public function getOutstandingAttribute(): float
    {
        return round((float) $this->amount_gross - (float) $this->paid_amount, 2);
    }
}
