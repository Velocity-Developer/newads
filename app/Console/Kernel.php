<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Jalankan mode validate setiap 10 menit (dengan output ke log)
        $schedule->command('negative-keywords:pipeline')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/negative_keywords_pipeline.log'));

        // Jalankan mode apply tiap hari jam 00:10 (opsional, sesuaikan)
        // $schedule->command('negative-keywords:pipeline', ['--apply' => true])
        //     ->dailyAt('00:10')
        //     ->withoutOverlapping()
        //     ->appendOutputTo(storage_path('logs/negative_keywords_pipeline.log'));
        
        // Diagnostic: simple inspire command to verify scheduler registration
        // $schedule->command('inspire')
        //     ->everyMinute()
        //     ->environments(['production', 'local']);

        // Negative Keywords Automation Schedule
        // Based on trae.md - 10-minute cycle automation

        // Menit ke-1: Fetch zero-click terms from External Ads API
        // $schedule->command('negative-keywords:fetch-terms')
        //     ->everyMinute()
        //     ->when(function () {
        //         return now()->minute % 10 === 1;
        //     })
        //     ->withoutOverlapping(5)
        //     ->runInBackground()
        //     ->environments(['production', 'local']);
        
        // // Menit ke-2: Analyze terms with AI
        // $schedule->command('negative-keywords:analyze-terms')
        //     ->everyMinute()
        //     ->when(function () {
        //         return now()->minute % 10 === 2;
        //     })
        //     ->withoutOverlapping(5)
        //     ->runInBackground()
        //     ->environments(['production', 'local']);
        
        // // Menit ke-3: Input negative keywords terms ke Velocity API
        // $schedule->command('negative-keywords:input-velocity --source=terms --mode=validate')
        //     ->everyMinute()
        //     ->when(function () {
        //         return now()->minute % 10 === 3;
        //     })
        //     ->withoutOverlapping(5)
        //     ->runInBackground()
        //     ->environments(['production', 'local']);
        
        // // Menit ke-6: Process individual phrases (split-only)
        // $schedule->command('negative-keywords:process-phrases')
        //     ->everyMinute()
        //     ->when(function () {
        //         return now()->minute % 10 === 6;
        //     })
        //     ->withoutOverlapping(5)
        //     ->runInBackground()
        //     ->environments(['production', 'local']);

        // // Menit ke-7: Input negative keywords frasa ke Velocity API
        // $schedule->command('negative-keywords:input-velocity --source=frasa --mode=validate')
        //     ->everyMinute()
        //     ->when(function () {
        //         return now()->minute % 10 === 7;
        //     })
        //     ->withoutOverlapping(5)
        //     ->runInBackground()
        //     ->environments(['production', 'local']);
        
        // // Additional maintenance tasks
        
        // // Daily summary at 23:00
        // $schedule->call(function () {
        //     $notificationService = app(\App\Services\Telegram\NotificationService::class);
        //     $notificationService->sendDailySummary();
        // })->dailyAt('23:00')
        //   ->environments(['production', 'local']);
        
        // Weekly cleanup of old processed records (older than 30 days)
        // $schedule->call(function () {
        //     \App\Models\NewTermsNegative0Click::where('created_at', '<', now()->subDays(30))
        //         ->where('status_input_google', \App\Models\NewTermsNegative0Click::STATUS_BERHASIL)
        //         ->delete();
                
        //     \App\Models\NewFrasaNegative::where('created_at', '<', now()->subDays(30))
        //         ->where('status_input_google', \App\Models\NewFrasaNegative::STATUS_BERHASIL)
        //         ->delete();
        // })->weekly()->sundays()->at('02:00')
        //   ->environments(['production', 'local']);
        
        // Retry failed operations every hour
        // $schedule->call(function () {
        //     \App\Models\NewTermsNegative0Click::where('status_input_google', \App\Models\NewTermsNegative0Click::STATUS_GAGAL)
        //         ->where('retry_count', '<', 3)
        //         ->update(['status_input_google' => null]);
                
        //     \App\Models\NewFrasaNegative::where('status_input_google', \App\Models\NewFrasaNegative::STATUS_GAGAL)
        //         ->where('retry_count', '<', 3)
        //         ->update(['status_input_google' => null]);
        // })->hourly()
        //   ->environments(['production', 'local']);
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}