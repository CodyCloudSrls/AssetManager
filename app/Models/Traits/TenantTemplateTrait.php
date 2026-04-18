<?php

namespace App\Models\Traits;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;

trait TenantTemplateTrait
{
    public const VISIBILITY_PRIVATE = 'private';
    public const VISIBILITY_DESCENDANTS = 'descendants';
    public const VISIBILITY_GLOBAL = 'global';

    public static function bootTenantTemplateTrait(): void
    {
        static::addGlobalScope('tenant_template_visibility', function (Builder $builder) {
            Company::scopeTemplateVisibility($builder);
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function isTenantScopedTemplate(): bool
    {
        return true;
    }

    public function isGlobalTemplate(): bool
    {
        return is_null($this->company_id);
    }

    public function isSharedToDescendants(): bool
    {
        return $this->visibility_type === static::VISIBILITY_DESCENDANTS;
    }

    public static function visibilityOptions(): array
    {
        return [
            static::VISIBILITY_PRIVATE => trans('general.template_visibility.private'),
            static::VISIBILITY_DESCENDANTS => trans('general.template_visibility.descendants'),
            static::VISIBILITY_GLOBAL => trans('general.template_visibility.global'),
        ];
    }

    public function getVisibilityLabelAttribute(): string
    {
        return static::visibilityOptions()[$this->visibility_type] ?? ucfirst((string) $this->visibility_type);
    }
}
