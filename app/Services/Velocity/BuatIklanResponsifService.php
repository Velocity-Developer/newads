<?php

namespace App\Services\Velocity;

use Illuminate\Support\Facades\Http;

class BuatIklanResponsifService
{
    private $api_url = 'https://api.velocitydeveloper.com/api/v1/buat-iklan-responsif.php';

    private $secret_key;

    private $time;

    // constructor
    public function __construct()
    {
        $this->secret_key = config('integrations.velocity_ads.api_secret_key');
        $this->time = time();
    }

    // buat iklan
    public function send($term, $group_name)
    {
        $success = false;
        $errorMsg = null;
        $dataRes = [];

        try {

            $data = [
                'term' => $term,
                'group_name' => $group_name,
            ];

            $response = Http::timeout(5)->withHeaders([
                'Authorization' => 'Bearer '.$this->secret_key,
                'X-Time' => $this->time,
            ])->post($this->api_url, $data);

            // jika respon success, tambahkan data ke table kirim_konversi
            if ($response->successful()) {
                $dataRes = $response->json();
                $success = $dataRes['success'] ?? false;
            } else {
                $errorMsg = 'HTTP '.$response->status();
                $dataRes = $response->json() ?? [];
            }
        } catch (\Exception $e) {
            $success = false;
            $errorMsg = $e->getMessage();
        } finally {
            return [
                'succes' => $success,
                'error' => $errorMsg,
                'results' => $dataRes,
            ];
        }
    }
}
