<?php

namespace App\Models;

use App\Enums\ActionType;
use App\Models\Traits\CompanyableTrait;
use App\Models\Traits\HasUploads;
use App\Models\Traits\Loggable;
use App\Models\Traits\Searchable;
use App\Presenters\Presentable;
use App\Presenters\TicketPresenter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Watson\Validating\ValidatingTrait;

class Ticket extends SnipeModel
{
    use CompanyableTrait;
    use HasFactory;
    use HasUploads;
    use Loggable;
    use Presentable;
    use Searchable;
    use SoftDeletes;
    use ValidatingTrait;

    protected $presenter = TicketPresenter::class;

    protected $table = 'tickets';

    public const SOURCE_INTERNAL = 'internal';
    public const SOURCE_PUBLIC = 'public';
    public const SOURCE_EMAIL = 'email';

    protected $fillable = [
        'company_id',
        'requester_id',
        'assignee_id',
        'ticket_type_id',
        'ticket_status_id',
        'ticket_priority_id',
        'asset_id',
        'document_id',
        'location_id',
        'related_user_id',
        'source',
        'portal_token',
        'subject',
        'description',
        'guest_name',
        'guest_email',
        'guest_phone',
        'first_response_due_at',
        'resolution_due_at',
        'first_responded_at',
        'last_replied_at',
        'last_public_reply_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'requester_id' => 'integer',
        'assignee_id' => 'integer',
        'ticket_type_id' => 'integer',
        'ticket_status_id' => 'integer',
        'ticket_priority_id' => 'integer',
        'asset_id' => 'integer',
        'document_id' => 'integer',
        'location_id' => 'integer',
        'related_user_id' => 'integer',
        'created_by' => 'integer',
        'first_response_due_at' => 'datetime',
        'resolution_due_at' => 'datetime',
        'first_responded_at' => 'datetime',
        'last_replied_at' => 'datetime',
        'last_public_reply_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected $rules = [
        'company_id' => 'nullable|integer|exists:companies,id',
        'requester_id' => 'nullable|integer|exists:users,id',
        'assignee_id' => 'nullable|integer|exists:users,id',
        'ticket_type_id' => 'nullable|integer|exists:ticket_types,id',
        'ticket_status_id' => 'nullable|integer|exists:ticket_statuses,id',
        'ticket_priority_id' => 'nullable|integer|exists:ticket_priorities,id',
        'asset_id' => 'nullable|integer|exists:assets,id',
        'document_id' => 'nullable|integer|exists:documents,id',
        'location_id' => 'nullable|integer|exists:locations,id',
        'related_user_id' => 'nullable|integer|exists:users,id',
        'source' => 'required|string|in:internal,public,email',
        'subject' => 'required|string|max:255',
        'description' => 'required|string|max:65535',
        'guest_name' => 'nullable|string|max:255',
        'guest_email' => 'nullable|email|max:255',
        'guest_phone' => 'nullable|string|max:80',
        'first_response_due_at' => 'nullable|date',
        'resolution_due_at' => 'nullable|date',
        'first_responded_at' => 'nullable|date',
        'last_replied_at' => 'nullable|date',
        'last_public_reply_at' => 'nullable|date',
        'resolved_at' => 'nullable|date',
        'closed_at' => 'nullable|date',
    ];

    protected $searchableAttributes = [
        'ticket_number',
        'subject',
        'description',
        'guest_name',
        'guest_email',
        'source',
    ];

    protected $searchableRelations = [
        'company' => ['name'],
        'requester' => ['first_name', 'last_name', 'username', 'display_name', 'email'],
        'assignee' => ['first_name', 'last_name', 'username', 'display_name', 'email'],
        'relatedUser' => ['first_name', 'last_name', 'username', 'display_name', 'email'],
        'asset' => ['asset_tag', 'name', 'serial'],
        'document' => ['name', 'document_number', 'reference'],
        'type' => ['name'],
        'status' => ['name', 'slug'],
        'priority' => ['name', 'slug'],
        'adminuser' => ['first_name', 'last_name', 'display_name'],
    ];

    protected static function booted(): void
    {
        static::creating(function (self $ticket) {
            if (blank($ticket->ticket_number)) {
                $ticket->ticket_number = static::generateTicketNumber();
            }

            if (blank($ticket->portal_token)) {
                $ticket->portal_token = Str::random(48);
            }

            $ticket->syncSlaDeadlines();
        });

        static::updating(function (self $ticket) {
            $ticket->syncSlaDeadlines();
            $ticket->syncLifecycleTimestamps();
        });
    }

    public static function sourceOptions(): array
    {
        return [
            static::SOURCE_INTERNAL => trans('admin/tickets/general.sources.internal'),
            static::SOURCE_PUBLIC => trans('admin/tickets/general.sources.public'),
            static::SOURCE_EMAIL => trans('admin/tickets/general.sources.email'),
        ];
    }

    public static function generateTicketNumber(): string
    {
        do {
            $number = 'TKT-'.Str::upper(Str::random(8));
        } while (static::withoutGlobalScopes()->withTrashed()->where('ticket_number', $number)->exists());

        return $number;
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id')->withTrashed();
    }

