<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImageUploadRequest;
use App\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * This controller handles all actions related to Companies for
 * the Snipe-IT Asset Management application.
 *
 * @version    v1.0
 */
final class CompaniesController extends Controller
{
    public function switchContext(Request $request): RedirectResponse
    {
        abort_unless(auth()->check(), 403);
        abort_unless(Company::canCurrentUserSelectCompany(), 403);

        $companyId = Company::getIdFromInput($request->input('company_id'));

        if (! is_null($companyId) && ! Company::canCurrentUserSwitchToCompany((int) $companyId)) {
            abort(403);
        }

        if (is_null($companyId)) {
            Company::clearActiveCompanyContext();
        } else {
            Company::setActiveCompanyContext((int) $companyId);
        }

        return redirect()->to($this->resolveSwitchRedirect($request));
    }

    /**
     * Returns view to display listing of companies.
     *
     * @author [Abdullah Alansari] [<ahimta@gmail.com>]
     *
     * @since [v1.8]
     */
    public function index(): View
    {
        $this->authorize('view', Company::class);

        return view('companies/index');
    }

    /**
     * Returns view to create a new company.
     *
     * @author [Abdullah Alansari] [<ahimta@gmail.com>]
     *
     * @since [v1.8]
     */
    public function create(): View
    {
        $this->authorize('create', Company::class);

        $company = new Company;

        // Allow pre-filling the parent (e.g. from the tenant page "add company"
        // button) so the new company lands in that company's tenant/group.
        if (request()->filled('parent_id')) {
            $parentId = (int) request('parent_id');
            if (Company::where('id', $parentId)->exists()) {
                $company->parent_id = $parentId;
            }
        }

        return view('companies/edit')->with('item', $company);
    }

    /**
     * Save data from new company form.
     *
     * @author [Abdullah Alansari] [<ahimta@gmail.com>]
     *
     * @since [v1.8]
     *
     * @param  Request  $request
     */
    public function store(ImageUploadRequest $request): RedirectResponse
    {
        $this->authorize('create', Company::class);

        $company = new Company;
        $this->fillCompany($company, $request);
        $company->created_by = auth()->id();

        $company = $request->handleImages($company);

        if ($company->save()) {
            Company::ensureTenantAssignment($company);
            $this->handleBrandingUploads($request, $company);

            return redirect()->route('companies.index')
                ->with('success', trans('admin/companies/message.create.success'));
        }

        return redirect()->back()->withInput()->withErrors($company->getErrors());
    }

    /**
     * Return form to edit existing company.
     *
     * @author [Abdullah Alansari] [<ahimta@gmail.com>]
     *
     * @since [v1.8]
     *
     * @param  int  $companyId
     */
    public function edit(Company $company): View|RedirectResponse
    {
        $this->authorize('update', $company);

        return view('companies/edit')->with('item', $company);
    }

    /**
     * Save data from edit company form.
     *
     * @author [Abdullah Alansari] [<ahimta@gmail.com>]
     *
     * @since [v1.8]
     *
     * @param  int  $companyId
     */
    public function update(ImageUploadRequest $request, Company $company): RedirectResponse
    {

        $this->authorize('update', $company);
        $this->fillCompany($company, $request);

        $company = $request->handleImages($company);

        if ($company->save()) {
            Company::ensureTenantAssignment($company);
            $this->handleBrandingUploads($request, $company);

            return redirect()->route('companies.index')
                ->with('success', trans('admin/companies/message.update.success'));
        }

        return redirect()->back()->withInput()->withErrors($company->getErrors());
    }

