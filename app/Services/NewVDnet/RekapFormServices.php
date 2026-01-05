<?php

namespace App\Services\NewVDnet;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RekapFormServices
{
    private string $rekapApiUrl;
    private ?string $apiToken;

    public function __construct()
    {
        $this->rekapApiUrl = config('services.newvdnet.api_url');
        $this->apiToken   = config('services.newvdnet.api_token');
    }

    //get list rekap form
    public function get_list(array $params = [])
    {
        $url = $this->rekapApiUrl . '/rekap-form-konversi-ads';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->get($url, $params);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'status'  => $response->status(),
                    'body'    => $response->body(),
                ];
            }

            return $response->json();
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    //get by id
    public function get_by_id(int $id): array
    {
        $url = $this->rekapApiUrl . '/rekap_form/' . $id;
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiToken,
        ])->get($url);

        return $response->json();
    }
}
