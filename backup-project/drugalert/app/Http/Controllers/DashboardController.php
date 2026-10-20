<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Box;
use App\Models\Drug;
use App\Models\Location;
use App\Models\Alert;
use App\Models\Notification;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Sync notifications with active alerts
        $this->syncNotifications();
        
        $boxes = $this->getBoxesQueryForUser($user)->with(['rounds' => function($q) {
            $q->where('status', 'active');
        }])->get();
        $alerts = $this->getAlertsQueryForUser($user)->whereNull('resolved_at')->with('box')->get();
        $locations = \App\Models\Location::whereNull('deleted_at')->get()->keyBy('id');

        $latestAlerts = $alerts->take(5)->map(function($alert) use ($locations) {
            $box = $alert->box;
            $location = $box && $box->location_id ? $locations->get($box->location_id) : null;
            $boxName = $box ? ($box->name ?? $box->code) : ($alert->box_name ?? '-');
            $locationName = $location ? $location->name : ($alert->location_name ?? '-');
            return [
                'box' => $boxName,
                'location' => $locationName,
                'drug' => $alert->drug_name ?? '-',
                'qty' => $alert->quantity ?? '-',
                'status' => $alert->status ?? '-',
                'date' => $alert->expiry_date ?? '-',
            ];
        })->toArray();

        $expiringSoon = $alerts->where('severity', 'warning')->count();
        $expired = $alerts->where('severity', 'danger')->count();
        $atRiskBoxes = $alerts->pluck('box_id')->unique()->count();
        
        // Count total items across all boxes for metric
        $totalItems = $boxes->sum(function($box) {
            $activeRound = $box->rounds->first();
            return $activeRound ? $activeRound->items_count : 0;
        });

        $boxSummary = $boxes->map(function($box) use ($locations) {
            $loc = $box->location_id ? $locations->get($box->location_id) : null;
            $activeRound = $box->rounds->first();
            $itemCount = $activeRound ? $activeRound->items_count : 0;
            return [
                'id' => $box->code ?? $box->name ?? '-',
                'name' => $loc ? $loc->name : '-',
                'item_count' => $itemCount . ' รายการ',
                'status' => $box->status ?? '-',
            ];
        })->toArray();

        $metrics = [
            [
                'label' => 'จำนวนกล่องยาทั้งหมด',
                'value' => (string)$boxes->count(),
                'meta' => "ประจำตามหน่วยงาน",
                'tone' => 'blue',
                'icon' => 'box'
            ],
            [
                'label' => 'รายการใกล้หมดอายุ',
                'value' => (string)$expiringSoon,
                'meta' => "ควรตรวจสอบและเปลี่ยนยา",
                'tone' => 'amber',
                'icon' => 'hourglass',
                'items' => $alerts->where('severity', 'warning')->map(function($alert) {
                    return [
                        'box_name' => $alert->box_name ?? ($alert->box ? ($alert->box->name ?? $alert->box->code) : '-'),
                        'drug_name' => $alert->drug_name,
                        'quantity' => (int)$alert->quantity,
                        'unit' => $alert->unit ?? 'ชิ้น',
                        'expiry_date' => $alert->expiry_date,
                    ];
                })->values()->toArray()
            ],
            [
                'label' => 'รายการหมดอายุแล้ว',
                'value' => (string)$expired,
                'meta' => 'ห้ามใช้งานเด็ดขาด',
                'tone' => 'red',
                'icon' => 'alert',
                'items' => $alerts->where('severity', 'danger')->map(function($alert) {
                    return [
                        'box_name' => $alert->box_name ?? ($alert->box ? ($alert->box->name ?? $alert->box->code) : '-'),
                        'drug_name' => $alert->drug_name,
                        'quantity' => (int)$alert->quantity,
                        'unit' => $alert->unit ?? 'ชิ้น',
                        'expiry_date' => $alert->expiry_date,
                    ];
                })->values()->toArray()
            ],
            [
                'label' => 'กล่องยาที่มีความเสี่ยง',
                'value' => (string)$atRiskBoxes,
                'meta' => 'มีรายการที่หมด/ใกล้หมดอายุ',
                'tone' => 'amber',
                'icon' => 'warning'
            ]
        ];

        $quickActions = [
            ['label' => 'ดูกล่องยาฉุกเฉิน', 'icon' => 'box', 'action' => 'boxes', 'primary' => true],
            ['label' => 'จัดการสถานที่', 'icon' => 'pin', 'action' => 'locations', 'primary' => false],
            ['label' => 'ดูแจ้งเตือน', 'icon' => 'bell', 'action' => 'alerts', 'primary' => false]
        ];

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

            $createdAt = $this->normalizeTimestamp($notification->created_at);

            return [
                'id' => $notification->id,
                'title' => $notification->title,
                'body' => $notification->body,
                'severity' => $notification->severity,
                'targetView' => $notification->target_view,
                'target_view' => $notification->target_view,
                'readBy' => $readBy,
                'read_by' => $readBy,
                'createdAt' => $createdAt,
                'created_at' => $createdAt,
            ];
        })->values();

        $unreadNotifications = $notifications->filter(function($notification) use ($user) {
            return !in_array($user->id, $notification['readBy']);
        })->values()->toArray();

        $dashboard = [
            'metrics' => $metrics,
            'boxSummary' => $boxSummary,
            'latestAlerts' => $latestAlerts,
            'quickActions' => $quickActions,
            'unreadNotifications' => $unreadNotifications
        ];

        $settings = Setting::normalizedMap();

        return response()->json([
            'dashboard' => $dashboard,
            'notifications' => $notifications,
            'settings' => $settings
        ]);
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
