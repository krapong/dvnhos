<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'event_type',
        'auditable_type',
        'auditable_id',
        'before_payload',
        'after_payload',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'before_payload' => 'array',
        'after_payload' => 'array',
    ];
}
