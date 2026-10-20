<?php

namespace App\Http\Controllers\Api\V1\DrugStock;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\DrugStock\StoreAlertRuleRequest;
use App\Http\Requests\DrugStock\UpdateAlertRuleRequest;
use App\Http\Services\DrugStockService;
use App\Models\AlertNotification;
use App\Models\AlertRule;
use Illuminate\Http\Request;

class AlertController extends BaseApiController
{
    public function __construct(private readonly DrugStockService $service)
    {
    }

    public function index(Request $request)
    {
        $status = $request->string('status')->toString();

        $alerts = AlertNotification::query()
            ->with(['rule', 'drug', 'warehouse'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate((int) $request->integer('per_page', 15));

        return $this->ok([
            'summary' => [
                'all' => AlertNotification::count(),
                'pending' => AlertNotification::where('status', 'pending')->count(),
                'resolved' => AlertNotification::where('status', 'resolved')->count(),
            ],
            'items' => $alerts,
        ]);
    }

    public function store(StoreAlertRuleRequest $request)
    {
        return $this->ok(AlertRule::create($request->validated()), 'บันทึกกฎแจ้งเตือนเรียบร้อย');
    }

    public function update(UpdateAlertRuleRequest $request, AlertRule $alertRule)
    {
        $alertRule->update($request->validated());
        return $this->ok($alertRule, 'แก้ไขกฎแจ้งเตือนเรียบร้อย');
    }

    public function storePresets(Request $request)
    {
        $rounds = collect(config('medstock.stock_policy.expiry_alert_rounds', [30, 90, 180]))
            ->filter(fn ($value) => is_numeric($value) && (int) $value > 0)
            ->map(fn ($value) => (int) $value)
            ->sort()
            ->values();

        $scope = $request->string('scope')->toString() ?: 'warehouse';

        $created = $rounds->map(function (int $round) use ($scope) {
            return AlertRule::firstOrCreate(
                [
                    'scope' => $scope,
                    'alert_type' => 'near_expiry',
                    'expiry_days' => $round,
                    'warehouse_id' => null,
                    'location_id' => null,
                    'drug_id' => null,
                ],
                [
                    'is_enabled' => true,
                    'notify_telegram' => true,
                ]
            );
        });

        return $this->ok([
            'items' => $created,
            'rounds' => $rounds,
        ], 'สร้างกฎแจ้งเตือน 4 รอบเรียบร้อย');
    }

    public function rounds()
    {
        $rounds = collect(config('medstock.stock_policy.expiry_alert_rounds', [30, 90, 180]))
            ->filter(fn ($value) => is_numeric($value) && (int) $value > 0)
            ->map(fn ($value) => (int) $value)
            ->sort()
            ->values();

        $rules = AlertRule::query()
            ->where('alert_type', 'near_expiry')
            ->whereIn('expiry_days', $rounds)
            ->get()
            ->keyBy('expiry_days');

        $notifications = AlertNotification::query()
            ->where('notification_type', 'like', 'near_expiry_%')
            ->latest()
            ->get()
            ->groupBy(fn (AlertNotification $notification) => (int) data_get($notification->payload, 'alert_round', 0));

        $rows = $rounds->map(function (int $round) use ($rules, $notifications) {
            $group = $notifications->get($round, collect());
            $rule = $rules->get($round);

            return [
                'round' => $round,
                'rule_id' => $rule?->id,
                'is_enabled' => (bool) ($rule?->is_enabled ?? false),
                'notify_telegram' => (bool) ($rule?->notify_telegram ?? false),
                'total' => $group->count(),
                'pending' => $group->where('status', 'pending')->count(),
                'resolved' => $group->where('status', 'resolved')->count(),
                'latest_alert_at' => optional($group->first()?->created_at)->toDateTimeString(),
            ];
        })->values();

        return $this->ok($rows);
    }

    public function resolve(AlertNotification $alertNotification)
    {
        $alertNotification->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return $this->ok($alertNotification->refresh(), 'ปิดการแจ้งเตือนเรียบร้อย');
    }

    public function timeline()
    {
        return $this->ok($this->service->alertItems()->values());
    }
}
