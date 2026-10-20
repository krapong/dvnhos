<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\MealOrder;
use App\Models\Patient;
use App\Models\Ward;
use Illuminate\Support\Collection;

class DailySummaryService
{
    public function buildForDate(string $serviceDate): array
    {
        $patients = Patient::with(['ward'])->get();

        $patientsWithMeals = collect();

        foreach ($patients as $patient) {
            $hasMeal = false;
            
            if ($patient->npo_status) {
                $hasMeal = true;
            } elseif (!empty($patient->diet_order)) {
                $hasMeal = true;
            } elseif (!empty($patient->clinical_note) && str_contains($patient->clinical_note, 'รายการอาหาร HIS')) {
                $hasMeal = true;
            }

            if ($hasMeal) {
                $patientsWithMeals->push($patient);
            }
        }

        return [
            'service_date' => $serviceDate,
            'total_orders' => $patientsWithMeals->count(),
            'stat_orders' => 0,
            'npo_orders' => $patientsWithMeals->where('npo_status', true)->count(),
            'by_ward' => $this->groupByWard($patientsWithMeals),
            'by_status' => [],
        ];
    }

    protected function groupByWard(Collection $orders): array
    {
        $grouped = [
            'male' => ['ward_name' => 'ตึกผู้ป่วยชาย', 'total' => 0, 'npo' => 0, 'stat' => 0],
            'female' => ['ward_name' => 'ตึกผู้ป่วยหญิง', 'total' => 0, 'npo' => 0, 'stat' => 0],
        ];

        foreach ($orders as $order) {
            $wardName = $order->ward?->name ?? $order->patient?->ward_name ?? '';

            if (empty($wardName)) {
                continue;
            }

            $groupKey = 'other';
            if (str_contains($wardName, 'ชาย') || str_contains($wardName, 'พระ') || str_contains($wardName, 'สามัญ')) {
                $groupKey = 'male';
            } elseif (str_contains($wardName, 'หญิง') || str_contains($wardName, 'เด็ก') || str_contains($wardName, 'พิเศษ')) {
                $groupKey = 'female';
            }

            if ($groupKey !== 'other') {
                $grouped[$groupKey]['total']++;
                if ($order->npo_status) {
                    $grouped[$groupKey]['npo']++;
                }
            }
        }

        return array_values($grouped);
    }
}
