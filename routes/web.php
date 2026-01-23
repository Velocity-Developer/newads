<?php

use App\Http\Controllers\AdminToolsController;
use App\Http\Controllers\BlacklistWordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KirimKonversiController;
use App\Http\Controllers\RekapFormController;
use App\Http\Controllers\TermsController;
use App\Http\Controllers\SearchTermsController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard to avoid 404
Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/terms', [TermsController::class, 'index'])->name('terms.index');
    Route::get('/terms/{term}', [TermsController::class, 'show'])->name('terms.show');
    Route::delete('/terms/{id}', [TermsController::class, 'destroy'])->name('terms.destroy');

    // Frasa Negative page
    Route::get('/frasa', [\App\Http\Controllers\FrasaController::class, 'index'])->name('frasa.index');

    // Search Terms NONE page
    Route::get('/search-terms-none', [SearchTermsController::class, 'none'])->name('search-terms.none');

    // Kirim Konversi pages
    Route::get('/kirim-konversi', [KirimKonversiController::class, 'index'])->name('kirim-konversi.index');
    Route::get('/kirim-konversi/{kirimKonversi}', [KirimKonversiController::class, 'show'])->name('kirim-konversi.show');
    Route::delete('/kirim-konversi/{kirimKonversi}', [KirimKonversiController::class, 'destroy'])->name('kirim-konversi.destroy');

    // Rekap Form pages
    Route::get('/rekap-form', [RekapFormController::class, 'index'])->name('rekap-form.index');
    Route::get('/rekap-form/{id}', [RekapFormController::class, 'show'])->name('rekap-form.show');
    Route::get('/rekap-form-sync-vdnet', [RekapFormController::class, 'syncVDnet'])->name('rekap-form.sync');
});

// Restore dashboard route
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->prefix('admin-tools')->group(function () {
    Route::post('storage-link', [AdminToolsController::class, 'storageLink']);
    Route::post('storage-permissions', [AdminToolsController::class, 'storagePermissions']);
    Route::post('clear-logs', [AdminToolsController::class, 'clearLogs']);
    Route::get('check-env', [AdminToolsController::class, 'checkEnv']);
    Route::get('show-env', [AdminToolsController::class, 'showEnv']);
    Route::post('backup-env', [AdminToolsController::class, 'backupEnv']);
    Route::get('health-check', [AdminToolsController::class, 'healthCheck']);
    Route::get('debug-500', [AdminToolsController::class, 'debug500']);
    Route::get('debug-hosting', [AdminToolsController::class, 'debugHosting']);
    Route::get('disk-space', [AdminToolsController::class, 'diskSpace']);
});

Route::middleware(['auth'])->group(function () {
    // Manajemen blacklist single-word
    Route::get('/blacklist-words', [BlacklistWordController::class, 'index'])->name('blacklist.index');
    Route::post('/blacklist-words', [BlacklistWordController::class, 'store'])->name('blacklist.store');
    Route::put('/blacklist-words/{blacklistWord}', [BlacklistWordController::class, 'update'])->name('blacklist.update');
    Route::delete('/blacklist-words/{blacklistWord}', [BlacklistWordController::class, 'destroy'])->name('blacklist.destroy');
    Route::post('/blacklist-words/{blacklistWord}/toggle', [BlacklistWordController::class, 'toggle'])->name('blacklist.toggle');
    Route::post('/blacklist-words/import-local', [BlacklistWordController::class, 'importLocal'])->name('blacklist.importLocal');
    // Tambah endpoint upload manual .txt
    Route::post('/blacklist-words/import-upload', [BlacklistWordController::class, 'importUpload'])->name('blacklist.importUpload');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/kirim_konversi/get_list_rekap_forms', [KirimKonversiController::class, 'get_list_rekap_forms']);
    Route::post('/kirim_konversi/kirim_konversi_velocity', [KirimKonversiController::class, 'kirim_konversi_velocity']);
    Route::post('/kirim_konversi/kirim_konversi_dari_rekap_form', [KirimKonversiController::class, 'kirim_konversi_dari_rekap_form']);
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
