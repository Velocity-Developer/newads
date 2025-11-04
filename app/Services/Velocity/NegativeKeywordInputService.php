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

        // Logging ringan sebelum kirim
        Log::info('Sending negative keywords to Velocity', [
            'count' => count($terms),
            'match_type' => strtoupper($matchType),
            'mode' => strtolower($mode),
            'campaign_id' => $campaignIdNormalized,
            'sample_terms' => array_slice($terms, 0, 3),
        ]);

        $headers = [
            'Accept' => 'application/json',
            // Jangan set Content-Type manual, biarkan multipart mengatur boundary
        ];
        if (!empty($this->apiToken)) {
            $headers['Authorization'] = $this->apiToken;
        }

        // Kirim sebagai multipart form-data:
        // - terms: JSON array string
        // - match_type: EXACT/PHRASE (uppercase)
        // - mode: validate/execute (lowercase)
        $multipart = [
            [
                'name' => 'terms',
                'contents' => json_encode($terms, JSON_UNESCAPED_UNICODE),
            ],
            [
                'name' => 'match_type',
                'contents' => strtoupper($matchType),
            ],
            [
                'name' => 'mode',
                'contents' => strtolower($mode),
            ],
        ];

        try {
            $form = [
                'match_type' => strtoupper($matchType),
                'mode' => strtolower($mode),
            ];

            // Tambahkan campaign_id terpisah jika tersedia (termasuk 0)
            if ($campaignIdNormalized !== null) {
                $form['campaign_id'] = $campaignIdNormalized;
            }

            // Terms murni (tanpa prefix)
            foreach ($terms as $t) {
                $form['terms'][] = $t;
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