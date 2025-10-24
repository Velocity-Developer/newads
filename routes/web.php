<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TermsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminToolsController;

// Redirect root to dashboard to avoid 404
Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/terms', [TermsController::class, 'index'])->name('terms.index');
    Route::get('/terms/{term}', [TermsController::class, 'show'])->name('terms.show');
    Route::delete('/terms/{id}', [TermsController::class, 'destroy'])->name('terms.destroy');

    // Frasa Negative page
    \App\Http\Controllers\FrasaController::class;
    Route::get('/frasa', [\App\Http\Controllers\FrasaController::class, 'index'])->name('frasa.index');
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

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
