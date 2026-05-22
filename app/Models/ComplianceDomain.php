<?php

namespace App\Models;

use App\Models\Traits\Searchable;
use App\Presenters\ComplianceDomainPresenter;
use App\Presenters\Presentable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Watson\Validating\ValidatingTrait;

class ComplianceDomain extends SnipeModel
{
    use HasFactory;
    use Presentable;
    use Searchable;
    use SoftDeletes;
    use ValidatingTrait;

    protected $table = 'compliance_domains';

    protected $presenter = ComplianceDomainPresenter::class;

    protected $fillable = [
        'key',
        'name',
        'description',
        'is_active',
        'is_system',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'sort_order' => 'integer',
        'created_by' => 'integer',
    ];

    protected $rules = [
        'key' => 'required|string|max:80',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:65535',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'sort_order' => 'nullable|integer|min:0|max:65535',
    ];

    protected $searchableAttributes = [
        'key',
        'name',
        'description',
    ];

    protected $searchableRelations = [
        'adminuser' => ['first_name', 'last_name', 'display_name'],
    ];

    public static function defaultDefinitions(): array
    {
        return [
            'nis2' => trans('admin/documentframeworks/general.compliance_domains.nis2'),
            'gdpr' => trans('admin/documentframeworks/general.compliance_domains.gdpr'),
            'ai_act' => trans('admin/documentframeworks/general.compliance_domains.ai_act'),
            'iso27001' => trans('admin/documentframeworks/general.compliance_domains.iso27001'),
            'supplier_risk' => trans('admin/documentframeworks/general.compliance_domains.supplier_risk'),
            'internal' => trans('admin/documentframeworks/general.compliance_domains.internal'),
            'custom' => trans('admin/documentframeworks/general.compliance_domains.custom'),
        ];
    }

    public static function options(bool $activeOnly = true): array
    {
        if (! self::tableIsAvailable()) {
            return self::defaultDefinitions();
        }

        $query = self::query()->ordered();

        if ($activeOnly) {
            $query->active();
        }

        $options = $query->pluck('name', 'key')->all();

        return $options ?: self::defaultDefinitions();
    }

    public static function isValidKey(?string $key, bool $activeOnly = true): bool
    {
        $key = self::normalizeKey($key);

        if ($key === null) {
            return true;
        }

        if (! self::tableIsAvailable()) {
            return array_key_exists($key, self::defaultDefinitions());
        }

        return self::query()
            ->where('key', $key)
            ->when($activeOnly, fn ($query) => $query->active())
            ->exists();
    }

    public static function normalizeKey(?string $key): ?string
    {
        $key = trim((string) $key);

        return $key === '' ? null : Str::slug($key, '_');
    }

    public static function tableIsAvailable(): bool
    {
        try {
            return Schema::hasTable('compliance_domains');
        } catch (\Throwable) {
            return false;
        }
    }

    public function setKeyAttribute($value): void
    {
        $this->attributes['key'] = self::normalizeKey($value);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name')->orderBy('key');
    }

    public function isDeletable(): bool
    {
        return ! $this->is_system && ! DocumentFramework::where('compliance_domain', $this->key)->exists();
    }
}
