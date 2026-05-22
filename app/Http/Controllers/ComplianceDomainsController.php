<?php

namespace App\Http\Controllers;

use App\Models\ComplianceDomain;
use App\Models\DocumentFramework;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComplianceDomainsController extends Controller
{
    public function index(): View
    {
        $this->authorize('index', ComplianceDomain::class);

        return view('compliancedomains.index');
    }

    public function create(): View
    {
        $this->authorize('create', ComplianceDomain::class);

        return view('compliancedomains.edit', [
            'item' => new ComplianceDomain([
                'is_active' => true,
                'sort_order' => 0,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ComplianceDomain::class);

        $validated = $this->validatedPayload($request);

        $complianceDomain = new ComplianceDomain;
        $complianceDomain->fill($validated);
        $complianceDomain->is_active = $request->boolean('is_active', true);
        $complianceDomain->is_system = false;
        $complianceDomain->created_by = auth()->id();

        if ($complianceDomain->save()) {
            return redirect()->route('compliancedomains.index')->with('success', trans('admin/compliancedomains/message.create.success'));
        }

        return redirect()->back()->withInput()->withErrors($complianceDomain->getErrors());
    }

    public function show(ComplianceDomain $compliancedomain): View
    {
        $this->authorize('view', $compliancedomain);

        $frameworksCount = DocumentFramework::where('compliance_domain', $compliancedomain->key)->count();

        return view('compliancedomains.view', [
            'compliancedomain' => $compliancedomain,
            'frameworksCount' => $frameworksCount,
        ]);
    }

    public function edit(ComplianceDomain $compliancedomain): View
    {
        $this->authorize('update', $compliancedomain);

        return view('compliancedomains.edit', ['item' => $compliancedomain]);
    }

    public function update(Request $request, ComplianceDomain $compliancedomain): RedirectResponse
    {
        $this->authorize('update', $compliancedomain);

        $validated = $this->validatedPayload($request, $compliancedomain);

        if ($this->keyIsImmutable($compliancedomain) && $validated['key'] !== $compliancedomain->key) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['key' => trans('admin/compliancedomains/message.update.key_immutable')]);
        }

        if ($this->isDeactivationBlocked($request, $compliancedomain)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['is_active' => trans('admin/compliancedomains/message.update.deactivation_blocked')]);
        }

        $compliancedomain->fill($validated);
        $compliancedomain->is_active = $request->boolean('is_active');

        if ($compliancedomain->save()) {
            return redirect()->route('compliancedomains.index')->with('success', trans('admin/compliancedomains/message.update.success'));
        }

        return redirect()->back()->withInput()->withErrors($compliancedomain->getErrors());
    }

    public function destroy(ComplianceDomain $compliancedomain): RedirectResponse
    {
        $this->authorize('delete', $compliancedomain);

        if (! $compliancedomain->isDeletable()) {
            return redirect()->route('compliancedomains.index')->with('error', trans('admin/compliancedomains/message.delete.associated_frameworks'));
        }

        $compliancedomain->delete();

        return redirect()->route('compliancedomains.index')->with('success', trans('admin/compliancedomains/message.delete.success'));
    }

    private function validatedPayload(Request $request, ?ComplianceDomain $complianceDomain = null): array
    {
        $request->merge([
            'key' => ComplianceDomain::normalizeKey($request->input('key')),
        ]);

        return $request->validate([
            'key' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('compliance_domains', 'key')->ignore($complianceDomain?->id),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
            'sort_order' => 'nullable|integer|min:0|max:65535',
            'is_active' => 'boolean',
        ]);
    }

    private function keyIsImmutable(ComplianceDomain $complianceDomain): bool
    {
        return $complianceDomain->is_system
            || DocumentFramework::where('compliance_domain', $complianceDomain->key)->exists();
    }

    private function isDeactivationBlocked(Request $request, ComplianceDomain $complianceDomain): bool
    {
        return $complianceDomain->is_active
            && ! $request->boolean('is_active')
            && DocumentFramework::where('compliance_domain', $complianceDomain->key)->exists();
    }
}
