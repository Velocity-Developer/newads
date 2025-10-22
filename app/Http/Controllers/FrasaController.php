<?php

namespace App\Http\Controllers;

use App\Models\NewFrasaNegative;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FrasaController extends Controller
{
    public function index(Request $request)
    {
        $query = NewFrasaNegative::query()
            ->with(['parentTerm'])
            ->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_order', 'desc'));

        if ($search = $request->get('search')) {
            $query->where('frasa', 'like', "%{$search}%");
        }
        if ($googleStatus = $request->get('google_status')) {
            $query->where('status_input_google', $googleStatus);
        }
        if ($telegramNotif = $request->get('telegram_notif')) {
            $query->where('notif_telegram', $telegramNotif);
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
            ],
        ]);
    }
}