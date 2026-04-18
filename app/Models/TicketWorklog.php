<?php

namespace App\Models;

use App\Models\Traits\CompanyableTrait;
use App\Models\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Watson\Validating\ValidatingTrait;

class TicketWorklog extends SnipeModel
{
    use CompanyableTrait;
    use HasFactory;
    use Searchable;
    use SoftDeletes;
    use ValidatingTrait;

    protected $table = 'ticket_worklogs';

    public const CATEGORY_ANALYSIS = 'analysis';
    public const CATEGORY_REMOTE = 'remote_support';
    public const CATEGORY_ONSITE = 'onsite';
    public const CATEGORY_VENDOR = 'vendor_coordination';
    public const CATEGORY_DOCUMENTATION = 'documentation';
    public const CATEGORY_ADMIN = 'admin';

    protected $fillable = [
        'ticket_id',
        'company_id',
        'user_id',
        'category',
        'is_billable',
        'started_at',
        'ended_at',
        'minutes',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'ticket_id' => 'integer',
        'company_id' => 'integer',
        'user_id' => 'integer',
        'is_billable' => 'boolean',
        'minutes' => 'integer',
        'created_by' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    protected $rules = [
        'ticket_id' => 'required|integer|exists:tickets,id',
        'company_id' => 'nullable|integer|exists:companies,id',
        'user_id' => 'nullable|integer|exists:users,id',
        'category' => 'required|string|in:analysis,remote_support,onsite,vendor_coordination,documentation,admin',
        'is_billable' => 'boolean',
        'started_at' => 'nullable|date',
        'ended_at' => 'nullable|date|after_or_equal:started_at',
        'minutes' => 'required|integer|min:1|max:10080',
        'notes' => 'nullable|string|max:65535',
    ];

    protected $searchableAttributes = [
        'category',
        'notes',
        'minutes',
    ];

    protected $searchableRelations = [
        'ticket' => ['ticket_number', 'subject'],
        'user' => ['first_name', 'last_name', 'username', 'display_name'],
        'company' => ['name'],
    ];

    public static function categoryOptions(): array
    {
        return [
            static::CATEGORY_ANALYSIS => trans('admin/tickets/general.worklog_categories.analysis'),
            static::CATEGORY_REMOTE => trans('admin/tickets/general.worklog_categories.remote_support'),
            static::CATEGORY_ONSITE => trans('admin/tickets/general.worklog_categories.onsite'),
            static::CATEGORY_VENDOR => trans('admin/tickets/general.worklog_categories.vendor_coordination'),
            static::CATEGORY_DOCUMENTATION => trans('admin/tickets/general.worklog_categories.documentation'),
            static::CATEGORY_ADMIN => trans('admin/tickets/general.worklog_categories.admin'),
        ];
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id')->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function setNotesAttribute($value): void
    {
        $this->attributes['notes'] = $value === '' ? null : $value;
    }
}
