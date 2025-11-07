<?php

namespace App\Services\GoogleAds;

use App\Models\NewTermsNegative0Click;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SearchTermFetcher
{

    public function getConfig(): array
    {
        // Baca konfigurasi API eksternal (velocity_ads)
        $cfg = config('integrations.velocity_ads', []);

        return [
            'api_url' => $cfg['api_url'] ?? 'https://api.velocitydeveloper.com/new/adsfetch/fetch_terms_negative0click_secure.php',
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
    public function fetchZeroClickTerms(int $limit = 100): array
    {
        $config = $this->getConfig();
        $apiUrl = $config['api_url'];
        $apiToken = $config['api_token'];

        try {
            $request = Http::timeout(30);

            if (!empty($apiToken)) {
                $request = $request->withHeaders([
                    'Authorization' => "Bearer {$apiToken}",
                    'Accept' => 'application/json',
                ]);
            }

            $response = $request->get($apiUrl);

            if (!$response->successful()) {
                throw new \Exception('External API error: HTTP ' . $response->status());
            }

            $data = $response->json();
            if (!is_array($data)) {
                throw new \Exception('Invalid response format from external API');
            }

            // Ambil daftar items dari payload yang dibungkus: data.search_terms
            $items = $data;
            if ($this->isAssoc($data)) {
                // langsung ambil jika ada key search_terms di top-level
                $items = $data['search_terms'] ?? $data['data'] ?? $data['results'] ?? $data['items'] ?? [];
                // jika yang diambil masih object dan punya search_terms di dalam data
                if ($this->isAssoc($items) && isset($items['search_terms']) && is_array($items['search_terms'])) {
                    $items = $items['search_terms'];
                }
            }

            Log::debug('Zero-click API payload', [
                'top_level_keys' => array_keys($data),
                'items_count' => is_array($items) ? count($items) : 0,
                'first_item_keys' => (is_array($items) && isset($items[0]) && is_array($items[0])) ? array_keys($items[0]) : null,
            ]);

            // Normalisasi data ke bentuk ['search_term' => string]
            $normalized = [];
            foreach ($items as $item) {
                $term = '';

                if (is_string($item)) {
                    if ($this->isValidSearchTerm($item)) {
                        $term = $item;
                    }
                } elseif (is_array($item)) {
                    // respons contoh pakai key 'search_term'
                    $candidates = [
                        $item['search_term'] ?? null,
                        $item['keyword'] ?? null,
                        $item['term'] ?? null,
                        $item['query'] ?? null,
                        $item['text'] ?? null,
                    ];

                    foreach ($candidates as $cand) {
                        if (is_string($cand) && $this->isValidSearchTerm($cand)) {
                            $term = $cand;
                            break;
                        }
                    }
                }

                if (!empty($term)) {
                    $normalized[] = ['search_term' => $term];
                }
            }

            // Batasi jumlah jika perlu
            if ($limit > 0 && count($normalized) > $limit) {
                $normalized = array_slice($normalized, 0, $limit);
            }

            $filteredResults = $normalized;

            Log::info('📊Fetced zero-click terms (external API)', [
                'total_results' => count($normalized),
                'filtered_results' => count($filteredResults),
                // 'api_url' => $apiUrl,
            ]);

            return $filteredResults;
        } catch (\Exception $e) {
            Log::error('Failed to fetch zero-click terms (external API)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Validate that a candidate string looks like a real search term, not a date/time.
     */
    private function isValidSearchTerm(string $term): bool
    {
        $t = trim($term);
        if ($t === '') {
            return false;
        }
        if (strlen($t) > 500) {
            return false;
        }
        // Tolak jika hanya angka dan tanda tanggal
        if (preg_match('/^[0-9:\\-\\/\\s]+$/', $t)) {
            return false;
        }
        // Tolak format ISO / umum lokal
        if (preg_match('/^\\d{4}-\\d{2}-\\d{2}(?:[ T]\\d{2}:\\d{2}(?::\\d{2})?)?$/', $t)) {
            return false;
        }
        if (preg_match('/^\\d{1,2}\\/\\d{1,2}\\/\\d{2,4}(?:\\s+\\d{1,2}:\\d{2}(?:\\s*[AP]M)?)?$/i', $t)) {
            return false;
        }
        // Wajib ada minimal satu huruf
        if (!preg_match('/[A-Za-zÀ-ÖØ-öø-ÿ]/', $t)) {
            return false;
        }
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
            // Log::debug('Processing term', ['term' => $searchTerm]);
            
            if (empty($searchTerm)) {
                continue;
            }
            
            // Skip if term already exists
            if (NewTermsNegative0Click::where('terms', $searchTerm)->exists()) {
                continue;
            }
            
            try {
                NewTermsNegative0Click::create([
                    'terms' => $searchTerm,
                    'hasil_cek_ai' => null,
                    'status_input_google' => null,
                    'retry_count' => 0,
                    'notif_telegram' => null,
                ]);
                
                $stored++;
                
            } catch (\Exception $e) {
                Log::error('Failed to store term', [
                    'term' => $searchTerm,
                    'error' => $e->getMessage()
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