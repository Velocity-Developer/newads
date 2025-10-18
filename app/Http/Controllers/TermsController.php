<?php

namespace App\Http\Controllers;

use App\Models\NewTermsNegative0Click;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $terms = $query->paginate(15)->withQueryString();

        // Statistics
        $stats = [
            'total' => NewTermsNegative0Click::count(),
            'ai_positive' => NewTermsNegative0Click::where('hasil_cek_ai', 'positive')->count(),
            'ai_negative' => NewTermsNegative0Click::where('hasil_cek_ai', 'negative')->count(),
            'ai_pending' => NewTermsNegative0Click::where('hasil_cek_ai', 'pending')->count(),
            'google_success' => NewTermsNegative0Click::where('status_input_google', 'success')->count(),
            'google_failed' => NewTermsNegative0Click::where('status_input_google', 'failed')->count(),
            'google_pending' => NewTermsNegative0Click::where('status_input_google', 'pending')->count(),
            'telegram_sent' => NewTermsNegative0Click::where('notif_telegram', 'sent')->count(),
            'telegram_failed' => NewTermsNegative0Click::where('notif_telegram', 'failed')->count(),
            'telegram_pending' => NewTermsNegative0Click::where('notif_telegram', 'pending')->count(),
        ];

        return Inertia::render('Terms/Index', [
            'terms' => $terms,
            'stats' => $stats,
            'filters' => $request->only(['search', 'ai_result', 'google_status', 'telegram_notif', 'sort_by', 'sort_order']),
        ]);
    }

    public function show(NewTermsNegative0Click $term)
    {
        $term->load('frasa');
        
        return Inertia::render('Terms/Show', [
            'term' => $term,
        ]);
    }
}