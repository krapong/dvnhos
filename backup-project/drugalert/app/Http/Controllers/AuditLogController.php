<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditLog;
use Illuminate\Support\Carbon;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['super_admin'])) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $logs = AuditLog::orderBy('created_at', 'desc')->take(100)->get()->map(function ($log) {
            $createdAt = $this->normalizeTimestamp($log->created_at);

            return [
                'id' => $log->id,
                'actor_user_id' => $log->actor_user_id,
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'details' => $log->details,
                'createdAt' => $createdAt,
                'created_at' => $createdAt,
            ];
        })->values();

        return response()->json($logs);
    }

    private function normalizeTimestamp(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toISOString();
        }

        try {
            return Carbon::parse($value)->toISOString();
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}
