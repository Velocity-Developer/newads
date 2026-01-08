<?php

namespace App\Services\Velocity;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
    public function kirimKonversi($action, $gclid = null, $conversion_time = null)
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
        ];

        $response = Http::timeout(5)->withHeaders([
            'Authorization' => 'Bearer ' . $this->secret_key,
            'X-Time' => $this->time,
        ])->post($this->api_url, $data);

        return $response->json();
    }
}
