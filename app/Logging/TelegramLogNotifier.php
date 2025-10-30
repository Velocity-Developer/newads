<?php

namespace App\Logging;

use Illuminate\Support\Facades\Http;

class TelegramLogNotifier
{
    // Masukkan kredensial khusus log di sini (hanya di file ini)
    private string $botToken = '7347197196:AAHj2Stttl0gA8T20-pfYc3PMQmYApFSUOY';
    // Satu chat ID bisa string, multi chat ID array
    private array|string $chatId = ['-1003107021002'];

    private string $baseUrl;

    public function __construct(?string $botToken = null, array|string|null $chatId = null)
    {
        // Allow override jika mau, default tetap dari properti di atas
        $this->botToken = $botToken ?? $this->botToken;
        $this->chatId = $chatId ?? $this->chatId;
        $this->baseUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    public function isConfigured(): bool
    {
        $hasToken = !empty($this->botToken);
        $ids = is_array($this->chatId) ? $this->chatId : [$this->chatId];
        $hasChat = count(array_filter($ids)) > 0;
        return $hasToken && $hasChat;
    }

    public function sendMessage(string $message): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        $ids = is_array($this->chatId) ? $this->chatId : [$this->chatId];

        foreach ($ids as $id) {
            try {
                Http::asForm()->post("{$this->baseUrl}/sendMessage", [
                    'chat_id' => $id,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);
            } catch (\Throwable $e) {
                // Jangan mengganggu alur logging saat gagal kirim
            }
        }
    }
}