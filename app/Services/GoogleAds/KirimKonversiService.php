<?php

namespace App\Services\GoogleAds;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V19\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\V19\Services\SearchGoogleAdsRequest;

class KirimKonversiService
{
    public $refresh_token;
    public $client_id;
    public $client_secret;
    public $developer_token;
    public $customer_id;

    //constructor
    public function __construct()
    {
        $this->refresh_token = config('services.googleads.refresh_token');
        $this->client_id = config('services.googleads.client_id');
        $this->client_secret = config('services.googleads.client_secret');
        $this->developer_token = config('services.googleads.developer_token');
        $this->customer_id = config('services.googleads.customer_id');
    }

    //connect GoogleAds
    public function connectGoogleAds()
    {
        //jika refresh token kosong
        if (empty($this->refresh_token)) {
            throw new \Exception('Refresh token is empty');
        }

        dd([
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'refresh_token' => $this->refresh_token,
        ]);

        //auth
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->withClientId($this->client_id)
            ->withClientSecret($this->client_secret)
            ->withRefreshToken($this->refresh_token)
            ->build();

        $client = (new GoogleAdsClientBuilder())
            ->withDeveloperToken($this->developer_token)
            ->withLoginCustomerId($this->customer_id)
            ->withOAuth2Credential($oAuth2Credential)
            ->build();

        return $client;
    }

    //fetchAccountTimeZone
    public function fetchAccountTimeZone()
    {
        $query = "
        SELECT
            customer.time_zone
        FROM customer ";

        $client = $this->connectGoogleAds();

        $response = $client->getGoogleAdsServiceClient()->search(
            \Google\Ads\GoogleAds\V19\Services\SearchGoogleAdsRequest::build($this->customer_id, $query)
        );

        foreach ($response->iterateAllElements() as $row) {
            return $row->getCustomer()->getTimeZone();
        }

        return null;
    }

    //send
    public function send(array $params = [])
    {
        $url = config('services.google_ads.api_url') . '/v1/customers/' . config('services.google_ads.customer_id') . '/conversionActions:upload';
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.google_ads.api_token'),
        ])->post($url, $params);
        return $response->json($params);
    }

    //timezone
    public function get_time_zone()
    {
        $time_zone = $this->fetchAccountTimeZone();
        //jika time_zone kosong
        if (empty($time_zone)) {
            return [
                'success' => false,
                'message' => 'Time zone not found',
            ];
        }

        return [
            'success' => true,
            'time_zone' => $time_zone,
        ];
    }
}
