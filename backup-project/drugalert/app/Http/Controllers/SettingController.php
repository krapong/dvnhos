<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(Setting::normalizedMap());
    }

    public function update(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['super_admin'])) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $settings = $request->all();
        
        // Extract telegram settings to write to .env
        $envUpdates = [];
        if (array_key_exists('telegramBotToken', $settings)) {
            $envUpdates['TELEGRAM_BOT_TOKEN'] = $settings['telegramBotToken'] ?? '';
            unset($settings['telegramBotToken']);
        }
        if (array_key_exists('telegramChatId', $settings)) {
            $envUpdates['TELEGRAM_CHAT_ID'] = $settings['telegramChatId'] ?? '';
            unset($settings['telegramChatId']);
        }

        if (!empty($envUpdates)) {
            // Use defer() to update the .env file after the response is sent.
            // This prevents the local development server (php artisan serve) from restarting
            // and killing the active connection before the JSON response is sent.
            defer(fn () => $this->updateEnvFile($envUpdates));
        }

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => json_encode($value, JSON_UNESCAPED_UNICODE)]);
        }

        $this->recordAudit($user->id, 'settings.update', 'settings', null, [
            'after' => $this->redactSensitiveData(Setting::normalizedMap()),
        ]);

        return response()->json(Setting::normalizedMap());
    }

    public function testTelegram(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['super_admin'])) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $botToken = $request->input('telegramBotToken') ?? env('TELEGRAM_BOT_TOKEN') ?? Setting::where('key', 'telegramBotToken')->value('value');
        $chatId = $request->input('telegramChatId') ?? env('TELEGRAM_CHAT_ID') ?? Setting::where('key', 'telegramChatId')->value('value');
        
        $botToken = Setting::normalizeValue('telegramBotToken', $botToken);
        $chatId = Setting::normalizeValue('telegramChatId', $chatId);

        if (!$botToken || !$chatId) {
            return response()->json(['error' => 'กรุณากรอก Telegram Bot Token และ Chat ID ก่อนทำการทดสอบ'], 400);
        }

        // Send a test message
        $message = "🔔 *ทดสอบระบบแจ้งเตือน Telegram*\n\n";
        $message .= "✅ การเชื่อมต่อระบบสำเร็จเรียบร้อย\n";
        $message .= "🏥 หน่วยงาน: " . (Setting::normalizeValue('hospitalName', Setting::where('key', 'hospitalName')->value('value')) ?? 'Medical Box Monitor') . "\n";
        $message .= "📅 เวลา: " . now()->format('Y-m-d H:i:s') . "\n";
        $message .= "👤 ผู้ทดสอบ: " . ($user->display_name ?? $user->name ?? $user->username);

        $response = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown',
        ]);

        if ($response->successful()) {
            $this->recordAudit($user->id, 'settings.telegram.test', 'telegram', null, [
                'status' => 'success',
                'chat_id' => $chatId,
            ]);
            return response()->json(['message' => 'ส่งข้อความทดสอบไปยัง Telegram สำเร็จแล้ว!']);
        } else {
            return response()->json(['error' => 'ส่งข้อความล้มเหลว: ' . $response->body()], 400);
        }
    }

    protected function updateEnvFile(array $data)
    {
        $path = base_path('.env');
        if (!file_exists($path)) {
            return;
        }

        $content = file_get_contents($path);
        foreach ($data as $key => $value) {
            $value = str_replace('"', '\\"', $value);
            $pattern = "/^{$key}=.*/m";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$key}=\"{$value}\"", $content);
            } else {
                if (substr($content, -1) !== "\n") {
                    $content .= "\n";
                }
                $content .= "{$key}=\"{$value}\"\n";
            }
        }
        file_put_contents($path, $content);
    }
}
