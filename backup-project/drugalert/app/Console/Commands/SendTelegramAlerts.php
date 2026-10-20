<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Alert;
use App\Models\Setting;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class SendTelegramAlerts extends Command
{
    protected $signature = 'alerts:telegram';
    protected $description = 'Send daily Telegram alerts for expiring items';

    public function handle()
    {
        $botToken = env('TELEGRAM_BOT_TOKEN') ?? Setting::where('key', 'telegramBotToken')->value('value');
        $chatId = env('TELEGRAM_CHAT_ID') ?? Setting::where('key', 'telegramChatId')->value('value');
        $botToken = Setting::normalizeValue('telegramBotToken', $botToken);
        $chatId = Setting::normalizeValue('telegramChatId', $chatId);

        if (!$botToken || !$chatId) {
            $this->error('Telegram credentials not configured.');
            return;
        }

        $alerts = Alert::with('box')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->where('category', 'expiration')
            ->get();

        if ($alerts->isEmpty()) {
            $this->info('No active alerts to send.');
            return;
        }

        $hospitalName = 'Medical Box Monitor';
        if (Schema::hasTable('settings')) {
            $hospitalName = Setting::normalizeValue('hospitalName', Setting::where('key', 'hospitalName')->value('value')) ?? $hospitalName;
        }

        $message = "🚨 *แจ้งเตือนยา/เวชภัณฑ์ฉุกเฉินใกล้หมดอายุ*\n";
        $message .= "🏥 *หน่วยงาน:* {$hospitalName}\n";
        $message .= "📅 *วันที่ตรวจสอบ:* " . now()->format('Y-m-d') . "\n\n";
        $message .= "--------------------------------------------------\n\n";

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

            $boxName = $alert->box_name ?? ($alert->box ? $alert->box->name : 'ไม่ระบุกล่อง');
            $locationName = $alert->location_name ?? 'ไม่ระบุสถานที่';
            
            // Safe check for schema discrepancies (description column vs dynamic template)
            $desc = (Schema::hasColumn('alerts', 'description') && $alert->description) 
                ? $alert->description 
                : "{$alert->drug_name} จำนวนคงเหลือ {$alert->quantity} {$alert->unit}";

            $message .= "*{$i}. {$alert->drug_name}*\n";
            $message .= "📦 *กล่อง:* {$boxName} ({$locationName})\n";
            $message .= "📝 *รายละเอียด:* {$desc}\n";
            $message .= "📅 *วันหมดอายุ:* {$alert->expiry_date}\n";
            $message .= "⚡ *สถานะ:* {$statusText}\n\n";
            $i++;
        }

        $message .= "--------------------------------------------------\n";
        $message .= "🔗 *เข้าจัดการระบบได้ที่:* " . env('APP_URL', 'http://localhost') . "\n";

        $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown',
        ]);

        if ($response->successful()) {
            $this->info('Telegram alerts sent successfully.');
            AuditLogger::record(null, 'alert.telegram.send', 'telegram', null, [
                'status' => 'success',
                'alert_count' => $alerts->count(),
                'chat_id' => $chatId,
            ]);
        } else {
            $this->error('Failed to send Telegram alerts: ' . $response->body());
            AuditLogger::record(null, 'alert.telegram.send', 'telegram', null, [
                'status' => 'failed',
                'alert_count' => $alerts->count(),
                'chat_id' => $chatId,
                'response' => $response->body(),
            ]);
        }
    }
}
