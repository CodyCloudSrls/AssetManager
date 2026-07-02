<?php

namespace App\Models;

use App\Models\Traits\CompanyableTrait;
use App\Models\Traits\HasUploads;
use App\Models\Traits\Loggable;
use App\Models\Traits\Searchable;
use App\Presenters\CompanyPresenter;
use App\Presenters\Presentable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Watson\Validating\ValidatingTrait;

/**
 * Model for Companies.
 *
 * @version v1.8
 */
final class Company extends SnipeModel
{
    public const ACTIVE_COMPANY_SESSION_KEY = 'active_company_id';

    use CompanyableTrait;
    use HasFactory;
    use HasUploads;
    use Loggable;
    use SoftDeletes;

    protected $table = 'companies';

    // Declare the rules for the model validation
    protected $rules = [
        'name' => 'required|max:255|unique:companies,name',
        'fax' => 'min:7|max:35|nullable',
        'phone' => 'min:3|max:35|nullable',
        'email' => 'email|max:150|nullable',
        'parent_id' => 'nullable|exists:companies,id|non_circular:companies,id',
    ];

    protected $presenter = CompanyPresenter::class;

    use Presentable;

    /**
     * Whether the model should inject it's identifier to the unique
     * validation rules before attempting validation. If this property
     * is not set in the model it will default to true.
     *
     * @var bool
     */
    protected $injectUniqueIdentifier = true;

    use Searchable;
    use ValidatingTrait;

    /**
     * The attributes that should be included when searching the model.
     *
     * @var array
     */
    protected $searchableAttributes = [
        'name',
        'phone',
        'fax',
        'email',
        'created_at',
        'updated_at',
        'notes',
    ];

    /**
     * The relations and their attributes that should be included when searching the model.
     *
     * @var array
     */
    protected $searchableRelations = [
        'adminuser' => ['first_name', 'last_name', 'display_name'],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'parent_id',
        'tenant_id',
        'phone',
        'fax',
        'email',
        'created_by',
        'tag_color',
        'notes',
        'brand',
        'brand_logo',
        'favicon',
        'header_color',
        'nav_link_color',
        'link_light_color',
        'link_dark_color',
        'footer_text',
        'privacy_policy_link',
        'custom_css',
        'helpdesk_enabled',
        'helpdesk_allow_attachments',
        'helpdesk_slug',
        'helpdesk_contact_email',
        'helpdesk_contact_phone',
        'tenant_notification_email',
        'tenant_mail_reply_to_email',
        'tenant_mail_reply_to_name',
        'tenant_mail_from_name',
        'tenant_mail_notification_events',
        'tenant_document_review_warning_days',
        'tenant_mail_host',
        'tenant_mail_port',
        'tenant_mail_username',
        'tenant_mail_password',
        'tenant_mail_encryption',
        'tenant_mail_from_email',
        'helpdesk_intro',
        'helpdesk_privacy_note',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'tenant_id' => 'integer',
        'helpdesk_enabled' => 'boolean',
        'helpdesk_allow_attachments' => 'boolean',
        'tenant_mail_notification_events' => 'array',
        'tenant_document_review_warning_days' => 'integer',
        'tenant_mail_port' => 'integer',
        'tenant_mail_password' => 'encrypted',
    ];

    /** True when this company has enough SMTP config to send through its own mail server. */
    public function hasTenantSmtp(): bool
    {
        return filled($this->tenant_mail_host) && filled($this->tenant_mail_port);
    }

    /** Runtime Laravel mailer config for this company's SMTP (used as a per-tenant mailer). */
    public function tenantMailerConfig(): array
    {
        return [
            'transport' => 'smtp',
            'host' => $this->tenant_mail_host,
            'port' => (int) $this->tenant_mail_port,
            'encryption' => $this->tenant_mail_encryption ?: null,
            'username' => $this->tenant_mail_username ?: null,
            'password' => $this->tenant_mail_password ?: null,
            'timeout' => null,
        ];
    }

    protected static array $descendantCompanyIdsCache = [];
    protected static array $ancestorCompanyIdsCache = [];
    protected static ?array $authContextCache = null;
    protected static ?object $authUserCache = null;

