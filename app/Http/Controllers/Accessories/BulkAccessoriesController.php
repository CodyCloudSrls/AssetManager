<?php

namespace App\Http\Controllers\Accessories;

use App\Http\Controllers\Controller;
use App\Models\Accessory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BulkAccessoriesController extends Controller
{
    /**
     * The accessory columns that can be set in bulk.
     */
    private const BULK_FIELDS = [
        'category_id',
        'company_id',
        'location_id',
        'manufacturer_id',
        'supplier_id',
        'min_amt',
        'notes',
    ];

    public function edit(Request $request): View|RedirectResponse
    {
        $this->authorize('update', Accessory::class);

        $accessories = $this->accessoriesFromRequest($request);

        if ($accessories->isEmpty() || $request->input('bulk_actions') !== 'edit') {
            return redirect()->route('accessories.index')->with('error', trans('admin/hardware/message.update.no_assets_selected'));
        }

        return view('accessories.bulk-edit', ['accessories' => $accessories]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('update', Accessory::class);

        $accessories = $this->accessoriesFromRequest($request);

        if ($accessories->isEmpty()) {
            return redirect()->route('accessories.index')->with('error', trans('admin/hardware/message.update.no_assets_selected'));
        }

        $request->validate([
            'ids' => 'required|array',
            'category_id' => 'nullable|integer|exists:categories,id',
            'company_id' => 'nullable|integer|exists:companies,id',
            'location_id' => 'nullable|integer|exists:locations,id',
            'manufacturer_id' => 'nullable|integer|exists:manufacturers,id',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'min_amt' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:65535',
        ]);

        $updates = [];
        foreach (self::BULK_FIELDS as $field) {
            if (! $request->filled('apply_'.$field)) {
                continue;
            }
            $value = $request->input($field);
            if ($value === '') {
                $value = null;
            }
            // The category is required on an accessory; never blank it out in bulk.
            if ($field === 'category_id' && $value === null) {
                continue;
            }
            $updates[$field] = $value;
        }

        if ($updates === []) {
            return redirect()->route('accessories.index')->with('error', trans('admin/hardware/message.update.nothing_updated'));
        }

        DB::transaction(function () use ($accessories, $updates) {
            foreach ($accessories as $accessory) {
                $accessory->fill($updates);
                $accessory->save();
            }
        });

        return redirect()->route('accessories.index')->with('success', trans('admin/accessories/message.update.success'));
    }

    private function accessoriesFromRequest(Request $request)
    {
        $ids = collect($request->input('ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return collect();
        }

        return Accessory::whereIn('id', $ids)->get();
    }
}
