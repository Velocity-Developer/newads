<?php

namespace App\Http\Controllers;

use App\Models\SearchTerm;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchTermsController extends Controller
{
    public function none(Request $request)
    {
        $query = SearchTerm::query()
            ->where(function ($q) {
                $q->whereNull('check_ai')
                    ->orWhere('check_ai', 'NONE');
            })
            ->orderBy($request->get('sort_by', 'id'), $request->get('sort_order', 'desc'));

        $perPage = (int) $request->get('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $items = $query->paginate($perPage)->withQueryString();

        return Inertia::render('SearchTermsNone/Index', [
            'items' => $items,
            'filters' => [
                'sort_by' => $request->get('sort_by', 'id'),
                'sort_order' => $request->get('sort_order', 'desc'),
                'per_page' => $perPage,
            ],
        ]);
    }
}
