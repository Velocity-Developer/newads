<?php

namespace App\Services\GoogleAds;

use App\Models\NewTermsNegative0Click;
use Illuminate\Support\Facades\Log;
use Google\Ads\GoogleAds\Lib\V17\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V17\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\V17\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V17\Services\SearchGoogleAdsStreamRequest;
use Google\Ads\GoogleAds\V17\Resources\CampaignCriterion;
use Google\Ads\GoogleAds\V17\Common\KeywordInfo;
use Google\Ads\GoogleAds\V17\Enums\KeywordMatchTypeEnum\KeywordMatchType;
use Google\Ads\GoogleAds\V17\Enums\CriterionTypeEnum\CriterionType;
use Google\Ads\GoogleAds\V17\Services\CampaignCriterionOperation;
use Google\Ads\GoogleAds\V17\Services\MutateCampaignCriteriaRequest;
use Google\ApiCore\ApiException;

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
     * Get Google Ads client instance.
     */
    private function getGoogleAdsClient(): GoogleAdsClient
    {
        $config = $this->getConfig();
        
        if (empty($config['refresh_token'])) {
            throw new \Exception('Google Ads refresh token not found. Please generate refresh token first.');
        }
        
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->withClientId($config['client_id'])
            ->withClientSecret($config['client_secret'])
            ->withRefreshToken($config['refresh_token'])
            ->build();

        return (new GoogleAdsClientBuilder())
            ->withOAuth2Credential($oAuth2Credential)
            ->withDeveloperToken($config['developer_token'])
            ->build();
    }

    /**
     * Execute GAQL query using Google Ads API.
     */
    private function executeGaqlQuery(string $query, array $config): array
    {
        try {
            $googleAdsClient = $this->getGoogleAdsClient();
            $googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();
            
            $customerId = str_replace('-', '', $config['customer_id']);
            
            $request = SearchGoogleAdsStreamRequest::build($customerId, $query);
            $stream = $googleAdsServiceClient->searchStream($request);
            
            $results = [];
            foreach ($stream->iterateAllElements() as $googleAdsRow) {
                /** @var GoogleAdsRow $googleAdsRow */
                $searchTermView = $googleAdsRow->getSearchTermView();
                
                if ($searchTermView) {
                    $results[] = [
                        'search_term' => $searchTermView->getSearchTerm(),
                        'clicks' => $googleAdsRow->getMetrics()->getClicks(),
                        'status' => $searchTermView->getStatus()
                    ];
                }
            }
            
            Log::info('GAQL query executed successfully', [
                'query' => $query,
                'results_count' => count($results)
            ]);
            
            return $results;
            
        } catch (ApiException $e) {
            Log::error('Google Ads API error in GAQL query', [
                'error' => $e->getMessage(),
                'status' => $e->getStatus(),
                'details' => $e->getBasicMessage()
            ]);
            throw new \Exception('Google Ads API error: ' . $e->getBasicMessage());
            
        } catch (\Exception $e) {
            Log::error('Failed to execute GAQL query', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Execute add negative keyword operation using Google Ads API.
     */
    private function executeAddNegativeKeyword(string $keyword, string $matchType, array $config): bool
    {
        try {
            $googleAdsClient = $this->getGoogleAdsClient();
            $campaignCriterionServiceClient = $googleAdsClient->getCampaignCriterionServiceClient();
            
            $customerId = str_replace('-', '', $config['customer_id']);
            $campaignId = $config['campaign_id'];
            
            // Create keyword info
            $keywordInfo = new KeywordInfo([
                'text' => $keyword,
                'match_type' => $this->getMatchTypeEnum($matchType)
            ]);
            
            // Create campaign criterion for negative keyword
            $campaignCriterion = new CampaignCriterion([
                'campaign' => "customers/{$customerId}/campaigns/{$campaignId}",
                'keyword' => $keywordInfo,
                'negative' => true,
                'type' => CriterionType::KEYWORD
            ]);
            
            // Create operation
            $operation = new CampaignCriterionOperation();
            $operation->setCreate($campaignCriterion);
            
            // Execute the operation
            $request = MutateCampaignCriteriaRequest::build($customerId, [$operation]);
            $response = $campaignCriterionServiceClient->mutateCampaignCriteria($request);
            
            $success = !empty($response->getResults());
            
            Log::info('Negative keyword added successfully', [
                'keyword' => $keyword,
                'match_type' => $matchType,
                'campaign_id' => $campaignId,
                'success' => $success
            ]);
            
            return $success;
            
        } catch (ApiException $e) {
            Log::error('Google Ads API error adding negative keyword', [
                'keyword' => $keyword,
                'error' => $e->getMessage(),
                'status' => $e->getStatus(),
                'details' => $e->getBasicMessage()
            ]);
            throw new \Exception('Google Ads API error: ' . $e->getBasicMessage());
            
        } catch (\Exception $e) {
            Log::error('Failed to add negative keyword', [
                'keyword' => $keyword,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Convert match type string to Google Ads enum.
     */
    private function getMatchTypeEnum(string $matchType): int
    {
        switch (strtoupper($matchType)) {
            case 'EXACT':
                return KeywordMatchType::EXACT;
            case 'PHRASE':
                return KeywordMatchType::PHRASE;
            case 'BROAD':
                return KeywordMatchType::BROAD;
            default:
                return KeywordMatchType::EXACT;
        }
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

    /**
     * Test Google Ads API connection with read-only query.
     */
    public function testConnection(): array
    {
        try {
            $config = $this->getConfig();
            
            if (empty($config['refresh_token'])) {
                return [
                    'success' => false,
                    'error' => 'Refresh token not found'
                ];
            }

            // Initialize Google Ads client
            $oAuth2Credential = (new OAuth2TokenBuilder())
                ->withClientId($config['client_id'])
                ->withClientSecret($config['client_secret'])
                ->withRefreshToken($config['refresh_token'])
                ->build();

            $googleAdsClient = (new GoogleAdsClientBuilder())
                ->withOAuth2Credential($oAuth2Credential)
                ->withDeveloperToken($config['developer_token'])
                ->build();

            // Simple read-only query to test connection
            $query = "SELECT campaign.id, campaign.name FROM campaign WHERE campaign.id = " . $config['campaign_id'];
            
            $request = new SearchGoogleAdsStreamRequest();
            $request->setCustomerId($config['customer_id']);
            $request->setQuery($query);

            $stream = $googleAdsClient->getGoogleAdsServiceClient()->searchStream($request);
            
            $campaignName = 'Unknown';
            foreach ($stream->iterateAllElements() as $googleAdsRow) {
                $campaignName = $googleAdsRow->getCampaign()->getName();
                break;
            }

            Log::info('Google Ads connection test successful', [
                'campaign_id' => $config['campaign_id'],
                'campaign_name' => $campaignName
            ]);

            return [
                'success' => true,
                'campaign_name' => $campaignName
            ];

        } catch (ApiException $e) {
            Log::error('Google Ads API connection test failed', [
                'error' => $e->getMessage(),
                'status' => $e->getStatus()
            ]);
            
            return [
                'success' => false,
                'error' => 'API Error: ' . $e->getMessage()
            ];
            
        } catch (\Exception $e) {
            Log::error('Google Ads connection test failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test fetch zero-click terms with limited results for safe testing.
     */
    public function testFetchZeroClickTerms(int $limit = 5): array
    {
        try {
            $config = $this->getConfig();
            
            if (empty($config['refresh_token'])) {
                return [
                    'success' => false,
                    'error' => 'Refresh token not found',
                    'terms' => []
                ];
            }

            // Initialize Google Ads client
            $oAuth2Credential = (new OAuth2TokenBuilder())
                ->withClientId($config['client_id'])
                ->withClientSecret($config['client_secret'])
                ->withRefreshToken($config['refresh_token'])
                ->build();

            $googleAdsClient = (new GoogleAdsClientBuilder())
                ->withOAuth2Credential($oAuth2Credential)
                ->withDeveloperToken($config['developer_token'])
                ->build();

            // Limited GAQL query for testing
            $query = "SELECT search_term_view.search_term, metrics.impressions, metrics.clicks 
                     FROM search_term_view 
                     WHERE campaign.id = {$config['campaign_id']} 
                     AND search_term_view.status = 'NONE' 
                     AND segments.date DURING LAST_7_DAYS 
                     LIMIT {$limit}";

            $request = new SearchGoogleAdsStreamRequest();
            $request->setCustomerId($config['customer_id']);
            $request->setQuery($query);

            $stream = $googleAdsClient->getGoogleAdsServiceClient()->searchStream($request);
            
            $terms = [];
            foreach ($stream->iterateAllElements() as $googleAdsRow) {
                $searchTerm = $googleAdsRow->getSearchTermView()->getSearchTerm();
                
                if (!empty($searchTerm)) {
                    $terms[] = [
                        'search_term' => $searchTerm,
                        'impressions' => $googleAdsRow->getMetrics()->getImpressions(),
                        'clicks' => $googleAdsRow->getMetrics()->getClicks(),
                    ];
                }
            }

            Log::info('Test fetch zero-click terms successful', [
                'limit' => $limit,
                'found' => count($terms)
            ]);

            return [
                'success' => true,
                'terms' => $terms
            ];

        } catch (ApiException $e) {
            Log::error('Test fetch zero-click terms failed', [
                'error' => $e->getMessage(),
                'status' => $e->getStatus()
            ]);
            
            return [
                'success' => false,
                'error' => 'API Error: ' . $e->getMessage(),
                'terms' => []
            ];
            
        } catch (\Exception $e) {
            Log::error('Test fetch zero-click terms failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'terms' => []
            ];
        }
    }
}