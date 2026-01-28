<?php

namespace App\Services\SearchTermsAds;

use App\Models\SearchTerm;
use App\Services\AI\OpenAiService;
use Illuminate\Support\Facades\Log;

class CheckAiServices
{
    // check_search_terms_none
    public function check_search_terms_none($terms = [])
    {
        try {
            // prompt
            $prompt = <<<'PROMPT'
                Anda adalah asisten yang membantu mengklasifikasikan istilah penelusuran Google Ads untuk bisnis jasa pembuatan website.

                ### Tugas Anda:
                Saya akan mengirimkan beberapa kata istilah penelusuran dalam satu baris, dipisahkan tanda pipe |.
                Klasifikasikan setiap istilah menjadi salah satu dari 2:
                1. RELEVAN → jika istilah tersebut jelas berhubungan dengan niat membeli atau mencari jasa pembuatan website (misalnya: "jasa pembuatan website", "bikin website company profile", "harga bikin website sekolah").
                2. NEGATIF → jika istilah tersebut tidak berhubungan, terlalu umum, edukasi, spam, brand lain, atau berpotensi hanya riset tanpa niat membeli.

                ### Aturan Klasifikasi
                1. Jika istilah **berbahasa Inggris penuh** → NEGATIF (kecuali jelas orang Indonesia nyari jasa website, contoh "jasa website jogja" walau ada kata "website").
                2. Jika istilah mengandung kata kunci yang menunjukkan **jasa bikin website, company profile, sekolah, rumah sakit, portofolio, custom, landing page, web produk digital, biaya bikin website** → RELEVAN.
                3. Jika istilah terkait **belajar, tutorial, langkah-langkah, CSS, WordPress plugin, hosting, server, domain, belajar pemrograman, iklan, jualan online, marketplace, dropship, shopee, tokopedia, ecommerce** → NEGATIF.
                4. Jika istilah adalah **brand, nama perusahaan, kreatif agency lain, tools/software, hosting provider** → NEGATIF.
                5. Jika istilah ambigu seperti "company profile", "profil company", atau "website cms" → NEGATIF, kecuali jelas mengarah ke jasa pembuatan website.
                6. Jika istilah terlihat **kompetitor yang mencari harga website** dengan kata "menentukan harga website" → NEGATIF.
                7. Jika istilah hanya sebutan umum tanpa kata "jasa", "harga", "biaya", "buat", "bikin", "pembuatan", "membuat", "murah", "beli" → cenderung NEGATIF, kecuali konteks jelas relevan.
                8. Berikut ini NEGATIF karena tidak melayani: "bikin company profile" (karena tidak ada kata web)
                9. NEGATIF jika berkaitan dengan ai, misal: buat website menggunakan ai, membuat website ai, buat landing page dengan ai, buat web pakai ai, buat website dengan chatgpt, membuat website dengan gemini.
                10. RELEVAN jika mengandung nama perusahaan saya yaitu: velocity developer, velocitydeveloper, velocity web, velocity website, velocity web developer, velocity web design, velocity websites

                ### Format Input
                contoh: website desa | website taman bermain | jasa pembuatan website sekolah

                ### FORMAT OUTPUT (WAJIB & KETAT)
                - Output HARUS berupa JSON VALID
                - Struktur output berupa array of object, setiap object memiliki 2 properti:
                {
                    "term": "<istilah_asli>",
                    "status": "RELEVAN" | "NEGATIF"
                }
                - Urutan HARUS sama dengan input 
                - JANGAN menambah, menghapus, atau mengubah teks istilah
                - TANPA penjelasan, TANPA teks lain
            PROMPT;

            //jika terms kosong
            if (empty($terms)) {

                // dapatkan 40 search term dengan check_ai = null
                $searchTerms = SearchTerm::whereNull('check_ai')->take(20)->get();

                // jika tidak ada search term
                if ($searchTerms->isEmpty()) {
                    throw new \Exception('Tidak ada search term dengan check_ai = null');
                }

                // ambil semua nilai kolom 'term' dari searchTerms, dan jadikan string dengan pemisah pipe |
                $terms = $searchTerms->pluck('term')->implode('|');
                $count_terms = $searchTerms->count();
            } else {
                $count_terms = count($terms);
                $terms = implode('|', $terms);
            }

            //jika terms kosong
            if (!$terms || empty($terms)) {
                throw new \Exception('Terms kosong');
            }

            // kirim ke openai
            $openAiService = new OpenAiService;
            $response = $openAiService->call(
                $prompt,
                $terms
            );

            // jika response tidak valid
            if (! isset($response['choices'][0]['message']['content'])) {

                Log::info('openAiService respon', $response);

                throw new \Exception('Response tidak valid');
            }

            // parse response
            $responseContent = $response['choices'][0]['message']['content'];
            $jsonResponse = json_decode($responseContent, true);

            // jika json response tidak valid
            if (! is_array($jsonResponse) || count($jsonResponse) !== $count_terms) {
                throw new \Exception('JSON response tidak valid');
            }

            $results = [];

            // update search term
            foreach ($jsonResponse as $item) {
                $updated = SearchTerm::where('term', $item['term'])
                    ->update(['check_ai' => $item['status']]);

                $results[] = [
                    'term'        => $item['term'],
                    'status_ai'  => $item['status'],
                    'updated'    => $updated > 0, // true / false
                ];
            }

            // return results
            return [
                'success'       => true,
                'total'         => count($results),
                'results'       => $results,
                'response_ai'   => $jsonResponse
            ];
        } catch (\Exception $e) {

            Log::error('check_search_terms_none failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success'   => false,
                'message'   => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ];
        }
    }
}
