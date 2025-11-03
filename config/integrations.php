<?php

return [
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

    'velocity_ads' => [
        'api_url' => env('VELOCITY_ADS_API_URL', 'https://api.velocitydeveloper.com/new/adsfetch/test_dita.php'),
        // Simpan token mentah dari .env (tanpa "Bearer ")
        'api_token' => env('VELOCITY_ADS_API_TOKEN'),
        // Konfigurasi input negative keywords ke Velocity
        'input_api_url' => env('VELOCITY_ADS_INPUT_API_URL', 'https://api.velocitydeveloper.com/new/adsfetch/input_keywords_negative.php'),
        'input_match_types' => [
            'terms' => env('VELOCITY_ADS_MATCH_TYPE_TERMS', 'EXACT'),
            'frasa' => env('VELOCITY_ADS_MATCH_TYPE_FRASA', 'PHRASE'),
        ],
    ],
];