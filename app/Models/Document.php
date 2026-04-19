<?php

namespace App\Models;

use App\Models\Traits\CompanyableTrait;
use App\Models\Traits\HasUploads;
use App\Models\Traits\Loggable;
use App\Models\Traits\Searchable;
use App\Presenters\DocumentPresenter;
use App\Presenters\Presentable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Gate;
use Watson\Validating\ValidatingTrait;

class Document extends SnipeModel
{
    use CompanyableTrait;
    use HasFactory;
    use HasUploads;
    use Loggable;
    use Presentable;
    use Searchable;
    use SoftDeletes;
    use ValidatingTrait;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_OBSOLETE = 'obsolete';
    public const STATUS_ARCHIVED = 'archived';

    protected $presenter = DocumentPresenter::class;

    protected $table = 'documents';

    protected $fillable = [
        'name',
        'company_id',
        'owner_id',
        'document_type_id',
        'document_framework_id',
        'document_number',
        'reference',
        'version',
        'status',
        'classification',
        'retention_period',
        'scope',
        'issued_at',
        'effective_at',
        'next_review_at',
        'control_url',
        'summary',
        'notes',
    ];

    public const COVERAGE_PRIMARY = 'primary';
    public const COVERAGE_SUPPORTING = 'supporting';

    protected $casts = [
        'company_id' => 'integer',
        'owner_id' => 'integer',
        'document_type_id' => 'integer',
        'document_framework_id' => 'integer',
        'created_by' => 'integer',
        'issued_at' => 'date',
        'effective_at' => 'date',
        'next_review_at' => 'date',
    ];

    protected $rules = [
        'name' => 'required|string|max:255',
        'company_id' => 'nullable|integer',
        'owner_id' => 'nullable|integer',
        'document_type_id' => 'nullable|integer',
        'document_framework_id' => 'nullable|integer',
        'document_number' => 'nullable|string|max:100',
        'reference' => 'nullable|string|max:255',
        'version' => 'nullable|string|max:50',
        'status' => 'required|string|in:draft,active,in_review,obsolete,archived',
        'classification' => 'nullable|string|max:100',
        'retention_period' => 'nullable|string|max:100',
        'scope' => 'nullable|string|max:150',
        'issued_at' => 'nullable|date_format:Y-m-d',
        'effective_at' => 'nullable|date_format:Y-m-d',
        'next_review_at' => 'nullable|date_format:Y-m-d',
        'control_url' => 'nullable|url|max:2048',
        'summary' => 'nullable|string|max:65535',
        'notes' => 'nullable|string|max:65535',
    ];

    protected $searchableAttributes = [
        'name',
        'document_number',
        'reference',
        'version',
        'status',
        'classification',
        'retention_period',
        'scope',
        'summary',
        'notes',
        'control_url',
    ];

    protected $searchableRelations = [
        'company' => ['name'],
        'owner' => ['first_name', 'last_name', 'username', 'display_name'],
        'type' => ['name'],
        'framework' => ['name'],
        'adminuser' => ['first_name', 'last_name', 'display_name'],
    ];

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => trans('admin/documents/general.statuses.draft'),
            self::STATUS_ACTIVE => trans('admin/documents/general.statuses.active'),
            self::STATUS_IN_REVIEW => trans('admin/documents/general.statuses.in_review'),
            self::STATUS_OBSOLETE => trans('admin/documents/general.statuses.obsolete'),
            self::STATUS_ARCHIVED => trans('admin/documents/general.statuses.archived'),
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id')->withTrashed();
    }

    public function type()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function framework()
    {
        return $this->belongsTo(DocumentFramework::class, 'document_framework_id');
    }

    public function frameworkRequirements()
    {
        return $this->belongsToMany(DocumentFrameworkRequirement::class, 'document_framework_requirement_document')
            ->withPivot(['coverage_role', 'notes', 'covered_at', 'created_by'])
            ->withTimestamps();
    }

    public function journal()
    {
        return $this->assetlog()
            ->where('action_type', '=', 'note added')
            ->orderBy('created_at', 'asc');
    }

    public function assetlog()
    {
        return $this->hasMany(Actionlog::class, 'item_id')
            ->where('item_type', '=', self::class)
            ->orderBy('created_at', 'desc')
            ->withTrashed();
    }

    public function isDeletable()
    {
        return Gate::allows('delete', $this) && ($this->deleted_at == '');
    }

    public function setIssuedAtAttribute($value)
    {
        $this->attributes['issued_at'] = ($value === '' ? null : $value);
    }

    public function setEffectiveAtAttribute($value)
    {
        $this->attributes['effective_at'] = ($value === '' ? null : $value);
    }

    public function setNextReviewAtAttribute($value)
    {
        $this->attributes['next_review_at'] = ($value === '' ? null : $value);
    }

    public function setNotesAttribute($value)
    {
        $this->attributes['notes'] = ($value === '' ? null : $value);
    }

    public function setSummaryAttribute($value)
    {
        $this->attributes['summary'] = ($value === '' ? null : $value);
    }

    public function setControlUrlAttribute($value)
    {
        $this->attributes['control_url'] = ($value === '' ? null : $value);
    }

    public function getDisplayNameAttribute()
    {
        return $this->name;
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInReview($query)
    {
        return $query->where('status', self::STATUS_IN_REVIEW);
    }

    public function scopeObsolete($query)
    {
        return $query->where('status', self::STATUS_OBSOLETE);
    }

    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    public function scopeDueForReview($query, int $days = 30)
    {
        return $query->whereNotNull('next_review_at')
            ->whereDate('next_review_at', '>=', Carbon::today())
            ->whereDate('next_review_at', '<=', Carbon::today()->addDays($days));
    }

    public function scopeOverdueForReview($query)
    {
        return $query->whereNotNull('next_review_at')
            ->whereDate('next_review_at', '<', Carbon::today());
    }

    public function scopeOrderByCompany($query, $order)
    {
        return $query->leftJoin('companies as documents_companies', 'documents.company_id', '=', 'documents_companies.id')
            ->select('documents.*')
            ->orderBy('documents_companies.name', $order);
    }

    public function scopeOrderByOwner($query, $order)
    {
        return $query->leftJoin('users as document_owners', 'documents.owner_id', '=', 'document_owners.id')
            ->select('documents.*')
            ->orderBy('document_owners.first_name', $order)
            ->orderBy('document_owners.last_name', $order);
    }

    public function scopeOrderByFramework($query, $order)
    {
        return $query->leftJoin('document_frameworks as frameworks_sort', 'documents.document_framework_id', '=', 'frameworks_sort.id')
            ->select('documents.*')
            ->orderBy('frameworks_sort.name', $order);
    }

    public function scopeOrderByType($query, $order)
    {
        return $query->leftJoin('document_types as types_sort', 'documents.document_type_id', '=', 'types_sort.id')
            ->select('documents.*')
            ->orderBy('types_sort.name', $order);
    }

    public static function coverageRoleOptions(): array
    {
        return [
            self::COVERAGE_PRIMARY => trans('admin/documents/general.coverage_roles.primary'),
            self::COVERAGE_SUPPORTING => trans('admin/documents/general.coverage_roles.supporting'),
        ];
    }
}
