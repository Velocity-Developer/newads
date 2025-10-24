<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleAds\SearchTermFetcher;
use App\Services\Telegram\NotificationService;
use App\Models\NewTermsNegative0Click;
use Exception;

class InputNegativeKeywordsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'negative-keywords:input-google {--batch-size=5 : Number of terms to process in one batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Input approved negative keywords to Google Ads campaigns';

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
        $this->warn('Google Ads integration is disabled; skipping negative keyword input.');
        return 0;

        $this->info('Starting to input negative keywords to Google Ads...');
        
        try {
            $batchSize = (int) $this->option('batch-size');
            
            // Get terms that need Google Ads input (AI result is 'relevan')
            $terms = NewTermsNegative0Click::needsGoogleAdsInput()
                ->where('hasil_cek_ai', 'relevan')
                ->limit($batchSize)
                ->get();
            
            if ($terms->isEmpty()) {
                $this->info('No terms found that need Google Ads input.');
                return 0;
            }
            
            $this->info("Found {$terms->count()} terms to input to Google Ads.");
            
            $successCount = 0;
            $failedCount = 0;
            $successTerms = [];
            
            foreach ($terms as $term) {
                try {
                    $this->line("Processing: {$term->terms}");
                    
                    // Add negative keyword to Google Ads
                    $success = $this->searchTermFetcher->addNegativeKeyword($term->terms);
                    
                    if ($success) {
                        // Update status to success
                        $term->update([
                            'status_input_google' => NewTermsNegative0Click::STATUS_BERHASIL,
                            'notif_telegram' => NewTermsNegative0Click::NOTIF_BERHASIL
                        ]);
                        
                        $successCount++;
                        $successTerms[] = $term->terms;
                        $this->info("✓ Successfully added: {$term->terms}");
                        
                    } else {
                        // Update status to failed and increment retry
                        $term->incrementRetry();
                        $term->update([
                            'status_input_google' => NewTermsNegative0Click::STATUS_GAGAL
                        ]);
                        
                        $failedCount++;
                        $failedTerms[] = $term->terms;
                        $this->error("✗ Failed to add: {$term->terms}");
                    }
                    
                } catch (Exception $e) {
                    $this->error("Error processing term '{$term->terms}': " . $e->getMessage());
                    
                    // Update status to failed and increment retry
                    $term->incrementRetry();
                    $term->update([
                        'status_input_google' => NewTermsNegative0Click::STATUS_GAGAL
                    ]);
                    
                    $failedCount++;
                    $failedTerms[] = $term->terms;
                }
            }
            
            $this->info("Input completed. Success: {$successCount}, Failed: {$failedCount}");
            
            // Send Telegram notifications
            if ($successCount > 0) {
                $list = implode(', ', array_slice($successTerms, 0, 50));
                $message = "✅ New Ads berhasil input kata kunci negatif ke Google Ads: {$successCount}\nTerms: {$list}";
                $this->notificationService->sendNegativeKeywordSuccess($message);
            }
            
            if ($failedCount > 0) {
                $list = implode(', ', array_slice($failedTerms, 0, 50));
                $message = "❌ New Ads gagal input kata kunci negatif ke Google Ads: {$failedCount}\nTerms: {$list}";
                $this->notificationService->sendNegativeKeywordFailed($message);
            }
            
            return 0;
            
        } catch (Exception $e) {
            $this->error("Error during Google Ads input: " . $e->getMessage());
            
            // Send error notification
            $this->notificationService->sendSystemError(
                'Google Ads Input',
                $e->getMessage()
            );
            
            return 1;
        }
    }
}
