<?php

namespace App\Services\Velocity;

use App\Models\SearchTerm;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SearchTermService
{
    private $api_url = 'https://api.velocitydeveloper.com/api/v1';

    private $secret_key;

    private $time;

    // constructor
    public function __construct()
    {
        $this->secret_key = config('integrations.velocity_ads.api_secret_key');
        $this->time = time();
    }

    public function getSearchTermsNone()
    {
        try {
            $response = Http::timeout(5)->withHeaders([
                'Authorization' => 'Bearer ' . $this->secret_key,
                'X-Time' => $this->time,
                'Accept' => 'application/json',
            ])->get($this->api_url . '/fetch-terms-none.php');

            // jika respon success, tambahkan data ke table kirim_konversi
            if ($response->successful()) {
                $dataRes = $response->json();
                $success = $dataRes['success'] ?? false;
            } else {
                $errorMsg = 'HTTP ' . $response->status();
                $dataRes = $response->json() ?? [];
                $dataRes['message'] = $errorMsg;
            }

            return $dataRes;
        } catch (\Exception $e) {

            // TOTAL FAILURE (timeout, DNS, dll)
            $errorMsg = $e->getMessage();

            return [
                'success' => false,
                'error' => $errorMsg,
                'response' => $response->json() ?? [],
            ];
        }
    }
}
