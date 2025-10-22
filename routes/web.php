<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TermsController;
use App\Http\Controllers\DashboardController;

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

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
