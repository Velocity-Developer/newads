<?php

namespace App\Services\Velocity;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class NegativeKeywordInputService
{
    private string $inputApiUrl;
    private ?string $apiToken;
    private array $matchTypes;

    public function __construct()
    {
        $cfg = config('integrations.velocity_ads', []);
        $this->inputApiUrl = $cfg['input_api_url'] ?? 'https://api.velocitydeveloper.com/new/adsfetch/input_keywords_negative.php';
        $this->apiToken = $cfg['api_token'] ?? null;
        $this->matchTypes = $cfg['input_match_types'] ?? [
            'terms' => 'EXACT',
            'frasa' => 'PHRASE',
        ];
    }

    public function getMatchTypeForSource(string $source): string
    {
        $source = strtolower($source);
        return $this->matchTypes[$source] ?? 'EXACT';
    }

    /**
     * Kirim negative keywords ke Velocity API.
     * @param array $terms Array of strings
     * @param string $matchType 'EXACT' atau 'PHRASE' (atau 'PHARSE' jika API memang demikian)
     * @param string $mode 'validate' atau 'execute'
     * @return array {success: bool, status: int|null, json: mixed|null, error: string|null}
     */
    public function send(array $terms, string $matchType, string $mode = 'validate'): array
    {
        $terms = array_values(array_filter(array_map('strval', $terms), fn($t) => trim($t) !== ''));
        if (empty($terms)) {
            return ['success' => false, 'status' => null, 'json' => null, 'error' => 'No terms provided'];
        }

        $url = rtrim($this->inputApiUrl, '?&');
        $url .= (str_contains($url, '?') ? '&' : '?') . 'mode=' . urlencode($mode);

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        if (!empty($this->apiToken)) {
            $headers['Authorization'] = $this->apiToken;
        }

        $payload = [
            'terms' => $terms,
            'match_type' => $matchType,
        ];

        try {
            $resp = Http::timeout(30)
                ->retry(3, 200)
                ->withHeaders($headers)
                ->post($url, $payload);

            $status = $resp->status();
            $json = null;
            try {
                $json = $resp->json();
            } catch (Exception $e) {
                // jika bukan JSON
                $json = null;
            }

            $ok = $resp->ok();
            $apiSuccess = is_array($json) && array_key_exists('success', $json) ? (bool)$json['success'] : null;
            $success = $apiSuccess ?? $ok;

            if (!$success) {
                Log::warning('Velocity input API failed', [
                    'status' => $status,
                    'body' => $resp->body(),
                    'json' => $json,
                ]);
            }

            return [
                'success' => $success,
                'status' => $status,
                'json' => $json,
                'error' => $success ? null : ($json['error'] ?? 'Unknown error'),
            ];
        } catch (Exception $e) {
            Log::error('Velocity input API exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'status' => null, 'json' => null, 'error' => $e->getMessage()];
        }
    }
}