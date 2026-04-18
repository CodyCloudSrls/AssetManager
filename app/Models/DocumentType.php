<?php

namespace App\Models;

use App\Models\Traits\Searchable;
use App\Models\Traits\TenantTemplateTrait;
use App\Presenters\Presentable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Watson\Validating\ValidatingTrait;

class DocumentType extends SnipeModel
{
    use HasFactory;
    use Presentable;
    use Searchable;
    use SoftDeletes;
    use TenantTemplateTrait;
    use ValidatingTrait;

    protected $table = 'document_types';

    protected $hidden = ['created_by', 'deleted_at'];

    protected $rules = [
        'name' => 'required|string|max:255',
        'slug' => 'nullable|string|max:255',
        'description' => 'nullable|string|max:65535',
        'sort_order' => 'nullable|integer|min:0|max:65535',
        'is_active' => 'boolean',
        'company_id' => 'nullable|integer|exists:companies,id',
        'visibility_type' => 'required|string|in:private,descendants,global',
    ];

    protected $injectUniqueIdentifier = true;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
        'created_by',
        'company_id',
        'visibility_type',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'created_by' => 'integer',
        'company_id' => 'integer',
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

    protected $searchableCounts = [
        'documents_count',
    ];

    public function documents()
    {
        return $this->hasMany(Document::class, 'document_type_id');
    }

    public function isDeletable()
    {
        return Gate::allows('delete', $this)
            && (($this->documents_count ?? $this->documents()->count()) === 0)
            && ($this->deleted_at == '');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeOrderByCreatedBy($query, $order)
    {
        return $query->leftJoin('users as admin_sort', 'document_types.created_by', '=', 'admin_sort.id')
            ->select('document_types.*')
            ->orderBy('admin_sort.first_name', $order)
            ->orderBy('admin_sort.last_name', $order);
    }

    public function setSlugAttribute($value)
    {
        $source = $value ?: $this->attributes['name'] ?? null;
        $this->attributes['slug'] = $source ? Str::slug($source) : null;
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;

        if (! array_key_exists('slug', $this->attributes) || blank($this->attributes['slug'])) {
            $this->attributes['slug'] = $value ? Str::slug($value) : null;
        }
    }
}
