<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

// Heartbeat: mencatat waktu eksekusi scheduler setiap menit
Schedule::call(function () {
    Log::channel('schedule')->info('Schedule ini dijalankan pada jam: ' . now()->format('Y-m-d H:i:s'));
})->everyMinute();

// Scheduler definitions (migrated from App\Console\Kernel)
// Jalankan mode validate setiap 10 menit (dengan output ke log)
Schedule::command('negative-keywords:pipeline')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/negative_keywords_pipeline.log'));

// Jalankan mode apply setiap 10 menit (dengan output ke log)
// Schedule::command('negative-keywords:pipeline', ['--apply' => true])
//     ->everyTenMinutes()
//     ->withoutOverlapping()
//     ->appendOutputTo(storage_path('logs/negative_keywords_pipeline.log'));