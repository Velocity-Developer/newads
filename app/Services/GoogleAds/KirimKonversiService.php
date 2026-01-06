<?php

namespace App\Services\GoogleAds;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class KirimKonversiService
{

    //send
    public function send(array $params = [])
    {
        $url = config('services.google_ads.api_url') . '/v1/customers/' . config('services.google_ads.customer_id') . '/conversionActions:upload';
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.google_ads.api_token'),
        ])->post($url, $params);
        return $response->json($params);
    }
}
