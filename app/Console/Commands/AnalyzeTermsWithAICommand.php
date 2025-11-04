<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AI\TermAnalyzer;
use App\Services\Telegram\NotificationService;
use App\Models\NewTermsNegative0Click;
use Illuminate\Support\Facades\Log;
use Exception;

class AnalyzeTermsWithAICommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'negative-keywords:analyze-terms {--batch-size=0 : Number of terms to analyze in one batch (0=all)}';

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
        // Log::info('Starting AI analysis of search terms...');
        
        try {
            $batchSize = (int) $this->option('batch-size');

            // Debug: Check total records first
            $totalRecords = NewTermsNegative0Click::count();
            Log::info("Total records in database: {$totalRecords}");

            // Debug: Check records with null hasil_cek_ai
            $nullAiCount = NewTermsNegative0Click::whereNull('hasil_cek_ai')->count();
            Log::info("Records with null hasil_cek_ai: {$nullAiCount}");

            // Debug: Check records with status_input_google conditions
            $statusCount = NewTermsNegative0Click::whereIn('status_input_google', [null, 'gagal'])->count();
            Log::info("Records with status_input_google null or gagal: {$statusCount}");

            // Get terms that need AI analysis
            $query = NewTermsNegative0Click::needsAiAnalysis();
            if ($batchSize > 0) {
                $query->limit($batchSize);
            }
            $terms = $query->get();
            
            // Log::info("Records matching needsAiAnalysis scope: {$terms->count()}");
            
            if ($terms->isEmpty()) {
                Log::info('No terms found that need AI analysis.');
                return 0;
            }
            
            // Log::info("Found {$terms->count()} terms to analyze.");
            
            $analyzedCount = 0;
            $positiveCount = 0;
            $relevanTerms = [];
            $negatifTerms = [];

            foreach ($terms as $term) {
                try {
                    Log::info("Analyzing: {$term->terms}");

                    // Kembali memakai hasil terstruktur (relevan/negatif)
                    $result = $this->termAnalyzer->analyzeTerm($term->terms); // 'relevan' / 'negatif'
                    // Log::info("AI Result: {$result}");
                    // Simpan ke database
                    $term->update([
                        'hasil_cek_ai' => $result
                    ]);

                    // Hitung ringkasan
                    if ($result === 'relevan') {
                        $relevanTerms[] = $term->terms;
                    } elseif ($result === 'negatif') {
                        $negatifTerms[] = $term->terms;
                        $positiveCount++; // tetap dipakai untuk jumlah kandidat negatif
                    } else {
                        Log::info("Unclear AI result for: {$term->terms} => '{$result}'");
                    }

                    $analyzedCount++;

                } catch (Exception $e) {
                    Log::error("Error analyzing term '{$term->terms}': " . $e->getMessage());
                }
            }

            $displayLimit = 20;
            // Log::info("Analysis completed. Total: {$analyzedCount}, Relevan: " . count($relevanTerms) . ", Negatif: " . count($negatifTerms));
            if (!empty($relevanTerms)) {
                Log::info("Relevan terms (max {$displayLimit}): " . implode(', ', array_slice($relevanTerms, 0, $displayLimit)));   
            }
            if (!empty($negatifTerms)) {
                Log::info("Negatif terms (max {$displayLimit}): " . implode(', ', array_slice($negatifTerms, 0, $displayLimit)));
            }

            return 0;
            
        } catch (Exception $e) {
            Log::error("Error during AI analysis: " . $e->getMessage());
            
            // Send error notification
            // $this->notificationService->notifySystemError(
            //     'AI Terms Analysis',
            //     $e->getMessage()
            // );
            
            return 1;
        }
    }
}
