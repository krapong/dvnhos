<?php

namespace App\Http\Controllers\Api\V1\DrugStock;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Services\TelegramNotificationService;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SettingController extends BaseApiController
{
    public function __construct(
        private readonly TelegramNotificationService $telegramService
    ) {
    }

    public function index()
    {
        return $this->ok($this->payload());
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'hospital_name' => ['nullable', 'string', 'max:255'],
            'app_name' => ['nullable', 'string', 'max:255'],
            'footer_text' => ['nullable', 'string', 'max:255'],
            'registration_default_role' => [
                'nullable',
                'string',
                'max:255',
                Rule::in(Role::query()->where('guard_name', 'web')->pluck('name')->all()),
            ],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'expiry_alert_days' => ['nullable', 'integer', 'min:1'],
            'default_allocation_rule' => ['nullable', 'string', Rule::in(['FEFO', 'FIFO'])],
            'telegram_enabled' => ['nullable', 'boolean'],
            'telegram_bot_token' => ['nullable', 'string', 'max:255'],
            'telegram_chat_id' => ['nullable', 'string', 'max:255'],
            'telegram_base_url' => ['nullable', 'url', 'max:255'],
        ]);

        $this->storeValues([
            'hospital_name' => ['general', Arr::get($data, 'hospital_name')],
            'app_name' => ['general', Arr::get($data, 'app_name')],
            'footer_text' => ['general', Arr::get($data, 'footer_text')],
            'registration_default_role' => ['access', Arr::get($data, 'registration_default_role')],
            'low_stock_threshold' => ['stock_policy', Arr::get($data, 'low_stock_threshold')],
            'expiry_alert_days' => ['stock_policy', Arr::get($data, 'expiry_alert_days')],
            'default_allocation_rule' => ['stock_policy', Arr::get($data, 'default_allocation_rule')],
            'telegram_enabled' => ['telegram', array_key_exists('telegram_enabled', $data) ? (bool) $data['telegram_enabled'] : null],
            'telegram_bot_token' => ['telegram', filled(Arr::get($data, 'telegram_bot_token')) ? Arr::get($data, 'telegram_bot_token') : null],
            'telegram_chat_id' => ['telegram', Arr::get($data, 'telegram_chat_id')],
            'telegram_base_url' => ['telegram', Arr::get($data, 'telegram_base_url')],
        ]);

        return $this->ok($this->payload(), 'บันทึกการตั้งค่าเรียบร้อย');
    }

    public function testTelegram()
    {
        $message = "Drug Stock Alert: ทดสอบการส่ง Telegram จากระบบแอดมิน\nเวลา: " . now()->format('d/m/Y H:i:s');
        $result = $this->telegramService->send($message);

        return $this->ok($result, 'ส่งข้อความทดสอบ Telegram เรียบร้อย');
    }

    private function payload(): array
    {
        return [
            'general' => [
                'hospital_name' => SystemSetting::getValue('hospital_name', config('medstock.hospital_name', 'โรงพยาบาลเทพรัตนเวชชานุกูล')),
                'app_name' => SystemSetting::getValue('app_name', config('app.name', 'Drug Stock Alert System')),
                'footer_text' => SystemSetting::getValue('footer_text', 'ระบบคลังยาและแจ้งเตือนสต็อก'),
            ],
            'access' => [
                'roles' => Role::query()
                    ->where('guard_name', 'web')
                    ->withCount('permissions')
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Role $role) => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'permissions_count' => $role->permissions_count,
                    ])
                    ->values(),
                'permissions_count' => Permission::query()->where('guard_name', 'web')->count(),
                'default_role' => SystemSetting::getValue('registration_default_role', config('medstock.registration_default_role', 'Viewer')),
            ],
            'stock_policy' => [
                'low_stock_threshold' => (float) SystemSetting::getValue('low_stock_threshold', config('medstock.stock_policy.low_stock_threshold', 10)),
                'expiry_alert_days' => (int) SystemSetting::getValue('expiry_alert_days', config('medstock.stock_policy.expiry_alert_days', 90)),
                'default_allocation_rule' => SystemSetting::getValue('default_allocation_rule', config('medstock.stock_policy.default_allocation_rule', 'FEFO')),
            ],
            'telegram' => [
                'enabled' => filter_var(SystemSetting::getValue('telegram_enabled', config('services.telegram.enabled', false)), FILTER_VALIDATE_BOOL),
                'bot_token' => '',
                'chat_id' => SystemSetting::getValue('telegram_chat_id', config('services.telegram.chat_id')),
                'base_url' => SystemSetting::getValue('telegram_base_url', config('services.telegram.base_url', 'https://api.telegram.org')),
                'configured' => $this->telegramService->isEnabled(),
            ],
        ];
    }

    private function storeValues(array $pairs): void
    {
        foreach ($pairs as $key => [$group, $value]) {
            if ($value === null) {
                continue;
            }

            SystemSetting::putValue($key, $value, $group);
        }
    }
}
