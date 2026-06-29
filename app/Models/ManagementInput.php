<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Manual financial input (key/value) — values FiC can't provide, e.g. cassa/banca.
 */
class ManagementInput extends Model
{
    protected $fillable = ['company_id', 'key', 'value'];

    protected $casts = ['value' => 'decimal:2'];

    public const KEY_CASSA = 'cassa_attuale';

    /** Resolve a single scope company id from the cockpit scope (null = global). */
    public static function scopeCompanyId(?array $companyIds): ?int
    {
        return is_null($companyIds) ? null : ($companyIds[0] ?? null);
    }

    public static function getValue(?array $companyIds, string $key): ?float
    {
        $row = static::query()
            ->where('key', $key)
            ->where('company_id', static::scopeCompanyId($companyIds))
            ->first();

        return $row ? (float) $row->value : null;
    }

    public static function setValue(?array $companyIds, string $key, float $value): void
    {
        static::updateOrCreate(
            ['company_id' => static::scopeCompanyId($companyIds), 'key' => $key],
            ['value' => $value],
        );
    }
}
