<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

// Heartbeat: mencatat waktu eksekusi scheduler setiap menit
Schedule::call(function () {
    // Log::channel('schedule')->info('Schedule ini dijalankan pada jam: ' . now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));
    // Log::channel('telegram')->info('Schedule ini dijalankan pada jam: ' . now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));
    // Log::channel('schedule')->info('Versi Laravel: ' . app()->version());
    // Log::channel('telegram')->info('Versi Laravel: ' . app()->version());
    // Log::channel('schedule')->info('Versi PHP: ' . PHP_VERSION);
    // Log::channel('telegram')->info('Versi PHP: ' . PHP_VERSION);
})->everyMinute();

// Scheduler definitions (migrated from App\Console\Kernel)
// Validate mode (tanpa --apply): jalan tiap 10 menit
// Schedule::command('negative-keywords:pipeline')
//     ->everyTenMinutes()
//     ->withoutOverlapping()
//     ->appendOutputTo(storage_path('logs/negative_keywords_pipeline.log'));

// Apply mode: contoh 1 (pakai string flag --apply)
// Schedule::command('negative-keywords:pipeline --apply')
//     ->everyTenMinutes() // atur sesuai kebutuhan: hourly(), dailyAt('02:00'), dll.
//     ->withoutOverlapping()
//     ->appendOutputTo(storage_path('logs/negative_keywords_pipeline_apply.log'));

Schedule::command('kirim-konversi:sync-vdnet')
    ->everyTwoMinutes();

// Fetch search terms none
// Schedule::command('app:fetch-search-terms-none')
//     ->cron('6-59/5 * * * *')
//     ->withoutOverlapping()
//     ->runInBackground();

// check AI Search Term
// Schedule::command('app:ai-check-search-terms-none')
//     ->cron('7-59/5 * * * *')
//     ->withoutOverlapping()
//     ->runInBackground();

//buat iklan responsif
// Schedule::command('app:buat-iklan-responsif')
//     ->everyTwoMinutes();
