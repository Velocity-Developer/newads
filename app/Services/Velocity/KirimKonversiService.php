<?php

namespace App\Services\Velocity;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\KirimKonversi;
use App\Services\NewVDnet\RekapFormServices;

class KirimKonversiService
{

    private $api_url = 'https://api.velocitydeveloper.com/api/v1/kirim-konversi.php';
    private $secret_key;
    private $time;

    //constructor
    public function __construct()
    {
        $this->secret_key = config('integrations.velocity_ads.api_secret_key');
        $this->time = time();
    }

    // Metode untuk mengirim konversi
    public function kirimKonversi($action, $gclid = null, $conversion_time = null, $datarekapform = null)
    {
        //jika action check_gclid, conversion_time dan gclid harus diisi
        if ($action == 'check_gclid' && (empty($conversion_time) || empty($gclid))) {
            throw new \Exception('Conversion time and GCLID are required for check_gclid action');
        }

        //jika action click_conversion, conversion_time dan gclid harus diisi
        if ($action == 'click_conversion' && (empty($conversion_time) || empty($gclid))) {
            throw new \Exception('Conversion time and GCLID are required for click_conversion action');
        }

        //ubah $conversion_time ke Y-m-d H:i
        $conversion_time = date('Y-m-d H:i', strtotime($conversion_time));

        $data = [
            'action' => $action,
            'gclid' => $gclid,
            'conversion_time' => $conversion_time,
            'rekap_form_source' => $datarekapform ? $datarekapform['source'] : null,
        ];

        $response = Http::timeout(5)->withHeaders([
            'Authorization' => 'Bearer ' . $this->secret_key,
            'X-Time' => $this->time,
        ])->post($this->api_url, $data);

        //jika respon success, tambahkan data ke table kirim_konversi
        $dataRes = $response ? $response->json() : [];

        if ($action == 'click_conversion') {
            $datakirim = KirimKonversi::create([
                'gclid'         => $dataRes['result']['results'][0]['gclid'],
                'jobid'         => $dataRes['result']['jobId'],
                'waktu'         => $dataRes['result']['results'][0]['conversionDateTime'],
                'status'        => $dataRes['success'] ? 'success' : 'failed',
                'response'      => $dataRes['result'],
                'source'        => $datarekapform ? 'greetingads' : 'manual',
                'tercatat'      => $dataRes['tercatat'] !== 'tidak' ? 1 : 0,
                'rekap_form_id' => $datarekapform ? $datarekapform['id'] : null,
                'rekap_form_source' => $datarekapform ? $datarekapform['source'] : null,
                'conversion_action_id' => $dataRes['conversion_action_id'] ?? null,
            ]);

            //tambahkan ke respon
            $dataRes['kirim_konversi'] = $datakirim;
        }

        return $dataRes;
    }

    //Kirim Konversi dari rekap form
    public function kirimKonversiDariRekapForm($rekapform)
    {
        //jika rekapform tidak ada, throw exception
        if (empty($rekapform)) {
            throw new \Exception('Data Rekap form is empty');
        }

        $gclid = $rekapform['gclid'] ?? null;
        $conversion_time = $rekapform['created_at_wib'] ?? null;

        //jika gclid atau conversion_time tidak ada, throw exception
        if (empty($gclid) || empty($conversion_time)) {
            throw new \Exception('GCLID and Conversion time are required');
        }

        //ambil kode gclid dari $gclid
        //sample : GCL.1767882111.Cj0KCQiAyP3KBhD9ARIsAAJLnnafQlHrtpQ9U8E4--iOfrr5O7VS2ncC7POXg5Itwv6tXSQaAhLcEALw_wc
        //kode gclid adalah bagian setelah GCL.1767882111.
        $gclid = explode('.', $gclid)[2];

        //ubah $conversion_time 
        // sample : 2026-01-09 04:40:35+07:00
        // ditambahkan 15 menit, menjadi 2026-01-09 04:55:35+07:00
        // pertahankan format Y-m-d H:i
        $conversion_time = date('Y-m-d H:i', strtotime($conversion_time . ' +5 minutes'));

        //kirim konversi click_conversion
        $dataRes = $this->kirimKonversi('click_conversion', $gclid, $conversion_time, $rekapform);

        //update ke Vdnet melalui RekapFormServices
        $rekapFormServices = new RekapFormServices();
        $rekapFormServices->update_cek_konversi([[
            'id' => $rekapform['id'],
            'cek_konversi_ads' => true,
            'jobid' => $dataRes['result']['jobId'],
        ]]);

        return $dataRes;
    }
}
