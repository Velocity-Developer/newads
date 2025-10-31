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
        $this->apiKey = config('services.openai.api_key', env('OPENAI_API_KEY', ''));
        $this->model = config('services.openai.model', env('OPENAI_MODEL', 'gpt-4o-mini'));
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

            // Hindari bias negatif saat API gagal
            return null;
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
        return '<<<PROMPT

        Istilah yang diuji: ' . $term . ' 
        Kamu adalah asisten yang bertugas memeriksa apakah sebuah istilah penelusuran Google Ads TIDAK RELEVAN atau RELEVAN untuk bisnis JASA PEMBUATAN WEBSITE.

        ---

        Tandai sebagai **Negatif** jika:
        - Istilah full Bahasa Inggris atau full asing, tanpa satu kata pun dalam Bahasa Indonesia.
        - Nama brand asing atau situs luar negeri seperti: wix, squarespace, shopify, godaddy, weebly.  
        **Kecuali jika platform tersebut adalah layanan iklan digital seperti: Google Ads, Facebook Ads, Instagram Ads, TikTok Ads, Meta Ads  
        dan hanya jika digabung dengan kata: jasa, pasang, iklan, murah, atau profesional.**
        - Mengandung karakter non-latin (misalnya: Jepang, Arab, Rusia, Hindi).
        - Frasa acak, tidak bermakna jelas, terlalu pendek atau terlalu umum tanpa konteks spesifik (contoh: "e commerce www", "online digital").
        - Frasa generik atau kombinasi kata populer yang tidak membentuk maksud pencarian yang jelas.
        - Typo berat, spam, atau tidak ada hubungan yang jelas dengan layanan jasa pembuatan website.
        - Istilah yang menunjukkan pengguna ingin membuat website sendiri atau belajar secara mandiri tanpa potensi menggunakan jasa, seperti:
        - tutorial
        - kursus
        - template gratis
        - auto generator
        - platform builder
        - Pencarian informatif yang secara eksplisit tidak berhubungan dengan layanan jasa pembuatan website, misalnya:
        - "template website blogger" (karena tidak melayani platform Blogger)
        - Mengandung kata yang bersifat vulgar, eksplisit, ilegal, atau tidak pantas, Penipuan, dan dilarang ajaran Islam, ilegal di Indonesia — termasuk atau menyerupai:
        - "slot", "judi", "judol", "phishing", "piahing", "hack", "hacker", "bokep", "porn", "porno", "seks", "ngentot"
        - Kata yang berkonotasi kontroversial atau bertentangan dengan norma sosial/etika, seperti: "dukun", "santet", "sihir", "ilmu hitam", dsb.
        - Penipuan bank BCA, BRI, BNI, Bank Mandiri, dan sejenisnya.
        - Menggunakan nama resmi lembaga keuangan seperti BCA, BRI, BNI, Bank Mandiri, untuk tujuan promosi tanpa izin berisiko tinggi menimbulkan penyalahgunaan identitas, pelanggaran merek dagang, atau penipuan.
        - Website pinjaman dana tunai jaminan BPKB. ini dilarang karena kami tidak melayani pembuatan web seperti pinjaman dana/ kredit/ apapun yang menyebabkan riba dalam islam.
        - Website Afiliasi, Multi Level Marketing (MLM), dan website skripsi. Karena kami tidak melayani web untuk bisnis/ bidang tersebut.
        - Istiah sejenis berikut ini kemungkinan tidak cocok dengan produk jasa web kami, contoh: ui ux agency Indonesia, ux design Jakarta, ux web.


        Tandai sebagai **Relevan** jika:
        - Mengandung kata dalam Bahasa Indonesia.
        - Menunjukkan minat terhadap pembuatan atau kualitas website, termasuk frasa seperti:
        - "situs web yang bagus"
        - "contoh website keren"
        - "desain website company profile"
        - Menyebut kata seperti: jasa, buat, bikin, pembuatan, website, web developer, toko online, katalog, landing page, desain web, domain, hosting, murah, profesional.
        - Ada indikasi pengguna ingin menggunakan layanan atau jasa.
        - Termasuk istilah seperti:
        - "website velocity" atau "velocity developer" (karena "velocity" adalah nama perusahaan lokal)
        - frasa yang mengandung "google ads" atau "adwords" bersama dengan kata jasa, murah, buat, bikin, layanan, pasang, iklan, atau nama kota (contoh: "jasa google ads murah", "jasa adwords jogja")
        - Menyebut platform iklan digital populer seperti: Google Ads, Facebook Ads, TikTok Ads, Instagram Ads, Meta Ads,  
        **asalkan digabung dengan kata: jasa, pasang, iklan, murah, atau profesional.**  
        Contoh: "jasa iklan tiktok", "pasang iklan google ads", "iklan facebook ads murah".
        - Istilah yang menunjukkan kebutuhan akan jasa, meskipun mengandung kata "pemula" atau kata kerja lain yang bisa diinterpretasikan sebagai proses belajar. Misal: belajar membuat blog
        - Frasa yang mengandung nama CMS (Content Management System) atau platform populer yang digunakan untuk pembuatan website (misalnya: wordpress, joomla, drupal), selama ada indikasi minat pembuatan atau jasa.
        - Istilah yang mungkin tampak informatif namun memiliki potensi kuat untuk mengarah pada kebutuhan jasa, seperti:
        - "cara membuat website sendiri" (karena pengguna mungkin mencari cara dan menyadari kesulitan, lalu beralih ke jasa)
        - "cara pakai wordpress" (karena pengguna mungkin kesulitan mengoperasikan WordPress dan akhirnya membutuhkan bantuan profesional untuk pembuatan atau pengelolaan)
        - "template wordpress" (karena berhubungan dengan CMS yang dilayani dan bisa mengarah ke jasa instalasi atau kustomisasi template)
        - Termasuk layanan/ produk berikut ini: penjualan domain, hosting, dan server. contohnya: penyedia server, jual hosting, jual domain, jasa graphic design, dsb.
        - Termasuk juga istilah berikut ini, karena sering kali terkait atau berpotensi mengarah pada layanan pembuatan website: jasa pembuatan video company profile, kode program membuat website, daftar web


        ---

        Instruksi Output:
        - Jawab hanya satu kata persis: Relevan atau Negatif.
        - Jangan menambahkan kata lain, tanda petik, atau mengulang instruksi.';
        
    }

    /**
     * Call OpenAI API.
     */
    private function callOpenAI(string $prompt): array
    {
        $response = Http::retry(5, 750, function ($exception, $request) {
                $resp = method_exists($request, 'response') ? $request->response : null;
                return $exception instanceof \Illuminate\Http\Client\ConnectionException
                    || ($resp && ($resp->serverError() || $resp->status() === 429));
            })
            ->timeout(120)
            ->connectTimeout(30)
            ->withToken($this->apiKey)
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->asJson()
            ->post($this->baseUrl, [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
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
        $raw = $response['choices'][0]['message']['content'] ?? '';
        $normalized = mb_strtolower(trim($raw));

        // Jika satu kata persis, langsung kembalikan
        if ($normalized === 'relevan' || $normalized === 'negatif') {
            return $normalized;
        }

        // Deteksi kata dengan word boundary, toleran terhadap kalimat panjang/tanda baca
        if (preg_match_all('/\b(relevan|negatif)\b/iu', $normalized, $matches, PREG_OFFSET_CAPTURE)) {
            // Ambil kemunculan pertama berdasarkan offset
            $firstMatchWord = $matches[1][0][0] ?? null;
            if ($firstMatchWord) {
                return mb_strtolower($firstMatchWord);
            }
        }

        // Jika tidak jelas, fallback aman ke 'relevan' dan log detail
        Log::warning('Unclear AI response, defaulting to relevan', [
            'response_raw' => $raw,
            'normalized' => $normalized,
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

    public function analyzeTermRaw(string $term): array
    {
        try {
            $prompt = $this->buildAnalysisPrompt($term);
            $response = $this->callOpenAI($prompt);

            $content = $response['choices'][0]['message']['content'] ?? '';

            Log::info('AI raw analysis', [
                'term' => $term,
                'content_raw' => $content,
            ]);

            return [
                'success' => true,
                'term' => $term,
                'content' => $content,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('AI raw analysis failed', [
                'term' => $term,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'term' => $term,
                'error' => $e->getMessage(),
            ];
        }
    }
    public function getModel(): string
    {
        return $this->model;
    }
}