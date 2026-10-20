<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Box;
use App\Models\Drug;
use App\Models\BoxRound;
use App\Models\BoxRoundItem;
use App\Models\Alert;
use App\Models\Location;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BoxRoundController extends Controller
{
    /**
     * Get all rounds of a box.
     */
    public function index(Request $request, $boxId)
    {
        $user = $request->user();
        $box = Box::find($boxId);
        if (!$box) {
            return response()->json(['error' => 'Box not found'], 404);
        }

        if (in_array($user->role, ['dept_admin', 'viewer']) && $box->location_id !== $user->location_id) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $rounds = BoxRound::where('box_id', $boxId)
            ->orderBy('round_number', 'desc')
            ->get()
            ->map(function ($round) {
                return [
                    'id' => $round->id,
                    'round_number' => $round->round_number,
                    'status' => $round->status,
                    'created_by' => $round->created_by,
                    'items_count' => $round->items_count,
                    'used_count' => $round->used_count,
                    'used_value' => (float)$round->used_value,
                    'created_at' => $round->created_at ? $round->created_at->toDateTimeString() : null,
                ];
            });

        return response()->json($rounds);
    }

    /**
     * Get current items of a box (from the latest active round).
     */
    public function currentItems(Request $request, $boxId)
    {
        $user = $request->user();
        $box = Box::find($boxId);
        if (!$box) {
            return response()->json(['error' => 'Box not found'], 404);
        }

        if (in_array($user->role, ['dept_admin', 'viewer']) && $box->location_id !== $user->location_id) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $latestRound = BoxRound::where('box_id', $boxId)
            ->orderBy('round_number', 'desc')
            ->first();

        if (!$latestRound) {
            return response()->json([]);
        }

        $items = BoxRoundItem::where('box_round_id', $latestRound->id)
            ->get()
            ->map(function ($item) {
                $drug = Drug::find($item->drug_id);
                return [
                    'id' => $item->id,
                    'drug_id' => $item->drug_id,
                    'drug_name' => $item->drug_name,
                    'lot_number' => $item->lot_number,
                    'quantity' => (int)$item->quantity,
                    'used_quantity' => (int)$item->used_quantity,
                    'expiry_date' => $item->expiry_date,
                    'alert_days' => (int)$item->alert_days,
                    'unit' => $drug ? $drug->unit : 'หน่วย',
                    'price' => $drug ? (float)$drug->price : 0.00,
                    'category' => $drug ? $drug->category : 'drug',
                ];
            });

        return response()->json($items);
    }

    /**
     * Store a new round (dispatch/repack) with items.
     */
    public function store(Request $request, $boxId)
    {
        $user = $request->user();
        $box = Box::find($boxId);
        if (!$box) {
            return response()->json(['error' => 'Box not found'], 404);
        }

        // Verify dispatch permissions
        if (!in_array($user->role, ['super_admin', 'pharmacist', 'dept_admin'])) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        if ($user->role === 'dept_admin' && $box->location_id !== $user->location_id) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $request->validate([
            'items' => 'required|array',
            'items.*.drug_id' => 'required|string',
            'items.*.drug_name' => 'required|string',
            'items.*.lot_number' => 'required|string',
            'items.*.quantity' => 'required|integer|min:0',
            'items.*.expiry_date' => 'required|string',
            'items.*.alert_days' => 'nullable|integer|min:0',
        ]);

        $itemsData = $request->input('items');

        return DB::transaction(function () use ($box, $user, $itemsData) {
            // Calculate next round number
            $latestRoundNo = BoxRound::where('box_id', $box->id)->max('round_number') ?: 0;
            $newRoundNo = $latestRoundNo + 1;

            // Create BoxRound
            $round = new BoxRound();
            $round->id = 'round_' . \Illuminate\Support\Str::random(8);
            $round->box_id = $box->id;
            $round->round_number = $newRoundNo;
            $round->status = 'active';
            $round->created_by = $user->name ?? $user->email ?? $user->id;
            $round->items_count = count($itemsData);
            $round->used_count = 0;
            $round->used_value = 0.00;
            $round->save();

            $totalQty = 0;
            $totalValue = 0.0;

            foreach ($itemsData as $itemData) {
                $drug = Drug::find($itemData['drug_id']);
                $price = $drug ? (float)$drug->price : 0.00;
                $quantity = (int)$itemData['quantity'];

                $roundItem = new BoxRoundItem();
                $roundItem->id = 'item_' . \Illuminate\Support\Str::random(8);
                $roundItem->box_round_id = $round->id;
                $roundItem->drug_id = $itemData['drug_id'];
                $roundItem->drug_name = $itemData['drug_name'];
                $roundItem->lot_number = $itemData['lot_number'];
                $roundItem->quantity = $quantity;
                $roundItem->used_quantity = 0;
                $roundItem->expiry_date = $itemData['expiry_date'];
                $roundItem->alert_days = (int)($itemData['alert_days'] ?? 30);
                $roundItem->save();

                $totalQty += $quantity;
                $totalValue += ($price * $quantity);
            }

            // Update Box total drug count and value
            $box->drug_count = $totalQty;
            $box->value = $totalValue;
            $box->save();

            // Sync alerts for this box
            $this->syncAlertsForBox($box);

            // Audit Log
            $this->recordAudit($user->id, 'box.repack', 'box', $box->id, [
                'round_number' => $newRoundNo,
                'items_count' => count($itemsData),
                'total_quantity' => $totalQty,
                'total_value' => $totalValue,
            ]);

            return response()->json([
                'success' => true,
                'round_number' => $newRoundNo,
                'box' => [
                    'id' => $box->id,
                    'drug_count' => $box->drug_count,
                    'value' => $box->value,
                ]
            ]);
        });
    }

    /**
     * Sync alerts for a box based on its latest active round.
     */
    public function syncAlertsForBox(Box $box)
    {
        $latestRound = BoxRound::where('box_id', $box->id)
            ->where('status', 'active')
            ->orderBy('round_number', 'desc')
            ->first();

        if (!$latestRound) {
            // Resolve all active alerts of this box
            Alert::where('box_id', $box->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                ]);
            return;
        }

        $items = BoxRoundItem::where('box_round_id', $latestRound->id)->get();
        $location = Location::find($box->location_id);
        $locationName = $location ? $location->name : '';

        // Get previous round to compare if settings changed
        $previousRound = BoxRound::where('box_id', $box->id)
            ->where('status', 'active')
            ->orderBy('round_number', 'desc')
            ->skip(1)
            ->first();
        $previousItems = $previousRound 
            ? BoxRoundItem::where('box_round_id', $previousRound->id)->get()->keyBy('drug_id') 
            : collect();

        $today = Carbon::today();
        $processedDrugIds = [];
        $newAlerts = [];

        foreach ($items as $item) {
            $prevItem = $previousItems->get($item->drug_id);
            $isUpdatedSettings = false;
            if ($prevItem && ($prevItem->expiry_date !== $item->expiry_date || $prevItem->alert_days != $item->alert_days)) {
                $isUpdatedSettings = true;
            }
            $expiry = Carbon::parse($item->expiry_date);
            $diffDays = $today->diffInDays($expiry, false);
            $alertDays = (int)$item->alert_days;

            $drug = Drug::find($item->drug_id);
            $unit = $drug ? $drug->unit : 'หน่วย';
            $category = $drug ? $drug->category : 'drug';

            // Determine alert state
            $isExpired = $diffDays <= 0;
            $isNearExpiry = $diffDays <= $alertDays;

            if ($isExpired || $isNearExpiry) {
                $severity = $isExpired ? 'danger' : 'warning';
                $status = 'active';
                $description = $isExpired 
                    ? "ยา {$item->drug_name} หมดอายุแล้ว (ล็อต: {$item->lot_number})" 
                    : "ยา {$item->drug_name} ใกล้หมดอายุ (ล็อต: {$item->lot_number}, วันหมดอายุ: {$item->expiry_date})";

                // Check if an active alert for this drug and lot already exists in this box
                $alert = Alert::where('box_id', $box->id)
                    ->where('drug_name', $item->drug_name)
                    ->where('status', 'active')
                    ->first();

                $isNew = false;
                if (!$alert) {
                    $alert = new Alert();
                    $alert->id = 'alert_' . \Illuminate\Support\Str::random(8);
                    $alert->box_id = $box->id;
                    $alert->drug_name = $item->drug_name;
                    $alert->created_at = now();
                    $isNew = true;
                }

                $alert->box_name = $box->name ?? $box->code;
                $alert->location_name = $locationName;
                $alert->quantity = (string)$item->quantity;
                $alert->unit = $unit;
                $alert->severity = $severity;
                $alert->status = $status;
                $alert->category = 'expiration';
                $this->setIfColumnExists($alert, 'description', $description);
                $alert->expiry_date = $item->expiry_date;
                $alert->resolved_at = null;
                $alert->save();

                if ($isNew || $isUpdatedSettings) {
                    $newAlerts[] = clone $alert;
                }

                $processedDrugIds[] = $alert->id;
            }
        }

        // Resolve any active alerts for this box that are NOT in the processed list
        Alert::where('box_id', $box->id)
            ->where('status', 'active')
            ->where('category', 'expiration')
            ->whereNotIn('id', $processedDrugIds)
            ->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);

        // Send real-time notification for new alerts
        if (!empty($newAlerts)) {
            $this->sendRealtimeAlerts($newAlerts, $box, $locationName);
        }
    }

    /**
     * Send immediate Telegram alerts for newly created alerts
     */
    protected function sendRealtimeAlerts($alerts, $box, $locationName)
    {
        $botToken = env('TELEGRAM_BOT_TOKEN') ?? \App\Models\Setting::where('key', 'telegramBotToken')->value('value');
        $chatId = env('TELEGRAM_CHAT_ID') ?? \App\Models\Setting::where('key', 'telegramChatId')->value('value');
        $botToken = \App\Models\Setting::normalizeValue('telegramBotToken', $botToken);
        $chatId = \App\Models\Setting::normalizeValue('telegramChatId', $chatId);

        if (!$botToken || !$chatId) return;

        $hospitalName = 'Medical Box Monitor';
        if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            $hospitalName = \App\Models\Setting::normalizeValue('hospitalName', \App\Models\Setting::where('key', 'hospitalName')->value('value')) ?? $hospitalName;
        }

        $message = "⚠️ *พบยา/เวชภัณฑ์ฉุกเฉินใกล้หมดอายุ (อัปเดตใหม่)*\n";
        $message .= "🏥 *หน่วยงาน:* {$hospitalName}\n";
        $message .= "📦 *กล่อง:* " . ($box->name ?? $box->code) . " ({$locationName})\n\n";

        $i = 1;
        foreach ($alerts as $alert) {
            $expiry = \Carbon\Carbon::parse($alert->expiry_date);
            $days = (int)now()->startOfDay()->diffInDays($expiry->startOfDay(), false);

            if ($days < 0) {
                $statusText = "🔴 *หมดอายุแล้ว* (ผ่านมาแล้ว " . abs($days) . " วัน)";
            } elseif ($days === 0) {
                $statusText = "🔴 *หมดอายุวันนี้*";
            } else {
                $statusText = "🟡 *ใกล้หมดอายุ* (เหลืออีก {$days} วัน)";
            }

            $desc = (\Illuminate\Support\Facades\Schema::hasColumn('alerts', 'description') && $alert->description) 
                ? $alert->description 
                : "{$alert->drug_name} จำนวนคงเหลือ {$alert->quantity} {$alert->unit}";

            $message .= "*{$i}. {$alert->drug_name}*\n";
            $message .= "📝 *รายละเอียด:* {$desc}\n";
            $message .= "📅 *วันหมดอายุ:* {$alert->expiry_date}\n";
            $message .= "⚡ *สถานะ:* {$statusText}\n\n";
            $i++;
        }

        $message .= "--------------------------------------------------\n";
        $message .= "🔗 *เข้าจัดการระบบได้ที่:* " . env('APP_URL', 'http://localhost');

        try {
            \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Exception $e) {
            // Ignore error so it doesn't block the UI
        }
    }
}
