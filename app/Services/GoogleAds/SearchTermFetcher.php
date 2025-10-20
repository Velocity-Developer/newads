<?php

namespace App\Services\GoogleAds;

use App\Models\NewTermsNegative0Click;
use Illuminate\Support\Facades\Log;

class SearchTermFetcher
{
    private array $excludedWords = [
        'jasa',
        'harga', 
        'buat',
        'bikin',
        'murah',
        'pembuatan',
        'biaya',
        'beli',
        'pesan',
        'velocity'
    ];

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

    /**
     * Fetch zero-click search terms from Google Ads.
     */
    public function fetchZeroClickTerms(int $limit = 100): array
    {
        $config = $this->getConfig();
        
        // GAQL Query for zero-click terms
        $gaqlQuery = "
            SELECT 
                search_term_view.search_term,
                metrics.clicks,
                search_term_view.status
            FROM search_term_view 
            WHERE 
                metrics.clicks = 0 
                AND search_term_view.status = 'NONE'
                AND segments.date DURING LAST_30_DAYS
            LIMIT {$limit}
        ";
        
        try {
            // Execute the query (placeholder - needs actual Google Ads API client)
            $results = $this->executeGaqlQuery($gaqlQuery, $config);
            
            // Filter out excluded words
            $filteredResults = $this->filterExcludedWords($results);
            
            Log::info('Fetched zero-click terms', [
                'total_results' => count($results),
                'filtered_results' => count($filteredResults)
            ]);
            
            return $filteredResults;
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch zero-click terms', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Add negative keyword to Google Ads campaign.
     */
    public function addNegativeKeyword(string $keyword, string $matchType = 'EXACT'): bool
    {
        $config = $this->getConfig();
        
        try {
            // Placeholder for actual Google Ads API call
            // This would use Google Ads API to add negative keyword
            $success = $this->executeAddNegativeKeyword($keyword, $matchType, $config);
            
            Log::info('Added negative keyword', [
                'keyword' => $keyword,
                'match_type' => $matchType,
                'success' => $success
            ]);
            
            return $success;
            
        } catch (\Exception $e) {
            Log::error('Failed to add negative keyword', [
                'keyword' => $keyword,
                'match_type' => $matchType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Execute GAQL query (placeholder for actual implementation).
     */
    private function executeGaqlQuery(string $query, array $config): array
    {
        // TODO: Implement actual Google Ads API client
        // This is a placeholder that should be replaced with:
        // - Google Ads API client initialization
        // - Authentication using refresh token
        // - Query execution
        // - Result parsing
        
        Log::warning('executeGaqlQuery is not implemented - using placeholder');
        
        // Return empty array for now
        return [];
    }

    /**
     * Execute add negative keyword operation (placeholder).
     */
    private function executeAddNegativeKeyword(string $keyword, string $matchType, array $config): bool
    {
        // TODO: Implement actual Google Ads API call
        // This should use Google Ads API to add negative keyword to campaign
        
        Log::warning('executeAddNegativeKeyword is not implemented - using placeholder');
        
        // Return true for testing purposes
        return true;
    }

    /**
     * Filter out terms containing excluded words.
     */
    private function filterExcludedWords(array $terms): array
    {
        return array_filter($terms, function($term) {
            $searchTerm = strtolower($term['search_term'] ?? '');
            
            foreach ($this->excludedWords as $excludedWord) {
                if (strpos($searchTerm, strtolower($excludedWord)) !== false) {
                    return false;
                }
            }
            
            return true;
        });
    }

    /**
     * Store zero-click terms in database.
     */
    public function storeZeroClickTerms(array $terms): int
    {
        $stored = 0;
        
        foreach ($terms as $termData) {
            $searchTerm = $termData['search_term'] ?? '';
            
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
}