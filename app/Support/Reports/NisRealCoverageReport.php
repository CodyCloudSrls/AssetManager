<?php

namespace App\Support\Reports;

use App\Models\Document;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class NisRealCoverageReport
{
    public function build(?array $companyIds = null): array
    {
        $frameworks = $this->frameworks($companyIds);
        $requirements = $frameworks->flatMap(fn (DocumentFramework $framework) => $framework->requirements);

        return [
            'summary' => $this->summary($requirements),
            'frameworkRows' => $this->frameworkRows($frameworks),
            'requirementRows' => $this->requirementRows($frameworks),
        ];
    }

    private function frameworks(?array $companyIds): Collection
    {
        return DocumentFramework::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('is_system_template', false)
            ->where('is_active', true)
            ->where('status', 'active')
            ->where(function (Builder $query) {
                $query->where('compliance_domain', 'nis2')
                    ->orWhere('framework_code', 'like', 'NIS2%')
                    ->orWhere('slug', 'like', '%nis2%')
                    ->orWhere('name', 'like', '%NIS2%');
            })
            ->when(! is_null($companyIds), function (Builder $query) use ($companyIds) {
                if ($companyIds === []) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereIn('company_id', $companyIds);
            })
            ->with(['company', 'requirements' => function ($query) {
                $query->whereNull('deleted_at')
                    ->where('is_active', true)
                    ->with(['owner'])
                    ->withCount($this->coverageCounts())
                    ->ordered();
            }])
            ->withCount(['requirements'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function coverageCounts(): array
    {
        return [
            'documents',
            'primaryDocuments as primary_documents_count',
            'primaryDocuments as healthy_primary_documents_count' => fn ($query) => $query
                ->where('documents.status', Document::STATUS_ACTIVE)
                ->where(function ($nested) {
                    $nested->whereNull('documents.next_review_at')
                        ->orWhereDate('documents.next_review_at', '>=', now()->toDateString());
                }),
        ];
    }

    private function summary(Collection $requirements): array
    {
        $coverageCounts = $requirements->countBy(fn (DocumentFrameworkRequirement $requirement) => $requirement->coverage_status);

        $covered = (int) $coverageCounts->get(DocumentFrameworkRequirement::COVERAGE_COVERED, 0);
        $total = $requirements->count();

        return [
            'total' => $total,
            'covered' => $covered,
            'at_risk' => (int) $coverageCounts->get(DocumentFrameworkRequirement::COVERAGE_AT_RISK, 0),
            'supporting_only' => (int) $coverageCounts->get(DocumentFrameworkRequirement::COVERAGE_SUPPORTING_ONLY, 0),
            'missing' => (int) $coverageCounts->get(DocumentFrameworkRequirement::COVERAGE_MISSING, 0),
            'coverage_percent' => $total > 0 ? (int) floor(($covered / $total) * 100) : 0,
            'minimum_required_documents' => (int) $requirements->sum(fn (DocumentFrameworkRequirement $requirement) => $requirement->minimum_required_documents),
            'healthy_primary_documents' => (int) $requirements->sum(fn (DocumentFrameworkRequirement $requirement) => (int) ($requirement->healthy_primary_documents_count ?? 0)),
            'document_shortfall_count' => (int) $requirements->sum(fn (DocumentFrameworkRequirement $requirement) => $requirement->document_shortfall_count),
        ];
    }

    private function frameworkRows(Collection $frameworks): Collection
    {
        return $frameworks->map(function (DocumentFramework $framework) {
            $summary = $this->summary($framework->requirements);

            return [
                'framework' => $framework,
                'company_name' => $framework->company?->name ?: trans('general.none'),
                'summary' => $summary,
            ];
        })->values();
    }

    private function requirementRows(Collection $frameworks): Collection
    {
        return $frameworks
            ->flatMap(function (DocumentFramework $framework) {
                return $framework->requirements->map(function (DocumentFrameworkRequirement $requirement) use ($framework) {
                    return [
                        'framework' => $framework,
                        'requirement' => $requirement,
                        'company_name' => $framework->company?->name ?: trans('general.none'),
                        'coverage_class' => $this->coverageClass($requirement->coverage_status),
                    ];
                });
            })
            ->values();
    }

    private function coverageClass(string $coverageStatus): string
    {
        return match ($coverageStatus) {
            DocumentFrameworkRequirement::COVERAGE_COVERED => 'label label-success',
            DocumentFrameworkRequirement::COVERAGE_AT_RISK => 'label label-danger',
            DocumentFrameworkRequirement::COVERAGE_SUPPORTING_ONLY => 'label label-warning',
            default => 'label label-default',
        };
    }
}
