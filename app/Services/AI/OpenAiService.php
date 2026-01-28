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
            $res = Http::retry(5, 750, function ($exception, $request) {
                $resp = method_exists($request, 'response') ? $request->response : null;

                return $exception instanceof \Illuminate\Http\Client\ConnectionException
                    || ($resp && ($resp->serverError() || $resp->status() === 429));
            })
                ->timeout(120)
                ->connectTimeout(30)
                // ->withToken($this->apiKey)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->asJson()
                ->post($this->baseUrl, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $prompt],
                        ['role' => 'user', 'content' => $message],
                    ],
                ]);

            if (! $res->successful()) {
                throw new \Exception('OpenAI API request failed: '.$res->body());
            }
        } catch (\Exception $e) {
            Log::error('OpenAI API error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return $res->json();
    }
}
