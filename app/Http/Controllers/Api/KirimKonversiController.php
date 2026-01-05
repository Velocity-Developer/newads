<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\NewVDnet\RekapFormServices;

class KirimKonversiController extends Controller
{
    //get list rekap form
    public function get_list_rekap_forms(Request $request): array
    {
        $params = $request->query();
        $rekapFormServices = app()->make(RekapFormServices::class);
        return $rekapFormServices->get_list($params);
    }
}
