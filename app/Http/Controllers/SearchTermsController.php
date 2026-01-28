<?php

namespace App\Http\Controllers;

use App\Models\SearchTerm;
use App\Services\SearchTermsAds\AnalyticServices;
use App\Services\SearchTermsAds\CheckAiServices as SearchTermsAdsCheckAiServices;
use App\Services\Velocity\SearchTermService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SearchTermsController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'term' => ['required', 'string', 'max:255', 'unique:search_terms,term'],
        ]);

        SearchTerm::create([
            'term' => $request->term,
            'waktu' => now(),
            'source' => 'manual',
        ]);

        return redirect()->back()->with('success', 'Search term berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $term = SearchTerm::findOrFail($id);

        $request->validate([
            'term' => ['required', 'string', 'max:255', Rule::unique('search_terms', 'term')->ignore($term->id)],
        ]);

        $term->update([
            'term' => $request->term,
        ]);

        return redirect()->back()->with('success', 'Search term berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $term = SearchTerm::findOrFail($id);
        $term->delete();

        return redirect()->back()->with('success', 'Search term berhasil dihapus.');
    }

    public function none(Request $request, AnalyticServices $analyticServices)
    {
        $search = trim((string) $request->get('search', ''));

        $query = SearchTerm::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where('term', 'like', '%' . $search . '%');
            })
            ->orderBy($request->get('sort_by', 'id'), $request->get('sort_order', 'desc'));

        $perPage = (int) $request->get('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $items = $query->paginate($perPage)->withQueryString();

        $analytics = [
            'total_by_check_ai' => $analyticServices->getTotalByCheckAi(),
            'total_by_iklan_dibuat' => $analyticServices->getTotalByIklanDibuat(),
            'total_new_data_by_date' => $analyticServices->getTotalNewDataByDate(now()->subDays(30)->format('Y-m-d'), now()->format('Y-m-d')),
        ];

        return Inertia::render('SearchTermsNone/Index', [
            'items' => $items,
            'filters' => [
                'search' => $search,
                'sort_by' => $request->get('sort_by', 'id'),
                'sort_order' => $request->get('sort_order', 'desc'),
                'per_page' => $perPage,
            ],
            'analytics' => $analytics,
        ]);
    }

    // check ai
    public function checkAi(Request $request)
    {
        $request->validate([
            'terms' => ['required', 'array'],
            'terms.*' => ['string'],
        ]);

        if (!$request->terms || empty($request->terms)) {
            return response()->json([
                'error' => 'kosong'
            ]);
        }

        try {
            $n = new SearchTermsAdsCheckAiServices;
            $result = $n->check_search_terms_none($request->terms);

            return redirect()->back()->with('success', 'Data berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // get update search term none
    public function update_search_terms_none(Request $request)
    {
        $searchTermService = new SearchTermService;
        $dataRes = $searchTermService->getSearchTermsNone();

        return response()->json($dataRes);
    }
}
