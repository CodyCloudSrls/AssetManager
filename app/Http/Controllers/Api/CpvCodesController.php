<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CpvCode;
use Illuminate\Http\Request;

class CpvCodesController extends Controller
{
    public function selectlist(Request $request): array
    {
        $this->authorize('view.selectlists');

        $cpvCodes = CpvCode::query()
            ->where('is_active', true);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $normalized = CpvCode::normalizeCode($search);

            $cpvCodes->where(function ($query) use ($search, $normalized) {
                $query->where('code', 'LIKE', '%'.$search.'%')
                    ->orWhere('description', 'LIKE', '%'.$search.'%');

                if ($normalized !== $search) {
                    $query->orWhere('code', 'LIKE', '%'.$normalized.'%');
                }
            });
        }

        if ($request->filled('division_code')) {
            $cpvCodes->where('division_code', str_pad((string) $request->input('division_code'), 2, '0', STR_PAD_LEFT));
        }

        $cpvCodes = $cpvCodes
            ->orderBy('code')
            ->paginate(50);

        return [
            'results' => $cpvCodes->getCollection()->map(fn (CpvCode $cpvCode) => [
                'id' => $cpvCode->code,
                'text' => $cpvCode->display_name,
            ])->values()->all(),
            'pagination' => [
                'more' => $cpvCodes->currentPage() < $cpvCodes->lastPage(),
                'per_page' => $cpvCodes->perPage(),
            ],
            'total_count' => $cpvCodes->total(),
            'page' => $cpvCodes->currentPage(),
            'page_count' => $cpvCodes->lastPage(),
        ];
    }
}
