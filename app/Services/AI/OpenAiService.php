<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiService
{
    private ?string $apiKey;

    private string $model;

    private string $baseUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', env('OPENAI_API_KEY', ''));
        $this->model = config('services.openai.model', env('OPENAI_MODEL', 'gpt-4o-mini'));
    }

    // call
    public function call(string $prompt, string $message = '')
    {
        try {
            if (! is_string($prompt) || ! is_string($message)) {
                throw new \InvalidArgumentException('Prompt and message must be strings');
            }

            $res = Http::retry(5, 750)
                ->timeout(120)
                ->connectTimeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->baseUrl, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $prompt],
                        ['role' => 'user', 'content' => $message],
                    ],
                ]);

            if (! $res->successful()) {
                throw new \Exception($res->body());
            }

            return $res->json();
        } catch (\Throwable $e) {
            Log::error('OpenAI API error', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
