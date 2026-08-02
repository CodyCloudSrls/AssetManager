<?php

namespace App\Support\Fic;

use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Hard guard that keeps the FiC connector inside Fatture in Cloud's published rate limits:
 *   - hourly: 1000 calls   (header: ratelimit-hourlyremaining / ratelimit-hourlylimit)
 *   - monthly: 40000 calls (header: ratelimit-monthlyremaining / ratelimit-monthlylimit)
 *
 * Every response goes through record(): we read the remaining-budget headers and, when the
 * budget drops under a safety floor — or the API answers 429 / 403 USAGE_LIMIT_REACHED — we
 * open a COOLDOWN in the (file) cache until the window resets. guard() is called before every
 * request and short-circuits (throws FicRateLimitException) while the cooldown is open, so no
 * further calls are made. State lives in the cache so it persists across scheduled CLI runs.
 *
 * This only ever REDUCES calls — it never writes to FiC, so it stays within the read-only
 * connector contract.
 */
class FicRateGuard
{
    private const COOLDOWN_KEY = 'fic:cooldown_until';
    private const MONTHLY_KEY = 'fic:ratelimit:monthly_remaining';
    private const HOURLY_KEY = 'fic:ratelimit:hourly_remaining';

    /** Stop calling while fewer than this remain, leaving headroom for other clients/retries. */
    private const MONTHLY_FLOOR = 2000;
    private const HOURLY_FLOOR = 50;

    public static function isCoolingDown(): bool
    {
        $until = Cache::get(self::COOLDOWN_KEY);

        return $until !== null && now()->lt(Carbon::parse($until));
    }

    public static function cooldownUntil(): ?string
    {
        $until = Cache::get(self::COOLDOWN_KEY);

        return $until !== null ? (string) $until : null;
    }

    /** Called BEFORE each request: refuse to make the call while the cooldown is open. */
    public static function guard(): void
    {
        if (self::isCoolingDown()) {
            throw new FicRateLimitException('FiC rate-limit cooldown active until '.self::cooldownUntil().' — skipping to protect the hourly/monthly quota.');
        }
    }

    /** Called AFTER each response: cache the remaining budget and open a cooldown when needed. */
    public static function record(Response $response): void
    {
        $monthly = $response->header('ratelimit-monthlyremaining');
        $hourly = $response->header('ratelimit-hourlyremaining');

        if ($monthly !== null && $monthly !== '') {
            Cache::put(self::MONTHLY_KEY, (int) $monthly, now()->addDay());
        }
        if ($hourly !== null && $hourly !== '') {
            Cache::put(self::HOURLY_KEY, (int) $hourly, now()->addHours(2));
        }

        // Explicit throttle / quota response: honour Retry-After, else back off to the window reset.
        if ($response->status() === 429 || self::isQuota($response)) {
            $retryAfter = (int) ($response->header('retry-after') ?? 0);
            $until = $retryAfter > 0 ? now()->addSeconds($retryAfter) : now()->addMonthNoOverflow()->startOfMonth();
            self::trip($until);

            return;
        }

        // Proactive: stop BEFORE we hit zero.
        if ($hourly !== null && $hourly !== '' && (int) $hourly <= self::HOURLY_FLOOR) {
            self::trip(now()->addHour()->startOfHour());
        }
        if ($monthly !== null && $monthly !== '' && (int) $monthly <= self::MONTHLY_FLOOR) {
            self::trip(now()->addMonthNoOverflow()->startOfMonth());
        }
    }

    /** @return array{monthly_remaining: ?int, hourly_remaining: ?int, cooldown_until: ?string} */
    public static function snapshot(): array
    {
        return [
            'monthly_remaining' => Cache::get(self::MONTHLY_KEY),
            'hourly_remaining' => Cache::get(self::HOURLY_KEY),
            'cooldown_until' => Cache::get(self::COOLDOWN_KEY),
        ];
    }

    /** Clear the cooldown (e.g. after renewing the token / upgrading the plan). */
    public static function clearCooldown(): void
    {
        Cache::forget(self::COOLDOWN_KEY);
    }

    private static function isQuota(Response $response): bool
    {
        return $response->status() === 403
            && str_contains(strtolower((string) $response->body()), 'usage_limit');
    }

    private static function trip(Carbon $until): void
    {
        $current = Cache::get(self::COOLDOWN_KEY);
        // Keep the LATEST (furthest) cooldown so a monthly block isn't shortened by an hourly one.
        if ($current === null || Carbon::parse($current)->lt($until)) {
            Cache::put(self::COOLDOWN_KEY, $until->toDateTimeString(), $until);
        }
    }
}
