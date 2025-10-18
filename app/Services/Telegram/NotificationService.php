<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    private ?string $botToken;
    private ?string $chatId;
    private string $baseUrl;

    public function __construct()
    {
        $this->botToken = config('integrations.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
        $this->chatId = config('integrations.telegram.chat_id', env('TELEGRAM_CHAT_ID'));
        $this->baseUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Send a notification message to Telegram.
     */
    public function sendMessage(string $message, bool $disablePreview = true): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('Telegram service not configured, skipping notification');
            return false;
        }

        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => $disablePreview,
            ]);

            if ($response->successful()) {
                Log::info('Telegram notification sent successfully');
                return true;
            } else {
                Log::error('Telegram API error', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Failed to send Telegram notification', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send notification about successful negative keyword addition.
     */
    public function notifyNegativeKeywordSuccess(string $keyword, string $matchType = 'EXACT'): bool
    {
        $message = "✅ <b>Negative Keyword Added</b>\n\n";
        $message .= "🔑 <b>Keyword:</b> {$keyword}\n";
        $message .= "📝 <b>Match Type:</b> {$matchType}\n";
        $message .= "⏰ <b>Time:</b> " . now()->format('Y-m-d H:i:s');

        return $this->sendMessage($message);
    }

    /**
     * Send notification about failed negative keyword addition.
     */
    public function notifyNegativeKeywordFailure(string $keyword, string $error, int $retryCount = 0): bool
    {
        $message = "❌ <b>Negative Keyword Failed</b>\n\n";
        $message .= "🔑 <b>Keyword:</b> {$keyword}\n";
        $message .= "❗ <b>Error:</b> {$error}\n";
        $message .= "🔄 <b>Retry Count:</b> {$retryCount}\n";
        $message .= "⏰ <b>Time:</b> " . now()->format('Y-m-d H:i:s');

        return $this->sendMessage($message);
    }

    /**
     * Send notification about batch processing results.
     */
    public function notifyBatchResults(string $operation, int $successful, int $failed, int $total): bool
    {
        $message = "📊 <b>{$operation} - Batch Results</b>\n\n";
        $message .= "✅ <b>Successful:</b> {$successful}\n";
        $message .= "❌ <b>Failed:</b> {$failed}\n";
        $message .= "📈 <b>Total:</b> {$total}\n";
        $message .= "📊 <b>Success Rate:</b> " . round(($successful / max($total, 1)) * 100, 1) . "%\n";
        $message .= "⏰ <b>Time:</b> " . now()->format('Y-m-d H:i:s');

        return $this->sendMessage($message);
    }

    /**
     * Send notification about new terms fetched.
     */
    public function notifyNewTermsFetched(int $newTerms, int $totalFetched): bool
    {
        $message = "🔍 <b>New Search Terms Fetched</b>\n\n";
        $message .= "🆕 <b>New Terms:</b> {$newTerms}\n";
        $message .= "📊 <b>Total Fetched:</b> {$totalFetched}\n";
        $message .= "⏰ <b>Time:</b> " . now()->format('Y-m-d H:i:s');

        return $this->sendMessage($message);
    }

    /**
     * Send notification about AI analysis results.
     */
    public function notifyAiAnalysisResults(int $analyzed, int $negative, int $relevant): bool
    {
        $message = "🤖 <b>AI Analysis Completed</b>\n\n";
        $message .= "📊 <b>Analyzed:</b> {$analyzed}\n";
        $message .= "❌ <b>Negative:</b> {$negative}\n";
        $message .= "✅ <b>Relevant:</b> {$relevant}\n";
        $message .= "📈 <b>Negative Rate:</b> " . round(($negative / max($analyzed, 1)) * 100, 1) . "%\n";
        $message .= "⏰ <b>Time:</b> " . now()->format('Y-m-d H:i:s');

        return $this->sendMessage($message);
    }

    /**
     * Send notification about system errors.
     */
    public function notifySystemError(string $operation, string $error): bool
    {
        $message = "🚨 <b>System Error</b>\n\n";
        $message .= "⚙️ <b>Operation:</b> {$operation}\n";
        $message .= "❗ <b>Error:</b> {$error}\n";
        $message .= "⏰ <b>Time:</b> " . now()->format('Y-m-d H:i:s');

        return $this->sendMessage($message);
    }

    /**
     * Send daily summary notification.
     */
    public function notifyDailySummary(array $stats): bool
    {
        $message = "📅 <b>Daily Summary - " . now()->format('Y-m-d') . "</b>\n\n";
        
        if (isset($stats['terms_fetched'])) {
            $message .= "🔍 <b>Terms Fetched:</b> {$stats['terms_fetched']}\n";
        }
        
        if (isset($stats['ai_analyzed'])) {
            $message .= "🤖 <b>AI Analyzed:</b> {$stats['ai_analyzed']}\n";
        }
        
        if (isset($stats['negative_keywords_added'])) {
            $message .= "❌ <b>Negative Keywords Added:</b> {$stats['negative_keywords_added']}\n";
        }
        
        if (isset($stats['phrases_processed'])) {
            $message .= "📝 <b>Phrases Processed:</b> {$stats['phrases_processed']}\n";
        }
        
        if (isset($stats['errors'])) {
            $message .= "🚨 <b>Errors:</b> {$stats['errors']}\n";
        }

        return $this->sendMessage($message);
    }

    /**
     * Check if the service is properly configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->botToken) && !empty($this->chatId);
    }

    /**
     * Test the Telegram service.
     */
    public function testService(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Telegram service not properly configured'
            ];
        }

        try {
            $testMessage = "🧪 <b>Test Notification</b>\n\nTelegram service is working correctly!\n⏰ " . now()->format('Y-m-d H:i:s');
            $success = $this->sendMessage($testMessage);

            return [
                'success' => $success,
                'message' => $success ? 'Test notification sent successfully' : 'Failed to send test notification'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get bot information.
     */
    public function getBotInfo(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Bot token not configured'
            ];
        }

        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/getMe");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'bot_info' => $response->json()['result']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to get bot info: ' . $response->body()
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}