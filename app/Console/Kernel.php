<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Diagnostic: simple inspire command to verify scheduler registration
        $schedule->command('inspire')
            ->everyMinute()
            ->environments(['production', 'local']);

        // Negative Keywords Automation Schedule
        // Based on trae.md - 7-minute cycle automation
        
        // Menit ke-1: Fetch zero-click terms from Google Ads
        $schedule->command('negative-keywords:fetch-terms --limit=50')
            ->everyMinute()
            ->when(function () {
                return now()->minute % 7 === 1;
            })
            ->withoutOverlapping()
            ->runInBackground()
            ->environments(['production', 'local']);
        
        // Menit ke-2: Analyze terms with AI
        $schedule->command('negative-keywords:analyze-terms --batch-size=50')
            ->everyMinute()
            ->when(function () {
                return now()->minute % 7 === 2;
            })
            ->withoutOverlapping()
            ->runInBackground()
            ->environments(['production', 'local']);
        
        // Menit ke-3: Input negative keywords ke Velocity API
        $schedule->command('negative-keywords:input-velocity --source=both --mode=execute --batch-size=50')
            ->everyMinute()
            ->when(function () {
                return now()->minute % 7 === 3;
            })
            ->withoutOverlapping()
            ->runInBackground()
            ->environments(['production', 'local']);
        
        // Menit ke-7: Process individual phrases
        $schedule->command('negative-keywords:process-phrases --batch-size=50')
            ->everyMinute()
            ->when(function () {
                return now()->minute % 7 === 0;
            })
            ->withoutOverlapping()
            ->runInBackground()
            ->environments(['production', 'local']);
        
        // Additional maintenance tasks
        
        // Daily summary at 23:00
        $schedule->call(function () {
            $notificationService = app(\App\Services\Telegram\NotificationService::class);
            $notificationService->sendDailySummary();
        })->dailyAt('23:00')
          ->environments(['production', 'local']);
        
        // Weekly cleanup of old processed records (older than 30 days)
        $schedule->call(function () {
            \App\Models\NewTermsNegative0Click::where('created_at', '<', now()->subDays(30))
                ->where('status_input_google', \App\Models\NewTermsNegative0Click::STATUS_BERHASIL)
                ->delete();
                
            \App\Models\NewFrasaNegative::where('created_at', '<', now()->subDays(30))
                ->where('status_input_google', \App\Models\NewFrasaNegative::STATUS_BERHASIL)
                ->delete();
        })->weekly()->sundays()->at('02:00')
          ->environments(['production', 'local']);
        
        // Retry failed operations every hour
        $schedule->call(function () {
            \App\Models\NewTermsNegative0Click::where('status_input_google', \App\Models\NewTermsNegative0Click::STATUS_GAGAL)
                ->where('retry_count', '<', 3)
                ->update(['status_input_google' => null]);
                
            \App\Models\NewFrasaNegative::where('status_input_google', \App\Models\NewFrasaNegative::STATUS_GAGAL)
                ->where('retry_count', '<', 3)
                ->update(['status_input_google' => null]);
        })->hourly()
          ->environments(['production', 'local']);
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