    public function adminuser()
    {
        return $this->belongsTo(User::class, 'created_by')
            ->withoutGlobalScopes()
            ->withTrashed();
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id')->withTrashed();
    }

    public function relatedUser()
    {
        return $this->belongsTo(User::class, 'related_user_id')->withTrashed();
    }

    public function type()
    {
        return $this->belongsTo(TicketType::class, 'ticket_type_id');
    }

    public function status()
    {
        return $this->belongsTo(TicketStatus::class, 'ticket_status_id')->withTrashed();
    }

    public function priority()
    {
        return $this->belongsTo(TicketPriority::class, 'ticket_priority_id')->withTrashed();
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id')->withTrashed();
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id')->withTrashed();
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id')->withTrashed();
    }

    public function worklogs()
    {
        return $this->hasMany(TicketWorklog::class, 'ticket_id')->orderByDesc('started_at')->orderByDesc('created_at');
    }

    public function comments()
    {
        return $this->assetlog()
            ->whereIn('action_type', [
                'ticket comment added',
                'ticket public reply',
            ])
            ->orderBy('created_at', 'asc');
    }

    public function publicReplies()
    {
        return $this->assetlog()
            ->where('action_type', 'ticket public reply')
            ->orderBy('created_at', 'asc');
    }

    public function publicUploads()
    {
        return $this->uploads()->whereNull('created_by')->orderBy('created_at', 'asc');
    }

    public function assetlog()
    {
        return $this->hasMany(Actionlog::class, 'item_id')
            ->where('item_type', '=', self::class)
            ->orderBy('created_at', 'desc')
            ->withTrashed();
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->ticket_number.' - '.$this->subject;
    }

    public function getRequesterDisplayNameAttribute(): string
    {
        return $this->requester?->display_name
            ?? $this->guest_name
            ?? $this->guest_email
            ?? trans('general.na');
    }

    public function getCreatedByDisplayNameAttribute(): ?string
    {
        return $this->adminuser?->display_name
            ?? ($this->created_by ? trans('admin/tickets/general.internal_user') : null)
            ?? ($this->source === self::SOURCE_PUBLIC ? ($this->guest_name ?? $this->guest_email ?? trans('admin/tickets/general.public_user')) : null);
    }

    public function isOpen(): bool
    {
        return ! ($this->status?->is_closed ?? false);
    }

    public function isDeletable(): bool
    {
        return $this->deleted_at == '';
    }

    public function scopeOpen($query)
    {
        return $query->whereHas('status', function ($statusQuery) {
            $statusQuery->where('is_closed', false);
        });
    }

    public function scopeClosed($query)
    {
        return $query->whereHas('status', function ($statusQuery) {
            $statusQuery->where('is_closed', true);
        });
    }

    public function scopeAssignedToMe($query, ?int $userId = null)
    {
        $userId = $userId ?: auth()->id();

        return $query->where('assignee_id', $userId);
    }

