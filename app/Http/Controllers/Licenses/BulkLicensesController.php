<?php

namespace App\Http\Controllers\Licenses;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\License;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Bulk edit / bulk delete for licenses. Same shape as BulkCustomerContractsController /
 * BulkSuppliersController: a single POST entry point (edit) branches on the bulk_actions
 * dropdown, and only fields whose apply_* checkbox is ticked are written.
 *
 * License-specific care:
 *  - `seats` is NEVER bulk-editable: License::boot()'s updating hook runs adjustSeatCount()
 *    and would create/delete license_seats rows en masse.
 *  - company_id is NOT written when the operator leaves the select blank: Company::
 *    getIdForCurrentUser(null) does NOT return null for a company-scoped admin (it falls back
 *    to their own company), which would silently move every selected license into their
 *    company. Blank company = "leave unchanged".
 *  - a license with assigned seats is NEVER deleted (mirrors the single-delete guard).
 */
class BulkLicensesController extends Controller
{
    /** License columns that may be set in bulk. Deliberately excludes seats / name / serial. */
    private const BULK_FIELDS = [
        'company_id',
        'category_id',
        'manufacturer_id',
        'supplier_id',
        'depreciation_id',
        'license_name',
        'license_email',
        'order_number',
        'purchase_order',
        'purchase_cost',
        'purchase_date',
        'expiration_date',
        'termination_date',
        'min_amt',
        'maintained',
        'reassignable',
        'notes',
    ];

    /** tinyint flags: applied as 0/1, never blanked. */
    private const BOOLEAN_FIELDS = ['maintained', 'reassignable'];

    /**
     * Entry point for the bulk-actions dropdown; the form always POSTs here and we branch on
     * the selected action.
     */
    public function edit(Request $request): View|RedirectResponse
    {
        $licenses = $this->licensesFromRequest($request);

        if ($licenses->isEmpty()) {
            return redirect()->route('licenses.index')
                ->with('error', trans('admin/licenses/bulk.no_licenses_selected'));
        }

        if ($request->input('bulk_actions') === 'delete') {
            $this->authorize('delete', License::class);

            return view('licenses.bulk-delete', [
                'licenses' => $licenses,
                'valid_count' => $licenses->filter(fn (License $l) => (int) $l->assigned_seats_count === 0)->count(),
            ]);
        }

        $this->authorize('update', License::class);

        return view('licenses.bulk-edit', ['licenses' => $licenses]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('update', License::class);

        $licenses = $this->licensesFromRequest($request);

        if ($licenses->isEmpty()) {
            return redirect()->route('licenses.index')
                ->with('error', trans('admin/licenses/bulk.no_licenses_selected'));
        }

        // Rules mirror License::$rules, relaxed to nullable because a field is only written
        // when its apply_* checkbox is set.
        $request->validate([
            'ids' => 'required|array',
            'company_id' => 'nullable|integer|exists:companies,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'manufacturer_id' => 'nullable|integer|exists:manufacturers,id',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'depreciation_id' => 'nullable|integer|exists:depreciations,id',
            'license_name' => 'nullable|string|max:100',
            'license_email' => 'nullable|email|max:120',
            'order_number' => 'nullable|string|max:50',
            'purchase_order' => 'nullable|string|max:191',
            'purchase_cost' => 'nullable|numeric|gte:0',
            'purchase_date' => 'nullable|date_format:Y-m-d|max:10',
            'expiration_date' => 'nullable|date_format:Y-m-d|max:10',
            'termination_date' => 'nullable|date_format:Y-m-d|max:10',
            'min_amt' => 'nullable|numeric|gte:0',
            'maintained' => 'nullable|boolean',
            'reassignable' => 'nullable|boolean',
            'notes' => 'nullable|string|max:65535',
        ]);

        $updates = [];
        foreach (self::BULK_FIELDS as $field) {
            if (! $request->filled('apply_'.$field)) {
                continue;
            }

            if (in_array($field, self::BOOLEAN_FIELDS, true)) {
                $updates[$field] = $request->boolean($field) ? 1 : 0;
                continue;
            }

            $value = $request->input($field);
            if ($value === '') {
                $value = null;
            }

            // Blank company would re-home the license into the operator's company — skip it.
            if ($field === 'company_id') {
                if ($value === null) {
                    continue;
                }
                $value = Company::getIdForCurrentUser($value);
            }

            $updates[$field] = $value;
        }

        if ($updates === []) {
            return redirect()->route('licenses.index')
                ->with('warning', trans('admin/licenses/bulk.nothing_updated'));
        }

        $errors = 0;
        DB::transaction(function () use ($licenses, $updates, &$errors) {
            foreach ($licenses as $license) {
                $license->fill($updates);
                // save() can return false (ValidatingTrait) — collect failures instead of
                // assuming success.
                if (! $license->save()) {
                    $errors++;
                }
            }
        });

        if ($errors > 0) {
            return redirect()->route('licenses.index')
                ->with('warning', trans('admin/licenses/bulk.partial_update', ['count' => $errors]));
        }

        return redirect()->route('licenses.index')
            ->with('success', trans('admin/licenses/bulk.update_success'));
    }

    /**
     * Actually delete the confirmed licenses. A license with assigned seats is skipped, exactly
     * like the single-delete path — no orphaned/checked-out seats are destroyed.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->authorize('delete', License::class);

        $licenses = $this->licensesFromRequest($request);

        if ($licenses->isEmpty()) {
            return redirect()->route('licenses.index')
                ->with('error', trans('admin/licenses/bulk.no_licenses_selected'));
        }

        $deleted = 0;
        $skipped = 0;
        DB::transaction(function () use ($licenses, &$deleted, &$skipped) {
            foreach ($licenses as $license) {
                if ((int) $license->assigned_seats_count !== 0) {
                    $skipped++;
                    continue;
                }
                DB::table('license_seats')
                    ->where('license_id', $license->id)
                    ->update(['assigned_to' => null, 'asset_id' => null]);
                $license->licenseseats()->delete();
                $license->delete();
                $deleted++;
            }
        });

        if ($deleted === 0) {
            return redirect()->route('licenses.index')
                ->with('error', trans('admin/licenses/bulk.all_skipped'));
        }
        if ($skipped > 0) {
            return redirect()->route('licenses.index')
                ->with('warning', trans('admin/licenses/bulk.delete_partial', ['deleted' => $deleted, 'skipped' => $skipped]));
        }

        return redirect()->route('licenses.index')
            ->with('success', trans('admin/licenses/bulk.delete_success', ['count' => $deleted]));
    }

    /**
     * Load the selected licenses. The CompanyableScope global scope keeps this tenant-safe:
     * ids forged from another company simply don't load.
     *
     * @return Collection<int, License>
     */
    private function licensesFromRequest(Request $request): Collection
    {
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));
        if ($ids === []) {
            return collect();
        }

        return License::whereIn('id', $ids)->get();
    }
}
