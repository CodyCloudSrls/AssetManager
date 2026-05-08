<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use LogicException;

class DocumentAssignmentEvent extends SnipeModel
{
    use HasFactory;

    public const EVENT_CREATED = 'created';
    public const EVENT_UPDATED = 'updated';
    public const EVENT_DELETED = 'deleted';
    public const EVENT_APPROVAL_STATUS_CHANGED = 'approval_status_changed';

    protected $table = 'document_assignment_events';

    public $timestamps = false;

    protected $fillable = [
        'document_assignment_id',
        'document_id',
        'company_id',
        'event_type',
        'approval_status',
        'actor_id',
        'old_values',
        'new_values',
        'note',
        'remote_ip',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'document_assignment_id' => 'integer',
        'document_id' => 'integer',
        'company_id' => 'integer',
        'actor_id' => 'integer',
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new LogicException('Document assignment audit events are append-only.');
        }

        return parent::save($options);
    }

    public function delete()
    {
        throw new LogicException('Document assignment audit events cannot be deleted.');
    }

    public function documentAssignment()
    {
        return $this->belongsTo(DocumentAssignment::class, 'document_assignment_id')->withTrashed();
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id')->withTrashed();
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }

    public static function eventTypeOptions(): array
    {
        return [
            self::EVENT_CREATED => trans('admin/documents/general.assignment_event_types.created'),
            self::EVENT_UPDATED => trans('admin/documents/general.assignment_event_types.updated'),
            self::EVENT_DELETED => trans('admin/documents/general.assignment_event_types.deleted'),
            self::EVENT_APPROVAL_STATUS_CHANGED => trans('admin/documents/general.assignment_event_types.approval_status_changed'),
        ];
    }

    public function getEventTypeLabelAttribute(): string
    {
        return self::eventTypeOptions()[$this->event_type] ?? $this->event_type;
    }

    public function getApprovalStatusLabelAttribute(): ?string
    {
        if (blank($this->approval_status)) {
            return null;
        }

        return DocumentAssignment::approvalStatusOptions()[$this->approval_status] ?? $this->approval_status;
    }
}
