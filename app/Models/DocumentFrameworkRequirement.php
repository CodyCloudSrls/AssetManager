<?php

namespace App\Models;

use App\Models\Traits\Searchable;
use App\Presenters\DocumentFrameworkRequirementPresenter;
use App\Presenters\Presentable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Gate;
use Watson\Validating\ValidatingTrait;

class DocumentFrameworkRequirement extends SnipeModel
{
    use HasFactory;
    use Presentable;
    use Searchable;
    use SoftDeletes;
    use ValidatingTrait;

    public const COVERAGE_MISSING = 'missing';
    public const COVERAGE_SUPPORTING_ONLY = 'supporting_only';
    public const COVERAGE_AT_RISK = 'at_risk';
    public const COVERAGE_COVERED = 'covered';

    protected $presenter = DocumentFrameworkRequirementPresenter::class;

    protected $table = 'document_framework_requirements';

    protected $fillable = [
        'document_framework_id',
        'parent_id',
        'code',
        'title',
        'domain',
        'is_mandatory',
        'is_active',
        'owner_id',
        'default_document_type_id',
        'review_frequency_months',
        'sort_order',
        'description',
        'evidence_guidance',
        'applicability_notes',
        'created_by',
    ];

    protected $casts = [
        'document_framework_id' => 'integer',
        'parent_id' => 'integer',
        'owner_id' => 'integer',
        'default_document_type_id' => 'integer',
        'review_frequency_months' => 'integer',
        'sort_order' => 'integer',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    protected $rules = [
        'document_framework_id' => 'required|integer|exists:document_frameworks,id',
        'parent_id' => 'nullable|integer|exists:document_framework_requirements,id',
        'code' => 'required|string|max:100',
        'title' => 'required|string|max:255',
        'domain' => 'nullable|string|max:120',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'owner_id' => 'nullable|integer|exists:users,id',
        'default_document_type_id' => 'nullable|integer|exists:document_types,id',
        'review_frequency_months' => 'nullable|integer|min:1|max:120',
        'sort_order' => 'nullable|integer|min:0|max:65535',
        'description' => 'nullable|string|max:65535',
        'evidence_guidance' => 'nullable|string|max:65535',
        'applicability_notes' => 'nullable|string|max:65535',
    ];

    protected $searchableAttributes = [
        'code',
        'title',
        'domain',
        'description',
        'evidence_guidance',
        'applicability_notes',
    ];

    protected $searchableRelations = [
        'framework' => ['name', 'framework_code', 'version'],
        'owner' => ['first_name', 'last_name', 'username', 'display_name'],
        'defaultDocumentType' => ['name'],
        'adminuser' => ['first_name', 'last_name', 'display_name'],
    ];

    protected $searchableCounts = [
        'documents_count',
    ];

    public function framework()
    {
        return $this->belongsTo(DocumentFramework::class, 'document_framework_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id')->withTrashed();
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id')->withTrashed();
    }

    public function defaultDocumentType()
    {
        return $this->belongsTo(DocumentType::class, 'default_document_type_id')->withTrashed();
    }

    public function documents()
    {
        return $this->belongsToMany(Document::class, 'document_framework_requirement_document')
            ->withPivot(['coverage_role', 'notes', 'covered_at', 'created_by'])
            ->withTimestamps();
    }

    public function primaryDocuments()
    {
        return $this->documents()->wherePivot('coverage_role', 'primary');
    }

    public function supportingDocuments()
    {
        return $this->documents()->wherePivot('coverage_role', 'supporting');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('code')->orderBy('title');
    }

    public function scopeForFramework($query, int $frameworkId)
    {
        return $query->where('document_framework_id', $frameworkId);
    }

    public function scopeVisibleThroughFramework($query)
    {
        return $query->whereHas('framework');
    }

    public function scopeOrderByFramework($query, $order)
    {
        return $query->leftJoin('document_frameworks as requirement_framework_sort', 'document_framework_requirements.document_framework_id', '=', 'requirement_framework_sort.id')
            ->select('document_framework_requirements.*')
            ->orderBy('requirement_framework_sort.name', $order);
    }

    public function scopeOrderByOwner($query, $order)
    {
        return $query->leftJoin('users as requirement_owner_sort', 'document_framework_requirements.owner_id', '=', 'requirement_owner_sort.id')
            ->select('document_framework_requirements.*')
            ->orderBy('requirement_owner_sort.first_name', $order)
            ->orderBy('requirement_owner_sort.last_name', $order);
    }

    public function scopeOrderByCreatedBy($query, $order)
    {
        return $query->leftJoin('users as requirement_admin_sort', 'document_framework_requirements.created_by', '=', 'requirement_admin_sort.id')
            ->select('document_framework_requirements.*')
            ->orderBy('requirement_admin_sort.first_name', $order)
            ->orderBy('requirement_admin_sort.last_name', $order);
    }

    public static function coverageOptions(): array
    {
        return [
            self::COVERAGE_MISSING => trans('admin/documentframeworkrequirements/general.coverage.missing'),
            self::COVERAGE_SUPPORTING_ONLY => trans('admin/documentframeworkrequirements/general.coverage.supporting_only'),
            self::COVERAGE_AT_RISK => trans('admin/documentframeworkrequirements/general.coverage.at_risk'),
            self::COVERAGE_COVERED => trans('admin/documentframeworkrequirements/general.coverage.covered'),
        ];
    }

    public function getCoverageStatusAttribute(): string
    {
        $documentsCount = (int) ($this->documents_count ?? $this->documents()->count());
        $primaryDocumentsCount = (int) ($this->primary_documents_count ?? $this->primaryDocuments()->count());
        $healthyPrimaryDocumentsCount = (int) ($this->healthy_primary_documents_count ?? $this->healthyPrimaryDocumentsQuery()->count());

        if ($documentsCount === 0) {
            return self::COVERAGE_MISSING;
        }

        if ($primaryDocumentsCount === 0) {
            return self::COVERAGE_SUPPORTING_ONLY;
        }

        if ($healthyPrimaryDocumentsCount === 0) {
            return self::COVERAGE_AT_RISK;
        }

        return self::COVERAGE_COVERED;
    }

    public function getCoverageLabelAttribute(): string
    {
        return static::coverageOptions()[$this->coverage_status] ?? ucfirst(str_replace('_', ' ', $this->coverage_status));
    }

    public function isDeletable(): bool
    {
        return Gate::allows('delete', $this)
            && (($this->documents_count ?? $this->documents()->count()) === 0)
            && ($this->deleted_at == '');
    }

    public function healthyPrimaryDocumentsQuery()
    {
        return $this->primaryDocuments()
            ->where('documents.status', Document::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->whereNull('documents.next_review_at')
                    ->orWhereDate('documents.next_review_at', '>=', Carbon::today());
            });
    }
}
