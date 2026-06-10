<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Http\Requests\ImageUploadRequest;
use App\Http\Requests\StoreTenantHelpdeskSettingsRequest;
use App\Http\Requests\StoreTenantMailSettingsRequest;
use App\Http\Requests\StoreTenantSettingsRequest;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use App\Support\Compliance\ComplianceFrameworkPackTenantUpdater;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TenantsController extends Controller
{
    public function switchContext(Request $request): RedirectResponse
    {
        abort_unless(auth()->check(), 403);
        abort_unless(Tenant::canCurrentUserSwitchTenants(), 403);

        $request->validate([
            'tenant_id' => 'required',
        ]);

        $tenantId = Company::getIdFromInput($request->input('tenant_id'));

        if (is_null($tenantId) || ! Tenant::canCurrentUserSwitchToTenant((int) $tenantId)) {
            abort(403);
        }

        $request->session()->put(Tenant::ACTIVE_TENANT_SESSION_KEY, (int) $tenantId);

        Company::flushHierarchyCache();

        return redirect()->to($this->resolveSwitchRedirect($request, (int) $tenantId));
    }

    public function index(): View
    {
        abort_unless(auth()->user()->hasAccessToTenantAdminArea(), 403);

        $authContext = Company::currentAuthContext();
        $tenantIds = ($authContext['can_view_all_tenants'] || $authContext['is_superuser'])
            ? Tenant::accessibleTenantIdsForCurrentUser()
            : Tenant::explicitMembershipTenantIdsForCurrentUser();

        $tenants = Tenant::query()
            ->whereIn('id', $tenantIds)
            ->get()
            ->map(function (Tenant $tenant) {
                $rootCompany = $tenant->rootCompany();
                $companyIds = $tenant->activeCompanyIds();

                return (object) [
                    'tenant' => $tenant,
                    'root_company' => $rootCompany,
                    'companies_count' => count($companyIds),
                    'users_count' => Company::withoutGlobalScopes()->whereIn('id', $companyIds)->withCount('users')->get()->sum('users_count'),
                    'assets_count' => Company::withoutGlobalScopes()->whereIn('id', $companyIds)->withCount('assets')->get()->sum('assets_count'),
                ];
            })
            ->filter(fn ($row) => ! is_null($row->root_company))
            ->sortBy(fn ($row) => mb_strtolower($row->root_company->name))
            ->values();

        return view('tenants.index', compact('tenants'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return view('tenants.create', [
            'item' => new Company,
            'jurisdictionOptions' => Tenant::complianceJurisdictionOptions(),
        ]);
    }

    public function store(ImageUploadRequest $request): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $request->validate([
            'name' => 'required|string|max:255|unique:companies,name',
            'phone' => 'nullable|string|max:35',
            'fax' => 'nullable|string|max:35',
            'email' => 'nullable|email|max:150',
            'tag_color' => 'nullable|string|max:16',
            'brand' => 'nullable|integer|in:1,2,3',
            'header_color' => 'nullable|string|max:16',
            'nav_link_color' => 'nullable|string|max:16',
            'link_light_color' => 'nullable|string|max:16',
            'link_dark_color' => 'nullable|string|max:16',
            'privacy_policy_link' => 'nullable|url|max:255',
            'default_locale' => 'required|string|in:'.implode(',', Helper::availableLanguageLocales()),
            'default_compliance_jurisdiction' => 'required|string|in:'.implode(',', Tenant::complianceJurisdictionValues()),
            'bootstrap_compliance_frameworks' => 'nullable|boolean',
        ]);

        $company = null;

        try {
            DB::transaction(function () use ($request, &$company) {
                $tenant = Tenant::createMinimal();
                $tenant->default_locale = $request->input('default_locale');
                $tenant->default_compliance_jurisdiction = $request->input('default_compliance_jurisdiction');
                $tenant->save();

                $company = new Company;
                $company->tenant_id = $tenant->id;
                $company->name = $request->input('name');
                $company->phone = $request->input('phone');
                $company->fax = $request->input('fax');
                $company->email = $request->input('email');
                $company->tag_color = $request->input('tag_color');
                $company->notes = $request->input('notes');
                $company->brand = $request->input('brand', 3);
                $company->header_color = $request->input('header_color');
                $company->nav_link_color = $request->input('nav_link_color');
                $company->link_light_color = $request->input('link_light_color');
                $company->link_dark_color = $request->input('link_dark_color');
                $company->footer_text = $request->input('footer_text');
                $company->privacy_policy_link = $request->input('privacy_policy_link');
                $company->custom_css = $request->input('custom_css');
                $company->helpdesk_slug = Company::generateUniqueHelpdeskSlug($company->name);
                $company->created_by = auth()->id();

                $company = $request->handleImages($company);

                if (! $company->save()) {
                    throw new \RuntimeException('Tenant root company save failed');
                }

                foreach (['brand_logo', 'favicon'] as $field) {
                    $company = $request->handleImages($company, 600, $field, 'companies/branding', $field);

                    if ($company->{$field} && ! str_contains($company->{$field}, '/')) {
                        $company->{$field} = 'companies/branding/'.$company->{$field};
                    }
                }

                $company->saveQuietly();

                if ($request->boolean('bootstrap_compliance_frameworks')) {
                    app(ComplianceFrameworkPackTenantUpdater::class)->applyAvailablePacks(
                        $tenant,
                        auth()->id(),
                    );
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->withInput()->withErrors([
                'name' => trans('admin/tenants/message.create.error'),
            ]);
        }

        return redirect()->route('tenants.index')->with('success', trans('admin/tenants/message.create.success'));
    }

    public function show(Tenant $tenant): View
    {
        abort_unless(auth()->user()->canViewTenant($tenant), 403);

        $rootCompany = $tenant->rootCompany();
        $companies = Company::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereNull('deleted_at')
            ->orderByRaw('parent_id is null desc')
            ->orderBy('name')
            ->get();

        $members = $tenant->members()
            ->with('company')
            ->when(! auth()->user()->isSuperAdmin(), fn ($query) => $query->withoutPlatformSuperAdmins())
            ->orderBy('display_name')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $canManageTenant = auth()->user()->canManageTenant($tenant);
        $publicTicketTypes = $tenant->publicHelpdeskSelectedTicketTypes();
        $complianceSummary = $this->tenantComplianceSummary($tenant, $companies->pluck('id')->map(fn ($id) => (int) $id)->all());
        $servicesCount = Schema::hasTable('tenant_services') ? $tenant->services()->count() : 0;
        $activeServicesCount = Schema::hasTable('tenant_services') ? $tenant->services()->active()->count() : 0;

        return view('tenants.show', compact('tenant', 'rootCompany', 'companies', 'members', 'canManageTenant', 'publicTicketTypes', 'complianceSummary', 'servicesCount', 'activeServicesCount'));
    }

    public function editSettings(Tenant $tenant): View
    {
        abort_unless(auth()->user()->canManageTenant($tenant), 403);
        $rootCompany = $tenant->rootCompany();
        abort_if(is_null($rootCompany), 404);

        return view('tenants.settings', [
            'tenant' => $tenant,
            'rootCompany' => $rootCompany,
            'languageOptions' => trans('localizations.languages'),
            'jurisdictionOptions' => Tenant::complianceJurisdictionOptions(),
        ]);
    }

    public function updateSettings(StoreTenantSettingsRequest $request, Tenant $tenant): RedirectResponse
    {
        abort_unless(auth()->user()->canManageTenant($tenant), 403);

        $tenant->default_locale = $request->input('default_locale');
        $tenant->default_compliance_jurisdiction = $request->input('default_compliance_jurisdiction');
        $tenant->save();

        $message = trans('admin/tenants/message.settings.update.success');

        if ($request->boolean('bootstrap_compliance_frameworks')) {
            $summary = app(ComplianceFrameworkPackTenantUpdater::class)->applyAvailablePacks(
                $tenant,
                auth()->id(),
            );

            $message .= ' '.trans('admin/tenants/message.settings.bootstrap.safe_update_success', [
                'applied' => $summary['applied'],
                'frameworks' => $summary['frameworks_created'],
                'requirements' => $summary['requirements_created'],
                'manual_review' => $summary['manual_review'],
                'skipped' => $summary['skipped'],
                'locale' => $tenant->defaultLocale(),
            ]);
        }

        return redirect()->route('tenants.show', $tenant)->with('success', $message);
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        if (! $tenant->isDeletable()) {
            return redirect()->route('tenants.index')->with('error', trans('admin/tenants/message.delete.not_deletable'));
        }

        DB::transaction(function () use ($tenant) {
            $companyIds = Company::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $tenant->helpdeskTicketTypes()->detach();
            $tenant->members()->detach();

            if (count($companyIds) > 0) {
                Company::withoutGlobalScopes()
                    ->whereIn('id', $companyIds)
                    ->get()
                    ->each
                    ->forceDelete();
            }

            $tenant->delete();
        });

        if ((int) (Tenant::activeTenantId() ?? 0) === (int) $tenant->id) {
            Tenant::clearActiveTenantContext();
        }

        Company::flushHierarchyCache();
        Tenant::clearCurrentUserTenantRoleCache();

        return redirect()->route('tenants.index')->with('success', trans('admin/tenants/message.delete.success'));
    }

    private function tenantComplianceSummary(Tenant $tenant, array $companyIds): array
    {
        $reviewWarningDays = $tenant->documentReviewWarningDays();

        if (count($companyIds) === 0) {
            return [
                'frameworks' => collect(),
                'requirements' => [
                    'total' => 0,
                    'covered' => 0,
                    'at_risk' => 0,
                    'supporting_only' => 0,
                    'missing' => 0,
                ],
                'documents' => [
                    'total' => 0,
                    'due' => 0,
                    'overdue' => 0,
                ],
                'suppliers' => [
                    'relevant' => 0,
                    'review_due' => 0,
                    'without_review_date' => 0,
                ],
                'assets' => [
                    'nis_relevant' => 0,
                    'high_impact' => 0,
                ],
                'tickets' => [
                    'open' => 0,
                    'sla_at_risk' => 0,
                ],
            ];
        }

        $frameworks = DocumentFramework::withoutGlobalScopes()
            ->whereIn('company_id', $companyIds)
            ->whereNull('deleted_at')
            ->where('is_system_template', false)
            ->where('is_active', true)
            ->where('status', 'active')
            ->with(['requirements' => function ($query) {
                $query->whereNull('deleted_at')
                    ->where('is_active', true)
                    ->withCount([
                        'documents',
                        'primaryDocuments as primary_documents_count',
                        'primaryDocuments as healthy_primary_documents_count' => fn ($documentsQuery) => $documentsQuery->currentForCoverage(),
                    ])
                    ->ordered();
            }])
            ->withCount(['documents', 'requirements'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $requirements = $frameworks->flatMap(fn (DocumentFramework $framework) => $framework->requirements);
        $coverageCounts = $requirements
            ->countBy(fn (DocumentFrameworkRequirement $requirement) => $requirement->coverage_status);

        $documentsBase = Document::withoutGlobalScopes()
            ->whereIn('company_id', $companyIds)
            ->whereNull('deleted_at');

        $suppliersBase = Supplier::withoutGlobalScopes()
            ->whereIn('company_id', $companyIds)
            ->whereNull('deleted_at');

        $assetsBase = Asset::withoutGlobalScopes()
            ->whereIn('company_id', $companyIds)
            ->whereNull('deleted_at');

        $ticketsBase = Ticket::withoutGlobalScopes()
            ->whereIn('company_id', $companyIds)
            ->whereNull('deleted_at');

        return [
            'frameworks' => $frameworks
                ->map(function (DocumentFramework $framework) {
                    $summary = $framework->coverage_summary;

                    return [
                        'id' => $framework->id,
                        'name' => $framework->name,
                        'coverage_percent' => $summary['coverage_percent'],
                        'covered' => $summary['covered'],
                        'at_risk' => $summary['at_risk'],
                        'missing' => $summary['missing'],
                        'total' => $summary['total'],
                    ];
                })
                ->values(),
            'requirements' => [
                'total' => $requirements->count(),
                'covered' => (int) $coverageCounts->get(DocumentFrameworkRequirement::COVERAGE_COVERED, 0),
                'at_risk' => (int) $coverageCounts->get(DocumentFrameworkRequirement::COVERAGE_AT_RISK, 0),
                'supporting_only' => (int) $coverageCounts->get(DocumentFrameworkRequirement::COVERAGE_SUPPORTING_ONLY, 0),
                'missing' => (int) $coverageCounts->get(DocumentFrameworkRequirement::COVERAGE_MISSING, 0),
            ],
            'documents' => [
                'total' => (clone $documentsBase)->count(),
                'due' => (clone $documentsBase)->dueForReview($reviewWarningDays)->count(),
                'overdue' => (clone $documentsBase)->overdueForReview()->count(),
            ],
            'suppliers' => [
                'relevant' => (clone $suppliersBase)->where('nis_relevant', true)->count(),
                'review_due' => (clone $suppliersBase)
                    ->where('nis_relevant', true)
                    ->whereNotNull('nis_next_review_at')
                    ->whereDate('nis_next_review_at', '<=', now()->addDays($reviewWarningDays)->toDateString())
                    ->count(),
                'without_review_date' => (clone $suppliersBase)
                    ->where('nis_relevant', true)
                    ->whereNull('nis_next_review_at')
                    ->count(),
            ],
            'assets' => [
                'nis_relevant' => (clone $assetsBase)->where('nis_relevant', true)->count(),
                'high_impact' => (clone $assetsBase)
                    ->where('nis_relevant', true)
                    ->whereIn('nis_service_impact', ['high', 'critical'])
                    ->count(),
            ],
            'tickets' => [
                'open' => (clone $ticketsBase)->open()->count(),
                'sla_at_risk' => (clone $ticketsBase)->slaAtRisk()->count(),
            ],
        ];
    }

    public function storeMember(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless(auth()->user()->canManageTenant($tenant), 403);

        $payload = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'role' => 'required|string|in:'.Tenant::ROLE_ADMIN.','.Tenant::ROLE_VIEWER,
        ]);

        $user = User::withoutGlobalScopes()->findOrFail($payload['user_id']);
        abort_if($user->isSuperAdmin(), 403);

        $tenant->members()->syncWithoutDetaching([
            $user->id => [
                'role' => $payload['role'],
                'created_by' => auth()->id(),
            ],
        ]);

        Tenant::clearCurrentUserTenantRoleCache();

        return redirect()->route('tenants.show', $tenant)->with('success', trans('admin/tenants/message.membership.create.success'));
    }

    public function updateMember(Request $request, Tenant $tenant, User $user): RedirectResponse
    {
        abort_unless(auth()->user()->canManageTenant($tenant), 403);
        abort_if($user->isSuperAdmin(), 403);

        $payload = $request->validate([
            'role' => 'required|string|in:'.Tenant::ROLE_ADMIN.','.Tenant::ROLE_VIEWER,
        ]);

        $tenant->members()->updateExistingPivot($user->id, [
            'role' => $payload['role'],
        ]);

        Tenant::clearCurrentUserTenantRoleCache();

        return redirect()->route('tenants.show', $tenant)->with('success', trans('admin/tenants/message.membership.update.success'));
    }

    public function destroyMember(Tenant $tenant, User $user): RedirectResponse
    {
        abort_unless(auth()->user()->canManageTenant($tenant), 403);
        abort_if($user->isSuperAdmin(), 403);

        $tenant->members()->detach($user->id);

        Tenant::clearCurrentUserTenantRoleCache();

        return redirect()->route('tenants.show', $tenant)->with('success', trans('admin/tenants/message.membership.delete.success'));
    }

    public function editHelpdesk(Tenant $tenant): View
    {
        abort_unless(auth()->user()->canManageTenant($tenant), 403);

        $rootCompany = $tenant->rootCompany();
        abort_if(is_null($rootCompany), 404);

        return view('tenants.helpdesk', [
            'tenant' => $tenant,
            'rootCompany' => $rootCompany,
            'availableTicketTypes' => $tenant->publicHelpdeskAvailableTicketTypes(),
            'selectedTicketTypeIds' => $tenant->publicHelpdeskSelectedTicketTypes()->pluck('id')->all(),
        ]);
    }

    public function editMail(Tenant $tenant): View
    {
        abort_unless(auth()->user()->canManageTenant($tenant), 403);

        $rootCompany = $tenant->rootCompany();
        abort_if(is_null($rootCompany), 404);

        return view('tenants.mail', [
            'tenant' => $tenant,
            'rootCompany' => $rootCompany,
            'mailEventOptions' => Tenant::mailNotificationEventOptions(),
            'enabledEvents' => $tenant->notificationEvents(),
        ]);
    }

    public function updateHelpdesk(StoreTenantHelpdeskSettingsRequest $request, Tenant $tenant): RedirectResponse
    {
        abort_unless(auth()->user()->canManageTenant($tenant), 403);

        $rootCompany = $tenant->rootCompany();
        abort_if(is_null($rootCompany), 404);

        $rootCompany->helpdesk_enabled = $request->boolean('helpdesk_enabled');
        $rootCompany->helpdesk_allow_attachments = $request->boolean('helpdesk_allow_attachments', true);
        $rootCompany->helpdesk_slug = Company::generateUniqueHelpdeskSlug(
            $request->input('helpdesk_slug') ?: $rootCompany->name,
            $rootCompany->id
        );
        $rootCompany->helpdesk_intro = $request->input('helpdesk_intro');
        $rootCompany->helpdesk_privacy_note = $request->input('helpdesk_privacy_note');
        $rootCompany->helpdesk_contact_email = $request->input('helpdesk_contact_email');
        $rootCompany->helpdesk_contact_phone = $request->input('helpdesk_contact_phone');
        $rootCompany->save();

        $selectedIds = collect($request->input('public_ticket_type_ids', []))
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $tenant->helpdeskTicketTypes()->sync($selectedIds);

        return redirect()->route('tenants.show', $tenant)
            ->with('success', trans('admin/tenants/message.helpdesk.update.success'));
    }

    public function updateMail(StoreTenantMailSettingsRequest $request, Tenant $tenant): RedirectResponse
    {
        abort_unless(auth()->user()->canManageTenant($tenant), 403);

        $rootCompany = $tenant->rootCompany();
        abort_if(is_null($rootCompany), 404);

        $selectedEvents = collect($request->input('tenant_mail_notification_events', []))
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->unique()
            ->values()
            ->all();

        $rootCompany->tenant_notification_email = $request->input('tenant_notification_email');
        $rootCompany->tenant_mail_reply_to_email = $request->input('tenant_mail_reply_to_email');
        $rootCompany->tenant_mail_reply_to_name = $request->input('tenant_mail_reply_to_name');
        $rootCompany->tenant_mail_from_name = $request->input('tenant_mail_from_name');
        $rootCompany->helpdesk_contact_email = $request->input('helpdesk_contact_email');
        $rootCompany->tenant_document_review_warning_days = $request->integer('tenant_document_review_warning_days', 30);
        $rootCompany->tenant_mail_notification_events = $selectedEvents;
        $rootCompany->save();

        return redirect()->route('tenants.show', $tenant)
            ->with('success', trans('admin/tenants/message.mail.update.success'));
    }

    private function resolveSwitchRedirect(Request $request, int $tenantId): string
    {
        $fallbackUrl = route('home');
        $redirectTo = trim((string) $request->input('redirect_to'));

        if ($redirectTo === '') {
            return $this->appendTenantSwitchFlag($fallbackUrl);
        }

        if (str_starts_with($redirectTo, '/') && ! str_starts_with($redirectTo, '//')) {
            return $this->appendTenantSwitchFlag($this->tenantAwareRedirectUrl($redirectTo, $tenantId));
        }

        $appUrl = parse_url(config('app.url'));
        $redirectUrl = parse_url($redirectTo);

        if (
            $appUrl
            && $redirectUrl
            && (($appUrl['host'] ?? null) === ($redirectUrl['host'] ?? null))
            && (($appUrl['scheme'] ?? null) === ($redirectUrl['scheme'] ?? null))
        ) {
            return $this->appendTenantSwitchFlag($this->tenantAwareRedirectUrl($redirectTo, $tenantId));
        }

        return $this->appendTenantSwitchFlag($fallbackUrl);
    }

    private function tenantAwareRedirectUrl(string $url, int $tenantId): string
    {
        $parts = parse_url($url) ?: [];
        $query = $this->tenantAwareQueryString($parts['query'] ?? '', $tenantId);
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';
        $tenantRoute = $this->tenantRouteForPath($parts['path'] ?? '', $tenantId);

        if ($tenantRoute) {
            return $this->withQueryAndFragment($tenantRoute, $query, $fragment);
        }

        return $this->withQueryAndFragment($this->urlWithoutQueryOrFragment($url), $query, $fragment);
    }

    private function tenantAwareQueryString(string $queryString, int $tenantId): string
    {
        parse_str($queryString, $query);
        unset($query['tenant_switched']);

        if (array_key_exists('tenant_id', $query)) {
            $query['tenant_id'] = (string) $tenantId;
        }

        return http_build_query($query);
    }

    private function tenantRouteForPath(string $path, int $tenantId): ?string
    {
        $path = $this->pathWithoutAppBase($path);
        $segments = array_values(array_filter(explode('/', trim($path, '/')), fn ($segment) => $segment !== ''));

        if (($segments[0] ?? null) !== 'admin' || ($segments[1] ?? null) !== 'tenants') {
            return null;
        }

        if (! isset($segments[2]) || ! ctype_digit($segments[2])) {
            return null;
        }

        $tenantArea = array_slice($segments, 3);

        if (count($tenantArea) === 0) {
            return route('tenants.show', $tenantId);
        }

        if ($tenantArea[0] === 'services') {
            if (($tenantArea[1] ?? null) === 'create') {
                return route('tenants.services.create', $tenantId);
            }

            return route('tenants.services.index', $tenantId);
        }

        return match ($tenantArea[0]) {
            'settings' => route('tenants.settings.edit', $tenantId),
            'helpdesk' => route('tenants.helpdesk.edit', $tenantId),
            'mail' => route('tenants.mail.edit', $tenantId),
            default => route('tenants.show', $tenantId),
        };
    }

    private function pathWithoutAppBase(string $path): string
    {
        $appPath = trim((string) (parse_url(config('app.url'), PHP_URL_PATH) ?: ''), '/');
        $path = '/'.ltrim($path, '/');

        if ($appPath === '') {
            return $path;
        }

        $pathWithoutLeadingSlash = ltrim($path, '/');

        if ($pathWithoutLeadingSlash === $appPath) {
            return '/';
        }

        if (str_starts_with($pathWithoutLeadingSlash, $appPath.'/')) {
            return '/'.substr($pathWithoutLeadingSlash, strlen($appPath) + 1);
        }

        return $path;
    }

    private function urlWithoutQueryOrFragment(string $url): string
    {
        return preg_replace('/[?#].*$/', '', $url) ?: $url;
    }

    private function withQueryAndFragment(string $url, string $query, string $fragment): string
    {
        return $url.($query !== '' ? '?'.$query : '').$fragment;
    }

    private function appendTenantSwitchFlag(string $url): string
    {
        $fragment = '';

        if (str_contains($url, '#')) {
            [$url, $fragment] = explode('#', $url, 2);
            $fragment = '#'.$fragment;
        }

        $baseUrl = $url;
        $query = [];

        if (str_contains($url, '?')) {
            [$baseUrl, $queryString] = explode('?', $url, 2);
            parse_str($queryString, $query);
        }

        $query['tenant_switched'] = '1';

        return $baseUrl.'?'.http_build_query($query).$fragment;
    }
}
