<?php

return [
    'telegram' => [
        'enabled' => env('TELEGRAM_ENABLED', false),
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        'base_url' => env('TELEGRAM_BASE_URL', 'https://api.telegram.org'),
    ],
];
