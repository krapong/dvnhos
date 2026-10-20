<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('causer')->latest();

        // Optional filtering by subject_type, event, causer_id
        if ($request->has('event') && $request->event) {
            $query->where('event', $request->event);
        }
        
        if ($request->has('subject_type') && $request->subject_type) {
            $query->where('subject_type', 'like', "%{$request->subject_type}%");
        }

        $logs = $query->paginate(20)->through(function ($log) {
            return [
                'id' => $log->id,
                'log_name' => $log->log_name,
                'description' => $log->description,
                'subject_type' => class_basename($log->subject_type),
                'subject_id' => $log->subject_id,
                'event' => $log->event,
                'causer_name' => $log->causer ? $log->causer->name : 'System',
                'properties' => $log->properties,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return Inertia::render('Admin/AuditLog', [
            'logs' => $logs,
            'filters' => $request->only(['event', 'subject_type'])
        ]);
    }
}
