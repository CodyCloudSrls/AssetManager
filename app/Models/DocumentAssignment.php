<?php

namespace App\Models;

use App\Models\Traits\CompanyableTrait;
use App\Models\Traits\Searchable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Watson\Validating\ValidatingTrait;

class DocumentAssignment extends SnipeModel
{
    use CompanyableTrait;
    use HasFactory;
    use Searchable;
    use SoftDeletes;
    use ValidatingTrait;

    public const ASSIGNABLE_USER = 'user';
    public const ASSIGNABLE_ASSET = 'asset';
    public const ASSIGNABLE_LOCATION = 'location';
    public const ASSIGNABLE_SUPPLIER = 'supplier';
    public const ASSIGNABLE_CUSTOMER = 'customer';

    public const RELATION_ISSUED_TO = 'issued_to';
    public const RELATION_APPLIES_TO = 'applies_to';
    public const RELATION_REQUIRED_FOR = 'required_for';
    public const RELATION_EVIDENCE_FOR = 'evidence_for';

    public const STATUS_PLANNED = 'planned';
    public const STATUS_REQUIRED = 'required';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';

    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_SUBMITTED = 'submitted';
    public const APPROVAL_IN_REVIEW = 'in_review';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';

    protected $table = 'document_assignments';

    protected $fillable = [
        'document_id',
        'company_id',
        'assignable_type',
        'assignable_id',
        'relation_type',
        'status',
        'approval_status',
        'issuer_id',
        'reviewer_id',
        'reference_number',
        'issued_at',
        'effective_at',
        'expires_at',
        'renewal_due_at',
        'completed_at',
        'revoked_at',
        'reviewed_at',
        'notes',
        'review_notes',
    ];

    protected $casts = [
        'document_id' => 'integer',
        'company_id' => 'integer',
        'assignable_id' => 'integer',
        'issuer_id' => 'integer',
        'reviewer_id' => 'integer',
        'created_by' => 'integer',
        'issued_at' => 'date',
        'effective_at' => 'date',
        'expires_at' => 'date',
        'renewal_due_at' => 'date',
        'completed_at' => 'date',
        'revoked_at' => 'date',
        'reviewed_at' => 'datetime',
    ];

    protected $rules = [
        'document_id' => 'required|integer|exists:documents,id',
        'company_id' => 'required|integer|exists:companies,id',
        'assignable_type' => 'required|string',
        'assignable_id' => 'required|integer',
        'relation_type' => 'required|string',
        'status' => 'required|string',
        'approval_status' => 'required|string',
        'issuer_id' => 'nullable|integer|exists:users,id',
        'reviewer_id' => 'nullable|integer|exists:users,id',
        'reference_number' => 'nullable|string|max:100',
        'notes' => 'nullable|string|max:65535',
        'review_notes' => 'nullable|string|max:65535',
    ];

    protected $searchableAttributes = [
        'relation_type',
        'status',
        'approval_status',
        'reference_number',
        'notes',
        'review_notes',
        'issued_at',
        'effective_at',
        'expires_at',
        'renewal_due_at',
        'reviewed_at',
    ];

    protected $searchableRelations = [
        'document' => ['name', 'document_number', 'reference'],
        'document.type' => ['name'],
        'company' => ['name'],
        'issuer' => ['first_name', 'last_name', 'display_name', 'username', 'email'],
        'reviewer' => ['first_name', 'last_name', 'display_name', 'username', 'email'],
    ];

    protected $searchableRelationAliases = [
        'document_number' => 'document',
        'document_type' => 'document.type',
    ];

