<?php

namespace App\Http\Controllers;

use App\Actions\Suppliers\DestroySupplierAction;
use App\Exceptions\ItemStillHasAccessories;
use App\Exceptions\ItemStillHasAssets;
use App\Exceptions\ItemStillHasComponents;
use App\Exceptions\ItemStillHasConsumables;
use App\Exceptions\ItemStillHasLicenses;
use App\Exceptions\ItemStillHasMaintenances;
use App\Http\Controllers\Concerns\AppliesTenantCompanyFilter;
use App\Http\Requests\ImageUploadRequest;
use App\Models\Company;
use App\Models\Supplier;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * This controller handles all actions related to Suppliers for
 * the Snipe-IT Asset Management application.
 *
 * @version    v1.0
 */
class SuppliersController extends Controller
{
    use AppliesTenantCompanyFilter;

    /**
     * Show a list of all suppliers
     *
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('view', Supplier::class);

        return view('suppliers/index');
    }

    /**
     * Supplier create.
     */
    public function create(): View
    {
        $this->authorize('create', Supplier::class);

        return view('suppliers/edit')->with('item', new Supplier);
    }

    /**
     * Supplier create form processing.
     */
    public function store(ImageUploadRequest $request): RedirectResponse
    {
        $this->authorize('create', Supplier::class);
        // Create a new supplier
        $supplier = new Supplier;
        // Save the location data
        $supplier->name = request('name');
        $supplier->tax_code = request('tax_code');
        $supplier->address = request('address');
        $supplier->address2 = request('address2');
        $supplier->city = request('city');
        $supplier->state = request('state');
        $supplier->country = request('country');
        $supplier->zip = request('zip');
        $supplier->contact = request('contact');
        $supplier->phone = request('phone');
        $supplier->fax = request('fax');
        $supplier->email = request('email');
        $supplier->nis_relevant = $request->boolean('nis_relevant');
        $supplier->nis_relevance_type = $request->input('nis_relevance_type', 'not_assessed');
        $supplier->nis_criticality = $request->input('nis_criticality', 'not_assessed');
        $supplier->nis_assessment_status = $request->input('nis_assessment_status', 'not_started');
        $supplier->nis_assessment_method = $request->input('nis_assessment_method', 'not_assessed');
        $supplier->nis_assessment_outcome = $request->input('nis_assessment_outcome', 'not_assessed');
        $supplier->nis_assessment_scope = $request->input('nis_assessment_scope');
        $supplier->nis_relevance_criteria = $request->input('nis_relevance_criteria');
        $supplier->cpv_codes = $request->input('cpv_codes');
        $supplier->nis_last_assessment_at = $request->input('nis_last_assessment_at');
        $supplier->nis_next_review_at = $request->input('nis_next_review_at');
        $supplier->tag_color = $request->input('tag_color');
        $supplier->notes = request('notes');
        $supplier->url = $supplier->addhttp(request('url'));
        $supplier->created_by = auth()->id();
        $supplier = $request->handleImages($supplier);
        [$supplier->company_id, $supplier->visibility_type] = Company::normalizeTemplateOwnership(
            $request->input('company_id'),
            $request->input('visibility_type'),
        );

        if ($supplier->save()) {
            return redirect()->route('suppliers.index')->with('success', trans('admin/suppliers/message.create.success'));
        }

        return redirect()->back()->withInput()->withErrors($supplier->getErrors());
    }

    /**
     * Supplier update.
     *
     * @param  int  $supplierId
     */
    public function edit(Supplier $supplier): View|RedirectResponse
    {
        $this->authorize('update', $supplier);

        return view('suppliers/edit')->with('item', $supplier);
    }

    /**
     * Supplier update form processing page.
     *
     * @param  int  $supplierId
     */
    public function update(ImageUploadRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->authorize('update', $supplier);
        // Save the  data
        $supplier->name = request('name');
        $supplier->tax_code = request('tax_code');
        $supplier->address = request('address');
        $supplier->address2 = request('address2');
        $supplier->city = request('city');
        $supplier->state = request('state');
        $supplier->country = request('country');
        $supplier->zip = request('zip');
        $supplier->contact = request('contact');
        $supplier->phone = request('phone');
        $supplier->fax = request('fax');
        $supplier->email = request('email');
        $supplier->url = $supplier->addhttp(request('url'));
        $supplier->nis_relevant = $request->boolean('nis_relevant');
        $supplier->nis_relevance_type = $request->input('nis_relevance_type', 'not_assessed');
        $supplier->nis_criticality = $request->input('nis_criticality', 'not_assessed');
        $supplier->nis_assessment_status = $request->input('nis_assessment_status', 'not_started');
        $supplier->nis_assessment_method = $request->input('nis_assessment_method', 'not_assessed');
        $supplier->nis_assessment_outcome = $request->input('nis_assessment_outcome', 'not_assessed');
        $supplier->nis_assessment_scope = $request->input('nis_assessment_scope');
        $supplier->nis_relevance_criteria = $request->input('nis_relevance_criteria');
        $supplier->cpv_codes = $request->input('cpv_codes');
        $supplier->nis_last_assessment_at = $request->input('nis_last_assessment_at');
        $supplier->nis_next_review_at = $request->input('nis_next_review_at');
        $supplier->tag_color = $request->input('tag_color');
        $supplier->notes = request('notes');
        $supplier = $request->handleImages($supplier);
        [$supplier->company_id, $supplier->visibility_type] = Company::normalizeTemplateOwnership(
            $request->input('company_id'),
            $request->input('visibility_type'),
        );

        if ($supplier->save()) {
            return redirect()->route('suppliers.index')->with('success', trans('admin/suppliers/message.update.success'));
        }

        return redirect()->back()->withInput()->withErrors($supplier->getErrors());
    }

