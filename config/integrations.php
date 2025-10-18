<?php

return [
    'google_ads' => [
        'client_id' => env('GOOGLE_ADS_CLIENT_ID'),
        'client_secret' => env('GOOGLE_ADS_CLIENT_SECRET'),
        'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
        'customer_id' => env('GOOGLE_ADS_CUSTOMER_ID'),
        'campaign_id' => env('GOOGLE_ADS_CAMPAIGN_ID'),
        // Gunakan path dari .env jika ada, kalau tidak pakai default di storage private
        'refresh_token_path' => env(
            'GOOGLE_ADS_REFRESH_TOKEN_PATH',
            storage_path('app/private/google_ads/refresh_token.txt')
        ),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        // Dukungan multi chat ID via comma-separated list di .env: "12345,67890"
        'chat_id' => array_filter(explode(',', env('TELEGRAM_CHAT_ID', ''))),
    ],

    'greeting_ads' => [
        'api_key' => env('GREETING_ADS_API_KEY'),
        'api_url' => env('GREETING_ADS_API_URL'),
        'webhook_url' => env('GREETING_ADS_WEBHOOK_URL'),
        'telegram_bot' => env('GREETING_ADS_TELEGRAM_BOT'),
        'telegram_chat' => env('GREETING_ADS_TELEGRAM_CHAT'),
        'telegram_notif' => env('GREETING_ADS_TELEGRAM_NOTIF', 'off'), // 'on' or 'off'
    ],
];