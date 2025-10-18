<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleAds\SearchTermFetcher;
use App\Services\Telegram\NotificationService;
use Exception;

class FetchZeroClickTermsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'negative-keywords:fetch-terms {--limit=100 : Maximum number of terms to fetch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch zero-click search terms from Google Ads and store them for analysis';

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
        $this->info('Starting to fetch zero-click terms from Google Ads...');
        
        try {
            $limit = (int) $this->option('limit');
            
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
            $this->notificationService->sendNewTermsFetched($storedCount);
            
            return 0;
            
        } catch (Exception $e) {
            $this->error("Error fetching zero-click terms: " . $e->getMessage());
            
            // Send error notification
            $this->notificationService->sendSystemError(
                'Fetch Zero-Click Terms',
                $e->getMessage()
            );
            
            return 1;
        }
    }
}
