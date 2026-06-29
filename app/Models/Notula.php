<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A "notula": an amount owed to a professional. Two payment states only — non pagata
 * (unpaid) / pagata (paid). The optional `invoice_received` flag is set manually after
 * payment to record that the professional's fiscal invoice arrived (now tracked in FiC),
 * so the cost is not double-counted in management control.
 */
class Notula extends Model
{
    use SoftDeletes;

    protected $table = 'notule';

    public const STATUS_UNPAID = 'unpaid';   // non pagata
    public const STATUS_PAID = 'paid';       // pagata

    protected $fillable = [
        'company_id', 'supplier_id', 'professional_name', 'description',
        'amount', 'paid_amount', 'competence_date', 'expected_invoice_date',
        'status', 'invoice_received', 'paid_at', 'fic_document_id', 'notes', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'invoice_received' => 'boolean',
        'competence_date' => 'date',
        'expected_invoice_date' => 'date',
        'paid_at' => 'date',
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_UNPAID => trans('erp/notule.status_unpaid'),
            self::STATUS_PAID => trans('erp/notule.status_paid'),
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    /** Display name: linked supplier name, else the free-text professional name. */
    public function getDisplayNameAttribute(): string
    {
        return $this->supplier?->name ?: ($this->professional_name ?: '—');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
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

    /**
     * Notule that still weigh on management control: unpaid AND without a received fiscal
     * invoice. Once the invoice is received the real FiC document carries the cost instead,
     * so it is excluded here to avoid double counting.
     */
    public function scopeAccruable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_UNPAID)->where('invoice_received', false);
    }

    /** Outstanding amount still to pay (da pagare) = amount − paid_amount. */
    public function getResiduoAttribute(): float
    {
        return round((float) $this->amount - (float) $this->paid_amount, 2);
    }

    /** Total still to pay across pending notule for the scope (feeds controllo di gestione). */
    public static function outstandingTotal(?array $companyIds): float
    {
        return round((float) static::query()->forCompanies($companyIds)->accruable()
            ->sum(\Illuminate\Support\Facades\DB::raw('amount - paid_amount')), 2);
    }
}
