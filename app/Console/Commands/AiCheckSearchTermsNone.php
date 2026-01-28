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
        $status = 'success';
        $error = null;
        $result = null;

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
            $result = json_encode($response, JSON_PRETTY_PRINT);

            Log::info('app:ai-check-search-terms-none ', $response);
        } catch (\Exception $e) {
            $status = 'failed';
            $error = $e->getMessage();
        } finally {
            $log->update([
                'finished_at' => now(),
                'status' => $status,
                'error' => $error,
                'result' => $result,
                'duration_ms' => $log->started_at->diffInMilliseconds(now(), true),
            ]);
        }
    }
}
