<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\KirimKonversiController;


Route::middleware(['auth'])->group(function () {
    Route::post('/kirim_konversi/get_list_rekap_forms', [KirimKonversiController::class, 'get_list_rekap_forms']);
});
