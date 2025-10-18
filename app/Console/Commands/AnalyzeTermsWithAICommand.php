<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AI\TermAnalyzer;
use App\Services\Telegram\NotificationService;
use App\Models\NewTermsNegative0Click;
use Exception;

class AnalyzeTermsWithAICommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'negative-keywords:analyze-terms {--batch-size=10 : Number of terms to analyze in one batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze stored search terms using AI to determine if they should be negative keywords';

    protected $termAnalyzer;
    protected $notificationService;

    public function __construct(TermAnalyzer $termAnalyzer, NotificationService $notificationService)
    {
        parent::__construct();
        $this->termAnalyzer = $termAnalyzer;
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting AI analysis of search terms...');
        
        try {
            $batchSize = (int) $this->option('batch-size');
            
            // Get terms that need AI analysis
            $terms = NewTermsNegative0Click::needsAiAnalysis()
                ->limit($batchSize)
                ->get();
            
            if ($terms->isEmpty()) {
                $this->info('No terms found that need AI analysis.');
                return 0;
            }
            
            $this->info("Found {$terms->count()} terms to analyze.");
            
            $analyzedCount = 0;
            $positiveCount = 0;
            
            foreach ($terms as $term) {
                try {
                    $this->line("Analyzing: {$term->terms}");
                    
                    // Analyze the term
                    $result = $this->termAnalyzer->analyzeTerm($term->terms);
                    
                    // Update the term with AI result
                    $term->update([
                        'hasil_cek_ai' => $result ? 'positive' : 'negative'
                    ]);
                    
                    if ($result) {
                        $positiveCount++;
                    }
                    
                    $analyzedCount++;
                    
                } catch (Exception $e) {
                    $this->error("Error analyzing term '{$term->terms}': " . $e->getMessage());
                    
                    // Mark as failed for retry
                    $term->update([
                        'hasil_cek_ai' => 'failed'
                    ]);
                }
            }
            
            $this->info("Analysis completed. Analyzed: {$analyzedCount}, Positive for negative keywords: {$positiveCount}");
            
            // Send Telegram notification
            $this->notificationService->sendAiAnalysisResult($analyzedCount, $positiveCount);
            
            return 0;
            
        } catch (Exception $e) {
            $this->error("Error during AI analysis: " . $e->getMessage());
            
            // Send error notification
            $this->notificationService->sendSystemError(
                'AI Terms Analysis',
                $e->getMessage()
            );
            
            return 1;
        }
    }
}
