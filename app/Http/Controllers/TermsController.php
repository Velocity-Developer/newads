<?php

namespace App\Http\Controllers;

use App\Exports\TermsExport;
use App\Models\NewTermsNegative0Click;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class TermsController extends Controller
{
    public function index(Request $request)
    {
        $query = NewTermsNegative0Click::query()
            ->withCount(['frasa as frasa_negatives_count'])
            ->orderBy($request->get('sort_by', 'id'), $request->get('sort_order', 'desc'));

        if ($search = $request->get('search')) {
            $query->where('terms', 'like', "%{$search}%");
        }
        if ($aiResult = $request->get('ai_result')) {
            $query->where('hasil_cek_ai', $aiResult);
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
            return Excel::download(new TermsExport($query), 'terms_export.xlsx');
        }

        $perPage = (int) $request->get('per_page', 15);

        $terms = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Terms/Index', [
            'terms' => $terms,
            'filters' => [
                'search' => $search,
                'ai_result' => $aiResult,
                'google_status' => $googleStatus,
                'telegram_notif' => $telegramNotif,
                'sort_by' => $request->get('sort_by', 'id'),
                'sort_order' => $request->get('sort_order', 'desc'),
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $term = NewTermsNegative0Click::findOrFail($id);

        try {
            $term->delete();
        } catch (\Throwable $e) {
            Log::error('Failed to delete term', [
                'term_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return Redirect::back()->with('error', 'Gagal menghapus data term.');
        }

        return Redirect::route('terms.index')
            ->with('success', 'Berhasil menghapus data term.');
    }
}
