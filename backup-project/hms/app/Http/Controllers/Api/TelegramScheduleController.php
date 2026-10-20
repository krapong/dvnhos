<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TelegramNotificationSchedule;
use App\Services\TelegramScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TelegramScheduleController extends Controller
{
    public function __construct(private readonly TelegramScheduleService $schedules)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'enabled' => (bool) config('telegram.enabled'),
            'has_bot_token' => filled(config('telegram.bot_token')),
            'has_chat_id' => filled(config('telegram.chat_id')),
            'bot_token' => config('telegram.bot_token', ''),
            'chat_id' => config('telegram.chat_id', ''),
            'schedules' => $this->schedules->ensureDefaults($request->user()?->id),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'schedules' => ['required', 'array', 'size:3'],
            'schedules.*.key' => ['required', Rule::in(['breakfast', 'lunch', 'dinner'])],
            'schedules.*.notify_at' => ['required', 'date_format:H:i'],
            'schedules.*.enabled' => ['required', 'boolean'],
        ]);

        return response()->json([
            'ok' => true,
            'schedules' => $this->schedules->update($data['schedules'], $request->user()->id),
        ]);
    }

    public function test(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => ['nullable', Rule::in(['breakfast', 'lunch', 'dinner'])],
        ]);

        $schedule = null;
        if (! empty($data['key'])) {
            $schedule = TelegramNotificationSchedule::query()->where('key', $data['key'])->first();
        }

        return response()->json($this->schedules->sendTest($schedule));
    }

    public function updateConfig(Request $request): JsonResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'bot_token' => ['nullable', 'string'],
            'chat_id' => ['nullable', 'string'],
        ]);

        $this->updateEnv([
            'TELEGRAM_ENABLED' => $data['enabled'] ? 'true' : 'false',
            'TELEGRAM_BOT_TOKEN' => $data['bot_token'] ?? '',
            'TELEGRAM_CHAT_ID' => $data['chat_id'] ?? '',
        ]);

        \Illuminate\Support\Facades\Artisan::call('config:clear');

        return response()->json([
            'ok' => true,
            'message' => 'บันทึกการตั้งค่า Telegram สำเร็จ'
        ]);
    }

    protected function updateEnv(array $data): void
    {
        $path = base_path('.env');
        
        if (!file_exists($path)) {
            return;
        }
        
        $env = file_get_contents($path);

        foreach ($data as $key => $value) {
            $escapedValue = preg_match('/\s/', $value) ? '"' . addslashes($value) . '"' : $value;
            
            if (preg_match("/^{$key}=.*/m", $env)) {
                $env = preg_replace("/^{$key}=.*/m", "{$key}={$escapedValue}", $env);
            } else {
                $env .= "\n{$key}={$escapedValue}";
            }
        }

        file_put_contents($path, $env);
    }
}
