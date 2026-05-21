<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpvCode extends Model
{
    private static array $labelCache = [];

    protected $fillable = [
        'code',
        'division_code',
        'description',
        'source',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function normalizeCode(?string $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        $code = strtoupper(trim($value));
        $code = preg_replace('/\s+/', '', $code);

        if (preg_match('/^\d{8}\d$/', $code)) {
            return substr($code, 0, 8).'-'.substr($code, 8, 1);
        }

        return $code;
    }

    public static function codesFromText(?string $value): array
    {
        preg_match_all('/\b\d{8}-?\d\b/', (string) $value, $matches);

        return collect($matches[0] ?? [])
            ->map(fn ($code) => static::normalizeCode($code))
            ->filter(fn ($code) => is_string($code) && preg_match('/^\d{8}-\d$/', $code))
            ->unique()
            ->values()
            ->all();
    }

    public static function labelsForCodes(array $codes): array
    {
        $codes = collect($codes)
            ->map(fn ($code) => static::normalizeCode($code))
            ->filter(fn ($code) => is_string($code) && preg_match('/^\d{8}-\d$/', $code))
            ->unique()
            ->values()
            ->all();

        $missingCodes = array_values(array_diff($codes, array_keys(self::$labelCache)));

        if ($missingCodes !== []) {
            static::query()
                ->whereIn('code', $missingCodes)
                ->get(['code', 'description'])
                ->each(function (self $cpvCode) {
                    self::$labelCache[$cpvCode->code] = $cpvCode->display_name;
                });

            foreach ($missingCodes as $code) {
                self::$labelCache[$code] ??= $code;
            }
        }

        return collect($codes)
            ->map(fn ($code) => self::$labelCache[$code] ?? $code)
            ->values()
            ->all();
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->code.' - '.$this->description;
    }
}
