<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Jalankan mode validate setiap menit (dengan output ke log)
        // $schedule->command('negative-keywords:pipeline')
        //     ->everyMinute()
        //     ->withoutOverlapping()
        //     ->appendOutputTo(storage_path('logs/negative_keywords_pipeline.log'));

        // Jalankan mode apply setiap 10 menit (dengan output ke log)
        // $schedule->command('negative-keywords:pipeline', ['--apply' => true])
        //     ->everyTenMinutes()
        //     ->withoutOverlapping()
        //     ->appendOutputTo(storage_path('logs/negative_keywords_pipeline.log'));
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