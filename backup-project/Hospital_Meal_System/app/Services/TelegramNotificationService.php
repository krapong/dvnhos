<?php

namespace App\Services;

use App\Models\TelegramLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    public function enabled(): bool
    {
        return (bool) config('telegram.enabled');
    }

    public function send(string $message, array $context = []): array
    {
        $chatId = config('telegram.chat_id');
        $token = config('telegram.bot_token');

        if (! $this->enabled() || ! $chatId || ! $token) {
            TelegramLog::create([
                'chat_id' => $chatId,
                'message' => $message,
                'payload' => $context,
                'status' => 'skipped',
                'sent_at' => now(),
            ]);

            return ['ok' => false, 'skipped' => true];
        }

        $response = Http::post(config('telegram.base_url')."/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);

        TelegramLog::create([
            'chat_id' => $chatId,
            'message' => $message,
            'payload' => array_merge($context, ['response' => $response->json()]),
            'status' => $response->successful() ? 'sent' : 'failed',
            'sent_at' => now(),
        ]);

        if (! $response->successful()) {
            Log::warning('Telegram notification failed', ['response' => $response->body()]);
        }

        return $response->json() ?? ['ok' => false];
    }
}
