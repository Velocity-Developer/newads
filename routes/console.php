<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

// Heartbeat: mencatat waktu eksekusi scheduler setiap menit
Schedule::call(function () {
    Log::channel('schedule')->info('Schedule ini dijalankan pada jam: ' . now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));
})->everyMinute();

// Scheduler definitions (migrated from App\Console\Kernel)
// Validate mode (tanpa --apply): jalan tiap menit
Schedule::command('negative-keywords:pipeline')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/negative_keywords_pipeline.log'));

// Apply mode: contoh 1 (pakai string flag --apply)
// Schedule::command('negative-keywords:pipeline --apply')
//     ->everyMinute() // atur sesuai kebutuhan: hourly(), dailyAt('02:00'), dll.
//     ->withoutOverlapping()
//     ->appendOutputTo(storage_path('logs/negative_keywords_pipeline_apply.log'));

// Apply mode: contoh 2 (pakai array opsi)
// Schedule::command('negative-keywords:pipeline', ['--apply' => true])
//     ->everyMinute()
//     ->withoutOverlapping()
//     ->appendOutputTo(storage_path('logs/negative_keywords_pipeline_apply.log'));