    public static function relationTypeOptions(): array
    {
        return [
            self::RELATION_ISSUED_TO => trans('admin/documents/form.assignment_relation_issued_to'),
            self::RELATION_APPLIES_TO => trans('admin/documents/form.assignment_relation_applies_to'),
            self::RELATION_REQUIRED_FOR => trans('admin/documents/form.assignment_relation_required_for'),
            self::RELATION_EVIDENCE_FOR => trans('admin/documents/form.assignment_relation_evidence_for'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PLANNED => trans('admin/documents/general.assignment_statuses.planned'),
            self::STATUS_REQUIRED => trans('admin/documents/general.assignment_statuses.required'),
            self::STATUS_ACTIVE => trans('admin/documents/general.assignment_statuses.active'),
            self::STATUS_COMPLETED => trans('admin/documents/general.assignment_statuses.completed'),
            self::STATUS_EXPIRED => trans('admin/documents/general.assignment_statuses.expired'),
            self::STATUS_REVOKED => trans('admin/documents/general.assignment_statuses.revoked'),
        ];
    }

    public static function approvalStatusOptions(): array
    {
        return [
            self::APPROVAL_PENDING => trans('admin/documents/general.assignment_approval_statuses.pending'),
            self::APPROVAL_SUBMITTED => trans('admin/documents/general.assignment_approval_statuses.submitted'),
            self::APPROVAL_IN_REVIEW => trans('admin/documents/general.assignment_approval_statuses.in_review'),
            self::APPROVAL_APPROVED => trans('admin/documents/general.assignment_approval_statuses.approved'),
            self::APPROVAL_REJECTED => trans('admin/documents/general.assignment_approval_statuses.rejected'),
        ];
    }

    public static function assignableTypeOptions(): array
    {
        return [
            self::ASSIGNABLE_USER => trans('general.user'),
            self::ASSIGNABLE_ASSET => trans('general.asset'),
            self::ASSIGNABLE_LOCATION => trans('general.location'),
            self::ASSIGNABLE_SUPPLIER => trans('general.supplier'),
            self::ASSIGNABLE_CUSTOMER => trans('general.customer'),
        ];
    }

    public static function assignableClassMap(): array
    {
        return [
            self::ASSIGNABLE_USER => User::class,
            self::ASSIGNABLE_ASSET => Asset::class,
            self::ASSIGNABLE_LOCATION => Location::class,
            self::ASSIGNABLE_SUPPLIER => Supplier::class,
            self::ASSIGNABLE_CUSTOMER => Customer::class,
        ];
    }

    public static function tokenForAssignableClass(?string $className): ?string
    {
        if (is_null($className)) {
            return null;
        }

        return array_search($className, self::assignableClassMap(), true) ?: null;
    }

    public static function classForAssignableToken(?string $token): ?string
    {
        if (blank($token)) {
            return null;
        }

        return self::assignableClassMap()[$token] ?? null;
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id')->withTrashed();
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issuer_id')->withTrashed();
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id')->withTrashed();
    }

    public function adminuser()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function events()
    {
        return $this->hasMany(DocumentAssignmentEvent::class, 'document_assignment_id')->orderByDesc('created_at')->orderByDesc('id');
    }

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getAssignableTypeLabelAttribute(): string
    {
        return self::assignableTypeOptions()[self::tokenForAssignableClass($this->assignable_type)] ?? class_basename((string) $this->assignable_type);
    }

    public function getRelationTypeLabelAttribute(): string
    {
        return self::relationTypeOptions()[$this->relation_type] ?? $this->relation_type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    public function getApprovalStatusLabelAttribute(): string
    {
        return self::approvalStatusOptions()[$this->approval_status] ?? (string) $this->approval_status;
    }

    public function getApprovalStatusClassAttribute(): string
    {
        return match ($this->approval_status) {
            self::APPROVAL_SUBMITTED,
            self::APPROVAL_IN_REVIEW => 'label label-warning',
            self::APPROVAL_APPROVED => 'label label-success',
            self::APPROVAL_REJECTED => 'label label-danger',
            default => 'label label-default',
        };
    }

    public function getAssignableDisplayNameAttribute(): ?string
    {
        $assignable = $this->assignable;

        if (! $assignable) {
            return null;
        }

        return match (true) {
            $assignable instanceof User => $assignable->display_name,
            $assignable instanceof Asset => $assignable->present()->fullName,
            $assignable instanceof Location => $assignable->name,
            $assignable instanceof Supplier => $assignable->name,
            $assignable instanceof Customer => $assignable->name,
            default => method_exists($assignable, 'getDisplayNameAttribute') ? $assignable->display_name : (string) ($assignable->name ?? $assignable->id),
        };
    }

    public function getAssignableUrlAttribute(): ?string
    {
        $assignable = $this->assignable;

        if (! $assignable) {
            return null;
        }

        return match (true) {
            $assignable instanceof User => route('users.show', $assignable),
            $assignable instanceof Asset => route('hardware.show', $assignable),
            $assignable instanceof Location => route('locations.show', $assignable),
            $assignable instanceof Supplier => route('suppliers.show', $assignable),
            $assignable instanceof Customer => route('customers.show', $assignable),
            default => null,
        };
    }

    public function getIsExpiringAttribute(): bool
    {
        return $this->renewal_due_at
            && $this->renewal_due_at->isFuture()
            && $this->renewal_due_at->lte(Carbon::today()->addDays(30));
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->lt(Carbon::today());
    }
}
