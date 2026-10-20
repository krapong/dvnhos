<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function normalizeValue(string $key, ?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $value;
        }

        if (is_array($decoded) && array_key_exists($key, $decoded) && count($decoded) === 1) {
            return $decoded[$key];
        }

        return $decoded;
    }

    public static function normalizedMap(): array
    {
        $map = static::query()
            ->get(['key', 'value'])
            ->mapWithKeys(fn (self $setting) => [
                $setting->key => static::normalizeValue($setting->key, $setting->value),
            ])
            ->all();

        $map['telegramBotToken'] = env('TELEGRAM_BOT_TOKEN') ?: ($map['telegramBotToken'] ?? null);
        $map['telegramChatId'] = env('TELEGRAM_CHAT_ID') ?: ($map['telegramChatId'] ?? null);

        return $map;
    }
}
