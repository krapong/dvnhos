<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Carbon;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $this->syncNotifications();

        // Get notifications
        // If the user is not super_admin, filter notifications to only include ones that belong to alerts the user can see
        $alerts = $this->getAlertsQueryForUser($user)->whereNull('resolved_at')->get();
        
        $notificationsQuery = Notification::orderBy('created_at', 'desc');
        if ($user->role !== 'super_admin') {
            $notificationsQuery->whereIn('id', $alerts->pluck('id')->toArray());
        }

        $notifications = $notificationsQuery->take(20)->get()->map(function ($notification) {
            $readBy = $notification->read_by ?? [];
            if (is_string($readBy)) {
                $decoded = json_decode($readBy, true);
                $readBy = is_array($decoded) ? $decoded : [];
            }

            return [
                'id' => $notification->id,
                'title' => $notification->title,
                'body' => $notification->body,
                'severity' => $notification->severity,
                'targetView' => $notification->target_view,
                'target_view' => $notification->target_view,
                'readBy' => $readBy,
                'read_by' => $readBy,
                'createdAt' => $notification->created_at ? Carbon::parse($notification->created_at)->toISOString() : null,
                'created_at' => $notification->created_at ? Carbon::parse($notification->created_at)->toISOString() : null,
            ];
        })->values();

        return response()->json([
            'notifications' => $notifications
        ]);
    }

    public function readAll(Request $request)
    {
        $user = $request->user();
        $this->syncNotifications();

        // Find notifications the user is allowed to see
        $alerts = $this->getAlertsQueryForUser($user)->whereNull('resolved_at')->get();
        $notificationsQuery = Notification::query();
        if ($user->role !== 'super_admin') {
            $notificationsQuery->whereIn('id', $alerts->pluck('id')->toArray());
        }

        $notifications = $notificationsQuery->get();
        foreach ($notifications as $notification) {
            $readBy = $notification->read_by ?? [];
            if (is_string($readBy)) {
                $decoded = json_decode($readBy, true);
                $readBy = is_array($decoded) ? $decoded : [];
            }
            if (!in_array($user->id, $readBy)) {
                $readBy[] = $user->id;
                $notification->read_by = json_encode($readBy);
                $notification->save();
            }
        }

        $this->recordAudit($user->id, 'notification.read_all', 'notification', null, []);

        return response()->json(['ok' => true]);
    }
}
