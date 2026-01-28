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
            $searchTermService = new SearchTermService;
            $dataRes = $searchTermService->getSearchTermsNone();
            $result = json_encode($dataRes, JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            $status = 'failed';
            $error = $e->getMessage();
        } finally {
            $log->update([
                'finished_at' => now(),
                'duration_ms' => $log->started_at->diffInMilliseconds(now(), true),
                'status' => $status,
                'error' => $error,
                'result' => $result,
            ]);
        };
    }
}
