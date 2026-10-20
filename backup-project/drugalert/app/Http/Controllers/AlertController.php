<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Alert;
use App\Models\Box;
use App\Models\Notification;
use Illuminate\Support\Str;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Sync notifications with active alerts
        $this->syncNotifications();
        
        $locations = \App\Models\Location::whereNull('deleted_at')->get()->keyBy('id');
        $alerts = $this->getAlertsQueryForUser($user)->whereNull('resolved_at')->with('box')->get()->map(function($alert) use ($locations) {
            $box = $alert->box;
            $location = $box && $box->location_id ? $locations->get($box->location_id) : null;
            
            $boxName = $box 
                ? ($box->name ?? $box->code) . ($location ? " - {$location->name}" : "")
                : ($alert->box_name ?? '-');

            return [
                'id' => $alert->id,
                'boxName' => $boxName,
                'locationId' => $location ? $location->id : null,
                'drugName' => $alert->drug_name ?? '-',
                'quantity' => $alert->quantity ?? 0,
                'unit' => $alert->unit ?? '',
                'severity' => $alert->severity ?? 'warning',
                'status' => $alert->status ?? '',
                'category' => $alert->category ?? 'drug',
                'expiryDate' => $alert->expiry_date ?? '',
            ];
        })->values();

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
                'createdAt' => $notification->created_at ? \Illuminate\Support\Carbon::parse($notification->created_at)->toISOString() : null,
                'created_at' => $notification->created_at ? \Illuminate\Support\Carbon::parse($notification->created_at)->toISOString() : null,
            ];
        })->values();

        return response()->json([
            'alerts' => $alerts,
            'notifications' => $notifications
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'box_id' => 'required|string',
            'category' => 'required|string',
        ]);

        $alert = new Alert();
        $alert->id = 'alert_' . Str::random(8);
        $alert->box_id = $request->box_id;
        $this->setIfColumnExists($alert, 'box_name', $request->box_name ?? null);
        $this->setIfColumnExists($alert, 'location_name', $request->location_name ?? null);
        $this->setIfColumnExists($alert, 'drug_name', $request->drug_name ?? $request->description ?? null);
        $this->setIfColumnExists($alert, 'quantity', $request->quantity ?? null);
        $this->setIfColumnExists($alert, 'unit', $request->unit ?? null);
        $this->setIfColumnExists($alert, 'severity', $request->severity ?? null);
        $alert->category = $request->category;
        $this->setIfColumnExists($alert, 'description', $request->description ?? '');
        $alert->expiry_date = $request->expiry_date;
        $alert->status = $request->status ?? 'active';
        $alert->save();
        $this->recordAudit($user->id, 'alert.create', 'alert', $alert->id, [
            'after' => $alert->getAttributes(),
        ]);

        return response()->json($alert);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $alert = Alert::find($id);
        if (!$alert) {
            return response()->json(['error' => 'Alert not found'], 404);
        }

        $before = $alert->getAttributes();
        if ($request->has('status')) $alert->status = $request->status;
        if ($request->has('resolved_at')) {
            $alert->resolved_at = clone now();
            if (! $request->has('status')) {
                $alert->status = 'resolved';
            }
        }
        $alert->save();
        $action = $request->has('resolved_at') ? 'alert.resolve' : 'alert.update';
        $this->recordAudit($user->id, $action, 'alert', $alert->id, [
            'before' => $before,
            'after' => $alert->getAttributes(),
        ]);

        return response()->json($alert);
    }

    public function resolve(Request $request, $id)
    {
        $user = $request->user();
        $alert = Alert::find($id);
        if (!$alert) {
            return response()->json(['error' => 'Alert not found'], 404);
        }

        $before = $alert->getAttributes();
        $alert->resolved_at = now();
        $alert->status = 'resolved';
        $alert->save();

        $this->recordAudit($user->id, 'alert.resolve', 'alert', $alert->id, [
            'before' => $before,
            'after' => $alert->getAttributes(),
        ]);

        return response()->json($alert);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'super_admin') {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $alert = Alert::find($id);
        if ($alert) {
            $this->recordAudit($user->id, 'alert.delete', 'alert', $alert->id, [
                'before' => $alert->getAttributes(),
            ]);
            $alert->delete(); // Or soft delete
        }

        return response()->json(['success' => true]);
    }
}
