<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleAds\SearchTermFetcher;
use App\Services\Telegram\NotificationService;
use App\Models\NewTermsNegative0Click;
use App\Models\NewFrasaNegative;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessIndividualPhrasesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'negative-keywords:process-phrases {--batch-size=0 : Number of terms to process in one batch (0=all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process individual phrases from approved terms (split only, no Google Ads input)';

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
        try {
            $batchSize = (int) $this->option('batch-size');

            // Ambil terms dengan AI hasil negatif yang belum dipecah menjadi frasa
            $query = NewTermsNegative0Click::where('hasil_cek_ai', NewTermsNegative0Click::HASIL_AI_NEGATIF)
                ->whereDoesntHave('frasa');

            if ($batchSize > 0) {
                $query->limit($batchSize);
            }

            $terms = $query->get();
            
            if ($terms->isEmpty()) {
                Log::info('No terms found that need phrase processing.');
                Log::info('Skipping Google Ads input; use negative-keywords:input-velocity for submission.');
                return 0;
            }

            Log::info("Found {$terms->count()} terms to break down into phrases.");
            
            $totalPhrasesCreated = 0;
            
            foreach ($terms as $term) {
                try {
                    // Extract allowed phrases from the term
                    $allowedPhrases = NewFrasaNegative::extractAllowedFrasa($term->terms);
                    
                    if (empty($allowedPhrases)) {
                        // Log::info("No valid phrases found in: {$term->terms}");
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
                                'status_input_google' => null,
                                'retry_count' => 0,
                                'notif_telegram' => null,
                                'campaign_id' => $term->campaign_id, // propagate campaign_id
                            ]);
                            
                            $phrasesCreated++;
                        }
                    }
                    
                    $totalPhrasesCreated += $phrasesCreated;
                    // Log::info("Created {$phrasesCreated} new phrases from: {$term->terms}");
                    
                } catch (Exception $e) {
                    Log::error("Error processing term '{$term->terms}': " . $e->getMessage());
                }
            }
            
            Log::info("Phrase extraction completed. Total new phrases created: {$totalPhrasesCreated}");
            // Log::info('Google Ads input removed. Use negative-keywords:input-velocity to submit phrases.');
            return 0;
            
        } catch (Exception $e) {
            Log::error("Error during phrase processing: " . $e->getMessage());
            return 1;
        }
    }
}
