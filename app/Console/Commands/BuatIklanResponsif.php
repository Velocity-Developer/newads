<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SearchTerm;
use App\Models\CronLog;
use App\Services\Velocity\BuatIklanResponsifService;

class BuatIklanResponsif extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:buat-iklan-responsif';

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
            // dapatkan 1 search term dengan iklan_dibuat = false dan failure_count < 3
            $searchTerms = SearchTerm::where('iklan_dibuat', false)
                ->where('check_ai', 'RELEVAN')
                ->where('failure_count', '<', 3)->first();

            //jika kosong
            if (!$searchTerms) {
                throw new \Exception('Tidak ada search term yang relevan dan belum dibuat iklan');
            }

            $service = new BuatIklanResponsifService;
            $result = $service->send($searchTerms->term, 'ai1 - ' . $searchTerms);

            // $result = json_encode($searchTerms, JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            $status = 'failed';
            $error = $e->getMessage();
        } finally {
            $log->update([
                'finished_at'   => now(),
                'status'        => $status,
                'error'         => $error,
                'result'        => $result,
                'duration_ms'   => $log->started_at->diffInMilliseconds(now(), true),
            ]);
        }
    }
}
