<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SystemSetting extends Model
{
    protected $fillable = [
        'setting_group',
        'setting_key',
        'setting_value',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('system_settings')) {
            return $default;
        }

        $record = static::query()->where('setting_key', $key)->first();

        if ($record === null || $record->setting_value === null || $record->setting_value === '') {
            return $default;
        }

        return $record->setting_value;
    }

    public static function putValue(
        string $key,
        mixed $value,
        string $group = 'general',
        bool $isPublic = false
    ): self {
        if (! Schema::hasTable('system_settings')) {
            return new static();
        }

        return static::query()->updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_group' => $group,
                'setting_value' => $value,
                'is_public' => $isPublic,
            ]
        );
    }
}
