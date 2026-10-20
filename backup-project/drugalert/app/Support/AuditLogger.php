<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Str;

class AuditLogger
{
    public static function record(
        ?string $actorUserId,
        string $action,
        string $entityType,
        ?string $entityId = null,
        array $details = []
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $actorUserId,
            'event' => $action,
            'auditable_type' => $entityType,
            'auditable_id' => $entityId,
            'old_values' => json_encode([]),
            'new_values' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'System',
        ]);
    }
}
