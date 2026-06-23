<?php

namespace App\Http\Controllers;

use App\Actions\Suppliers\DestroySupplierAction;
use App\Exceptions\ItemStillHasAccessories;
use App\Exceptions\ItemStillHasAssets;
use App\Exceptions\ItemStillHasComponents;
use App\Exceptions\ItemStillHasConsumables;
use App\Exceptions\ItemStillHasLicenses;
use App\Exceptions\ItemStillHasMaintenances;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BulkSuppliersController extends Controller
{
    /**
     * Entry point for the bulk-actions dropdown. The form always POSTs here; we
     * branch on the selected action ('delete' reuses destroy(), 'edit' shows the
     * group-edit form).
     */
    public function edit(Request $request)
    {
        if (! $request->filled('ids')) {
            return redirect()->route('suppliers.index')->with('error', trans('admin/suppliers/message.no_suppliers_selected'));
        }

        if ($request->input('bulk_actions') === 'delete') {
            return $this->destroy($request);
        }

        $this->authorize('update', Supplier::class);

        $suppliers = Supplier::whereIn('id', (array) $request->input('ids'))->get();

        if ($suppliers->isEmpty()) {
            return redirect()->route('suppliers.index')->with('error', trans('admin/suppliers/message.no_suppliers_selected'));
        }

        return view('suppliers.bulk-edit', [
            'suppliers' => $suppliers,
            'ids' => $suppliers->pluck('id')->all(),
        ]);
    }

    /**
     * Apply the group edit. Only the fields whose "apply_*" checkbox is set are
     * written (everything else is left untouched on every selected supplier).
     */
    public function update(Request $request)
    {
        $this->authorize('update', Supplier::class);

        $suppliers = Supplier::whereIn('id', (array) $request->input('ids'))->get();

        if ($suppliers->isEmpty()) {
            return redirect()->route('suppliers.index')->with('error', trans('admin/suppliers/message.no_suppliers_selected'));
        }

        $validator = Validator::make($request->all(), [
            'nis_criticality' => ['nullable', Rule::in(array_keys(Supplier::nisCriticalityOptions()))],
            'nis_relevance_type' => ['nullable', Rule::in(array_keys(Supplier::nisRelevanceTypeOptions()))],
            'nis_assessment_status' => ['nullable', Rule::in(array_keys(Supplier::nisAssessmentStatusOptions()))],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator);
        }

        $updates = [];

        if ($request->boolean('apply_nis_relevant')) {
            $updates['nis_relevant'] = $request->boolean('nis_relevant_value');
        }
        if ($request->boolean('apply_nis_criticality') && $request->filled('nis_criticality')) {
            $updates['nis_criticality'] = $request->input('nis_criticality');
        }
        if ($request->boolean('apply_nis_relevance_type') && $request->filled('nis_relevance_type')) {
            $updates['nis_relevance_type'] = $request->input('nis_relevance_type');
        }
        if ($request->boolean('apply_nis_assessment_status') && $request->filled('nis_assessment_status')) {
            $updates['nis_assessment_status'] = $request->input('nis_assessment_status');
        }
        if ($request->boolean('apply_notes')) {
            $updates['notes'] = $request->input('notes');
        }

        if ($updates === []) {
            return redirect()->route('suppliers.index')->with('warning', trans('admin/hardware/message.update.nothing_updated'));
        }

        DB::transaction(function () use ($suppliers, $updates) {
            foreach ($suppliers as $supplier) {
                $supplier->fill($updates);
                $supplier->save();
            }
        });

        return redirect()->route('suppliers.index')->with('success', trans('admin/suppliers/message.update.success'));
    }

    public function destroy(Request $request)
    {
        $this->authorize('delete', Supplier::class);

        $errors = [];
        $success_count = 0;

        foreach ($request->ids as $id) {
            $supplier = Supplier::find($id);
            if (is_null($supplier)) {
                $errors[] = trans('admin/suppliers/message.delete.not_found');

                continue;
            }
            try {
                DestroySupplierAction::run(supplier: $supplier);
            } catch (ItemStillHasAssets $e) {
                $errors[] = trans('general.bulk_delete_associations.assoc_assets', ['asset_count' => (int) $supplier->assets_count, 'item' => trans('general.supplier'), 'item_name' => $supplier->name]);
            } catch (ItemStillHasMaintenances $e) {
                $errors[] = trans('general.bulk_delete_associations.assoc_maintenances', ['asset_maintenances_count' => $supplier->asset_maintenances_count, 'item' => trans('general.supplier'), 'item_name' => $supplier->name]);
            } catch (ItemStillHasLicenses $e) {
                $errors[] = trans('general.bulk_delete_associations.assoc_licenses', ['licenses_count' => (int) $supplier->licenses_count, 'item' => trans('general.supplier'), 'item_name' => $supplier->name]);
            } catch (ItemStillHasAccessories $e) {
                $errors[] = trans('general.bulk_delete_associations.assoc_accessories', ['accessories_count' => (int) $supplier->accessories_count, 'item' => trans('general.supplier'), 'item_name' => $supplier->name]);
            } catch (ItemStillHasConsumables $e) {
                $errors[] = trans('general.bulk_delete_associations.assoc_consumables', ['consumables_count' => (int) $supplier->consumables_count, 'item' => trans('general.supplier'), 'item_name' => $supplier->name]);
            } catch (ItemStillHasComponents $e) {
                $errors[] = trans('general.bulk_delete_associations.assoc_components', ['components_count' => (int) $supplier->components_count, 'item' => trans('general.supplier'), 'item_name' => $supplier->name]);
            } catch (\Exception $e) {
                report($e);
                $errors[] = trans('general.something_went_wrong');
            }
        }
        if (count($errors) > 0) {
            if ($success_count > 0) {
                return redirect()->route('suppliers.index')->with('success', trans_choice('admin/suppliers/message.delete.partial_success', $success_count, ['count' => $success_count]))->with('multi_error_messages', $errors);
            }

            return redirect()->route('suppliers.index')->with('multi_error_messages', $errors);
        } else {
            return redirect()->route('suppliers.index')->with('success', trans('admin/suppliers/message.delete.bulk_success'));
        }
    }
}
