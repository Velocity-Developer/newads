<?php

namespace App\Http\Controllers;

use App\Models\SearchTerm;
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

    public function none(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $query = SearchTerm::query()
            ->where(function ($q) {
                $q->whereNull('check_ai')
                    ->orWhere('check_ai', 'NONE');
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->where('term', 'like', '%'.$search.'%');
            })
            ->orderBy($request->get('sort_by', 'id'), $request->get('sort_order', 'desc'));

        $perPage = (int) $request->get('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $items = $query->paginate($perPage)->withQueryString();

        return Inertia::render('SearchTermsNone/Index', [
            'items' => $items,
            'filters' => [
                'search' => $search,
                'sort_by' => $request->get('sort_by', 'id'),
                'sort_order' => $request->get('sort_order', 'desc'),
                'per_page' => $perPage,
            ],
        ]);
    }

    // get update search term none
    public function update_search_terms_none(Request $request)
    {
        $searchTermService = new SearchTermService;
        $dataRes = $searchTermService->getSearchTermsNone();

        return response()->json($dataRes);
    }
}
