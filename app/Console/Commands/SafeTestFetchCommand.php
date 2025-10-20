<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleAds\SearchTermFetcher;
use Illuminate\Support\Facades\Log;

class SafeTestFetchCommand extends Command
{
    protected $signature = 'test:safe-fetch {--limit=5 : Jumlah maksimal terms untuk test}';
    protected $description = 'Test fetch search terms tanpa menyimpan ke database';

    public function handle()
    {
        $this->info('🔍 Testing Search Terms Fetch (Safe Mode)...');
        $this->newLine();

        $fetcher = new SearchTermFetcher();
        $limit = (int) $this->option('limit');
        
        try {
            // Test fetch dengan limit kecil
            $this->info("📥 Fetching up to {$limit} search terms...");
            
            $result = $fetcher->testFetchZeroClickTerms($limit);
            
            if (!$result['success']) {
                $this->error('❌ Fetch failed: ' . $result['error']);
                return 1;
            }
            
            $terms = $result['terms'];
            $this->info("✅ Successfully fetched " . count($terms) . " terms");
            
            if (empty($terms)) {
                $this->warn('⚠️ No terms found. This might be normal if:');
                $this->warn('- Campaign has no search term data');
                $this->warn('- Date range has no activity');
                $this->warn('- All terms are filtered out');
                return 0;
            }
            
            // Display sample terms
            $this->newLine();
            $this->info('📋 Sample terms found:');
            
            $tableData = [];
            foreach (array_slice($terms, 0, 10) as $index => $term) {
                $tableData[] = [
                    $index + 1,
                    $term['search_term'] ?? 'N/A',
                    $term['impressions'] ?? 'N/A',
                    $term['clicks'] ?? 'N/A',
                ];
            }
            
            $this->table(['#', 'Search Term', 'Impressions', 'Clicks'], $tableData);
            
            if (count($terms) > 10) {
                $this->info('... and ' . (count($terms) - 10) . ' more terms');
            }
            
            // Test filtering
            $this->newLine();
            $this->info('🔍 Testing term filtering...');
            
            $excludedWords = ['jasa', 'harga', 'buat', 'bikin', 'murah', 'pembuatan', 'biaya', 'beli', 'pesan', 'velocity'];
            $filteredTerms = [];
            
            foreach ($terms as $term) {
                $searchTerm = strtolower($term['search_term'] ?? '');
                $isExcluded = false;
                
                foreach ($excludedWords as $excludedWord) {
                    if (strpos($searchTerm, strtolower($excludedWord)) !== false) {
                        $isExcluded = true;
                        break;
                    }
                }
                
                if (!$isExcluded) {
                    $filteredTerms[] = $term;
                }
            }
            
            $excludedCount = count($terms) - count($filteredTerms);
            $this->info("📊 Filtering results:");
            $this->info("- Total terms: " . count($terms));
            $this->info("- Excluded terms: {$excludedCount}");
            $this->info("- Terms to process: " . count($filteredTerms));
            
            if ($excludedCount > 0) {
                $this->info("✅ Filtering working correctly");
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            Log::error('Safe fetch test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        $this->newLine();
        $this->info('🎉 Safe fetch test completed successfully!');
        $this->info('Next step: Run full system with php artisan fetch:zero-click-terms');
        
        return 0;
    }
}