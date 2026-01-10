<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\NewVDnet\RekapFormServices;
use App\Services\Velocity\KirimKonversiService;
use App\Models\Setting;

class KirimKonversiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kirim-konversi:sync-vdnet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command Sinkronisasi Kirim Konversi dari data rekap form di vdnet';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //jika setting kirim_konversi_active = 0, maka tidak perlu dijalankan
        $active = Setting::get('schedule_active_kirim_konversi_sync_vdnet', 1);
        if (!$active || $active == 0) {
            Log::info('[CRON] kirim-konversi:sync-vdnet SKIP, SETTING NOT ACTIVE');
            return self::SUCCESS;
        }

        //catat last run time
        Setting::set('schedule_last_run_kirim_konversi_sync_vdnet', now());

        try {
            //get rekap form
            $rekapFormServices = app()->make(RekapFormServices::class);
            $rekapForms = $rekapFormServices->get_list([
                'per_page' => 1,
            ]);

            //jika rekap form ada, kirim konversi
            if ($rekapForms['data'] && $rekapForms['total'] > 0) {

                $kirimKonversiService = new KirimKonversiService();

                $kirimKonversiService->kirimKonversiDariRekapForm($rekapForms['data'][0]);
            } else {
                Log::info('[CRON] kirim-konversi:sync-vdnet NO REKAP FORM');
            }
        } catch (\Exception $e) {
            Log::error('[CRON] kirim-konversi:sync-vdnet FAILED', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }
}
