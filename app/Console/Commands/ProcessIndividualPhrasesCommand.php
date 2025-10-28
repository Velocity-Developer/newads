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
        $this->info('Starting to process individual phrases...');
        
        try {
            $batchSize = (int) $this->option('batch-size');
            
            // Ambil terms dengan AI hasil negatif yang belum dipecah menjadi frasa
            $terms = NewTermsNegative0Click::where('hasil_cek_ai', NewTermsNegative0Click::HASIL_AI_NEGATIF)
                ->whereDoesntHave('frasa')
                ->limit($batchSize)
                ->get();
            
            if ($terms->isEmpty()) {
                $this->info('No terms found that need phrase processing.');
                $this->info('Skipping Google Ads input; use negative-keywords:input-velocity for submission.');
                return 0;
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
                                'status_input_google' => null,
                                'retry_count' => 0,
                                'notif_telegram' => null
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
            $this->info('Google Ads input removed. Use negative-keywords:input-velocity to submit phrases.');
            return 0;
            
        } catch (Exception $e) {
            $this->error("Error during phrase processing: " . $e->getMessage());
            return 1;
        }
    }
}
