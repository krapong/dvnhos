<?php

namespace App\Http\Services;

use App\Models\SystemSetting;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramNotificationService
{
    public function isEnabled(): bool
    {
        return (bool) $this->setting('telegram_enabled', config('services.telegram.enabled', false))
            && filled($this->setting('telegram_bot_token', config('services.telegram.bot_token')))
            && filled($this->setting('telegram_chat_id', config('services.telegram.chat_id')));
    }

    /**
     * @throws RequestException
     */
    public function send(string $message): array
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('Telegram is not configured.');
        }

        $token = (string) $this->setting('telegram_bot_token', config('services.telegram.bot_token'));
        $chatId = (string) $this->setting('telegram_chat_id', config('services.telegram.chat_id'));
        $baseUrl = rtrim((string) $this->setting('telegram_base_url', config('services.telegram.base_url', 'https://api.telegram.org')), '/');

        $response = Http::asForm()
            ->timeout(20)
            ->post($baseUrl . '/bot' . $token . '/sendMessage', [
                'chat_id' => $chatId,
                'text' => $message,
                'disable_web_page_preview' => true,
            ]);

        $response->throw();

        return $response->json();
    }

    private function setting(string $key, mixed $default = null): mixed
    {
        return SystemSetting::getValue($key, $default);
    }
}
