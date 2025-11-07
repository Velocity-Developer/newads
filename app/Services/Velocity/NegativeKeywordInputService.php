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
    public function send(array $terms, string $matchType, string $mode = 'validate', ?string $campaignId = null): array
    {
        $terms = array_values(array_filter(array_map('strval', $terms), fn($t) => trim($t) !== ''));
        if (empty($terms)) {
            return ['success' => false, 'status' => null, 'json' => null, 'error' => 'No terms provided'];
        }

        // Normalisasi campaign_id: terima '0' sebagai valid
        $campaignIdNormalized = null;
        if ($campaignId !== null && $campaignId !== '') {
            if (is_numeric($campaignId)) {
                $campaignIdNormalized = (int) $campaignId;
            } else {
                Log::warning('Invalid campaign_id provided, ignoring', ['campaign_id' => $campaignId]);
            }
        }

        $url = rtrim($this->inputApiUrl, '?&'); // jangan taruh mode di query

        // Preflight log sebelum kirim
        Log::info('📩Sending negative keywords to Velocity', [
            'count' => count($terms),
            'match_type' => strtoupper($matchType),
            'mode' => strtolower($mode),
            'campaign_id' => $campaignIdNormalized,
            'sample_terms' => array_slice($terms, 0, 3),
        ]);

        try {
            // Bangun form payload
            $form = [
                'match_type' => strtoupper($matchType),
                'mode' => strtolower($mode),
            ];
            foreach ($terms as $t) {
                $form['terms'][] = $t;
            }
            if ($campaignIdNormalized !== null) {
                $form['campaign_id'] = $campaignIdNormalized;
            }

            // Tambahkan header Authorization Bearer jika token tersedia
            $request = Http::timeout(30)->retry(3, 200);
            if (!empty($this->apiToken)) {
                $request = $request->withHeaders([
                    'Authorization' => "Bearer {$this->apiToken}",
                    'Accept' => 'application/json',
                ]);
            }

            $resp = $request
                ->asForm()
                ->post($url, $form);

            $status = $resp->status();
            $json = null;
            try {
                $json = $resp->json();
            } catch (Exception $e) {
                $json = null;
            }

            // Log metrik selalu ditulis (tidak bergantung parse JSON)
            // Log::info('METRIC:API_REQUEST ' . json_encode([
            //     'date' => now()->toDateString(),
            //     'timestamp' => now()->toIso8601String(),
            //     'component' => 'velocity_input',
            //     'target' => 'google-ads',
            //     'request_count' => 1,
            //     'terms_count' => count($terms),
            //     'mode' => strtolower($mode),
            //     'match_type' => strtoupper($matchType),
            //     'campaign_id' => $campaignIdNormalized,
            //     'status' => $status,
            //     'api_success' => is_array($json) && array_key_exists('success', $json) ? (bool)$json['success'] : null,
            // ], JSON_UNESCAPED_UNICODE));

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
                'error' => $success ? null : (is_array($json) && array_key_exists('error', $json) ? (string)$json['error'] : 'Unknown error'),
            ];
        } catch (Exception $e) {
            Log::error('Velocity input API exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return ['success' => false, 'status' => null, 'json' => null, 'error' => $e->getMessage()];
        }
    }
}