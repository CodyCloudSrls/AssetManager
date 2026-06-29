<?php

namespace App\Models;

use App\Helpers\Helper;
use App\Support\Compliance\ComplianceJurisdictions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    public const ACTIVE_TENANT_SESSION_KEY = 'active_tenant_id';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_VIEWER = 'viewer';

    public const MAIL_EVENT_TICKET_CREATED = 'ticket_created';

    public const MAIL_EVENT_TICKET_PUBLIC_REPLY = 'ticket_public_reply';

    public const MAIL_EVENT_TICKET_ASSIGNED = 'ticket_assigned';

    public const MAIL_EVENT_TICKET_SLA_ALERT = 'ticket_sla_alert';

    public const MAIL_EVENT_DOCUMENT_REVIEW_DUE = 'document_review_due';

    public const MAIL_EVENT_DOCUMENT_ASSIGNMENT_REMINDER = 'document_assignment_reminder';

    public const COMPLIANCE_JURISDICTION_EU = ComplianceJurisdictions::EU;

    public const COMPLIANCE_JURISDICTION_IT = ComplianceJurisdictions::IT;

    protected static ?array $currentUserTenantRolesCache = null;

    protected $fillable = [
        'uuid',
        'default_locale',
        'default_compliance_jurisdiction',
        'enabled_features',
    ];

    protected $casts = [
        'default_locale' => 'string',
        'default_compliance_jurisdiction' => 'string',
        'enabled_features' => 'array',
    ];

    /** Per-tenant feature flags (modules a tenant has enabled). */
    public const FEATURE_ASSETS = 'assets';
    public const FEATURE_DOCUMENTS = 'documents';
    public const FEATURE_TICKETS = 'tickets';
    public const FEATURE_NIS2 = 'nis2';
    public const FEATURE_ERP = 'erp';

    // Compliance domains as individually-activatable modules. The flag key matches the
    // framework's compliance_domain, so the nav/pages can be gated + filtered per area.
    public const FEATURE_GDPR = 'gdpr';
    public const FEATURE_DL81 = 'dl81';
    public const FEATURE_ISO27001 = 'iso27001';
    public const FEATURE_AI_ACT = 'ai_act';
    public const FEATURE_ISO9001 = 'iso9001';

    /** Compliance-domain feature flags, mapped to their framework compliance_domain key. */
    public static function complianceFeatureDomains(): array
    {
        return [
            self::FEATURE_NIS2 => 'nis2',
            self::FEATURE_GDPR => 'gdpr',
            self::FEATURE_DL81 => 'dl81',
            self::FEATURE_ISO27001 => 'iso27001',
            self::FEATURE_AI_ACT => 'ai_act',
            self::FEATURE_ISO9001 => 'iso9001',
        ];
    }

    /**
     * All feature keys mapped to their display label, in nav order.
     */
    public static function featureOptions(): array
    {
        return [
            self::FEATURE_ASSETS => trans('admin/tenants/general.features.assets'),
            self::FEATURE_DOCUMENTS => trans('admin/tenants/general.features.documents'),
            self::FEATURE_NIS2 => trans('admin/tenants/general.features.nis2'),
            self::FEATURE_GDPR => trans('admin/tenants/general.features.gdpr'),
            self::FEATURE_DL81 => trans('admin/tenants/general.features.dl81'),
            self::FEATURE_ISO27001 => trans('admin/tenants/general.features.iso27001'),
            self::FEATURE_AI_ACT => trans('admin/tenants/general.features.ai_act'),
            self::FEATURE_ISO9001 => trans('admin/tenants/general.features.iso9001'),
            self::FEATURE_TICKETS => trans('admin/tenants/general.features.tickets'),
            self::FEATURE_ERP => trans('admin/tenants/general.features.erp'),
        ];
    }

    public static function featureKeys(): array
    {
        return array_keys(self::featureOptions());
    }

    /**
     * Features enabled for this tenant. A NULL stored value means "all enabled"
     * (so existing tenants keep every module until a choice is made).
     */
    public function enabledFeatures(): array
    {
        $stored = $this->enabled_features;

        if (! is_array($stored)) {
            return self::featureKeys();
        }

        // Keep the whitelist canonical, drop unknown keys, and core is always on.
        return array_values(array_unique(array_merge(
            [self::FEATURE_ASSETS],
            array_intersect(self::featureKeys(), $stored),
        )));
    }

    public function hasFeature(string $feature): bool
    {
        // Core asset management is always available; everything else is gated.
        if ($feature === self::FEATURE_ASSETS) {
            return true;
        }

        return in_array($feature, $this->enabledFeatures(), true);
    }

    /**
     * Whether the tenant in the CURRENT request context has a feature. With no
     * tenant context (e.g. a platform superadmin viewing everything) all features
     * are shown — gating only narrows things for tenant-scoped users.
     */
    public static function currentContextHasFeature(string $feature): bool
    {
        $tenant = static::activeTenant() ?? static::currentTenant();

        return $tenant instanceof self ? $tenant->hasFeature($feature) : true;
    }

    protected static function booted(): void
    {
        static::creating(function (self $tenant) {
            if (blank($tenant->uuid)) {
                $tenant->uuid = (string) Str::uuid();
            }
        });
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'tenant_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users')
            ->withPivot(['role', 'created_by'])
            ->withTimestamps();
    }

    public function helpdeskTicketTypes(): BelongsToMany
    {
        return $this->belongsToMany(TicketType::class, 'tenant_helpdesk_ticket_types')
            ->withTimestamps();
    }

    public function services(): HasMany
    {
        return $this->hasMany(TenantService::class, 'tenant_id');
    }

    public function rootCompany(): ?Company
    {
        return Company::withoutGlobalScopes()
            ->where('tenant_id', $this->id)
            ->whereNull('deleted_at')
            ->whereNull('parent_id')
            ->orderBy('id')
            ->first();
    }

    public function activeCompanyIds(): array
    {
        return Company::withoutGlobalScopes()
            ->where('tenant_id', $this->id)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function isDeletable(): bool
    {
        $companyIds = $this->activeCompanyIds();

        if ($this->members()->exists()) {
            return false;
        }

        if (Schema::hasTable('tenant_services') && $this->services()->exists()) {
            return false;
        }

        if (count($companyIds) === 0) {
            return true;
        }

        $scopedTables = [
            'users' => 'company_id',
            'assets' => 'company_id',
            'licenses' => 'company_id',
            'accessories' => 'company_id',
            'consumables' => 'company_id',
            'components' => 'company_id',
            'documents' => 'company_id',
            'tickets' => 'company_id',
            'locations' => 'company_id',
            'departments' => 'company_id',
            'document_frameworks' => 'company_id',
        ];

        foreach ($scopedTables as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            if (DB::table($table)->whereIn($column, $companyIds)->exists()) {
                return false;
            }
        }

        return true;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->rootCompany()?->name ?? ('Tenant '.$this->uuid);
    }

    public function setDefaultLocaleAttribute($value): void
    {
        $this->attributes['default_locale'] = Helper::normalizeSupportedLocale($value);
    }

    public function setDefaultComplianceJurisdictionAttribute($value): void
    {
        $this->attributes['default_compliance_jurisdiction'] = ComplianceJurisdictions::normalize($value);
    }

    public function defaultLocale(): string
    {
        return Helper::normalizeSupportedLocale($this->default_locale ?: config('app.locale', 'en-US'));
    }

    public function defaultComplianceJurisdiction(): string
    {
        // Italy-first: a tenant without an explicit jurisdiction defaults to the
        // verified IT NIS2 national overlay (the EU baseline stays a valid choice).
        return ComplianceJurisdictions::normalize($this->default_compliance_jurisdiction ?: static::COMPLIANCE_JURISDICTION_IT);
    }

    public static function complianceJurisdictionValues(): array
    {
        return ComplianceJurisdictions::values();
    }

    public static function complianceJurisdictionOptions(): array
    {
        return ComplianceJurisdictions::options();
    }

    public function publicHelpdeskUrl(): string
    {
        return route('tickets.portal.create', ['tenantPortal' => $this->publicHelpdeskRouteKey()]);
    }

    public function publicHelpdeskRouteKey(): string
    {
        return $this->rootCompany()?->helpdesk_slug ?: $this->uuid;
    }

    public function isHelpdeskEnabled(): bool
    {
        return (bool) ($this->rootCompany()?->helpdesk_enabled ?? false);
    }

    public function publicHelpdeskAllowsAttachments(): bool
    {
        return (bool) ($this->rootCompany()?->helpdesk_allow_attachments ?? true);
    }

    public function publicHelpdeskContactEmail(): ?string
    {
        return $this->rootCompany()?->helpdesk_contact_email ?: $this->rootCompany()?->email;
    }

    public function publicHelpdeskContactPhone(): ?string
    {
        return $this->rootCompany()?->helpdesk_contact_phone ?: $this->rootCompany()?->phone;
    }

    public function publicHelpdeskIntro(): ?string
    {
        return $this->rootCompany()?->helpdesk_intro;
    }

    public function publicHelpdeskPrivacyNote(): ?string
    {
        return $this->rootCompany()?->helpdesk_privacy_note;
    }

    public function notificationEmail(): ?string
    {
        $rootCompany = $this->rootCompany();

        return $rootCompany?->tenant_notification_email
            ?: $rootCompany?->helpdesk_contact_email
            ?: $rootCompany?->email;
    }

    public function notificationRecipients(): array
    {
        return collect(explode(',', (string) $this->notificationEmail()))
            ->map(fn ($email) => trim($email))
            ->filter(fn ($email) => $email !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function notificationReplyToEmail(): ?string
    {
        $rootCompany = $this->rootCompany();

        return $rootCompany?->tenant_mail_reply_to_email
            ?: $rootCompany?->helpdesk_contact_email
            ?: config('mail.reply_to.address');
    }

    public function notificationReplyToName(): ?string
    {
        $rootCompany = $this->rootCompany();

        return $rootCompany?->tenant_mail_reply_to_name
            ?: $rootCompany?->tenant_mail_from_name
            ?: $rootCompany?->name
            ?: config('mail.reply_to.name');
    }

    public function notificationFromName(): string
    {
        $rootCompany = $this->rootCompany();

        return $rootCompany?->tenant_mail_from_name
            ?: $rootCompany?->name
            ?: config('mail.from.name')
            ?: config('app.name');
    }

    public function documentReviewWarningDays(): int
    {
        return max(1, (int) ($this->rootCompany()?->tenant_document_review_warning_days ?: 30));
    }

    public function notificationEvents(): array
    {
        $rootCompany = $this->rootCompany();

        if (is_null($rootCompany)) {
            return [];
        }

        $configured = $rootCompany->tenant_mail_notification_events;

        if (is_null($configured)) {
            return array_keys(static::mailNotificationEventOptions());
        }

        if (! is_array($configured)) {
            return [];
        }

        return collect($configured)
            ->filter(fn ($event) => is_string($event) && array_key_exists($event, static::mailNotificationEventOptions()))
            ->unique()
            ->values()
            ->all();
    }

    public function notificationEventEnabled(string $event): bool
    {
        return in_array($event, $this->notificationEvents(), true);
    }

    public static function mailNotificationEventOptions(): array
    {
        return [
            static::MAIL_EVENT_TICKET_CREATED => trans('admin/tenants/general.mail.events.ticket_created'),
            static::MAIL_EVENT_TICKET_PUBLIC_REPLY => trans('admin/tenants/general.mail.events.ticket_public_reply'),
            static::MAIL_EVENT_TICKET_ASSIGNED => trans('admin/tenants/general.mail.events.ticket_assigned'),
            static::MAIL_EVENT_TICKET_SLA_ALERT => trans('admin/tenants/general.mail.events.ticket_sla_alert'),
            static::MAIL_EVENT_DOCUMENT_REVIEW_DUE => trans('admin/tenants/general.mail.events.document_review_due'),
            static::MAIL_EVENT_DOCUMENT_ASSIGNMENT_REMINDER => trans('admin/tenants/general.mail.events.document_assignment_reminder'),
        ];
    }

    public static function resolvePublicHelpdeskIdentifier(string $identifier): ?self
    {
        $rootCompany = Company::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereNull('parent_id')
            ->whereNotNull('tenant_id')
            ->where('helpdesk_slug', $identifier)
            ->first();

        if ($rootCompany?->tenant_id) {
            return static::query()->find($rootCompany->tenant_id);
        }

        return static::query()->where('uuid', $identifier)->first();
    }

    public function publicHelpdeskAvailableTicketTypes(): Collection
    {
        $rootCompany = $this->rootCompany();
        $companyIds = $this->activeCompanyIds();

        if (is_null($rootCompany)) {
            return collect();
        }

        $ancestorIds = Company::ancestorCompanyIds($rootCompany->id);

        return TicketType::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->active()
            ->public()
            ->where(function ($query) use ($companyIds, $ancestorIds) {
                $query->whereNull('company_id');

                if (count($companyIds) > 0) {
                    $query->orWhereIn('company_id', $companyIds);
                }

                if (count($ancestorIds) > 0) {
                    $query->orWhere(function ($nested) use ($ancestorIds) {
                        $nested->whereIn('company_id', $ancestorIds)
                            ->where('visibility_type', 'descendants');
                    });
                }
            })
            ->ordered()
            ->get();
    }

    public function publicHelpdeskSelectedTicketTypes(): Collection
    {
        $selectedIds = DB::table('tenant_helpdesk_ticket_types')
            ->where('tenant_id', $this->id)
            ->pluck('ticket_type_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($selectedIds) === 0) {
            return $this->publicHelpdeskAvailableTicketTypes();
        }

        return $this->publicHelpdeskAvailableTicketTypes()
            ->whereIn('id', $selectedIds)
            ->values();
    }

    public static function createMinimal(): self
    {
        return static::create();
    }

    public static function explicitMembershipTenantIdsForCurrentUser(): array
    {
        $authContext = Company::currentAuthContext();

        if (is_null($authContext['id'])) {
            return [];
        }

        return DB::table('tenant_users')
            ->where('user_id', $authContext['id'])
            ->pluck('tenant_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public static function currentUserTenantRoles(): array
    {
        if (! is_null(static::$currentUserTenantRolesCache)) {
            return static::$currentUserTenantRolesCache;
        }

        $authContext = Company::currentAuthContext();

        if (is_null($authContext['id'])) {
            return static::$currentUserTenantRolesCache = [];
        }

        return static::$currentUserTenantRolesCache = DB::table('tenant_users')
            ->where('user_id', $authContext['id'])
            ->pluck('role', 'tenant_id')
            ->mapWithKeys(fn ($role, $tenantId) => [(int) $tenantId => (string) $role])
            ->all();
    }

    public static function currentUserRoleForTenant(?int $tenantId): ?string
    {
        if (is_null($tenantId)) {
            return null;
        }

        $roles = static::currentUserTenantRoles();

        return $roles[(int) $tenantId] ?? null;
    }

    public static function accessibleTenantIdsForCurrentUser(): array
    {
        $authContext = Company::currentAuthContext();

        if (is_null($authContext['id'])) {
            return [];
        }

        if ($authContext['can_view_all_tenants']) {
            return Company::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->whereNotNull('tenant_id')
                ->pluck('tenant_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        $tenantIds = static::explicitMembershipTenantIdsForCurrentUser();

        if (! is_null($authContext['company_id'])) {
            $currentTenantId = Company::withoutGlobalScopes()
                ->where('id', $authContext['company_id'])
                ->value('tenant_id');

            if (! is_null($currentTenantId)) {
                $tenantIds[] = (int) $currentTenantId;
            }
        }

        return collect($tenantIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public static function switchableTenantIdsForCurrentUser(): array
    {
        return static::accessibleTenantIdsForCurrentUser();
    }

    public static function switchableTenantsForCurrentUser(): Collection
    {
        $tenantIds = static::switchableTenantIdsForCurrentUser();

        if (count($tenantIds) === 0) {
            return collect();
        }

        return static::query()
            ->whereIn('id', $tenantIds)
            ->get()
            ->filter(fn (self $tenant) => ! is_null($tenant->rootCompany()))
            ->sortBy(fn (self $tenant) => mb_strtolower($tenant->display_name))
            ->values();
    }

    public static function canCurrentUserSwitchTenants(): bool
    {
        return static::switchableTenantsForCurrentUser()->count() > 1;
    }

    public static function shouldShowGlobalTenantContextOption(): bool
    {
        return false;
    }

    public static function canCurrentUserUseGlobalTenantContext(): bool
    {
        $authContext = Company::currentAuthContext();

        return ! is_null($authContext['id'])
            && $authContext['can_view_all_tenants']
            && is_null(static::activeTenantId());
    }

    public static function canCurrentUserSwitchToTenant(?int $tenantId): bool
    {
        if (is_null(Company::currentAuthContext()['id'])) {
            return false;
        }

        if (is_null($tenantId)) {
            return false;
        }

        return in_array($tenantId, static::switchableTenantIdsForCurrentUser(), true);
    }

    public static function defaultTenantIdForCurrentUser(): ?int
    {
        $authContext = Company::currentAuthContext();

        if (is_null($authContext['id'])) {
            return null;
        }

        $switchableTenantIds = static::switchableTenantIdsForCurrentUser();

        if (count($switchableTenantIds) === 0) {
            return null;
        }

        if (! is_null($authContext['company_id'])) {
            $currentCompanyTenantId = Company::withoutGlobalScopes()
                ->where('id', $authContext['company_id'])
                ->value('tenant_id');

            if (! is_null($currentCompanyTenantId) && in_array((int) $currentCompanyTenantId, $switchableTenantIds, true)) {
                return (int) $currentCompanyTenantId;
            }
        }

        return static::switchableTenantsForCurrentUser()->first()?->id;
    }

    public static function activeTenantId(): ?int
    {
        if (is_null(Company::currentAuthContext()['id'])) {
            return null;
        }

        if (! session()->has(static::ACTIVE_TENANT_SESSION_KEY) && session()->has(Company::ACTIVE_COMPANY_SESSION_KEY)) {
            $legacyCompanyId = Company::getIdFromInput(session(Company::ACTIVE_COMPANY_SESSION_KEY));
            $legacyTenantId = $legacyCompanyId
                ? Company::withoutGlobalScopes()->where('id', $legacyCompanyId)->value('tenant_id')
                : null;

            if ($legacyTenantId) {
                session([static::ACTIVE_TENANT_SESSION_KEY => (int) $legacyTenantId]);
            }

            session()->forget(Company::ACTIVE_COMPANY_SESSION_KEY);
        }

        if (! session()->has(static::ACTIVE_TENANT_SESSION_KEY)) {
            $defaultTenantId = static::defaultTenantIdForCurrentUser();

            if (! is_null($defaultTenantId)) {
                session([static::ACTIVE_TENANT_SESSION_KEY => (int) $defaultTenantId]);
            } else {
                return null;
            }
        }

        $activeTenantId = Company::getIdFromInput(session(static::ACTIVE_TENANT_SESSION_KEY));

        if (is_null($activeTenantId) || ! static::canCurrentUserSwitchToTenant((int) $activeTenantId)) {
            $defaultTenantId = static::defaultTenantIdForCurrentUser();

            if (! is_null($defaultTenantId)) {
                session([static::ACTIVE_TENANT_SESSION_KEY => (int) $defaultTenantId]);

                return (int) $defaultTenantId;
            }

            session()->forget(static::ACTIVE_TENANT_SESSION_KEY);

            return null;
        }

        return (int) $activeTenantId;
    }

    public static function activeTenant(): ?self
    {
        $activeTenantId = static::activeTenantId();

        if (is_null($activeTenantId)) {
            return null;
        }

        return static::find($activeTenantId);
    }

    public static function currentTenant(): ?self
    {
        $activeTenant = static::activeTenant();

        if ($activeTenant) {
            return $activeTenant;
        }

        return null;
    }

    public static function activeTenantCompanyIds(): array
    {
        $tenant = static::activeTenant();

        if (! $tenant) {
            return [];
        }

        return $tenant->activeCompanyIds();
    }

    public static function currentTenantRootCompany(): ?Company
    {
        return static::currentTenant()?->rootCompany();
    }

    public static function aggregatedAccessibleCompanyIdsForCurrentUser(): array
    {
        $tenantIds = static::accessibleTenantIdsForCurrentUser();

        if (count($tenantIds) === 0) {
            return [];
        }

        return Company::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereIn('tenant_id', $tenantIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public static function canCurrentUserViewTenant(self $tenant): bool
    {
        $authContext = Company::currentAuthContext();

        if (is_null($authContext['id'])) {
            return false;
        }

        return in_array((int) $tenant->id, static::accessibleTenantIdsForCurrentUser(), true)
            || in_array(static::currentUserRoleForTenant((int) $tenant->id), [static::ROLE_ADMIN, static::ROLE_VIEWER], true);
    }

    public static function canCurrentUserManageTenant(self $tenant): bool
    {
        $authContext = Company::currentAuthContext();

        if (is_null($authContext['id'])) {
            return false;
        }

        if ($authContext['is_superadmin'] && $authContext['can_view_all_tenants']) {
            return true;
        }

        $currentCompanyTenantId = static::currentUserCompanyTenantId($authContext);

        if ($authContext['is_superuser'] && (int) ($currentCompanyTenantId ?? 0) === (int) $tenant->id) {
            return true;
        }

        return static::currentUserRoleForTenant((int) $tenant->id) === static::ROLE_ADMIN;
    }

    public static function canCurrentUserAccessTenantAdminArea(): bool
    {
        $authContext = Company::currentAuthContext();

        if (is_null($authContext['id'])) {
            return false;
        }

        return $authContext['can_view_all_tenants']
            || ($authContext['is_superuser'] && ! is_null(static::currentUserCompanyTenantId($authContext)))
            || (count(static::explicitMembershipTenantIdsForCurrentUser()) > 0);
    }

    public static function tenantManageablePermissions(): array
    {
        return [
            'import',
            'reports.view', 'reports.nis_risk_matrix.view', 'reports.nis_real_coverage.view',
            'assets.view', 'assets.create', 'assets.edit', 'assets.delete', 'assets.checkin', 'assets.checkout', 'assets.audit', 'assets.view.requestable', 'assets.view.encrypted_custom_fields', 'assets.files',
            'documents.view', 'documents.create', 'documents.edit', 'documents.delete', 'documents.restore', 'documents.force_delete', 'documents.files.view', 'documents.files', 'documents.requirements.map',
            'documents.area.administration.view', 'documents.area.administration.edit', 'documents.area.administration.files.view', 'documents.area.administration.files',
            'documents.area.it.view', 'documents.area.it.edit', 'documents.area.it.files.view', 'documents.area.it.files',
            'documents.area.cybersecurity.view', 'documents.area.cybersecurity.edit', 'documents.area.cybersecurity.files.view', 'documents.area.cybersecurity.files',
            'tickets.view', 'tickets.create', 'tickets.operate', 'tickets.edit', 'tickets.delete', 'tickets.files',
            'documenttypes.view', 'documenttypes.create', 'documenttypes.edit', 'documenttypes.delete',
            'documentframeworks.view', 'documentframeworks.create', 'documentframeworks.edit', 'documentframeworks.delete',
            'compliancedomains.view',
            'accessories.view', 'accessories.create', 'accessories.edit', 'accessories.delete', 'accessories.checkout', 'accessories.checkin', 'accessories.files',
            'consumables.view', 'consumables.create', 'consumables.edit', 'consumables.delete', 'consumables.checkout', 'consumables.files',
            'licenses.view', 'licenses.create', 'licenses.edit', 'licenses.delete', 'licenses.checkout', 'licenses.checkin', 'licenses.keys', 'licenses.files',
            'components.view', 'components.create', 'components.edit', 'components.delete', 'components.checkout', 'components.checkin', 'components.files',
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.files',
            'locations.view', 'locations.create', 'locations.edit', 'locations.delete',
            'departments.view', 'departments.create', 'departments.edit', 'departments.delete',
            'companies.view', 'companies.create', 'companies.edit', 'companies.delete',
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'manufacturers.view', 'manufacturers.create', 'manufacturers.edit', 'manufacturers.delete',
            'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete',
            'models.view', 'models.create', 'models.edit', 'models.delete',
            'statuslabels.view', 'statuslabels.create', 'statuslabels.edit', 'statuslabels.delete',
            'depreciations.view', 'depreciations.create', 'depreciations.edit', 'depreciations.delete',
            'customfields.view', 'customfields.create', 'customfields.edit', 'customfields.delete',
            'tenants.services.view', 'tenants.services.manage',
        ];
    }

    public static function tenantViewerPermissions(): array
    {
        return array_values(array_filter(static::tenantManageablePermissions(), function ($permission) {
            return ($permission === 'reports.view')
                || str_contains($permission, '.view');
        }));
    }

    public static function clearCurrentUserTenantRoleCache(): void
    {
        static::$currentUserTenantRolesCache = null;
    }

    public static function setActiveTenantContext($tenantId): void
    {
        $tenantId = Company::getIdFromInput($tenantId);

        if (is_null($tenantId)) {
            static::clearActiveTenantContext();

            return;
        }

        session([static::ACTIVE_TENANT_SESSION_KEY => (int) $tenantId]);
    }

    public static function clearActiveTenantContext(): void
    {
        $defaultTenantId = static::defaultTenantIdForCurrentUser();

        if (is_null($defaultTenantId)) {
            session()->forget(static::ACTIVE_TENANT_SESSION_KEY);

            return;
        }

        session([static::ACTIVE_TENANT_SESSION_KEY => (int) $defaultTenantId]);
    }

    private static function currentUserCompanyTenantId(array $authContext): ?int
    {
        if (is_null($authContext['company_id'])) {
            return null;
        }

        $tenantId = Company::withoutGlobalScopes()
            ->where('id', $authContext['company_id'])
            ->value('tenant_id');

        return $tenantId ? (int) $tenantId : null;
    }
}
