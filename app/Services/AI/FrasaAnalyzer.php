<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FrasaAnalyzer
{
    private ?string $apiKey;
    private string $model;
    private string $baseUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', env('OPENAI_API_KEY', ''));
        $this->model = config('services.openai.model', env('OPENAI_MODEL', 'gpt-4o-mini'));
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function analyzeFrasa(string $frasa): ?string
    {
        try {
            $prompt = $this->buildPrompt($frasa);
            $response = $this->callOpenAI($prompt);
            $result = $this->parseResponse($response);

            Log::info('AI frasa analysis completed', ['frasa' => $frasa, 'result' => $result]);
            return $result;
        } catch (\Throwable $e) {
            Log::error('AI frasa analysis failed', ['frasa' => $frasa, 'error' => $e->getMessage()]);
            return null;
        }
    }

    private function buildPrompt(string $frasa): string
    {
        return '
        Frasa yang diuji: '.$frasa.' 
        Kamu adalah detektor bahasa yang sangat akurat. Tugasmu adalah menentukan apakah sebuah frasa ditulis dalam bahasa Indonesia atau bahasa luar negeri (non-Indonesia). Jawablah hanya dengan satu kata output: "INDONESIA" atau "LUAR".';
    }

    private function callOpenAI(string $prompt): array
    {
        $res = Http::withToken($this->apiKey)
            ->post($this->baseUrl, [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Anda adalah asisten yang mengklasifikasikan frasa.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0,
            ]);

        if (!$res->successful()) {
            throw new \RuntimeException("OpenAI error: {$res->status()} {$res->body()}");
        }

        return $res->json();
    }

    private function parseResponse(array $response): ?string
    {
        $content = $response['choices'][0]['message']['content'] ?? '';
        $normalized = strtolower(trim($content));
        if (in_array($normalized, ['indonesia', 'luar'], true)) {
            return $normalized;
        }
        // fallback heuristic: anything non-latin indicating foreign terms could be 'luar'
        return null;
    }
}