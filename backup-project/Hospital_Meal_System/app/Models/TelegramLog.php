<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramLog extends Model
{
    protected $fillable = ['chat_id', 'message', 'payload', 'status', 'sent_at'];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
    ];
}
