<?php

namespace App\Support\Reports;

use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use App\Models\User;
use App\Support\Compliance\ComplianceDomainAccess;
use App\Support\Documents\DocumentAreaAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class NisRealCoverageReport
{
    public function build(?array $companyIds = null, ?User $user = null): array
    {
        $frameworks = $this->frameworks($companyIds, $user);
        $requirements = $frameworks->flatMap(fn (DocumentFramework $framework) => $framework->requirements);

        return [
            'summary' => $this->summary($requirements),
            'frameworkRows' => $this->frameworkRows($frameworks),
            'requirementRows' => $this->requirementRows($frameworks),
        ];
    }

    private function frameworks(?array $companyIds, ?User $user): Collection
    {
        $frameworks = DocumentFramework::query()
            ->operational()
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
            ->with(['company', 'requirements' => function ($query) use ($user) {
                $query->whereNull('deleted_at')
                    ->where('is_active', true)
                    ->with([
                        'owner',
                        'primaryDocuments' => function ($query) use ($user) {
                            $this->applyVisibleDocumentScope($query, $user);
                        },
                        'healthyPrimaryDocuments' => function ($query) use ($user) {
                            $query->currentForCoverage();
                            $this->applyVisibleDocumentScope($query, $user);
                        },
                    ])
                    ->withCount($this->coverageCounts($user))
                    ->ordered();
            }])
            ->withCount(['requirements'])
            ->orderBy('sort_order')
            ->orderBy('name');

        ComplianceDomainAccess::applyFrameworkScope($frameworks, $user, 'compliance_domain');

        return $frameworks->get();
    }

    private function coverageCounts(?User $user): array
    {
        return [
            'documents' => fn ($query) => $this->applyVisibleDocumentScope($query, $user),
            'primaryDocuments as primary_documents_count' => fn ($query) => $this->applyVisibleDocumentScope($query, $user),
            'primaryDocuments as healthy_primary_documents_count' => function ($query) use ($user) {
                $query->currentForCoverage();
                $this->applyVisibleDocumentScope($query, $user);
            },
        ];
    }

    private function applyVisibleDocumentScope($query, ?User $user): void
    {
        ComplianceDomainAccess::applyDocumentScope($query, $user);
        DocumentAreaAccess::applyDocumentScope($query, $user);
    }

    private function summary(Collection $requirements): array
    {
        $coverageCounts = $requirements->countBy(fn (DocumentFrameworkRequirement $requirement) => $requirement->coverage_status);
        $primaryDocumentCounts = $this->primaryDocumentCounts($requirements);
        $documentTypeCounts = $this->documentTypeCounts($requirements);

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
            'required_documents_count' => $primaryDocumentCounts['required'],
            'healthy_required_documents_count' => $primaryDocumentCounts['healthy'],
            'missing_required_documents_count' => $primaryDocumentCounts['missing'],
            'required_document_types_count' => $documentTypeCounts['required'],
            'healthy_required_document_types_count' => $documentTypeCounts['healthy'],
            'missing_required_document_types_count' => $documentTypeCounts['missing'],
        ];
    }

    private function primaryDocumentCounts(Collection $requirements): array
    {
        $requiredDocumentIds = $requirements
            ->flatMap(function (DocumentFrameworkRequirement $requirement) {
                $documents = $requirement->relationLoaded('primaryDocuments')
                    ? $requirement->primaryDocuments
                    : $requirement->primaryDocuments()->get(['documents.id']);

                return $documents->pluck('id');
            })
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $healthyDocumentIds = $requirements
            ->flatMap(function (DocumentFrameworkRequirement $requirement) {
                $documents = $requirement->relationLoaded('healthyPrimaryDocuments')
                    ? $requirement->healthyPrimaryDocuments
                    : $requirement->healthyPrimaryDocuments()->get(['documents.id']);

                return $documents->pluck('id');
            })
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return [
            'required' => $requiredDocumentIds->count(),
            'healthy' => $requiredDocumentIds->intersect($healthyDocumentIds)->count(),
            'missing' => $requiredDocumentIds->diff($healthyDocumentIds)->count(),
        ];
    }

    private function documentTypeCounts(Collection $requirements): array
    {
        $requiredTypeIds = $requirements
            ->pluck('default_document_type_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $healthyTypeIds = $requirements
            ->flatMap(function (DocumentFrameworkRequirement $requirement) {
                $documents = $requirement->relationLoaded('healthyPrimaryDocuments')
                    ? $requirement->healthyPrimaryDocuments
                    : $requirement->healthyPrimaryDocumentsQuery()->get(['documents.id', 'documents.document_type_id']);

                return $documents->pluck('document_type_id');
            })
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $healthyRequiredTypeIds = $requiredTypeIds->intersect($healthyTypeIds);

        return [
            'required' => $requiredTypeIds->count(),
            'healthy' => $healthyRequiredTypeIds->count(),
            'missing' => $requiredTypeIds->diff($healthyTypeIds)->count(),
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
