<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpvCode extends Model
{
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

    public function getDisplayNameAttribute(): string
    {
        return $this->code.' - '.$this->description;
    }
}
