<?php

namespace App\Models;

use App\Models\Traits\Searchable;
use App\Models\Traits\TenantTemplateTrait;
use App\Presenters\Presentable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Watson\Validating\ValidatingTrait;

class TicketType extends SnipeModel
{
    use HasFactory;
    use Presentable;
    use Searchable;
    use SoftDeletes;
    use TenantTemplateTrait;
    use ValidatingTrait;

    protected $table = 'ticket_types';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'is_public',
        'sort_order',
        'company_id',
        'visibility_type',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'sort_order' => 'integer',
        'company_id' => 'integer',
        'created_by' => 'integer',
    ];

    protected $rules = [
        'name' => 'required|string|max:255',
        'slug' => 'nullable|string|max:255',
        'description' => 'nullable|string|max:65535',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'sort_order' => 'nullable|integer|min:0|max:65535',
        'company_id' => 'nullable|integer|exists:companies,id',
        'visibility_type' => 'required|string|in:private,descendants,global',
    ];

    protected $searchableAttributes = [
        'name',
        'slug',
        'description',
        'visibility_type',
    ];

    protected $searchableRelations = [
        'company' => ['name'],
        'adminuser' => ['first_name', 'last_name', 'display_name'],
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'ticket_type_id');
    }

    public function isDeletable(): bool
    {
        return Gate::allows('delete', $this)
            && (($this->tickets_count ?? $this->tickets()->count()) === 0)
            && ($this->deleted_at == '');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function setSlugAttribute($value): void
    {
        $source = $value ?: ($this->attributes['name'] ?? null);
        $this->attributes['slug'] = $source ? Str::slug($source) : null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value;

        if (! array_key_exists('slug', $this->attributes) || blank($this->attributes['slug'])) {
            $this->attributes['slug'] = $value ? Str::slug($value) : null;
        }
    }
}
