<?php

namespace App\Services\AI;

use App\Models\NewTermsNegative0Click;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TermAnalyzer
{
    private ?string $apiKey;
    private string $model;
    private string $baseUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', env('OPENAI_API_KEY'));
        $this->model = config('services.openai.model', env('OPENAI_MODEL', 'gpt-4'));
    }

    /**
     * Analyze a search term to determine if it's relevant or negative.
     */
    public function analyzeTerm(string $term): string
    {
        try {
            $prompt = $this->buildAnalysisPrompt($term);
            $response = $this->callOpenAI($prompt);
            
            $result = $this->parseResponse($response);
            
            Log::info('AI analysis completed', [
                'term' => $term,
                'result' => $result
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('AI analysis failed', [
                'term' => $term,
                'error' => $e->getMessage()
            ]);
            
            // Return 'relevan' as default to be safe
            return 'relevan';
        }
    }

    /**
     * Analyze multiple terms in batch.
     */
    public function analyzeTermsBatch(array $terms): array
    {
        $results = [];
        
        foreach ($terms as $term) {
            $results[$term] = $this->analyzeTerm($term);
            
            // Add small delay to respect API rate limits
            usleep(100000); // 0.1 second
        }
        
        return $results;
    }

    /**
     * Process terms from database that need AI analysis.
     */
    public function processTermsNeedingAnalysis(int $limit = 50): int
    {
        $terms = NewTermsNegative0Click::needsAiAnalysis()
            ->limit($limit)
            ->get();
        
        $processed = 0;
        
        foreach ($terms as $termRecord) {
            try {
                $result = $this->analyzeTerm($termRecord->terms);
                
                $termRecord->update([
                    'hasil_cek_ai' => $result
                ]);
                
                $processed++;
                
            } catch (\Exception $e) {
                Log::error('Failed to process term for AI analysis', [
                    'term_id' => $termRecord->id,
                    'term' => $termRecord->terms,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        Log::info('Processed terms for AI analysis', [
            'processed' => $processed,
            'total_found' => $terms->count()
        ]);
        
        return $processed;
    }

    /**
     * Build the analysis prompt for OpenAI.
     */
    private function buildAnalysisPrompt(string $term): string
    {
        return "Analisis search term berikut untuk menentukan apakah term ini relevan atau negatif untuk bisnis web development/pembuatan website.

Search term: \"{$term}\"

Kriteria:
- RELEVAN: Jika term menunjukkan minat genuine untuk jasa web development, pembuatan website, atau layanan terkait
- NEGATIF: Jika term menunjukkan pencarian yang tidak relevan, spam, atau tidak menunjukkan intent pembelian yang serius

Contoh RELEVAN:
- \"buat website toko online\"
- \"jasa pembuatan web\"
- \"web developer jakarta\"

Contoh NEGATIF:
- \"download template gratis\"
- \"cara buat website sendiri\"
- \"tutorial html css\"

Jawab hanya dengan kata: \"relevan\" atau \"negatif\"";
    }

    /**
     * Call OpenAI API.
     */
    private function callOpenAI(string $prompt): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($this->baseUrl, [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 10,
            'temperature' => 0.1,
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenAI API request failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Parse OpenAI response to extract the result.
     */
    private function parseResponse(array $response): string
    {
        $content = $response['choices'][0]['message']['content'] ?? '';
        $content = strtolower(trim($content));
        
        // Clean up the response
        $content = preg_replace('/[^a-z]/', '', $content);
        
        if (in_array($content, ['relevan', 'negatif'])) {
            return $content;
        }
        
        // If response is unclear, default to 'relevan' to be safe
        Log::warning('Unclear AI response, defaulting to relevan', [
            'response' => $content,
            'full_response' => $response
        ]);
        
        return 'relevan';
    }

    /**
     * Check if the service is properly configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->model);
    }

    /**
     * Test the AI service with a sample term.
     */
    public function testService(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'AI service not properly configured'
            ];
        }
        
        try {
            $testTerm = 'jasa pembuatan website';
            $result = $this->analyzeTerm($testTerm);
            
            return [
                'success' => true,
                'test_term' => $testTerm,
                'result' => $result
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}