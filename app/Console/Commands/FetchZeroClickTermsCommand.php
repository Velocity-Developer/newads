<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleAds\SearchTermFetcher;
use App\Services\Telegram\NotificationService;
use Illuminate\Support\Facades\Log;
use Exception;

class FetchZeroClickTermsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'negative-keywords:fetch-terms {--limit=0 : Maximum number of terms to fetch (0=all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch zero-click search terms from External Ads API and store them for analysis';

    protected $searchTermFetcher;
    protected $notificationService;

    public function __construct(SearchTermFetcher $searchTermFetcher, NotificationService $notificationService)
    {
        parent::__construct();
        $this->searchTermFetcher = $searchTermFetcher;
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to fetch zero-click terms from External Ads API...');
        
        try {
            $limit = (int) $this->option('limit');

            // Tambahkan visibilitas konfigurasi sebelum fetch
            $cfg = $this->searchTermFetcher->getConfig();
            Log::info('🔍 Preflight fetch zero-click terms', [
                'limit' => $limit,
                'api_url' => $cfg['api_url'] ?? null,
                'token_present' => !empty($cfg['api_token']),
            ]);
            
            // Fetch zero-click terms
            $terms = $this->searchTermFetcher->fetchZeroClickTerms($limit);
            
            if (empty($terms)) {
                $this->info('No zero-click terms found.');
                return 0;
            }
            
            // Store terms in database
            $storedCount = $this->searchTermFetcher->storeZeroClickTerms($terms);
            
            $this->info("Successfully fetched and stored {$storedCount} zero-click terms.");
            
            // Send Telegram notification
            // $this->notificationService->notifyNewTermsFetched($storedCount, count($terms));
            
            return 0;
            
        } catch (Exception $e) {
            Log::error("❌ Error fetching zero-click terms", [
                'exception_class' => get_class($e),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            Log::error($e->getTraceAsString());
            // Send error notification
            // $this->notificationService->notifySystemError(
            //     'Fetch Zero-Click Terms',
            //     $e->getMessage()
            // );
            
            return 1;
        }
    }
}
