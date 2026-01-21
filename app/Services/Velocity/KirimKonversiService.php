<?php

namespace App\Services\Velocity;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\KirimKonversi;
use App\Models\RekapForm;
use App\Services\NewVDnet\RekapFormServices;

use DateTimeZone, DateTime;

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
    public function kirimKonversi($action, $gclid = null, $conversion_time = null, $datarekapform = null, $conversion_action_id = null)
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
            'conversion_action_id' => $conversion_action_id ?? null,
        ];

        $response = null;
        $dataRes  = [];
        $success  = false;
        $errorMsg = null;

        try {
            $response = Http::timeout(5)->withHeaders([
                'Authorization' => 'Bearer ' . $this->secret_key,
                'X-Time' => $this->time,
            ])->post($this->api_url, $data);

            //jika respon success, tambahkan data ke table kirim_konversi  
            if ($response->successful()) {
                $dataRes = $response->json();
                $success = $dataRes['success'] ?? false;
            } else {
                $errorMsg = 'HTTP ' . $response->status();
                $dataRes  = $response->json() ?? [];
            }
        } catch (\Throwable $e) {
            // TOTAL FAILURE (timeout, DNS, dll)
            $errorMsg = $e->getMessage();
        }

        if ($action == 'click_conversion') {
            // $datakirim = KirimKonversi::create([
            //     'gclid'         => $dataRes['result']['results'][0]['gclid'],
            //     'jobid'         => $dataRes['result']['jobId'],
            //     'waktu'         => $dataRes['result']['results'][0]['conversionDateTime'],
            //     'status'        => $dataRes['success'] ? 'success' : 'failed',
            //     'response'      => $dataRes['result'],
            //     'source'        => $datarekapform ? 'greetingads' : 'manual',
            //     'tercatat'      => $dataRes['tercatat'] !== 'tidak' ? 1 : 0,
            //     'rekap_form_id' => $datarekapform ? $datarekapform['id'] : null,
            //     'rekap_form_source' => $datarekapform ? $datarekapform['source'] : null,
            //     'conversion_action_id' => $dataRes['conversion_action_id'] ?? null,
            // ]);

            $datakirim = KirimKonversi::create([
                'gclid' => $dataRes['result']['results'][0]['gclid']
                    ?? $gclid,
                'jobid' => $dataRes['result']['jobId']
                    ?? null,
                'waktu' => $dataRes['result']['results'][0]['conversionDateTime']
                    ?? $conversion_time,
                'status' => $success ? 'success' : 'failed',
                'response' => $dataRes ?: [
                    'error' => $errorMsg,
                ],
                'source' => $datarekapform ? 'greetingads' : 'manual',
                'tercatat' => ($dataRes['tercatat'] ?? 'tidak') !== 'tidak' ? 1 : 0,
                'rekap_form_id' => $datarekapform['id'] ?? null,
                'rekap_form_source' => $datarekapform['source'] ?? null,
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

        //jika gclid diawali dengan GCL., ambil kode gclid dari $gclid
        if (str_starts_with($gclid, 'GCL.')) {
            //ambil kode gclid dari $gclid
            //sample : GCL.1767882111.Cj0KCQiAyP3KBhD9ARIsAAJLnnafQlHrtpQ9U8E4--iOfrr5O7VS2ncC7POXg5Itwv6tXSQaAhLcEALw_wc
            //kode gclid adalah bagian setelah GCL.1767882111.
            $gclid = explode('.', $gclid)[2];
        }

        // ubah $conversion_time 
        // pertahankan format Y-m-d H:i
        $tz = new DateTimeZone('Asia/Jakarta');
        $conversionDt = new DateTime($conversion_time, $tz);
        $conversionDt->modify('+5 minutes');
        $now = new DateTime('now', $tz);

        // validasi conversion_time tidak boleh melebihi waktu saat ini + 5 minutes
        if ($conversionDt > $now) {
            throw new \Exception('Conversion time tidak boleh melebihi waktu saat ini');
        }

        $conversion_time = $conversionDt->format('Y-m-d H:i');

        //updateOrCreate rekapform
        RekapForm::updateOrCreate([
            'id' => $rekapform['id'],
        ], [
            'source' => $rekapform['source'],
            'source_id' => $rekapform['source_id'],
            'nama' => $rekapform['nama'],
            'no_whatsapp' => $rekapform['no_whatsapp'],
            'jenis_website' => $rekapform['jenis_website'],
            'ai_result' => $rekapform['ai_result'],
            'via' => $rekapform['via'],
            'utm_content' => $rekapform['utm_content'],
            'utm_medium' => $rekapform['utm_medium'],
            'greeting' => $rekapform['greeting'],
            'status' => $rekapform['status'],
            'gclid' => $rekapform['gclid'],
            'cek_konversi_ads' => $rekapform['cek_konversi_ads'],
            'cek_konversi_nominal' => $rekapform['cek_konversi_nominal'],
            'kategori_konversi_nominal' => $rekapform['kategori_konversi_nominal'],
            'tanggal' => $rekapform['created_at'],
            'created_at' => $rekapform['created_at'],
        ]);

        //tentukan conversion_action_id
        $conversion_action_id = '';

        if ($rekapform['source'] == 'vdcom') {
            $conversion_action_id = '7439007312';
        } else if ($rekapform['source'] == 'tidio') {
            $conversion_action_id = '7449463884'; //tidio
        } else if ($rekapform['source'] == 'vdcom_id') {
            $conversion_action_id = '7449287249'; //vdcom_id
        }

        //cek apakah sudah ada kirim_konversi dengan gclid dan rekap_form_id,
        //handle duplikasi kirim_konversi
        $kirim_konversi = KirimKonversi::where('gclid', $gclid)
            ->where('rekap_form_id', $rekapform['id'])
            ->where('status', 'success')
            ->where('rekap_form_source', $rekapform['source'])
            ->where('conversion_action_id', $conversion_action_id)
            ->first();

        if (!$kirim_konversi) {

            //kirim konversi click_conversion
            $dataRes = $this->kirimKonversi('click_conversion', $gclid, $conversion_time, $rekapform, $conversion_action_id);

            //jika success, update rekapform
            if (isset($dataRes['success']) && $dataRes['success'] == true) {

                //update ke Vdnet melalui RekapFormServices
                $rekapFormServices = new RekapFormServices();
                $rekapFormServices->update_cek_konversi([[
                    'id' => $rekapform['id'],
                    'cek_konversi_ads' => true,
                    'jobid' => $dataRes['result']['jobId'],
                    'kirim_konversi_id' => $dataRes['kirim_konversi']['id'] ?? null,
                    'conversion_action_id' => $dataRes['kirim_konversi']['conversion_action_id'] ?? null,
                ]]);

                //update rekapform
                RekapForm::where('id', $rekapform['id'])
                    ->update([
                        'cek_konversi_ads' => true,
                    ]);
            }
        } else {
            //update ke Vdnet melalui RekapFormServices
            $rekapFormServices = new RekapFormServices();
            $update = $rekapFormServices->update_cek_konversi([[
                'id' => $kirim_konversi['rekap_form_id'],
                'cek_konversi_ads' => true,
                'jobid' => $kirim_konversi['jobid'],
                'kirim_konversi_id' => $kirim_konversi['id'] ?? null,
                'conversion_action_id' => $kirim_konversi['conversion_action_id'] ?? null,
            ]]);

            //update rekapform
            RekapForm::where('id', $rekapform['id'])
                ->update([
                    'cek_konversi_ads' => true,
                ]);
        }

        return $dataRes;
    }

    //Kirim Konversi berdasarkan nominal dari rekap form 
    public function kirimKonversiDariRekapFormNominal($rekapform)
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

        //jika gclid diawali dengan GCL., ambil kode gclid dari $gclid
        if (str_starts_with($gclid, 'GCL.')) {
            //ambil kode gclid dari $gclid
            //sample : GCL.1767882111.Cj0KCQiAyP3KBhD9ARIsAAJLnnafQlHrtpQ9U8E4--iOfrr5O7VS2ncC7POXg5Itwv6tXSQaAhLcEALw_wc
            //kode gclid adalah bagian setelah GCL.1767882111.
            $gclid = explode('.', $gclid)[2];
        }

        // ubah $conversion_time 
        // pertahankan format Y-m-d H:i
        $tz = new DateTimeZone('Asia/Jakarta');
        $conversionDt = new DateTime($conversion_time, $tz);
        $conversionDt->modify('+5 minutes');
        $now = new DateTime('now', $tz);

        // validasi conversion_time tidak boleh melebihi waktu saat ini + 5 minutes
        if ($conversionDt > $now) {
            throw new \Exception('Conversion time tidak boleh melebihi waktu saat ini');
        }

        $conversion_time = $conversionDt->format('Y-m-d H:i');

        //kirim konversi berdasarkan kategori_konversi_nominal
        $kategori_konversi_nominal = $rekapform['kategori_konversi_nominal'] ?? null;

        $konversi_actions = [
            'dibawah500rb' => [7452857980],
            '500rb-900rb' => [7452857974, 7452857977],
            '1jt-1,4jt' => [7447336446, 7447105159, 7447105162],
            '1,5jt' => [7452857983, 7452857986, 7452857989, 7452857992],
        ];

        if (!isset($konversi_actions[$kategori_konversi_nominal])) {
            Log::info('[KONVERSI] kategori tidak ada / kosong', [
                'kategori' => $kategori_konversi_nominal,
                'rekap_id' => $rekapform['id'] ?? null,
            ]);
            return;
        }

        //try loop kirim konversi
        try {
            if (isset($konversi_actions[$kategori_konversi_nominal])) {

                foreach ($konversi_actions[$kategori_konversi_nominal] as $conversion_action_id) {
                    // $dataRes = $this->kirimKonversi('click_conversion', $gclid, $conversion_time, $rekapform, $conversion_action_id);
                    Log::info('[KONVERSI] nominal ', [
                        'kategori' => $kategori_konversi_nominal,
                        'rekap_form_id' => $rekapform['id'] ?? null,
                        'conversion_action_id' => $conversion_action_id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('[CRON] kirim-konversi:sync-vdnet FAILED', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }
}
