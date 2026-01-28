<?php

namespace App\Console\Commands;

use App\Models\CronLog;
use App\Services\Velocity\SearchTermService;
use Illuminate\Console\Command;

class FetchSearchTermsNone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-search-terms-none';

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
        //
        $log = CronLog::create([
            'name' => $this->signature,
            'type' => 'command',
            'started_at' => now(),
            'status' => 'running',
        ]);

        try {
            $searchTermService = new SearchTermService;
            $dataRes = $searchTermService->getSearchTermsNone();

            $log->update([
                'finished_at' => now(),
                'duration_ms' => now()->diffInMilliseconds($log->started_at),
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            $log->update([
                'status' => 'failed',
                'ended_at' => now(),
                'error' => $e->getMessage(),
            ]);

            return $this->error($e->getMessage());
        }
    }
}
