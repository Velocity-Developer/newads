<?php

namespace App\Http\Controllers;

use App\Models\NewTermsNegative0Click;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TermsExport;

class TermsController extends Controller
{
    public function index(Request $request)
    {
        $query = NewTermsNegative0Click::with('frasa');

        // Search functionality
        if ($request->filled('search')) {
            $query->where('terms', 'like', '%' . $request->search . '%');
        }

        // Filter by AI result
        if ($request->filled('ai_result')) {
            $query->where('hasil_cek_ai', $request->ai_result);
        }

        // Filter by Google Ads status
        if ($request->filled('google_status')) {
            $query->where('status_input_google', $request->google_status);
        }

        // Filter by Telegram notification status
        if ($request->filled('telegram_notif')) {
            $query->where('notif_telegram', $request->telegram_notif);
        }

        // Apply date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Handle Excel export
        if ($request->get('export') === 'excel') {
            return Excel::download(new TermsExport($query), 'terms-' . date('Y-m-d') . '.xlsx');
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $terms = $query->paginate($perPage)->withQueryString();

        // Statistics
        $stats = [
            'total' => NewTermsNegative0Click::count(),
            'ai_positive' => NewTermsNegative0Click::where('hasil_cek_ai', 'relevan')->count(),
            'ai_negative' => NewTermsNegative0Click::where('hasil_cek_ai', 'negatif')->count(),
            'ai_null' => NewTermsNegative0Click::whereNull('hasil_cek_ai')->count(),
            'google_success' => NewTermsNegative0Click::where('status_input_google', 'sukses')->count(),
            'google_failed' => NewTermsNegative0Click::where('status_input_google', 'gagal')->count(),
            'google_error' => NewTermsNegative0Click::where('status_input_google', 'error')->count(),
            'telegram_sent' => NewTermsNegative0Click::where('notif_telegram', 'sukses')->count(),
            'telegram_failed' => NewTermsNegative0Click::where('notif_telegram', 'gagal')->count(),
            'telegram_null' => NewTermsNegative0Click::whereNull('notif_telegram')->count(),
        ];

        return Inertia::render('Terms/Index', [
            'terms' => $terms,
            'stats' => $stats,
            'filters' => $request->only(['search', 'ai_result', 'google_status', 'telegram_notif', 'date_from', 'date_to', 'per_page', 'sort_by', 'sort_order']),
        ]);
    }
}