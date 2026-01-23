<?php

namespace App\Logging;

use Monolog\Logger;

class TelegramLoggerFactory
{
    public function __invoke(array $config)
    {
        $logger = new Logger('telegram');

        $level = \Monolog\Logger::toMonologLevel($config['level'] ?? 'error');

        // Gunakan notifier khusus log yang menyimpan token/chat di file sendiri
        $notifier = new \App\Logging\TelegramLogNotifier;

        $handler = new TelegramHandler($notifier, $level);
        $logger->pushHandler($handler);

        return $logger;
    }
}
