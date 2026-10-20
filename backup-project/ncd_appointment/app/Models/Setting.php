<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'group'
    ];

    /**
     * Get setting value, casted to its proper type
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        if (!$setting) return $default;

        return match($setting->type) {
            'boolean' => (bool)$setting->value,
            'integer' => (int)$setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value
        };
    }
}
