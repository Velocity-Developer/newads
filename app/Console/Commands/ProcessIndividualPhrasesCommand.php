<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleAds\SearchTermFetcher;
use App\Services\Telegram\NotificationService;
use App\Models\NewTermsNegative0Click;
use App\Models\NewFrasaNegative;
use Exception;

class ProcessIndividualPhrasesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'negative-keywords:process-phrases {--batch-size=10 : Number of terms to process in one batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process individual phrases from approved terms and input them to Google Ads';

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
        $this->info('Starting to process individual phrases...');
        
        try {
            $batchSize = (int) $this->option('batch-size');
            
            // Get successfully processed terms that haven't been broken down into phrases yet
            $terms = NewTermsNegative0Click::where('status_input_google', 'success')
                ->whereDoesntHave('phrases')
                ->limit($batchSize)
                ->get();
            
            if ($terms->isEmpty()) {
                $this->info('No terms found that need phrase processing.');
                
                // Check for phrases that need Google Ads input
                $phrasesToProcess = NewFrasaNegative::needsGoogleAdsInput()->limit($batchSize)->get();
                
                if ($phrasesToProcess->isEmpty()) {
                    $this->info('No phrases found that need Google Ads input.');
                    return 0;
                }
                
                return $this->processPhrasesToGoogleAds($phrasesToProcess);
            }
            
            $this->info("Found {$terms->count()} terms to break down into phrases.");
            
            $totalPhrasesCreated = 0;
            
            foreach ($terms as $term) {
                try {
                    $this->line("Processing term: {$term->terms}");
                    
                    // Extract allowed phrases from the term
                    $allowedPhrases = NewFrasaNegative::extractAllowedFrasa($term->terms);
                    
                    if (empty($allowedPhrases)) {
                        $this->line("No valid phrases found in: {$term->terms}");
                        continue;
                    }
                    
                    $phrasesCreated = 0;
                    
                    foreach ($allowedPhrases as $phrase) {
                        // Check if phrase already exists to avoid duplicates
                        $existingPhrase = NewFrasaNegative::where('frasa', $phrase)->first();
                        
                        if (!$existingPhrase) {
                            NewFrasaNegative::create([
                                'frasa' => $phrase,
                                'parent_term_id' => $term->id,
                                'status_input_google' => 'pending',
                                'retry_count' => 0,
                                'notif_telegram' => false
                            ]);
                            
                            $phrasesCreated++;
                        }
                    }
                    
                    $totalPhrasesCreated += $phrasesCreated;
                    $this->info("Created {$phrasesCreated} new phrases from: {$term->terms}");
                    
                } catch (Exception $e) {
                    $this->error("Error processing term '{$term->terms}': " . $e->getMessage());
                }
            }
            
            $this->info("Phrase extraction completed. Total new phrases created: {$totalPhrasesCreated}");
            
            // Now process phrases that need Google Ads input
            $phrasesToProcess = NewFrasaNegative::needsGoogleAdsInput()->limit($batchSize)->get();
            
            if (!$phrasesToProcess->isEmpty()) {
                $this->info("Processing {$phrasesToProcess->count()} phrases for Google Ads input...");
                return $this->processPhrasesToGoogleAds($phrasesToProcess);
            }
            
            return 0;
            
        } catch (Exception $e) {
            $this->error("Error during phrase processing: " . $e->getMessage());
            
            // Send error notification
            $this->notificationService->sendSystemError(
                'Process Individual Phrases',
                $e->getMessage()
            );
            
            return 1;
        }
    }
    
    private function processPhrasesToGoogleAds($phrases)
    {
        $successCount = 0;
        $failedCount = 0;
        
        foreach ($phrases as $phrase) {
            try {
                $this->line("Adding phrase to Google Ads: {$phrase->frasa}");
                
                // Add negative keyword to Google Ads
                $success = $this->searchTermFetcher->addNegativeKeyword($phrase->frasa);
                
                if ($success) {
                    // Update status to success
                    $phrase->update([
                        'status_input_google' => 'success',
                        'notif_telegram' => false
                    ]);
                    
                    $successCount++;
                    $this->info("✓ Successfully added phrase: {$phrase->frasa}");
                    
                } else {
                    // Update status to failed and increment retry
                    $phrase->incrementRetry();
                    $phrase->update([
                        'status_input_google' => 'failed'
                    ]);
                    
                    $failedCount++;
                    $this->error("✗ Failed to add phrase: {$phrase->frasa}");
                }
                
            } catch (Exception $e) {
                $this->error("Error processing phrase '{$phrase->frasa}': " . $e->getMessage());
                
                // Update status to failed and increment retry
                $phrase->incrementRetry();
                $phrase->update([
                    'status_input_google' => 'failed'
                ]);
                
                $failedCount++;
            }
        }
        
        $this->info("Phrase processing completed. Success: {$successCount}, Failed: {$failedCount}");
        
        // Send Telegram notifications
        if ($successCount > 0) {
            $this->notificationService->sendNegativeKeywordSuccess($successCount, 'phrases');
        }
        
        if ($failedCount > 0) {
            $this->notificationService->sendNegativeKeywordFailed($failedCount, 'phrases');
        }
        
        return 0;
    }
}