    /**
     * Delete the given supplier.
     *
     * @param  int  $supplierId
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorize('delete', $supplier);
        try {
            DestroySupplierAction::run(supplier: $supplier);
        } catch (ItemStillHasAssets $e) {
            return redirect()->route('suppliers.index')->with('error', trans('general.bulk_delete_associations.assoc_assets', [
                'asset_count' => (int) $supplier->assets_count, 'item' => trans('general.supplier'),
            ]));
        } catch (ItemStillHasMaintenances $e) {
            return redirect()->route('suppliers.index')->with('error', trans('general.bulk_delete_associations.assoc_maintenances', [
                'asset_maintenances_count' => $supplier->asset_maintenances_count, 'item' => trans('general.supplier'),
            ]));
        } catch (ItemStillHasLicenses $e) {
            return redirect()->route('suppliers.index')->with('error', trans('general.bulk_delete_associations.assoc_licenses', [
                'licenses_count' => (int) $supplier->licenses_count, 'item' => trans('general.supplier'),
            ]));
        } catch (ItemStillHasAccessories $e) {
            return redirect()->route('suppliers.index')->with('error', trans('general.bulk_delete_associations.assoc_accessories', [
                'accessories_count' => (int) $supplier->accessories_count, 'item' => trans('general.supplier'),
            ]));
        } catch (ItemStillHasConsumables $e) {
            return redirect()->route('suppliers.index')->with('error', trans('general.bulk_delete_associations.assoc_consumables', [
                'consumables_count' => (int) $supplier->consumables_count, 'item' => trans('general.supplier'),
            ]));
        } catch (ItemStillHasComponents $e) {
            return redirect()->route('suppliers.index')->with('error', trans('general.bulk_delete_associations.assoc_components', [
                'components_count' => (int) $supplier->components_count, 'item' => trans('general.supplier'),
            ]));
        } catch (\Exception $e) {
            report($e);

            return redirect()->route('suppliers.index')->with('error', trans('admin/suppliers/message.delete.error'));
        }

        return redirect()->route('suppliers.index')->with('success', trans('admin/suppliers/message.delete.success'));
    }

    public function exportAcnCsv(Request $request): StreamedResponse
    {
        $this->authorize('view', Supplier::class);
        $this->disableDebugbar();

        $response = new StreamedResponse(function () use ($request) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $this->acnExportHeaders());

            $this->acnExportQuery($request)->chunkById(200, function ($suppliers) use ($handle) {
                foreach ($suppliers as $supplier) {
                    fputcsv($handle, $this->acnExportRow($supplier));
                }
            }, 'suppliers.id', 'id');

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="acn-supplier-preparation-'.date('Y-m-d-His').'.csv"',
        ]);

        return $response;
    }

    /**
     *  Get the asset information to present to the supplier view page
     *
     * @param  null  $supplierId
     *
     * @internal param int $assetId
     */
    public function show(Supplier $supplier): View|RedirectResponse
    {
        $this->authorize('view', $supplier);
        $supplier->load([
            'documentAssignments.document.type',
            'documentAssignments.document.framework',
            'documentAssignments.document.frameworkRequirements',
            'documentAssignments.issuer',
            'documentAssignments.reviewer',
        ]);

        return view('suppliers/view', compact('supplier'));
    }

