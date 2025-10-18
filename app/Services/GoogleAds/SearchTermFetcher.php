<?php

namespace App\Services\GoogleAds;

class SearchTermFetcher
{
    public function getConfig(): array
    {
        $cfg = config('integrations.google_ads');

        $refreshToken = null;
        $tokenPath = $cfg['refresh_token_path'];
        if (is_string($tokenPath) && file_exists($tokenPath)) {
            $refreshToken = trim((string) file_get_contents($tokenPath));
        }

        return [
            'client_id' => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
            'developer_token' => $cfg['developer_token'],
            'customer_id' => $cfg['customer_id'],
            'campaign_id' => $cfg['campaign_id'],
            'refresh_token' => $refreshToken,
        ];
    }
}