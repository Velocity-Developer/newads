<?php

namespace App\Console\Commands;

use App\Models\CronLog;
use App\Models\IklanResponsif;
use App\Models\SearchTerm;
use App\Services\Velocity\BuatIklanResponsifService;
use Illuminate\Console\Command;

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

            // jika kosong
            if (! $searchTerms) {
                throw new \Exception('Tidak ada search term yang relevan dan belum dibuat iklan');
            }

            $service = new BuatIklanResponsifService;
            $groupName = 'ai1 - '.$searchTerms->term;
            $result = $service->send($searchTerms->term, $groupName);

            if (! empty($result['succes'])) {
                $dataRes = $result['results'] ?? [];

                IklanResponsif::create([
                    'group_iklan' => $dataRes['group_iklan'] ?? $groupName,
                    'kata_kunci' => $dataRes['kata_kunci'] ?? $searchTerms->term,
                    'search_term_id' => $searchTerms->id,
                    'nomor_group_iklan' => $dataRes['data']['ad_group_id'] ?? $dataRes['data']['ad_group_id'] ?? null,
                    'nomor_kata_kunci' => $dataRes['data']['criterion_id'] ?? $dataRes['data']['criterion_id'] ?? null,
                    'status' => 'sudah',
                ]);

                $searchTerms->update(['iklan_dibuat' => true]);
            } else {
                $searchTerms->increment('failure_count');
                throw new \Exception($result['error'] ?? 'Gagal membuat iklan responsif');
            }
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
