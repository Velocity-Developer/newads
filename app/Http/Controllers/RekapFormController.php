<?php

namespace App\Http\Controllers;

use App\Models\RekapForm;
use App\Services\NewVDnet\RekapFormServices;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RekapFormController extends Controller
{
    public function index(Request $request): Response
    {
        $query = RekapForm::with('kirim_konversi');

        $search = $request->get('search');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('no_whatsapp', 'like', "%{$search}%")
                    ->orWhere('gclid', 'like', "%{$search}%")
                    ->orWhere('source_id', 'like', "%{$search}%");
            });
        }

        if ($dateFrom) {
            $query->whereDate('tanggal', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('tanggal', '<=', $dateTo);
        }

        $perPage = (int) $request->get('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $rekapForms = $query
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('RekapForm/Index', [
            'rekapForms' => $rekapForms,
            'filters' => [
                'search' => $search,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
        ]);
    }

    public function show(int $id): Response
    {
        $rekapForm = RekapForm::with('kirim_konversi')->findOrFail($id);
        return Inertia::render('RekapForm/Show', [
            'rekapForm' => $rekapForm,
        ]);
    }

    public function syncVDnet(Request $request)
    {
        $service = app()->make(RekapFormServices::class);

        $payload = [
            'cek_konversi_ads' => $request->input('cek_konversi_ads', 1),
            'per_page' => $request->input('per_page', 50),
        ];
        $result = $service->get_list($payload);

        //if success
        if (isset($result['total']) && $result['total'] > 0) {
            //upsert rekap forms
            $data = collect($result['data'])
                ->filter(fn($row) => !empty($row['id']))
                ->map(function ($row) {
                    unset($row['created_at_wib']);
                    unset($row['log_konversi']);
                    unset($row['updated_at']);
                    return $row;
                })
                ->values()
                ->toArray();

            RekapForm::upsert(
                $data,
                ['id'], // anchor
                [
                    'source',
                    'source_id',
                    'nama',
                    'no_whatsapp',
                    'jenis_website',
                    'ai_result',
                    'via',
                    'utm_content',
                    'utm_medium',
                    'greeting',
                    'status',
                    'gclid',
                    'cek_konversi_ads',
                    'cek_konversi_nominal',
                    'kategori_konversi_nominal',
                    'created_at',
                ]
            );
        }

        return response()->json($result);
    }
}
