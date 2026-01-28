<?php

namespace App\Console\Commands;

use App\Models\CronLog;
use App\Services\SearchTermsAds\CheckAiServices;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AiCheckSearchTermsNone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:ai-check-search-terms-none';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $log = CronLog::create([
            'name' => $this->signature,
            'type' => 'command',
            'started_at' => now(),
            'status' => 'running',
        ]);

        try {
            // CheckAiServices
            $checkAiServices = new CheckAiServices;
            $response = $checkAiServices->check_search_terms_none();

            Log::info('app:ai-check-search-terms-none ', $response);

            $log->update([
                'finished_at' => now(),
                'duration_ms' => now()->diffInMilliseconds($log->started_at),
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            $log->update([
                'finished_at' => now(),
                'status' => 'failed',
                'error' => $this->error($e->getMessage()),
            ]);
        }
    }
}
