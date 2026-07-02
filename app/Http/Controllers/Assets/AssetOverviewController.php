<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Category;
use App\Models\CustomFieldset;
use Illuminate\Contracts\View\View;

/**
 * "Panoramica Beni": a cruscotto-style overview of the assets, grouped by CATEGORIA
 * and by CAMPO (custom fieldset), each rendered as a card with the asset count and a
 * link into the filtered asset list. Respects the same company scope as the list.
 */
class AssetOverviewController extends Controller
{
    public function index(): View
    {
        $this->authorize('index', Asset::class);

        $categories = Category::where('category_type', 'asset')
            ->withCount('assets')
            ->orderByDesc('assets_count')
            ->orderBy('name')
            ->get()
            ->filter(fn (Category $c) => $c->assets_count > 0)
            ->values();

        // Asset count per custom fieldset (via the asset's model->fieldset_id).
        $fieldsetCounts = Asset::query()
            ->join('models', 'assets.model_id', '=', 'models.id')
            ->whereNotNull('models.fieldset_id')
            ->selectRaw('models.fieldset_id as fid, count(*) as c')
            ->groupBy('models.fieldset_id')
            ->pluck('c', 'fid');

        $fieldsets = CustomFieldset::whereIn('id', $fieldsetCounts->keys())
            ->orderBy('name')
            ->get()
            ->map(fn (CustomFieldset $fs) => (object) [
                'fieldset' => $fs,
                'count' => (int) ($fieldsetCounts[$fs->id] ?? 0),
            ])
            ->sortByDesc('count')
            ->values();

        return view('hardware.overview', compact('categories', 'fieldsets'));
    }
}
