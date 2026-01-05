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
        $this->rekapApiUrl = 'https://new.velocitydeveloper.net/api/api/public';
        $this->apiToken = env('NEW_VDNET_API_TOKEN');
    }

    //get list rekap form
    public function get_list(array $params = []): array
    {
        $url = $this->rekapApiUrl . '/rekap_form';
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiToken,
        ])->get($url, $params);

        return $response->json();
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
