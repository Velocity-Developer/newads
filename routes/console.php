<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

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

// buat iklan responsif
// Schedule::command('app:buat-iklan-responsif')
//     ->everyTwoMinutes();
