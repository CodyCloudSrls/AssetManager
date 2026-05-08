<?php

namespace App\Support\Reports;

use App\Models\Asset;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class NisRiskMatrixReport
{
    public const RISK_NEEDS_ASSESSMENT = 'needs_assessment';
    public const RISK_LOW = 'low';
    public const RISK_MEDIUM = 'medium';
    public const RISK_HIGH = 'high';
    public const RISK_CRITICAL = 'critical';

    public function build(?array $companyIds = null): array
    {
        $categories = $this->categories($companyIds);
        $rows = $this->assets($companyIds)
            ->map(fn (Asset $asset) => $this->assetRow($asset))
            ->values();

        return [
            'riskLevelOptions' => $this->riskLevelOptions(),
            'summary' => $this->summary($rows),
            'categoryRows' => $this->categoryRows($rows, $categories),
            'rows' => $rows,
        ];
    }

    private function categories(?array $companyIds): Collection
    {
        return Category::query()
            ->where('category_type', 'asset')
            ->where('nis_inventory_required', true)
            ->when(! is_null($companyIds), function (Builder $query) use ($companyIds) {
                if ($companyIds === []) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereIn('company_id', $companyIds);
            })
            ->orderBy('name')
            ->get();
    }

    private function assets(?array $companyIds): Collection
    {
        return Asset::query()
            ->with(['company', 'model.category'])
            ->where(function (Builder $query) {
                $query->where('assets.nis_relevant', true)
                    ->orWhereHas('model.category', fn (Builder $categoryQuery) => $categoryQuery->where('nis_inventory_required', true));
            })
            ->when(! is_null($companyIds), function (Builder $query) use ($companyIds) {
                if ($companyIds === []) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereIn('assets.company_id', $companyIds);
            })
            ->orderBy('asset_tag')
            ->get();
    }

    private function assetRow(Asset $asset): array
    {
        $category = $asset->model?->category;
        $scope = $asset->nis_inventory_scope ?: $category?->nis_inventory_scope;
        $impact = $asset->nis_service_impact ?: 'unknown';
        $exposureScore = $this->exposureScore($scope);
        $impactScore = $this->impactScore($impact);
        $riskScore = $exposureScore * $impactScore;
        $riskLevel = $this->riskLevel($scope, $impact, $riskScore);
        $source = $this->source((bool) $asset->nis_relevant, (bool) ($category?->nis_inventory_required));

        return [
            'asset' => $asset,
            'asset_name' => $asset->display_name,
            'company_name' => $asset->company?->name ?: '-',
            'category' => $category,
            'category_key' => $category?->id ? 'category_'.$category->id : 'none',
            'category_name' => $category?->name ?: trans('general.none'),
            'category_scope_label' => $this->scopeLabel($category?->nis_inventory_scope),
            'scope' => $scope,
            'scope_label' => $this->scopeLabel($scope),
            'service_impact' => $impact,
            'service_impact_label' => $this->impactLabel($impact),
            'exposure_score' => $exposureScore,
            'exposure_label' => $this->exposureLabel($exposureScore),
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'risk_label' => $this->riskLevelOptions()[$riskLevel],
            'risk_class' => $this->riskLabelClass($riskLevel),
            'source' => $source,
            'source_label' => $this->sourceOptions()[$source],
            'notes' => $this->notes($scope, $impact),
        ];
    }

    private function summary(Collection $rows): array
    {
        return collect(array_keys($this->riskLevelOptions()))
            ->mapWithKeys(fn (string $level) => [$level => $rows->where('risk_level', $level)->count()])
            ->all();
    }

    private function categoryRows(Collection $rows, Collection $requiredCategories): Collection
    {
        $categoryRows = $rows
            ->groupBy('category_key')
            ->map(function (Collection $categoryRows) {
                $first = $categoryRows->first();

                return [
                    'category' => $first['category'],
                    'category_name' => $first['category_name'],
                    'scope_label' => $first['category_scope_label'],
                    'assets_count' => $categoryRows->count(),
                    'counts' => $this->summary($categoryRows),
                ];
            });

        foreach ($requiredCategories as $category) {
            $key = 'category_'.$category->id;

            if ($categoryRows->has($key)) {
                continue;
            }

            $categoryRows->put($key, [
                'category' => $category,
                'category_name' => $category->name,
                'scope_label' => $this->scopeLabel($category->nis_inventory_scope),
                'assets_count' => 0,
                'counts' => $this->summary(collect()),
            ]);
        }

        return $categoryRows
            ->sortBy('category_name')
            ->values();
    }

    private function riskLevelOptions(): array
    {
        return [
            self::RISK_NEEDS_ASSESSMENT => trans('admin/reports/general.nis_risk_levels.needs_assessment'),
            self::RISK_LOW => trans('admin/reports/general.nis_risk_levels.low'),
            self::RISK_MEDIUM => trans('admin/reports/general.nis_risk_levels.medium'),
            self::RISK_HIGH => trans('admin/reports/general.nis_risk_levels.high'),
            self::RISK_CRITICAL => trans('admin/reports/general.nis_risk_levels.critical'),
        ];
    }

    private function sourceOptions(): array
    {
        return [
            'asset' => trans('admin/reports/general.nis_sources.asset'),
            'category' => trans('admin/reports/general.nis_sources.category'),
            'both' => trans('admin/reports/general.nis_sources.both'),
        ];
    }

    private function source(bool $assetRelevant, bool $categoryRequired): string
    {
        if ($assetRelevant && $categoryRequired) {
            return 'both';
        }

        return $assetRelevant ? 'asset' : 'category';
    }

    private function riskLevel(?string $scope, string $impact, int $riskScore): string
    {
        if (! $scope || $impact === 'unknown') {
            return self::RISK_NEEDS_ASSESSMENT;
        }

        return match (true) {
            $riskScore >= 10 => self::RISK_CRITICAL,
            $riskScore >= 7 => self::RISK_HIGH,
            $riskScore >= 4 => self::RISK_MEDIUM,
            default => self::RISK_LOW,
        };
    }

    private function exposureScore(?string $scope): int
    {
        return match ($scope) {
            'network', 'server', 'cloud' => 3,
            'security', 'identity', 'backup' => 2,
            'endpoint', 'facility', 'other' => 1,
            default => 0,
        };
    }

    private function impactScore(string $impact): int
    {
        return match ($impact) {
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'critical' => 4,
            default => 0,
        };
    }

    private function exposureLabel(int $score): string
    {
        return match ($score) {
            3 => trans('admin/reports/general.nis_exposure_levels.high'),
            2 => trans('admin/reports/general.nis_exposure_levels.elevated'),
            1 => trans('admin/reports/general.nis_exposure_levels.limited'),
            default => trans('admin/reports/general.nis_exposure_levels.unknown'),
        };
    }

    private function riskLabelClass(string $riskLevel): string
    {
        return match ($riskLevel) {
            self::RISK_CRITICAL => 'label label-danger',
            self::RISK_HIGH => 'label label-warning',
            self::RISK_MEDIUM => 'label label-info',
            self::RISK_LOW => 'label label-success',
            default => 'label label-default',
        };
    }

    private function scopeLabel(?string $scope): string
    {
        if (! $scope) {
            return trans('general.none');
        }

        return Category::nisInventoryScopeOptions()[$scope] ?? ucfirst(str_replace('_', ' ', $scope));
    }

    private function impactLabel(string $impact): string
    {
        return Asset::nisServiceImpactOptions()[$impact] ?? ucfirst(str_replace('_', ' ', $impact));
    }

    private function notes(?string $scope, string $impact): string
    {
        $notes = [];

        if (! $scope) {
            $notes[] = trans('admin/reports/general.nis_missing_scope');
        }

        if ($impact === 'unknown') {
            $notes[] = trans('admin/reports/general.nis_missing_impact');
        }

        return implode(', ', $notes);
    }
}
