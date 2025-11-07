<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

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
// Validate mode (tanpa --apply): jalan tiap 5 menit
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

Artisan::command('metrics:api-requests {--date=} {--component=}', function () {
    $date = $this->option('date') ?: now()->toDateString();
    $component = $this->option('component');

    $logPath = storage_path('logs/laravel.log');
    if (!file_exists($logPath)) {
        $this->error('Log file not found: ' . $logPath);
        return 1;
    }

    $lines = @file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        $this->error('Unable to read log file.');
        return 1;
    }

    $totalRequests = 0;
    $totalTerms = 0;
    $byComponent = [];

    foreach ($lines as $line) {
        $pos = strpos($line, 'METRIC:API_REQUEST ');
        if ($pos === false) continue;

        $jsonPart = substr($line, $pos + strlen('METRIC:API_REQUEST '));
        $data = json_decode($jsonPart, true);
        if (!is_array($data)) continue;

        if (($data['date'] ?? null) !== $date) continue;
        if ($component && ($data['component'] ?? null) !== $component) continue;

        $req = (int)($data['request_count'] ?? 1);
        $cnt = (int)($data['terms_count'] ?? 0);
        $comp = (string)($data['component'] ?? 'unknown');

        $totalRequests += $req;
        $totalTerms += $cnt;
        $byComponent[$comp] = ($byComponent[$comp] ?? 0) + $req;
    }

    $this->info("📊 API Requests on {$date}");
    $this->line("Total Requests: {$totalRequests}");
    $this->line("Total Terms Sent: {$totalTerms}");
    if (!empty($byComponent)) {
        $this->line("By Component:");
        foreach ($byComponent as $comp => $count) {
            $this->line("- {$comp}: {$count}");
        }
    }

    return 0;
})->describe('Hitung jumlah request API per hari dari log METRIC:API_REQUEST');