<?php

namespace App\Support\Compliance;

class ComplianceJurisdictions
{
    public const EU = 'EU';

    public const IT = 'IT';

    public static function values(): array
    {
        $matrix = config('compliance_frameworks.nis2_country_overlays', []);

        if (is_array($matrix) && count($matrix) > 0) {
            return array_keys($matrix);
        }

        return [self::EU, self::IT];
    }

    public static function normalize(?string $jurisdiction): string
    {
        $jurisdiction = strtoupper(trim((string) $jurisdiction));

        return in_array($jurisdiction, self::values(), true) ? $jurisdiction : self::EU;
    }

    public static function options(): array
    {
        $matrix = config('compliance_frameworks.nis2_country_overlays', []);
        $countries = trans('localizations.countries');
        $countries = is_array($countries) ? $countries : [];

        return collect(self::values())
            ->mapWithKeys(function (string $jurisdiction) use ($matrix, $countries) {
                $status = $matrix[$jurisdiction]['status'] ?? 'review_required';

                if ($jurisdiction === self::EU) {
                    return [$jurisdiction => trans('admin/tenants/general.settings.compliance_jurisdiction_eu')];
                }

                $countryName = $countries[$jurisdiction] ?? $jurisdiction;
                $statusLabel = trans('admin/tenants/general.settings.compliance_jurisdiction_statuses.'.$status);

                return [$jurisdiction => $countryName.' - '.$statusLabel];
            })
            ->all();
    }
}
