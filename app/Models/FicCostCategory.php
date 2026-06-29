<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Maps a FiC expense category to a management-control bucket for the reclassified
 * Conto Economico. MIXED splits 70% COGS / 30% OPEX (the "Spese materiali" rule).
 */
class FicCostCategory extends Model
{
    public const BUCKET_COGS = 'cogs';
    public const BUCKET_OPEX = 'opex';
    public const BUCKET_LABOR = 'labor';
    public const BUCKET_MIXED = 'mixed';

    public const MIXED_COGS_SHARE = 0.70;

    protected $fillable = ['company_id', 'category', 'bucket'];

    public static function bucketOptions(): array
    {
        return [
            self::BUCKET_COGS => trans('erp/controllo.bucket_cogs'),
            self::BUCKET_OPEX => trans('erp/controllo.bucket_opex'),
            self::BUCKET_LABOR => trans('erp/controllo.bucket_labor'),
            self::BUCKET_MIXED => trans('erp/controllo.bucket_mixed'),
        ];
    }

    /**
     * Allocate a net cost amount across [cogs, opex, labor] for a given bucket.
     *
     * @return array{cogs:float, opex:float, labor:float}
     */
    public static function allocate(string $bucket, float $amount): array
    {
        return match ($bucket) {
            self::BUCKET_COGS => ['cogs' => $amount, 'opex' => 0.0, 'labor' => 0.0],
            self::BUCKET_LABOR => ['cogs' => 0.0, 'opex' => 0.0, 'labor' => $amount],
            self::BUCKET_MIXED => [
                'cogs' => round($amount * self::MIXED_COGS_SHARE, 2),
                'opex' => round($amount * (1 - self::MIXED_COGS_SHARE), 2),
                'labor' => 0.0,
            ],
            default => ['cogs' => 0.0, 'opex' => $amount, 'labor' => 0.0], // opex
        };
    }
}
