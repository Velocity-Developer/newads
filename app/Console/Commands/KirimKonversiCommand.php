<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\NewVDnet\RekapFormServices;
use App\Services\Velocity\KirimKonversiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
        // jika setting kirim_konversi_active = 0, maka tidak perlu dijalankan
        $active = Setting::get('schedule_active_kirim_konversi_sync_vdnet', 1);
        if (! $active || $active == 0) {
            Log::info('[CRON] kirim-konversi:sync-vdnet SKIP, SETTING NOT ACTIVE');

            return self::SUCCESS;
        }

        // Log::info('[CRON] kirim-konversi:sync-vdnet START');

        // catat last run time
        Setting::set('schedule_last_run_kirim_konversi_sync_vdnet', now());

        try {
            // get rekap form
            $rekapFormServices = app()->make(RekapFormServices::class);
            $rekapForms = $rekapFormServices->get_list([
                'per_page' => 2,
            ]);

            // jika rekap form ada, kirim konversi
            if ($rekapForms['data'] && $rekapForms['total'] > 0) {

                // loop kirim konversi
                foreach ($rekapForms['data'] as $rekapForm) {

                    try {
                        $kirimKonversiService = new KirimKonversiService;
                        $kirimKonversiService->kirimKonversiDariRekapForm($rekapForm);
                    } catch (\Exception $e) {
                        Log::error('[CRON] kirim-konversi:sync-vdnet FAILED', [
                            'message' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            } else {
                // Log::info('[CRON] kirim-konversi:sync-vdnet NO REKAP FORM');
            }
        } catch (\Exception $e) {
            Log::error('[CRON] kirim-konversi:sync-vdnet FAILED', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }

        try {

            // get rekap form
            $rekapFormServices = app()->make(RekapFormServices::class);
            $rekapForms = $rekapFormServices->get_list_kategori_nominal([
                'per_page' => 1,
            ]);
            // jika rekap form ada, kirim konversi
            if ($rekapForms['data'] && $rekapForms['total'] > 0) {

                // loop kirim konversi
                foreach ($rekapForms['data'] as $rekapForm) {

                    try {
                        $kirimKonversiService = new KirimKonversiService;
                        $kirimKonversiService->kirimKonversiDariRekapFormNominal($rekapForm);
                    } catch (\Exception $e) {
                        Log::error('[CRON] kirim-konversi:sync-vdnet nominal FAILED', [
                            'message' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            } else {
                // Log::info('[CRON] kirim-konversi:sync-vdnet nominal NO REKAP FORM');
            }
        } catch (\Exception $e) {
            Log::error('[CRON] kirim-konversi:sync-vdnet nominal FAILED', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }
}
