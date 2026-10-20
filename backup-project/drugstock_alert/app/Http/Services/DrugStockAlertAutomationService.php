<?php

namespace App\Http\Services;

use App\Models\AlertNotification;
use App\Models\AlertRule;
use App\Models\Drug;
use App\Models\SystemSetting;
use App\Models\WarehouseStock;
use App\Models\WarehouseStockLot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class DrugStockAlertAutomationService
{
    public function __construct(
        private readonly TelegramNotificationService $telegramService
    ) {
    }

    public function sweep(): array
    {
        $created = 0;
        $created += $this->syncLowStockAlerts();
        $created += $this->syncExpiryAlerts();

        $sent = $this->dispatchTelegramQueue();

        return [
            'created' => $created,
            'sent' => $sent,
        ];
    }

    public function dispatchTelegramQueue(): int
    {
        $sent = 0;

        AlertNotification::query()
            ->where('channel', 'telegram')
            ->where('status', 'pending')
            ->whereNull('sent_at')
            ->orderBy('id')
            ->limit(100)
            ->each(function (AlertNotification $notification) use (&$sent) {
                try {
                    $this->telegramService->send($notification->message);
                    $notification->forceFill([
                        'sent_at' => now(),
                        'error_message' => null,
                    ])->save();
                    $sent++;
                } catch (Throwable $throwable) {
                    $notification->forceFill([
                        'error_message' => $throwable->getMessage(),
                    ])->save();
                }
            });

        return $sent;
    }

    private function syncLowStockAlerts(): int
    {
        $rules = AlertRule::query()
            ->where('is_enabled', true)
            ->where('alert_type', 'low_stock')
            ->get();

        $stocks = WarehouseStock::query()
            ->select('warehouse_stocks.*')
            ->join('drugs', 'warehouse_stocks.drug_id', '=', 'drugs.id')
            ->with(['warehouse.location', 'drug'])
            ->whereRaw('warehouse_stocks.qty_on_hand <= COALESCE(drugs.min_stock_qty, ?)', [SystemSetting::getValue('low_stock_threshold', config('medstock.stock_policy.low_stock_threshold', 10))])
            ->get();

        $created = 0;
        foreach ($stocks as $stock) {
            $rule = $this->matchRule($rules, 'low_stock', $stock);
            $threshold = (float) ($rule?->threshold_qty ?? $stock->drug?->min_stock_qty ?? SystemSetting::getValue('low_stock_threshold', config('medstock.stock_policy.low_stock_threshold', 10)));
            $message = sprintf(
                'แจ้งเตือนยาคงเหลือต่ำ: %s คลัง %s คงเหลือ %s หน่วย ต่ำกว่าจุดสั่งซื้อ %s',
                $stock->drug?->name ?? '-',
                $stock->warehouse?->name ?? '-',
                $stock->qty_on_hand,
                $threshold
            );

            $created += $this->createNotification(
                type: 'low_stock',
                rule: $rule,
                warehouseId: $stock->warehouse_id,
                drugId: $stock->drug_id,
                title: 'แจ้งเตือนยาคงเหลือต่ำ',
                message: $message,
                payload: [
                    'warehouse_stock_id' => $stock->id,
                    'qty_on_hand' => $stock->qty_on_hand,
                    'threshold_qty' => $threshold,
                ],
                telegram: $rule?->notify_telegram ?? true
            );
        }

        return $created;
    }

    private function syncExpiryAlerts(): int
    {
        $rules = AlertRule::query()
            ->where('is_enabled', true)
            ->whereIn('alert_type', ['near_expiry', 'expired'])
            ->get();

        $rounds = collect(config('medstock.stock_policy.expiry_alert_rounds', [30, 90, 180]))
            ->filter(fn ($value) => is_numeric($value) && (int) $value > 0)
            ->map(fn ($value) => (int) $value)
            ->sortDesc()
            ->values();

        $expiredItems = WarehouseStockLot::query()
            ->with(['stock.warehouse.location', 'stock.drug'])
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', today())
            ->where('qty_on_hand', '>', 0)
            ->get();

        $created = 0;

        foreach ($expiredItems as $lot) {
            $rule = $this->matchRule($rules, 'expired', $lot->stock);
            $created += $this->createNotification(
                type: 'expired',
                rule: $rule,
                warehouseId: $lot->stock?->warehouse_id,
                drugId: $lot->stock?->drug_id,
                title: 'แจ้งเตือนยาหมดอายุ',
                message: sprintf(
                    'ยา %s คลัง %s Lot %s หมดอายุแล้วเมื่อ %s',
                    $lot->stock?->drug?->name ?? '-',
                    $lot->stock?->warehouse?->name ?? '-',
                    $lot->lot_no,
                    optional($lot->expiry_date)->format('d/m/Y')
                ),
                payload: [
                    'warehouse_stock_lot_id' => $lot->id,
                    'lot_no' => $lot->lot_no,
                    'expiry_date' => optional($lot->expiry_date)->toDateString(),
                    'qty_on_hand' => $lot->qty_on_hand,
                ],
                telegram: $rule?->notify_telegram ?? true
            );
        }

        $nearExpiryItems = WarehouseStockLot::query()
            ->with(['stock.warehouse.location', 'stock.drug'])
            ->whereNotNull('expiry_date')
            ->where('qty_on_hand', '>', 0)
            ->get();

        foreach ($nearExpiryItems as $lot) {
            $daysRemaining = now()->startOfDay()->diffInDays($lot->expiry_date?->copy()->startOfDay() ?? now(), false);
            if ($daysRemaining < 0) {
                continue;
            }

            $round = $rounds->first(fn (int $round) => $daysRemaining <= $round);
            if ($round === null) {
                continue;
            }

            $rule = $rules->first(function (AlertRule $rule) use ($lot, $round) {
                return $rule->alert_type === 'near_expiry'
                    && (int) $rule->expiry_days === $round
                    && match ($rule->scope) {
                        'drug' => $rule->drug_id === $lot->stock?->drug_id,
                        'warehouse' => $rule->warehouse_id === $lot->stock?->warehouse_id,
                        'location' => $rule->location_id === $lot->stock?->warehouse?->location_id,
                        default => true,
                    };
            });

            $created += $this->createNotification(
                type: 'near_expiry_' . $round,
                rule: $rule,
                warehouseId: $lot->stock?->warehouse_id,
                drugId: $lot->stock?->drug_id,
                title: 'แจ้งเตือนยาใกล้หมดอายุ ' . $round . ' วัน',
                message: sprintf(
                    'ยา %s คลัง %s Lot %s จะหมดอายุวันที่ %s คงเหลือ %s หน่วย (รอบ %s วัน)',
                    $lot->stock?->drug?->name ?? '-',
                    $lot->stock?->warehouse?->name ?? '-',
                    $lot->lot_no,
                    optional($lot->expiry_date)->format('d/m/Y'),
                    $lot->qty_on_hand,
                    $round
                ),
                payload: [
                    'warehouse_stock_lot_id' => $lot->id,
                    'lot_no' => $lot->lot_no,
                    'expiry_date' => optional($lot->expiry_date)->toDateString(),
                    'qty_on_hand' => $lot->qty_on_hand,
                    'days_remaining' => $daysRemaining,
                    'alert_round' => $round,
                ],
                telegram: $rule?->notify_telegram ?? true
            );
        }

        return $created;
    }

    private function createNotification(
        string $type,
        ?AlertRule $rule,
        ?int $warehouseId,
        ?int $drugId,
        string $title,
        string $message,
        array $payload,
        bool $telegram = true
    ): int {
        $dedupKey = sha1(implode('|', [
            $type,
            $rule?->id ?? 'global',
            $warehouseId ?? 'none',
            $drugId ?? 'none',
            $payload['warehouse_stock_id'] ?? $payload['warehouse_stock_lot_id'] ?? Str::random(8),
            $payload['alert_round'] ?? 'none',
            now()->toDateString(),
        ]));

        $notification = AlertNotification::firstOrCreate(
            ['dedup_key' => $dedupKey],
            [
                'alert_rule_id' => $rule?->id,
                'warehouse_id' => $warehouseId,
                'drug_id' => $drugId,
                'notification_type' => $type,
                'channel' => $telegram ? 'telegram' : 'database',
                'title' => $title,
                'message' => $message,
                'payload' => $payload,
                'status' => 'pending',
            ]
        );

        return $notification->wasRecentlyCreated ? 1 : 0;
    }

    private function matchRule(Collection $rules, string $type, ?WarehouseStock $stock): ?AlertRule
    {
        if ($stock === null) {
            return null;
        }

        return $rules->first(function (AlertRule $rule) use ($type, $stock) {
            if ($rule->alert_type !== $type) {
                return false;
            }

            return match ($rule->scope) {
                'drug' => is_null($rule->drug_id) || $rule->drug_id === $stock->drug_id,
                'warehouse' => is_null($rule->warehouse_id) || $rule->warehouse_id === $stock->warehouse_id,
                'location' => is_null($rule->location_id) || $rule->location_id === $stock->warehouse?->location_id,
                default => true,
            };
        });
    }
}
