<?php

namespace App\Http\Controllers;

use App\Models\IklanResponsif;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IklanResponsifController extends Controller
{
    public function index(Request $request)
    {
        $query = IklanResponsif::query()->with('search_term');

        if ($request->filled('q')) {
            $searchTerm = $request->input('q');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('group_iklan', 'like', '%'.$searchTerm.'%')
                    ->orWhere('kata_kunci', 'like', '%'.$searchTerm.'%')
                    ->orWhere('nomor_group_iklan', 'like', '%'.$searchTerm.'%')
                    ->orWhere('nomor_kata_kunci', 'like', '%'.$searchTerm.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Support per_page parameter
        $perPage = $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $items = $query->orderBy($request->get('sort_by', 'id'), $request->get('sort_order', 'desc'))
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('IklanResponsif/Index', [
            'items' => $items,
            'filters' => [
                'q' => $request->input('q'),
                'status' => $request->input('status'),
                'sort_by' => $request->get('sort_by', 'id'),
                'sort_order' => $request->get('sort_order', 'desc'),
            ],
        ]);
    }
}
