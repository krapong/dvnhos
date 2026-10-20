<?php

namespace App\Services;

use App\Models\TelegramNotificationSchedule;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class TelegramScheduleService
{
    public function __construct(
        private readonly DailySummaryService $dailySummary,
        private readonly TelegramNotificationService $telegram,
    ) {
    }

    public function ensureDefaults(?int $userId = null): Collection
    {
        $defaults = [
            ['key' => 'breakfast', 'label' => 'มื้อเช้า', 'notify_at' => '06:00'],
            ['key' => 'lunch', 'label' => 'มื้อกลางวัน', 'notify_at' => '10:30'],
            ['key' => 'dinner', 'label' => 'มื้อเย็น', 'notify_at' => '15:30'],
        ];

        foreach ($defaults as $default) {
            TelegramNotificationSchedule::query()->firstOrCreate(
                ['key' => $default['key']],
                array_merge($default, [
                    'enabled' => true,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]),
            );
        }

        return $this->schedules();
    }

    public function schedules(): Collection
    {
        $order = ['breakfast' => 1, 'lunch' => 2, 'dinner' => 3];

        return TelegramNotificationSchedule::query()
            ->get()
            ->sortBy(fn (TelegramNotificationSchedule $schedule) => $order[$schedule->key] ?? 99)
            ->values();
    }

    public function update(array $items, int $userId): Collection
    {
        $this->ensureDefaults($userId);

        foreach ($items as $item) {
            TelegramNotificationSchedule::query()
                ->where('key', $item['key'])
                ->update([
                    'notify_at' => $item['notify_at'],
                    'enabled' => (bool) $item['enabled'],
                    'updated_by' => $userId,
                ]);
        }

        return $this->schedules();
    }

    public function sendDue(CarbonInterface $now): array
    {
        $this->ensureDefaults();

        $currentTime = $now->format('H:i');
        $today = $now->toDateString();
        $sent = [];

        TelegramNotificationSchedule::query()
            ->where('enabled', true)
            ->whereTime('notify_at', '<=', $currentTime)
            ->where(function ($query) use ($today): void {
                $query->whereNull('last_sent_date')
                    ->orWhereDate('last_sent_date', '!=', $today);
            })
            ->get()
            ->each(function (TelegramNotificationSchedule $schedule) use ($now, $today, &$sent): void {
                $result = $this->sendSchedule($schedule, $now);

                $schedule->forceFill([
                    'last_sent_date' => $today,
                    'last_sent_at' => $now,
                ])->save();

                $sent[] = [
                    'key' => $schedule->key,
                    'label' => $schedule->label,
                    'result' => $result,
                ];
            });

        return [
            'checked_at' => $now->toDateTimeString(),
            'sent_count' => count($sent),
            'sent' => $sent,
        ];
    }

    public function sendTest(?TelegramNotificationSchedule $schedule = null): array
    {
        $schedule ??= $this->ensureDefaults()->first();

        return $this->sendSchedule($schedule, now(), true);
    }

    protected function buildDashboardSummary(): array
    {
        $patients = \App\Models\Patient::all();

        $total = $patients->count();
        $male = $patients->where('sex', '1')->count();
        $female = $patients->where('sex', '2')->count();

        $withOrder = 0;
        $normal = 0;
        $therapeutic = 0;
        $npo = 0;
        $highBmi = 0;

        foreach ($patients as $p) {
            if ($p->bmi && ($p->bmi < 18.5 || $p->bmi >= 30)) {
                $highBmi++;
            }
            
            if ($p->npo_status) {
                $npo++;
                $withOrder++;
                continue;
            }
            
            $hasMeal = false;
            if (!empty($p->diet_order) || (str_contains((string)$p->clinical_note, 'รายการอาหาร HIS'))) {
                $hasMeal = true;
            }
            
            if ($hasMeal) {
                $withOrder++;
                if (!empty($p->therapeutic_diet)) {
                    $therapeutic++;
                } else {
                    $normal++;
                }
            }
        }

        return [
            'total' => $total,
            'male' => $male,
            'female' => $female,
            'with_order' => $withOrder,
            'without_order' => $total - $withOrder,
            'normal' => $normal,
            'therapeutic' => $therapeutic,
            'npo' => $npo,
            'high_bmi' => $highBmi,
        ];
    }

    protected function sendSchedule(TelegramNotificationSchedule $schedule, CarbonInterface $now, bool $isTest = false): array
    {
        $serviceDate = $now->toDateString();
        $stats = $this->buildDashboardSummary();
        $summary = [
            'service_date' => $serviceDate,
            'stats' => $stats,
        ];

        $message = $this->buildMessage($schedule, $summary, $now, $isTest);

        return $this->telegram->send($message, [
            'schedule_key' => $schedule->key,
            'schedule_label' => $schedule->label,
            'service_date' => $serviceDate,
            'is_test' => $isTest,
        ]);
    }

    protected function buildMessage(TelegramNotificationSchedule $schedule, array $summary, CarbonInterface $now, bool $isTest): string
    {
        $prefix = $isTest ? '[ทดสอบ] ' : '';
        $stats = $summary['stats'];

        return "{$prefix}<b>แจ้งเตือนอาหารผู้ป่วย {$schedule->label}</b>\n"
            ."วันที่ {$summary['service_date']} เวลา {$now->format('H:i')} น.\n\n"
            ."📊 <b>สรุปข้อมูลผู้ป่วยปัจจุบัน:</b>\n"
            ."- ผู้ป่วยทั้งหมด: {$stats['total']} คน (ชาย {$stats['male']} / หญิง {$stats['female']})\n"
            ."- มีรายการสั่งอาหาร: {$stats['with_order']} คน\n"
            ."- ไม่ได้สั่งอาหาร: {$stats['without_order']} คน\n\n"
            ."🍽 <b>รายละเอียดอาหาร (เฉพาะผู้ที่มีคำสั่ง):</b>\n"
            ."- อาหารธรรมดา: {$stats['normal']} คน\n"
            ."- อาหารเฉพาะโรค: {$stats['therapeutic']} คน\n"
            ."- NPO (งดน้ำงดอาหาร): {$stats['npo']} คน\n\n"
            ."⚠️ <b>ข้อมูลสุขภาพ:</b>\n"
            ."- ผู้ป่วย BMI เสี่ยง: {$stats['high_bmi']} คน";
    }
}