    protected static function booted(): void
    {
        static::saved(function () {
            self::flushHierarchyCache();
        });

        static::deleted(function () {
            self::flushHierarchyCache();
        });

        static::restored(function () {
            self::flushHierarchyCache();
        });
    }

    public static function getIdFromInput($unescaped_input)
    {
        if (is_null($unescaped_input)) {
            return null;
        }

        $normalizedInput = trim((string) $unescaped_input);
        $normalizedInput = html_entity_decode($normalizedInput, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalizedInput = trim($normalizedInput, " \t\n\r\0\x0B\"'");

        if (($normalizedInput === '') || ($normalizedInput == '0')) {
            return null;
        }

        return is_numeric($normalizedInput) ? (int) $normalizedInput : $normalizedInput;
    }

    public static function generateUniqueHelpdeskSlug(?string $seed, ?int $ignoreCompanyId = null): string
    {
        $baseSlug = Str::slug((string) $seed);

        if ($baseSlug === '') {
            $baseSlug = 'helpdesk';
        }

        $slug = $baseSlug;
        $counter = 2;

        while (
            static::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->when($ignoreCompanyId, fn ($query) => $query->where('id', '!=', $ignoreCompanyId))
                ->where('helpdesk_slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public static function currentAuthUserData(): ?object
    {
        if (! is_null(self::$authUserCache)) {
            return self::$authUserCache;
        }

        $guard = Auth::guard();
        $userId = null;

        if (method_exists($guard, 'hasUser') && $guard->hasUser() && method_exists($guard, 'user')) {
            $userId = $guard->user()?->getAuthIdentifier();
        }

        if (is_null($userId) && method_exists($guard, 'getName')) {
            $userId = session()->get($guard->getName());
        }

        if (is_null($userId) && ! method_exists($guard, 'getName') && method_exists($guard, 'id')) {
            $userId = $guard->id();
        }

        if (is_null($userId)) {
            return null;
        }

        return self::$authUserCache = DB::table('users')
            ->select([
                'id',
                'company_id',
                'permissions',
                'username',
                'locale',
                'activated',
                'two_factor_optin',
                'two_factor_secret',
                'two_factor_enrolled',
                'nav_link_color',
                'link_dark_color',
                'link_light_color',
            ])
            ->where('id', $userId)
            ->first();
    }

    public static function currentAuthContext(): array
    {
        if (! is_null(self::$authContextCache)) {
            return self::$authContextCache;
        }

        $user = self::currentAuthUserData();

        if (is_null($user)) {
            return self::$authContextCache = [
                'id' => null,
                'company_id' => null,
                'is_superadmin' => false,
                'is_superuser' => false,
                'can_view_all_tenants' => false,
            ];
        }

        $isSuperadmin = self::currentAuthUserHasPermission($user, 'superadmin');
        $isSuperuser = $isSuperadmin || self::currentAuthUserHasPermission($user, 'superuser');
        $canViewAllTenants = $isSuperadmin && self::currentAuthUserHasPermission($user, 'tenants.view_all');

        return self::$authContextCache = [
            'id' => $user->id ? (int) $user->id : null,
            'company_id' => $user->company_id ? (int) $user->company_id : null,
            'is_superadmin' => $isSuperadmin,
            'is_superuser' => $isSuperuser,
            'can_view_all_tenants' => $canViewAllTenants,
        ];
    }

    /**
     * Get the company id for the current user while respecting subtree access
     * and superuser overrides.
     *
     * @return int|mixed|string|null
     */
    public static function getIdForCurrentUser($unescaped_input)
    {
        if (! self::companyScopingEnabled()) {
            return self::getIdFromInput($unescaped_input);
        }

        $authContext = self::currentAuthContext();

        if ($authContext['is_superuser']) {
            $requested_company_id = self::getIdFromInput($unescaped_input);
            $context_company_ids = self::activeCompanyContextIds();

            if (count($context_company_ids) === 0) {
                return $authContext['can_view_all_tenants'] ? $requested_company_id : null;
            }

            if (($requested_company_id !== null) && in_array((int) $requested_company_id, $context_company_ids, true)) {
                return $requested_company_id;
            }

            return self::activeCompanyId();
        }

        $requested_company_id = self::getIdFromInput($unescaped_input);
        $manageable_company_ids = self::activeCompanyContextIds();

        if (($requested_company_id !== null) && in_array((int) $requested_company_id, $manageable_company_ids, true)) {
            return $requested_company_id;
        }

        if (! is_null($authContext['company_id'])) {
            return $authContext['company_id'];
        }

        return self::activeCompanyId();
    }

    /**
     * Check to see if the current user should have access to the model.
     * I hate this method and I think it should be refactored.
     *
     * @return bool|void
     */
    public static function isCurrentUserHasAccess($companyable)
    {
        // When would this even happen tho??
        if (is_null($companyable)) {
            return false;
        }

        // Again, where would this happen? But check that $companyable is not a string
        if (! is_string($companyable)) {
            $company_table = $companyable->getModel()->getTable();
            try {
                // This is primarily for the gate:allows-check in location->isDeletable()
                // Locations don't have a company_id so without this it isn't possible to delete locations with FullMultipleCompanySupport enabled
                // because this function is called by SnipePermissionsPolicy->before()
                if (! Schema::hasColumn($company_table, 'company_id')) {
                    return true;
                }

            } catch (\Exception $e) {
                Log::warning($e);
            }
        }

        $authContext = self::currentAuthContext();

        if (! self::companyScopingEnabled()) {
            return true;
        }

        if (! is_null($authContext['id'])) {
            if ($authContext['is_superuser']) {
                $activeCompanyContextIds = self::activeCompanyContextIds();

                if (count($activeCompanyContextIds) === 0) {
                    return $authContext['can_view_all_tenants'];
                }

                if ($companyable instanceof Company) {
                    return in_array((int) $companyable->id, $activeCompanyContextIds, true);
                }

                return in_array((int) $companyable->company_id, $activeCompanyContextIds, true);
            }

            if ($companyable instanceof Company) {
                return in_array((int) $companyable->id, self::activeCompanyContextIds(), true);
            }

            return self::isOwnedByAccessibleCompanyId($companyable->company_id);
        }

        return false;

    }

    public static function isCurrentUserHasTemplateAccess($template): bool
    {
        if (is_null($template)) {
            return false;
        }

        $authContext = self::currentAuthContext();

        if (is_null($authContext['id'])) {
            return false;
        }

        if (method_exists($template, 'isSystemTemplate')) {
            if ($template->isSystemTemplate()) {
                return Tenant::canCurrentUserUseGlobalTenantContext();
            }

            if (is_null($template->company_id)) {
                return Tenant::canCurrentUserUseGlobalTenantContext();
            }
        }

        if ($authContext['is_superuser']) {
            $activeCompanyContextIds = self::activeCompanyContextIds();

            if (count($activeCompanyContextIds) === 0) {
                return $authContext['can_view_all_tenants'];
            }

            if (is_null($template->company_id)) {
                return false;
            }

            if (in_array((int) $template->company_id, $activeCompanyContextIds, true)) {
                return true;
            }

            return in_array((int) $template->company_id, self::activeCompanyAncestorIds(), true)
                && ($template->visibility_type === 'descendants');
        }

        if (is_null($template->company_id)) {
            return true;
        }

        if (in_array((int) $template->company_id, self::activeCompanyContextIds(), true)) {
            return true;
        }

        return in_array((int) $template->company_id, self::activeCompanyAncestorIds(), true)
            && ($template->visibility_type === 'descendants');
    }

    public static function canCurrentUserManageTemplate($template): bool
    {
        if (is_null($template)) {
            return false;
        }

        $authContext = self::currentAuthContext();

        if (is_null($authContext['id'])) {
            return false;
        }

        if (! self::companyScopingEnabled()) {
            return true;
        }

        if ($authContext['is_superuser']) {
            $activeCompanyContextIds = self::activeCompanyContextIds();

            return (count($activeCompanyContextIds) === 0)
                ? $authContext['can_view_all_tenants']
                : (! is_null($template->company_id) && in_array((int) $template->company_id, $activeCompanyContextIds, true));
        }

        if (is_null($template->company_id)) {
            return is_null($authContext['company_id']);
        }

        return in_array((int) $template->company_id, self::activeCompanyContextIds(), true);
    }

    public static function templateCanBeAppliedToCompany($template, ?int $companyId): bool
    {
        if (is_null($template)) {
            return false;
        }

        if (method_exists($template, 'isSystemTemplate')) {
            if ($template->isSystemTemplate()) {
                return false;
            }

            if (is_null($template->company_id)) {
                return false;
            }
        }

        if (is_null($template->company_id)) {
            return true;
        }

        if (is_null($companyId)) {
            return false;
        }

        if ((int) $template->company_id === (int) $companyId) {
            return true;
        }

        return ($template->visibility_type === 'descendants')
            && in_array((int) $companyId, self::descendantCompanyIds((int) $template->company_id), true);
    }

    public static function isCurrentUserAuthorized()
    {
        return self::currentAuthContext()['is_superuser'];
    }

    public static function canManageUsersCompanies()
    {
        $authContext = self::currentAuthContext();

        return ! is_null($authContext['id'])
            && ($authContext['is_superuser']
                || (Tenant::activeTenantId() && Tenant::currentUserRoleForTenant(Tenant::activeTenantId()) === Tenant::ROLE_ADMIN)
                || (is_null($authContext['company_id']) && in_array(Tenant::ROLE_ADMIN, array_values(Tenant::currentUserTenantRoles()), true)));
    }

    public static function canCurrentUserSelectCompany(): bool
    {
        $authContext = self::currentAuthContext();

        if (is_null($authContext['id'])) {
            return false;
        }

        return $authContext['is_superuser']
            || ! is_null($authContext['company_id'])
            || (count(self::activeCompanyContextIds()) > 0);
    }

    public static function canCurrentUserSwitchCompanies(): bool
    {
        return Tenant::canCurrentUserSwitchTenants();
    }

    public static function shouldShowGlobalCompanyContextOption(): bool
    {
        return Tenant::shouldShowGlobalTenantContextOption();
    }

    public static function switchableCompaniesForCurrentUser(): Collection
    {
        return Tenant::switchableTenantsForCurrentUser()
            ->map(fn (Tenant $tenant) => $tenant->rootCompany())
            ->filter()
            ->values();
    }

    public static function activeCompanyId(): ?int
    {
        return Tenant::currentTenantRootCompany()?->id;
    }

    public static function activeCompany(): ?self
    {
        $activeCompanyId = self::activeCompanyId();

        if (is_null($activeCompanyId)) {
            return null;
        }

        return self::withoutGlobalScopes()
            ->select(['id', 'name', 'parent_id', 'tag_color'])
            ->whereNull('deleted_at')
            ->find($activeCompanyId);
    }

    public static function activeCompanyContextIds(): array
    {
        $authContext = self::currentAuthContext();

        if (is_null($authContext['id'])) {
            return [];
        }

        if ($authContext['is_superuser'] || $authContext['can_view_all_tenants']) {
            return Tenant::activeTenantCompanyIds();
        }

        $activeTenantId = Tenant::activeTenantId();
        $activeTenantCompanyIds = Tenant::activeTenantCompanyIds();
        $currentCompanyIds = self::currentUserCompanyIds();
        $explicitMembershipTenantIds = Tenant::explicitMembershipTenantIdsForCurrentUser();

        if (! is_null($activeTenantId)) {
            if (! is_null(Tenant::currentUserRoleForTenant($activeTenantId))) {
                return $activeTenantCompanyIds;
            }

            if (count($activeTenantCompanyIds) === 0) {
                return [];
            }

            return array_values(array_intersect($activeTenantCompanyIds, $currentCompanyIds));
        }

        $aggregatedCompanyIds = $currentCompanyIds;

        if (count($explicitMembershipTenantIds) > 0) {
            $aggregatedCompanyIds = array_merge(
                $aggregatedCompanyIds,
                self::withoutGlobalScopes()
                    ->whereNull('deleted_at')
                    ->whereIn('tenant_id', $explicitMembershipTenantIds)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all()
            );
        }

        return array_values(array_unique(array_map('intval', $aggregatedCompanyIds)));
    }

    public static function activeCompanyAncestorIds(): array
    {
        $authContext = self::currentAuthContext();

        if (is_null($authContext['id'])) {
            return [];
        }

        $activeTenantCompanyIds = Tenant::activeTenantCompanyIds();

        if (count($activeTenantCompanyIds) === 0) {
            return self::currentUserAncestorCompanyIds();
        }

        if ($authContext['is_superuser'] || $authContext['can_view_all_tenants']) {
            return [];
        }

        if (Tenant::activeTenantId() && ! is_null(Tenant::currentUserRoleForTenant(Tenant::activeTenantId()))) {
            return [];
        }

        return array_values(array_intersect(self::currentUserAncestorCompanyIds(), $activeTenantCompanyIds));
    }

    public static function setActiveCompanyContext($unescapedCompanyId): void
    {
        $companyId = self::getIdFromInput($unescapedCompanyId);

        if (is_null($companyId)) {
            Tenant::clearActiveTenantContext();

            return;
        }

        $tenantId = self::withoutGlobalScopes()
            ->where('id', $companyId)
            ->value('tenant_id');

        if ($tenantId) {
            Tenant::setActiveTenantContext((int) $tenantId);
        }
    }

    public static function clearActiveCompanyContext(): void
    {
        Tenant::clearActiveTenantContext();
    }

    public static function canCurrentUserSwitchToCompany(?int $companyId): bool
    {
        if (is_null(self::currentAuthContext()['id'])) {
            return false;
        }

        if (is_null($companyId)) {
            return true;
        }

        $tenantId = self::withoutGlobalScopes()
            ->where('id', $companyId)
            ->value('tenant_id');

        return $tenantId ? Tenant::canCurrentUserSwitchToTenant((int) $tenantId) : false;
    }

    public static function normalizeTemplateOwnership($unescapedCompanyId, ?string $visibilityType): array
    {
        $companyId = self::getIdFromInput($unescapedCompanyId);

        $authContext = self::currentAuthContext();

        if (is_null($authContext['id'])) {
            return [$companyId, self::normalizeTemplateVisibility($companyId, $visibilityType)];
        }

        if ($authContext['is_superuser'] && Tenant::canCurrentUserUseGlobalTenantContext()) {
            return [$companyId, self::normalizeTemplateVisibility($companyId, $visibilityType)];
        }

        $manageableCompanyIds = self::activeCompanyContextIds();

        if (($companyId === null) || (! in_array((int) $companyId, $manageableCompanyIds, true))) {
            $companyId = self::preferredCompanySelectionId();
        }

        return [$companyId, self::normalizeTemplateVisibility($companyId, $visibilityType)];
    }

    /**
     * Checks if company can be deleted
     *
     * @author [Dan Meltzer] [<dmeltzer.devel@gmail.com>]
     *
     * @since  [v5.0]
     *
     * @return bool
     */
    public function isDeletable()
    {

        return Gate::allows('delete', $this)
            && (($this->assets_count ?? $this->assets()->count()) === 0)
            && (($this->accessories_count ?? $this->accessories()->count()) === 0)
            && (($this->licenses_count ?? $this->licenses()->count()) === 0)
            && (($this->components_count ?? $this->components()->count()) === 0)
            && (($this->consumables_count ?? $this->consumables()->count()) === 0)
            && (($this->accessories_count ?? $this->accessories()->count()) === 0)
            && (($this->users_count ?? $this->users()->count()) === 0);
    }

    /**
     * @return int|mixed|string|null
     */
    public static function getIdForUser($unescaped_input)
    {
        return self::getIdForCurrentUser($unescaped_input);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'company_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function isTenantRoot(): bool
    {
        return ! is_null($this->tenant_id) && is_null($this->parent_id);
    }

    public function tenantDisplayName(): string
    {
        return $this->name;
    }

    public function assets()
    {
        return $this->hasMany(Asset::class, 'company_id');
    }

    public function licenses()
    {
        return $this->hasMany(License::class, 'company_id');
    }

    public function accessories()
    {
        return $this->hasMany(Accessory::class, 'company_id');
    }

    public function consumables()
    {
        return $this->hasMany(Consumable::class, 'company_id');
    }

    public function components()
    {
        return $this->hasMany(Component::class, 'company_id');
    }

    /**
     * START COMPANY SCOPING FOR FMCS
     */

    /**
     * Scoping table queries, determining if a logged in user is part of a company, and only allows the user to access items associated with that company if FMCS is enabled.
     *
     * This method is the one that the CompanyableTrait uses to contrain queries automatically, however that trait CANNOT be
     * applied to the user's model, since it causes an infinite loop against the authenticated user.
     *
     * @todo - refactor that trait to handle the user's model as well.
     *
     * @author [A. Gianotto] <snipe@snipe.net>
     *
     * @return mixed
     */
    public static function scopeCompanyables($query, $column = 'company_id', $table_name = null)
    {
        if (! Auth::hasUser()) {
            return $query;
        }

        if (! self::companyScopingEnabled()) {
            return $query;
        }

        if (Tenant::canCurrentUserUseGlobalTenantContext()) {
            return $query;
        }

        return self::scopeCompanyablesDirectly($query, $column, $table_name);
    }

    /**
     * Scoping table queries, determining if a logged-in user is part of a company, and only allows
     * that user to see items associated with that company
     *
     * @see https://github.com/laravel/framework/pull/24518 for info on Auth::hasUser()
     */
    private static function scopeCompanyablesDirectly($query, $column = 'company_id', $table_name = null)
    {

        // If we are scoping the companies table itself, look for the company.id
        if ($query->getModel()->getTable() == 'companies') {
            $company_ids = self::activeCompanyContextIds();

            if (count($company_ids) === 0) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereIn('companies.id', $company_ids);
        }

        // If the column exists in the table, use it to scope the query
        if ((($query) && ($query->getModel()) && (Schema::hasColumn($query->getModel()->getTable(), $column)))) {

            // Dynamically get the table name if it's not passed in, based on the model we're querying against
            $table = ($table_name) ? $table_name.'.' : $query->getModel()->getTable().'.';

            $company_ids = self::activeCompanyContextIds();

            if (count($company_ids) === 0) {
                return $query->whereNull($table.$column);
            }

            return $query->whereIn($table.$column, $company_ids);
        }

    }

    public static function scopeTemplateVisibility($query, $column = 'company_id', $visibilityColumn = 'visibility_type', $table_name = null)
    {
        if (! Auth::hasUser()) {
            return $query;
        }

        if (Tenant::canCurrentUserUseGlobalTenantContext()) {
            return $query;
        }

        $table = ($table_name) ? $table_name.'.' : $query->getModel()->getTable().'.';
        $currentCompanyIds = self::activeCompanyContextIds();
        $ancestorCompanyIds = self::activeCompanyAncestorIds();

        return $query->where(function ($visibilityQuery) use ($table, $column, $visibilityColumn, $currentCompanyIds, $ancestorCompanyIds) {
            $visibilityQuery->whereNull($table.$column);

            if (count($currentCompanyIds) > 0) {
                $visibilityQuery->orWhereIn($table.$column, $currentCompanyIds);
            }

            if (count($ancestorCompanyIds) > 0) {
                $visibilityQuery->orWhere(function ($ancestorQuery) use ($table, $column, $visibilityColumn, $ancestorCompanyIds) {
                    $ancestorQuery
                        ->whereIn($table.$column, $ancestorCompanyIds)
                        ->where($table.$visibilityColumn, 'descendants');
                });
            }
        });
    }

    /**
     * I legit do not know what this method does, but we can't remove it (yet).
     *
     * This gets invoked by CompanyableChildScope, but I'm not sure what it does.
     *
     * @author [A. Gianotto] <snipe@snipe.net>
     *
     * @return mixed
     */
    public static function scopeCompanyableChildren(array $companyable_names, $query)
    {
        if (! Auth::hasUser() || ! self::companyScopingEnabled()) {
            return $query;
        }

        if (count($companyable_names) == 0) {
            throw new Exception('No Companyable Children to scope');
        } elseif (Auth::hasUser() && Tenant::canCurrentUserUseGlobalTenantContext()) {
            return $query;
        } else {
            $f = function ($q) {
                static::scopeCompanyablesDirectly($q);
            };

            $q = $query->where(
                function ($q) use ($companyable_names, $f) {
                    $q2 = $q->whereHas($companyable_names[0], $f);

                    for ($i = 1; $i < count($companyable_names); $i++) {
                        $q2 = $q2->orWhereHas($companyable_names[$i], $f);
                    }
                }
            );

            return $q;
        }
    }

    /**
     * Query builder scope to order on the user that created it
     */
    public function scopeOrderByCreatedBy($query, $order)
    {
        return $query->leftJoin('users as admin_sort', 'companies.created_by', '=', 'admin_sort.id')->select('companies.*')->orderBy('admin_sort.first_name', $order)->orderBy('admin_sort.last_name', $order);
    }

    public static function currentUserCompanyIds(): array
    {
        $companyId = self::currentAuthContext()['company_id'];

        if (is_null($companyId)) {
            return [];
        }

        return self::descendantCompanyIds((int) $companyId);
    }

    public static function currentUserAncestorCompanyIds(): array
    {
        $companyId = self::currentAuthContext()['company_id'];

        if (is_null($companyId)) {
            return [];
        }

        return self::ancestorCompanyIds((int) $companyId);
    }

    public static function descendantCompanyIds(?int $companyId): array
    {
        if (is_null($companyId)) {
            return [];
        }

        if (array_key_exists($companyId, self::$descendantCompanyIdsCache)) {
            return self::$descendantCompanyIdsCache[$companyId];
        }

        $rootTenantId = self::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('id', $companyId)
            ->value('tenant_id');

        if (! self::withoutGlobalScopes()->whereNull('deleted_at')->where('id', $companyId)->exists()) {
            return self::$descendantCompanyIdsCache[$companyId] = [];
        }

        $allIds = [$companyId];
        $frontier = [$companyId];

        while (count($frontier) > 0) {
            $children = self::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->whereIn('parent_id', $frontier)
                ->when(! is_null($rootTenantId), fn ($query) => $query->where('tenant_id', $rootTenantId))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $children = array_values(array_diff($children, $allIds));

            if (count($children) === 0) {
                break;
            }

            $allIds = array_merge($allIds, $children);
            $frontier = $children;
        }

        self::$descendantCompanyIdsCache[$companyId] = array_values(array_unique(array_map('intval', $allIds)));

        return self::$descendantCompanyIdsCache[$companyId];
    }

    public static function ancestorCompanyIds(?int $companyId): array
    {
        if (is_null($companyId)) {
            return [];
        }

        if (array_key_exists($companyId, self::$ancestorCompanyIdsCache)) {
            return self::$ancestorCompanyIdsCache[$companyId];
        }

        $ancestorIds = [];
        $visited = [];
        $currentCompany = self::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->select(['id', 'parent_id', 'tenant_id'])
            ->find($companyId);
        $rootTenantId = $currentCompany?->tenant_id ? (int) $currentCompany->tenant_id : null;

        while ($currentCompany?->parent_id) {
            $parentId = (int) $currentCompany->parent_id;

            if (in_array($parentId, $visited, true)) {
                break;
            }

            $visited[] = $parentId;
            $currentCompany = self::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->select(['id', 'parent_id', 'tenant_id'])
                ->find($parentId);

            if (! $currentCompany) {
                break;
            }

            if (! is_null($rootTenantId) && (int) ($currentCompany->tenant_id ?? 0) !== $rootTenantId) {
                break;
            }

            $ancestorIds[] = $parentId;
        }

        self::$ancestorCompanyIdsCache[$companyId] = $ancestorIds;

        return $ancestorIds;
    }

    public static function flushHierarchyCache(): void
    {
        self::$descendantCompanyIdsCache = [];
        self::$ancestorCompanyIdsCache = [];
        self::$authContextCache = null;
        self::$authUserCache = null;
    }

    public static function preferredCompanySelectionId(): ?int
    {
        $authContext = self::currentAuthContext();

        if (is_null($authContext['id'])) {
            return self::activeCompanyId();
        }

        if (! $authContext['is_superuser'] && ! is_null($authContext['company_id'])) {
            return (int) $authContext['company_id'];
        }

        return self::activeCompanyId();
    }

    public static function ensureTenantAssignment(self $company): void
    {
        $tenantId = $company->tenant_id;

        if (! is_null($company->parent_id)) {
            $tenantId = self::withoutGlobalScopes()
                ->where('id', $company->parent_id)
                ->value('tenant_id');
        }

        if (is_null($tenantId)) {
            $tenantId = Tenant::createMinimal()->id;
        }

        if ((int) ($company->tenant_id ?? 0) !== (int) $tenantId) {
            $company->tenant_id = (int) $tenantId;
            $company->saveQuietly();
        }

        if (is_null($company->parent_id) && ($settings = Setting::getRawSettings())) {
            $company->brand ??= $settings->brand;
            $company->brand_logo ??= $settings->logo;
            $company->favicon ??= $settings->favicon;
            $company->header_color ??= $settings->header_color;
            $company->nav_link_color ??= $settings->nav_link_color;
            $company->link_light_color ??= $settings->link_light_color;
            $company->link_dark_color ??= $settings->link_dark_color;
            $company->footer_text ??= $settings->footer_text;
            $company->privacy_policy_link ??= $settings->privacy_policy_link;
            $company->custom_css ??= $settings->custom_css;
            $company->saveQuietly();
        }

        $descendantIds = array_values(array_filter(
            self::descendantCompanyIds($company->id),
            fn (int $id) => $id !== (int) $company->id
        ));

        if (count($descendantIds) > 0) {
            self::withoutGlobalScopes()
                ->whereIn('id', $descendantIds)
                ->update(['tenant_id' => (int) $tenantId]);
        }
    }

    private static function normalizeTemplateVisibility(?int $companyId, ?string $visibilityType): string
    {
        if (is_null($companyId)) {
            return 'global';
        }

        if ($visibilityType === 'descendants') {
            return 'descendants';
        }

        return 'private';
    }

    private static function companyScopingEnabled(): bool
    {
        try {
            if (! Schema::hasTable('settings') || ! Schema::hasColumn('settings', 'full_multiple_companies_support')) {
                return false;
            }

            return (bool) (Setting::getSettings()?->full_multiple_companies_support);
        } catch (\Exception $e) {
            return false;
        }
    }

    private static function isOwnedByAccessibleCompanyId(?int $companyId): bool
    {
        $authContext = self::currentAuthContext();

        if (is_null($authContext['id'])) {
            return false;
        }

        if ($authContext['is_superuser']) {
            $activeCompanyContextIds = self::activeCompanyContextIds();

            return (count($activeCompanyContextIds) === 0)
                ? $authContext['can_view_all_tenants']
                : in_array((int) $companyId, $activeCompanyContextIds, true);
        }

        if (is_null($authContext['company_id'])) {
            $activeCompanyContextIds = self::activeCompanyContextIds();

            if (count($activeCompanyContextIds) === 0) {
                return is_null($companyId);
            }

            return in_array((int) $companyId, $activeCompanyContextIds, true);
        }

        return in_array((int) $companyId, self::activeCompanyContextIds(), true);
    }

    private static function switchableCompanyIdsForCurrentUser(): array
    {
        return self::switchableCompaniesForCurrentUser()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private static function currentAuthUserHasPermission(object $user, string $permission): bool
    {
        $directPermissions = json_decode((string) ($user->permissions ?? '{}'), true) ?: [];

        if (array_key_exists($permission, $directPermissions)) {
            if (self::permissionValueAllows($directPermissions[$permission])) {
                return true;
            }

            if (self::permissionValueDenies($directPermissions[$permission])) {
                return false;
            }
        }

        return self::currentAuthUserGroupPermissionAllows((int) $user->id, $permission);
    }

    private static function currentAuthUserDeniedPermission(object $user, string $permission): bool
    {
        $directPermissions = json_decode((string) ($user->permissions ?? '{}'), true) ?: [];

        return array_key_exists($permission, $directPermissions)
            && self::permissionValueDenies($directPermissions[$permission]);
    }

    private static function currentAuthUserGroupPermissionAllows(int $userId, string $permission): bool
    {
        return DB::table('users_groups')
            ->join('permission_groups', 'permission_groups.id', '=', 'users_groups.group_id')
            ->where('users_groups.user_id', $userId)
            ->pluck('permission_groups.permissions')
            ->contains(function ($permissionsJson) use ($permission) {
                $permissions = json_decode((string) $permissionsJson, true) ?: [];

                return array_key_exists($permission, $permissions)
                    && self::permissionValueAllows($permissions[$permission]);
            });
    }

    private static function permissionValueAllows(mixed $value): bool
    {
        return (string) $value === '1';
    }

    private static function permissionValueDenies(mixed $value): bool
    {
        return (string) $value === '-1';
    }
}
