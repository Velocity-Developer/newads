<?php

namespace App\Services\GoogleAds;

use App\Models\NewTermsNegative0Click;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SearchTermFetcher
{
    public function getConfig(): array
    {
        // Baca konfigurasi API eksternal (velocity_ads)
        $cfg = config('integrations.velocity_ads', []);

        return [
            'api_url' => $cfg['api_url'] ?? 'https://api.velocitydeveloper.com/new/adsfetch/multi_campaign_fetch_terms.php',
            'api_token' => $cfg['api_token'] ?? null,
        ];
    }

    /**
     * Filter out terms containing excluded words.
     * DISABLED: Now returns all terms without filtering.
     */
    private function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Fetch zero-click search terms via external API (Velocity Developer).
     */
    public function fetchZeroClickTerms(?int $limit = null): array
    {
        $config = $this->getConfig();
        $apiUrl = $config['api_url'];
        $apiToken = $config['api_token'];

        // Ambil limit dari ENV jika tidak diberikan
        $limit = $limit ?? (int) env('ZERO_CLICK_LIMIT', 100);

        try {
            $request = Http::timeout(30);

            if (! empty($apiToken)) {
                $request = $request->withHeaders([
                    'Authorization' => "Bearer {$apiToken}",
                    'Accept' => 'application/json',
                ]);
            }

            // Log pre-call agar terlihat di produksi
            // Log::info('🌐 Calling external zero-click API', [
            //     'url' => $apiUrl,
            //     'token_present' => !empty($apiToken),
            // ]);

            $response = $request->get($apiUrl);

            if (! $response->successful()) {
                // Log detail status + cuplikan body untuk diagnosa cepat
                Log::error('❌ External API HTTP error', [
                    'url' => $apiUrl,
                    'status' => $response->status(),
                    'body_excerpt' => mb_substr((string) $response->body(), 0, 500),
                    'headers' => $response->headers(),
                ]);
                throw new \Exception('External API error: HTTP '.$response->status());
            }

            $body = (string) $response->body();
            $data = $response->json();

            if (! is_array($data)) {
                Log::error('❌ Invalid JSON response from external API', [
                    'url' => $apiUrl,
                    'content_type' => $response->header('Content-Type'),
                    'body_excerpt' => mb_substr($body, 0, 500),
                ]);
                throw new \Exception('Invalid response format from external API');
            }

            // Ambil daftar items dari payload yang dibungkus di berbagai bentuk
            $items = $data;
            if ($this->isAssoc($data)) {
                $items = $data['search_terms'] ?? $data['data'] ?? $data['results'] ?? $data['items'] ?? [];
                if ($this->isAssoc($items) && isset($items['search_terms']) && is_array($items['search_terms'])) {
                    $items = $items['search_terms'];
                }
                // Fallback tambahan untuk nested umum
                if (! is_array($items) || ($this->isAssoc($items) && ! isset($items[0]))) {
                    $paths = [
                        ['data', 'search_terms'],
                        ['payload', 'terms'],
                        ['data', 'items'],
                        ['results', 'items'],
                        ['response', 'search_terms'],
                    ];
                    foreach ($paths as $path) {
                        $tmp = $data;
                        foreach ($path as $key) {
                            if (is_array($tmp) && isset($tmp[$key])) {
                                $tmp = $tmp[$key];
                            } else {
                                $tmp = null;
                                break;
                            }
                        }
                        if (is_array($tmp)) {
                            $items = $tmp;
                            break;
                        }
                    }
                }
            }

            // Log struktur data untuk debugging
            // Log::debug('📊Zero-click API payload', [
            //     'top_level_keys' => array_keys($data),
            //     'items_count' => is_array($items) ? count($items) : 0,
            //     'first_item_keys' => (is_array($items) && isset($items[0]) && is_array($items[0])) ? array_keys($items[0]) : null,
            // ]);

            // Normalisasi data ke bentuk ['search_term' => string]
            $normalized = [];
            $rejectedCount = 0;

            foreach ($items as $it) {
                $term = '';
                if (is_string($it)) {
                    $term = $it;
                } elseif (is_array($it)) {
                    $term = $it['search_term'] ?? $it['term'] ?? $it['query'] ?? '';
                }

                $term = is_string($term) ? trim($term) : '';
                if ($term === '') {
                    $rejectedCount++;

                    continue;
                }

                // Try to carry campaign_id if available in payload
                $campaignId = null;
                if (is_array($it)) {
                    $campaignId = $it['campaign_id']
                        ?? $it['campaignId']
                        ?? ($it['campaign']['id'] ?? null)
                        ?? null;
                }

                $normalized[] = [
                    'search_term' => $term,
                    'campaign_id' => is_numeric($campaignId) ? (int) $campaignId : null,
                ];
            }

            // Batasi jumlah jika perlu
            if ($limit > 0 && count($normalized) > $limit) {
                $normalized = array_slice($normalized, 0, $limit);
            }

            Log::info('📊Fetched zero-click terms (external API)', [
                'total_results' => count($normalized),
                'rejected' => $rejectedCount,
                'api_url' => $apiUrl,
            ]);

            return $normalized;
        } catch (\Exception $e) {
            Log::error('❌Failed to fetch zero-click terms (external API)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Validate that a candidate string looks like a real search term, not a date/time.
     */
    private function isValidSearchTerm(string $term, ?string &$reason = null): bool
    {
        $t = trim($term);
        if ($t === '') {
            $reason = '❌empty after trim';

            return false;
        }
        if (strlen($t) > 500) {
            $reason = '❌too long (>500 chars)';

            return false;
        }
        // Tolak jika hanya angka dan tanda tanggal
        if (preg_match('/^[0-9:\\-\\/\\s]+$/', $t)) {
            $reason = '❌digits/date-like only';

            return false;
        }
        // Tolak format ISO / umum lokal
        if (preg_match('/^\\d{4}-\\d{2}-\\d{2}(?:[ T]\\d{2}:\\d{2}(?::\\d{2})?)?$/', $t)) {
            $reason = '❌ISO date/time';

            return false;
        }
        if (preg_match('/^\\d{1,2}\\/\\d{1,2}\\/\\d{2,4}(?:\\s+\\d{1,2}:\\d{2}(?:\\s*[AP]M)?)?$/i', $t)) {
            $reason = '❌local date/time';

            return false;
        }
        // Wajib ada minimal satu huruf
        if (! preg_match('/[A-Za-zÀ-ÖØ-öø-ÿ]/', $t)) {
            $reason = '❌no letters';

            return false;
        }
        $reason = null;

        return true;
    }

    /**
     * Store zero-click terms in database.
     */
    public function storeZeroClickTerms(array $terms): int
    {
        $stored = 0;

        foreach ($terms as $termData) {
            $searchTerm = $termData['search_term'] ?? '';
            $searchTerm = trim($searchTerm);
            if ($searchTerm === '') {
                Log::debug('❌Skip empty term after trim');

                continue;
            }

            // Validasi format term, log alasan jika tidak valid
            $reason = null;
            if (! $this->isValidSearchTerm($searchTerm, $reason)) {
                Log::warning('❌Skip invalid search term', [
                    'term' => $searchTerm,
                    'reason' => $reason,
                ]);

                continue;
            }

            // Skip jika duplikat (exact match), log agar terlihat di monitoring
            if (NewTermsNegative0Click::where('terms', $searchTerm)->exists()) {
                $campaignIdForLog = $termData['campaign_id'] ?? null;

                // Log::info('❌Skip duplicate search term', [
                //     'term' => $searchTerm,
                //     'campaign_id' => is_numeric($campaignIdForLog) ? (int)$campaignIdForLog : null,
                // ]);
                continue;
            }

            $campaignId = $termData['campaign_id'] ?? null;

            try {
                NewTermsNegative0Click::create([
                    'terms' => $searchTerm,
                    'hasil_cek_ai' => null,
                    'status_input_google' => null,
                    'retry_count' => 0,
                    'notif_telegram' => null,
                    'campaign_id' => is_numeric($campaignId) ? (int) $campaignId : null,
                ]);

                $stored++;
            } catch (\Throwable $e) {
                Log::error('Failed to store zero-click term', [
                    'term' => $searchTerm,
                    'campaign_id' => is_numeric($campaignId) ? (int) $campaignId : null,
                    'exception_class' => get_class($e),
                    'exception_code' => $e->getCode(),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('Stored zero-click terms', ['count' => $stored]);

        return $stored;
    }

    /**
     * Stub for backward compatibility: Google Ads input disabled.
     */
    public function addNegativeKeyword(string $keyword, string $matchType = 'EXACT'): bool
    {
        Log::warning('addNegativeKeyword called but Google Ads integration is disabled', [
            'keyword' => $keyword,
            'match_type' => $matchType,
        ]);

        return false;
    }

    /**
     * Test fetch zero-click terms with limited results for safe testing (external API).
     */
    public function testFetchZeroClickTerms(int $limit = 5): array
    {
        try {
            $terms = $this->fetchZeroClickTerms($limit);

            Log::info('Test fetch zero-click terms (external API) successful', [
                'limit' => $limit,
                'found' => count($terms),
            ]);

            return [
                'success' => true,
                'terms' => $terms,
            ];
        } catch (\Exception $e) {
            Log::error('Test fetch zero-click terms (external API) failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'terms' => [],
            ];
        }
    }
}