    /**
     * Delete company
     *
     * @author [Abdullah Alansari] [<ahimta@gmail.com>]
     *
     * @since [v1.8]
     *
     * @param  int  $companyId
     */
    public function destroy($companyId): RedirectResponse
    {

        if (is_null($company = Company::find($companyId))) {
            return redirect()->route('companies.index')
                ->with('error', trans('admin/companies/message.does_not_exist'));
        }

        $this->authorize('delete', $company);
        if ($company->isTenantRoot()) {
            return redirect()->route('companies.index')
                ->with('error', trans('admin/companies/message.assoc_users'));
        }

        if (! $company->isDeletable()) {
            return redirect()->route('companies.index')
                ->with('error', trans('admin/companies/message.assoc_users'));
        }

        if ($company->image) {
            try {
                Storage::disk('public')->delete('companies'.'/'.$company->image);
            } catch (\Exception $e) {
                Log::debug($e);
            }
        }

        $company->delete();

        return redirect()->route('companies.index')
            ->with('success', trans('admin/companies/message.delete.success'));
    }

    public function show(Company $company): View|RedirectResponse
    {
        $this->authorize('view', $company);

        return view('companies/view')->with('company', $company);
    }

    private function fillCompany(Company $company, Request $request): void
    {
        $company->name = $request->input('name');
        $company->parent_id = $this->normalizedParentId($company, $request);
        $company->phone = $request->input('phone');
        $company->fax = $request->input('fax');
        $company->email = $request->input('email');
        $company->tag_color = $request->input('tag_color');
        $company->notes = $request->input('notes');
        $company->brand = $request->input('brand');
        $company->header_color = $request->input('header_color');
        $company->nav_link_color = $request->input('nav_link_color');
        $company->link_light_color = $request->input('link_light_color');
        $company->link_dark_color = $request->input('link_dark_color');
        $company->footer_text = $request->input('footer_text');
        $company->privacy_policy_link = $request->input('privacy_policy_link');
        $company->custom_css = $request->input('custom_css');
    }

    private function normalizedParentId(Company $company, Request $request): ?int
    {
        if (! $request->has('parent_id') && $company->exists) {
            return $company->parent_id ? (int) $company->parent_id : null;
        }

        $parentId = Company::getIdFromInput($request->input('parent_id'));

        if (! is_null($parentId) && (int) $parentId === (int) ($company->id ?? 0)) {
            return null;
        }

        if (! is_null($parentId) && Company::canCurrentUserSwitchToCompany((int) $parentId)) {
            return (int) $parentId;
        }

        if (! $company->exists && ! Company::currentAuthContext()['can_view_all_tenants']) {
            return Company::activeCompanyId();
        }

        return null;
    }

    private function handleBrandingUploads(ImageUploadRequest $request, Company $company): void
    {
        if (! $company->isTenantRoot()) {
            return;
        }

        foreach (['brand_logo', 'favicon'] as $field) {
            $company = $request->handleImages($company, 600, $field, 'companies/branding', $field);

            if ($company->{$field} && ! str_contains($company->{$field}, '/')) {
                $company->{$field} = 'companies/branding/'.$company->{$field};
            }

            $clearField = 'clear_'.$field;
            if ($request->input($clearField) == '1') {
                $company = $request->deleteExistingImage($company, null, $field);
                $company->{$field} = null;
            }
        }

        $company->saveQuietly();
    }

    private function resolveSwitchRedirect(Request $request): string
    {
        $fallbackUrl = route('home');
        $redirectTo = trim((string) $request->input('redirect_to'));

        if ($redirectTo === '') {
            return $this->appendTenantSwitchFlag($fallbackUrl);
        }

        if (str_starts_with($redirectTo, '/')) {
            return $this->appendTenantSwitchFlag($redirectTo);
        }

        $appUrl = parse_url(config('app.url'));
        $redirectUrl = parse_url($redirectTo);

        if (
            $appUrl
            && $redirectUrl
            && (($appUrl['host'] ?? null) === ($redirectUrl['host'] ?? null))
            && (($appUrl['scheme'] ?? null) === ($redirectUrl['scheme'] ?? null))
        ) {
            return $this->appendTenantSwitchFlag($redirectTo);
        }

        return $this->appendTenantSwitchFlag($fallbackUrl);
    }

    private function appendTenantSwitchFlag(string $url): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        if (str_contains($url, 'tenant_switched=')) {
            return $url;
        }

        return $url.$separator.'tenant_switched=1';
    }
}
