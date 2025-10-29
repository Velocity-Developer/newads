<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote');

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

// Diagnostic: schedule inspire every minute to verify scheduler wiring
// Schedule::command('inspire')
//     ->everyMinute()
//     ->environments(['production', 'local'])
//     ->appendOutputTo(storage_path('logs/schedule.log'));

// Menit ke-1: Fetch zero-click terms from Google Ads
// Schedule::command('negative-keywords:fetch-terms')
//     ->everyMinute()
//     ->when(function () {
//         return now()->minute % 10 === 1;
//     })
//     ->withoutOverlapping(5)
//     ->runInBackground()
//     ->environments(['production', 'local'])
//     ->appendOutputTo(storage_path('logs/schedule.log'));

// // Menit ke-2: Analyze terms with AI
// Schedule::command('negative-keywords:analyze-terms')
//     ->everyMinute()
//     ->when(function () {
//         return now()->minute % 10 === 2;
//     })
//     ->withoutOverlapping(5)
//     ->runInBackground()
//     ->environments(['production', 'local'])
//     ->appendOutputTo(storage_path('logs/schedule.log'));

// // Menit ke-3: Input negative keywords terms ke Velocity API
// Schedule::command('negative-keywords:input-velocity --source=terms --mode=validate')
//     ->everyMinute()
//     ->when(function () {
//         return now()->minute % 10 === 3;
//     })
//     ->withoutOverlapping(5)
//     ->runInBackground()
//     ->environments(['production', 'local'])
//     ->appendOutputTo(storage_path('logs/schedule.log'));

// // Menit ke-6: Process individual phrases
// Schedule::command('negative-keywords:process-phrases')
//     ->everyMinute()
//     ->when(function () {
//         return now()->minute % 10 === 6;
//     })
//     ->withoutOverlapping(5)
//     ->runInBackground()
//     ->environments(['production', 'local'])
//     ->appendOutputTo(storage_path('logs/schedule.log'));

// // Menit ke-7: Input negative keywords frasa ke Velocity API
// Schedule::command('negative-keywords:input-velocity --source=frasa --mode=validate')
//     ->everyMinute()
//     ->when(function () {
//         return now()->minute % 10 === 7;
//     })
//     ->withoutOverlapping(5)
//     ->runInBackground()
//     ->environments(['production', 'local'])
//     ->appendOutputTo(storage_path('logs/schedule.log'));



// Daily summary at 23:00
// Schedule::call(function () {
//     Log::channel('schedule')->info('[scheduler] Daily summary started');
//     $notificationService = app(\App\Services\Telegram\NotificationService::class);
//     $notificationService->sendDailySummary();
//     Log::channel('schedule')->info('[scheduler] Daily summary finished');
// })->dailyAt('23:00')
//   ->environments(['production', 'local']);

// Weekly cleanup (DISABLED per kebutuhan, dibiarkan commented)
// Schedule::call(function () {
//     Log::info('[scheduler] Weekly cleanup started');
//     \App\Models\NewTermsNegative0Click::where('created_at', '<', now()->subDays(30))
//         ->where('status_input_google', \App\Models\NewTermsNegative0Click::STATUS_BERHASIL)
//         ->delete();
        
//     \App\Models\NewFrasaNegative::where('created_at', '<', now()->subDays(30))
//         ->where('status_input_google', \App\Models\NewFrasaNegative::STATUS_BERHASIL)
//         ->delete();
//     Log::info('[scheduler] Weekly cleanup finished');
// })->weekly()->sundays()->at('02:00')
//   ->environments(['production', 'local']);

// Retry failed operations every hour
// Schedule::call(function () {
//     Log::channel('schedule')->info('[scheduler] Hourly retry reset started');
//     \App\Models\NewTermsNegative0Click::where('status_input_google', \App\Models\NewTermsNegative0Click::STATUS_GAGAL)
//         ->where('retry_count', '<', 3)
//         ->update(['status_input_google' => null]);

//     \App\Models\NewFrasaNegative::where('status_input_google', \App\Models\NewFrasaNegative::STATUS_GAGAL)
//         ->where('retry_count', '<', 3)
//         ->update(['status_input_google' => null]);

//     Log::channel('schedule')->info('[scheduler] Hourly retry reset finished');
// })->hourly()
//   ->environments(['production', 'local']);

// Ubah file output perintah menjadi harian (rotasi otomatis per-hari)
// Schedule::command('inspire')
//     ->everyMinute()
//     ->environments(['production', 'local'])
//     ->appendOutputTo(storage_path('logs/schedule-' . now()->format('Y-m-d') . '.log'));

// Schedule::command('negative-keywords:fetch-terms')
//     ->everyMinute()
//     ->when(function () { return now()->minute % 10 === 1; })
//     ->withoutOverlapping(5)
//     ->runInBackground()
//     ->environments(['production', 'local'])
//     ->appendOutputTo(storage_path('logs/schedule-' . now()->format('Y-m-d') . '.log'));

// Schedule::command('negative-keywords:analyze-terms')
//     ->everyMinute()
//     ->when(function () { return now()->minute % 10 === 2; })
//     ->withoutOverlapping(5)
//     ->runInBackground()
//     ->environments(['production', 'local'])
//     ->appendOutputTo(storage_path('logs/schedule-' . now()->format('Y-m-d') . '.log'));

// Schedule::command('negative-keywords:input-velocity --source=terms --mode=validate')
//     ->everyMinute()
//     ->when(function () { return now()->minute % 10 === 3; })
//     ->withoutOverlapping(5)
//     ->runInBackground()
//     ->environments(['production', 'local'])
//     ->appendOutputTo(storage_path('logs/schedule-' . now()->format('Y-m-d') . '.log'));

// Schedule::command('negative-keywords:process-phrases')
//     ->everyMinute()
//     ->when(function () { return now()->minute % 10 === 6; })
//     ->withoutOverlapping(5)
//     ->runInBackground()
//     ->environments(['production', 'local'])
//     ->appendOutputTo(storage_path('logs/schedule-' . now()->format('Y-m-d') . '.log'));

// Schedule::command('negative-keywords:input-velocity --source=frasa --mode=validate')
//     ->everyMinute()
//     ->when(function () { return now()->minute % 10 === 7; })
//     ->withoutOverlapping(5)
//     ->runInBackground()
//     ->environments(['production', 'local'])
//     ->appendOutputTo(storage_path('logs/schedule-' . now()->format('Y-m-d') . '.log'));