    public function scopeRequestedByMe($query, ?int $userId = null)
    {
        $userId = $userId ?: auth()->id();

        return $query->where('requester_id', $userId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assignee_id');
    }

    public function scopeWaitingCustomer($query)
    {
        return $query->whereHas('status', function ($statusQuery) {
            $statusQuery->where('slug', 'waiting-customer');
        });
    }

    public function scopeWaitingVendor($query)
    {
        return $query->whereHas('status', function ($statusQuery) {
            $statusQuery->where('slug', 'waiting-vendor');
        });
    }

    public function scopePublicPortal($query)
    {
        return $query->where('source', self::SOURCE_PUBLIC);
    }

    public function scopeSlaAtRisk($query)
    {
        $responseThreshold = Carbon::now()->copy()->addHours(2);
        $resolutionThreshold = Carbon::now()->copy()->addHours(8);

        return $query->open()->where(function ($riskQuery) use ($responseThreshold, $resolutionThreshold) {
            $riskQuery->where(function ($responseQuery) use ($responseThreshold) {
                $responseQuery->whereNull('first_responded_at')
                    ->whereNotNull('first_response_due_at')
                    ->where('first_response_due_at', '<=', $responseThreshold);
            })->orWhere(function ($resolutionQuery) use ($resolutionThreshold) {
                $resolutionQuery->whereNotNull('resolution_due_at')
                    ->where('resolution_due_at', '<=', $resolutionThreshold);
            });
        });
    }

    public function scopeOrderByCompany($query, $order)
    {
        return $query->leftJoin('companies as tickets_companies', 'tickets.company_id', '=', 'tickets_companies.id')
            ->select('tickets.*')
            ->orderBy('tickets_companies.name', $order);
    }

    public function scopeOrderByRequester($query, $order)
    {
        return $query->leftJoin('users as requester_sort', 'tickets.requester_id', '=', 'requester_sort.id')
            ->select('tickets.*')
            ->orderBy('requester_sort.first_name', $order)
            ->orderBy('requester_sort.last_name', $order);
    }

    public function scopeOrderByAssignee($query, $order)
    {
        return $query->leftJoin('users as assignee_sort', 'tickets.assignee_id', '=', 'assignee_sort.id')
            ->select('tickets.*')
            ->orderBy('assignee_sort.first_name', $order)
            ->orderBy('assignee_sort.last_name', $order);
    }

    public function scopeOrderByStatus($query, $order)
    {
        return $query->leftJoin('ticket_statuses as ticket_status_sort', 'tickets.ticket_status_id', '=', 'ticket_status_sort.id')
            ->select('tickets.*')
            ->orderBy('ticket_status_sort.sort_order', $order)
            ->orderBy('ticket_status_sort.name', $order);
    }

    public function scopeOrderByPriority($query, $order)
    {
        return $query->leftJoin('ticket_priorities as ticket_priority_sort', 'tickets.ticket_priority_id', '=', 'ticket_priority_sort.id')
            ->select('tickets.*')
            ->orderBy('ticket_priority_sort.sort_order', $order)
            ->orderBy('ticket_priority_sort.name', $order);
    }

    public function scopeOrderByType($query, $order)
    {
        return $query->leftJoin('ticket_types as ticket_type_sort', 'tickets.ticket_type_id', '=', 'ticket_type_sort.id')
            ->select('tickets.*')
            ->orderBy('ticket_type_sort.sort_order', $order)
            ->orderBy('ticket_type_sort.name', $order);
    }

    public function addComment(string $note, ?User $actor = null, bool $isPublic = false, array $meta = []): Actionlog
    {
        $log = new Actionlog;
        $log->item_type = self::class;
        $log->item_id = $this->id;
        $log->created_by = $actor?->id;
        $log->note = $note;
        $log->log_meta = ! empty($meta) ? json_encode($meta) : null;
        $log->logaction($isPublic ? 'ticket public reply' : 'ticket comment added');

        $this->forceFill([
            'first_responded_at' => ! $isPublic ? ($this->first_responded_at ?: Carbon::now()) : $this->first_responded_at,
            'last_replied_at' => Carbon::now(),
            'last_public_reply_at' => $isPublic ? Carbon::now() : $this->last_public_reply_at,
        ])->saveQuietly();

        return $log;
    }

    public function addWorklog(TicketWorklog $worklog): Actionlog
    {
        $meta = [
            'minutes' => [
                'old' => null,
                'new' => $worklog->minutes,
            ],
            'category' => [
                'old' => null,
                'new' => $worklog->category,
            ],
            'is_billable' => [
                'old' => null,
                'new' => $worklog->is_billable ? 'yes' : 'no',
            ],
        ];

        $log = new Actionlog;
        $log->item_type = self::class;
        $log->item_id = $this->id;
        $log->created_by = $worklog->created_by ?: $worklog->user_id;
        $log->note = $worklog->notes;
        $log->log_meta = json_encode($meta);
        $log->logaction('ticket worklog added');

        $this->forceFill([
            'first_responded_at' => $this->first_responded_at ?: Carbon::now(),
            'last_replied_at' => Carbon::now(),
        ])->saveQuietly();

        return $log;
    }

    public function syncSlaDeadlines(): void
    {
        if (! $this->ticket_priority_id && $this->priority) {
            $this->ticket_priority_id = $this->priority->id;
        }

        $priority = $this->priority ?: ($this->ticket_priority_id ? TicketPriority::find($this->ticket_priority_id) : null);
        $reference = $this->created_at ?: Carbon::now();

        if ($priority && blank($this->first_response_due_at) && $priority->response_hours) {
            $this->first_response_due_at = $reference->copy()->addHours($priority->response_hours);
        }

        if ($priority && blank($this->resolution_due_at) && $priority->resolution_hours) {
            $this->resolution_due_at = $reference->copy()->addHours($priority->resolution_hours);
        }
    }

    public function syncLifecycleTimestamps(): void
    {
        $status = $this->status ?: ($this->ticket_status_id ? TicketStatus::find($this->ticket_status_id) : null);

        if (! $status) {
            return;
        }

        if ($status->slug === 'resolved' && blank($this->resolved_at)) {
            $this->resolved_at = Carbon::now();
        }

        if ($status->is_closed && blank($this->closed_at)) {
            $this->closed_at = Carbon::now();
        }

        if (! $status->is_closed) {
            $this->closed_at = null;
            if ($status->slug !== 'resolved') {
                $this->resolved_at = null;
            }
        }
    }

    public function setDescriptionAttribute($value): void
    {
        $this->attributes['description'] = $value === '' ? null : $value;
    }

    public function setGuestNameAttribute($value): void
    {
        $this->attributes['guest_name'] = $value === '' ? null : $value;
    }

    public function setGuestEmailAttribute($value): void
    {
        $this->attributes['guest_email'] = $value === '' ? null : $value;
    }

    public function setGuestPhoneAttribute($value): void
    {
        $this->attributes['guest_phone'] = $value === '' ? null : $value;
    }
}
