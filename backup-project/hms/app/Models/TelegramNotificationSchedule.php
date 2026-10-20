<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramNotificationSchedule extends Model
{
    protected $fillable = [
        'key',
        'label',
        'notify_at',
        'enabled',
        'last_sent_date',
        'last_sent_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'last_sent_date' => 'date',
        'last_sent_at' => 'datetime',
    ];
}
