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

    protected $table = 'document_assignments';

    protected $fillable = [
        'document_id',
        'company_id',
        'assignable_type',
        'assignable_id',
        'relation_type',
        'status',
        'issuer_id',
        'reference_number',
        'issued_at',
        'effective_at',
        'expires_at',
        'renewal_due_at',
        'completed_at',
        'revoked_at',
        'notes',
    ];

    protected $casts = [
        'document_id' => 'integer',
        'company_id' => 'integer',
        'assignable_id' => 'integer',
        'issuer_id' => 'integer',
        'created_by' => 'integer',
        'issued_at' => 'date',
        'effective_at' => 'date',
        'expires_at' => 'date',
        'renewal_due_at' => 'date',
        'completed_at' => 'date',
        'revoked_at' => 'date',
    ];

    protected $rules = [
        'document_id' => 'required|integer|exists:documents,id',
        'company_id' => 'required|integer|exists:companies,id',
        'assignable_type' => 'required|string',
        'assignable_id' => 'required|integer',
        'relation_type' => 'required|string',
        'status' => 'required|string',
        'issuer_id' => 'nullable|integer|exists:users,id',
        'reference_number' => 'nullable|string|max:100',
        'notes' => 'nullable|string|max:65535',
    ];

    protected $searchableAttributes = [
        'relation_type',
        'status',
        'reference_number',
        'notes',
        'issued_at',
        'effective_at',
        'expires_at',
        'renewal_due_at',
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

    public static function assignableTypeOptions(): array
    {
        return [
            self::ASSIGNABLE_USER => trans('general.user'),
            self::ASSIGNABLE_ASSET => trans('general.asset'),
            self::ASSIGNABLE_LOCATION => trans('general.location'),
        ];
    }

    public static function assignableClassMap(): array
    {
        return [
            self::ASSIGNABLE_USER => User::class,
            self::ASSIGNABLE_ASSET => Asset::class,
            self::ASSIGNABLE_LOCATION => Location::class,
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

    public function adminuser()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
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