    private function acnExportQuery(Request $request)
    {
        $selectedSupplierIds = $this->selectedSupplierIds($request);
        $suppliers = Supplier::query()
            ->with([
                'company',
                'documentAssignments.document.type',
                'documentAssignments.issuer',
                'documentAssignments.reviewer',
            ])
            ->withCount('assets as assets_count')
            ->withCount('licenses as licenses_count')
            ->withCount('accessories as accessories_count')
            ->withCount('components as components_count')
            ->withCount('consumables as consumables_count');

        Company::scopeCompanyables($suppliers);
        $this->applyTenantCompanyFilter($suppliers, $request, 'suppliers.company_id');

        if ($selectedSupplierIds !== []) {
            return $suppliers->whereIn('suppliers.id', $selectedSupplierIds)
                ->orderBy('suppliers.id');
        }

        if ($request->filled('filter') || $request->filled('search')) {
            $suppliers->TextSearch($request->input('filter') ?: $request->input('search'));
        }

        if ($request->filled('nis_relevant')) {
            $suppliers->where('nis_relevant', '=', $request->boolean('nis_relevant'));
        } else {
            $suppliers->where('nis_relevant', '=', true);
        }

        foreach ([
            'nis_relevance_type',
            'nis_criticality',
            'nis_assessment_status',
            'nis_assessment_method',
            'nis_assessment_outcome',
        ] as $filterField) {
            if ($request->filled($filterField)) {
                $suppliers->where($filterField, '=', $request->input($filterField));
            }
        }

        if ($request->filled('nis_review_status')) {
            if ($request->input('nis_review_status') === 'due') {
                $reviewWarningDays = $this->tenantFromRequest($request)?->documentReviewWarningDays() ?? 0;

                $suppliers->whereNotNull('nis_next_review_at')
                    ->whereDate('nis_next_review_at', '<=', now()->addDays($reviewWarningDays)->toDateString());
            }

            if ($request->input('nis_review_status') === 'missing') {
                $suppliers->whereNull('nis_next_review_at');
            }
        }

        if ($request->filled('cpv_code')) {
            $suppliers->where('cpv_codes', 'LIKE', '%'.$request->input('cpv_code').'%');
        }

        return $suppliers->orderBy('suppliers.id');
    }

    private function selectedSupplierIds(Request $request): array
    {
        $ids = $request->input('ids', []);

        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if (! is_array($ids)) {
            $ids = [$ids];
        }

        return collect($ids)
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function acnExportHeaders(): array
    {
        $headers = [
            strtolower(trans('general.id')),
            trans('general.company'),
            trans('general.name'),
            trans('admin/suppliers/table.tax_code'),
            trans('admin/suppliers/table.nis_relevant'),
            trans('admin/suppliers/table.nis_relevance_type'),
            trans('admin/suppliers/table.nis_criticality'),
            trans('admin/suppliers/table.nis_assessment_status'),
            trans('admin/suppliers/table.nis_assessment_method'),
            trans('admin/suppliers/table.nis_assessment_outcome'),
            trans('admin/suppliers/table.cpv_codes'),
            trans('admin/suppliers/table.nis_relevance_criteria'),
            trans('admin/suppliers/table.nis_assessment_scope'),
            trans('admin/suppliers/table.nis_last_assessment_at'),
            trans('admin/suppliers/table.nis_next_review_at'),
            trans('admin/suppliers/table.supplier_evidence_documents'),
        ];

        foreach (Supplier::nisEvidenceCategories() as $category) {
            $headers[] = $category['label'].' - '.trans('admin/suppliers/table.supplier_evidence_linked');
            $headers[] = $category['label'].' - '.trans('admin/suppliers/table.supplier_evidence_review_status');
        }

        return array_merge($headers, [
            trans('general.assets'),
            trans('general.licenses'),
            trans('general.accessories'),
            trans('general.components'),
            trans('general.consumables'),
            trans('admin/suppliers/table.contact'),
            trans('admin/suppliers/table.email'),
            trans('admin/suppliers/table.phone'),
            trans('general.url'),
            trans('general.notes'),
        ]);
    }

    private function acnExportRow(Supplier $supplier): array
    {
        $evidenceChecklist = $supplier->nisEvidenceChecklist()->keyBy('key');
        $row = [
            $supplier->id,
            $supplier->company?->name,
            $supplier->name,
            $supplier->tax_code,
            $supplier->nis_relevant ? trans('general.yes') : trans('general.no'),
            $supplier->nis_relevance_type_label,
            $supplier->nis_criticality_label,
            $supplier->nis_assessment_status_label,
            $supplier->nis_assessment_method_label,
            $supplier->nis_assessment_outcome_label,
            $supplier->cpv_codes,
            $supplier->nis_relevance_criteria,
            $supplier->nis_assessment_scope,
            $this->exportDate($supplier->nis_last_assessment_at),
            $this->exportDate($supplier->nis_next_review_at),
            $supplier->documentAssignments->count(),
        ];

        foreach (Supplier::nisEvidenceCategories() as $categoryKey => $category) {
            $evidenceItem = $evidenceChecklist->get($categoryKey);
            $row[] = $evidenceItem['count'] ?? 0;
            $row[] = $evidenceItem['status_label'] ?? trans('admin/suppliers/table.supplier_evidence_status_missing');
        }

        return array_merge($row, [
            (int) ($supplier->assets_count ?? 0),
            (int) ($supplier->licenses_count ?? 0),
            (int) ($supplier->accessories_count ?? 0),
            (int) ($supplier->components_count ?? 0),
            (int) ($supplier->consumables_count ?? 0),
            $supplier->contact,
            $supplier->email,
            $supplier->phone,
            $supplier->url,
            $supplier->notes,
        ]);
    }

    private function exportDate($date): ?string
    {
        return $date ? $date->format('Y-m-d') : null;
    }
}
