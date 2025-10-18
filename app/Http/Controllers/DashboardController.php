<?php

namespace App\Http\Controllers;

use App\Models\NewTermsNegative0Click;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistics for dashboard
        $stats = [
            'total' => NewTermsNegative0Click::count(),
            'ai_relevan' => NewTermsNegative0Click::where('hasil_cek_ai', 'relevan')->count(),
            'ai_negative' => NewTermsNegative0Click::where('hasil_cek_ai', 'negatif')->count(),
            'ai_null' => NewTermsNegative0Click::whereNull('hasil_cek_ai')->count(),
            'google_sukses' => NewTermsNegative0Click::where('status_input_google', 'sukses')->count(),
            'google_gagal' => NewTermsNegative0Click::where('status_input_google', 'gagal')->count(),
            'google_error' => NewTermsNegative0Click::where('status_input_google', 'error')->count(),
            'google_null' => NewTermsNegative0Click::whereNull('status_input_google')->count(),
            'telegram_sukses' => NewTermsNegative0Click::where('notif_telegram', 'sukses')->count(),
            'telegram_gagal' => NewTermsNegative0Click::where('notif_telegram', 'gagal')->count(),
            'telegram_null' => NewTermsNegative0Click::whereNull('notif_telegram')->count(),
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
        ]);
    }
}