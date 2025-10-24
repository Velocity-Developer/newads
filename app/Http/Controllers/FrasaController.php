<?php

namespace App\Http\Controllers;

use App\Models\NewFrasaNegative;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Exports\FrasaExport;
use Maatwebsite\Excel\Facades\Excel;

class FrasaController extends Controller
{
    public function index(Request $request)
    {
        $query = NewFrasaNegative::query()
            ->with(['parentTerm'])
            ->orderBy($request->get('sort_by', 'id'), $request->get('sort_order', 'desc'));

        if ($search = $request->get('search')) {
            $query->where('frasa', 'like', "%{$search}%");
        }
        if ($googleStatus = $request->get('google_status')) {
            $query->where('status_input_google', $googleStatus);
        }
        if ($telegramNotif = $request->get('telegram_notif')) {
            $query->where('notif_telegram', $telegramNotif);
        }
        // Filter tanggal
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Export to Excel jika diminta
        if ($request->get('export') === 'excel') {
            return Excel::download(new FrasaExport($query), 'frasa_export.xlsx');
        }

        $perPage = (int) $request->get('per_page', 15);

        $frasa = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Frasa/Index', [
            'frasa' => $frasa,
            'filters' => [
                'search' => $search,
                'google_status' => $googleStatus,
                'telegram_notif' => $telegramNotif,
                'sort_by' => $request->get('sort_by', 'created_at'),
                'sort_order' => $request->get('sort_order', 'desc'),
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }
}