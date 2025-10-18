<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\TermsController;

Route::redirect('/', '/dashboard')->middleware('auth')->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Terms routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('terms', [TermsController::class, 'index'])->name('terms.index');
    Route::get('terms/{term}', [TermsController::class, 'show'])->name('terms.show');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
