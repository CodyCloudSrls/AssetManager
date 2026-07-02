<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Italian date inputs (gg/mm/aaaa) are converted back to ISO (Y-m-d) before controllers and
 * validation run, so the entire backend keeps working in ISO while users type, paste and see
 * dd/mm/yyyy. Only a value that is a COMPLETE, valid d/m/Y date is rewritten; everything else
 * (search boxes, free text, serials, ISO dates already in Y-m-d) is left untouched.
 */
class NormalizeLocalizedDates
{
    /** Field names we never touch, even if the value looks like a date. */
    private array $skip = ['search', 'filter', 'q', '_token', '_method'];

    public function handle(Request $request, Closure $next)
    {
        // Never rewrite a Livewire update payload: it is a signed snapshot and altering it
        // would break the integrity checksum. Those forms keep their own ISO datepickers.
        if ($request->is('livewire/*') || $request->hasHeader('X-Livewire')) {
            return $next($request);
        }

        $changed = false;
        $normalized = $this->normalize($request->all(), $changed);

        if ($changed) {
            $request->merge($normalized);
        }

        return $next($request);
    }

    private function normalize($value, bool &$changed, ?string $key = null)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->normalize($v, $changed, is_string($k) ? $k : $key);
            }

            return $value;
        }

        if (! is_string($value) || ($key !== null && in_array($key, $this->skip, true))) {
            return $value;
        }

        $iso = $this->toIso($value);
        if ($iso !== null && $iso !== $value) {
            $changed = true;

            return $iso;
        }

        return $value;
    }

    /** Convert a complete, valid d/m/Y (or d-m-Y) string to Y-m-d; null if it isn't one. */
    private function toIso(string $value): ?string
    {
        if (! preg_match('#^(\d{1,2})[/\-](\d{1,2})[/\-](\d{4})$#', trim($value), $m)) {
            return null;
        }

        [$day, $month, $year] = [(int) $m[1], (int) $m[2], (int) $m[3]];

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
}
