<?php

namespace App\Http\Controllers;

use App\Models\KirimKonversi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
use App\Services\NewVDnet\RekapFormServices;
use App\Services\Velocity\KirimKonversiService;

class KirimKonversiController extends Controller
{
    public function index(Request $request)
    {
        $query = KirimKonversi::with('rekap_form')
            ->orderBy($request->get('sort_by', 'id'), $request->get('sort_order', 'desc'));

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('gclid', 'like', "%{$search}%")
                    ->orWhere('jobid', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($source = $request->get('source')) {
            $query->where('source', $source);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $perPage = (int) $request->get('per_page', 15);

        $kirimKonversis = $query->paginate($perPage)->withQueryString();

        return Inertia::render('KirimKonversi/Index', [
            'kirimKonversis' => $kirimKonversis,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'source' => $source,
                'sort_by' => $request->get('sort_by', 'id'),
                'sort_order' => $request->get('sort_order', 'desc'),
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function show(Request $request, int $id)
    {
        $kirimKonversi = KirimKonversi::with('rekap_form')->findOrFail($id);

        return Inertia::render('KirimKonversi/Show', [
            'kirimKonversi' => $kirimKonversi,
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $kirimKonversi = KirimKonversi::findOrFail($id);

        try {
            $kirimKonversi->delete();
        } catch (\Throwable $e) {
            Log::error('Failed to delete kirim konversi', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);
            return Redirect::back()->with('error', 'Gagal menghapus data kirim konversi.');
        }

        return Redirect::route('kirim-konversi.index')
            ->with('success', 'Berhasil menghapus data kirim konversi.');
    }

    //get list rekap form
    public function get_list_rekap_forms(Request $request)
    {
        $params = $request->query();
        $rekapFormServices = app()->make(RekapFormServices::class);
        $rekapForms = $rekapFormServices->get_list($params);
        return response()->json($rekapForms);
    }

    //kirim konversi ke Velocity Ads
    public function kirim_konversi_velocity(Request $request)
    {
        $kirimKonversiService = new KirimKonversiService();

        $response = $kirimKonversiService->kirimKonversi(
            $request->input('action'),
            $request->input('gclid'),
            $request->input('conversion_time'),
            null,
            7438121184
        );

        return response()->json($response);
    }

    //kirim konversi dari rekap form ke Velocity Ads
    public function kirim_konversi_dari_rekap_form(Request $request)
    {
        $kirimKonversiService = new KirimKonversiService();

        $response = $kirimKonversiService->kirimKonversiDariRekapForm(
            $request->input('rekapform')
        );

        return response()->json($response);
    }
}
