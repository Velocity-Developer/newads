<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use App\Services\Telegram\NotificationService;

class TelegramHandler extends AbstractProcessingHandler
{
    protected TelegramLogNotifier $notifier;

    public function __construct(TelegramLogNotifier $notifier, int $level = Logger::ERROR, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->notifier = $notifier;
    }

    protected function write(array $record): void
    {
        if (!$this->notifier->isConfigured()) {
            return;
        }

        $escape = fn(string $s) => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $message = "🧾 <b>Laravel Log</b>\n\n";
        $message .= "🔢 <b>Level:</b> {$escape($record['level_name'])}\n";
        $message .= "📝 <b>Message:</b> {$escape((string) $record['message'])}\n";
        $message .= "⏰ <b>Time:</b> " . $record['datetime']->format('Y-m-d H:i:s');

        if (!empty($record['context'])) {
            $contextStr = json_encode($record['context'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $message .= "\n🔎 <b>Context:</b> {$escape($contextStr)}";
        }

        if (!empty($record['extra'])) {
            $extraStr = json_encode($record['extra'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $message .= "\n➕ <b>Extra:</b> {$escape($extraStr)}";
        }

        $this->notifier->sendMessage($message);
    }
}