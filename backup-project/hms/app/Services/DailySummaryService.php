<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\MealOrder;
use App\Models\Patient;
use App\Models\SyncLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DailySummaryService
{
    private const CURRENT_INPATIENT_WARD_CODES = ['002', '003'];

    public function buildForDate(string $serviceDate): array
    {
        $activeSyncAt = $this->latestSuccessfulSyncAt();
        $hisPatients = $this->currentHisInpatients();
        $hisPatientCount = $this->countCurrentHisInpatients();
        $currentHns = $hisPatients->pluck('hn')->filter()->unique()->values();
        $patients = Patient::query()
            ->with(['ward'])
            ->whereIn('hn', $currentHns->all())
            ->get();

        $serviceOrders = MealOrder::query()
            ->whereDate('meal_date', $serviceDate)
            ->whereNotIn('status', [
                OrderStatus::Cancelled->value,
                OrderStatus::Rejected->value,
            ])
            ->get();

        $orderedPatientIds = $serviceOrders->pluck('patient_id')->filter()->unique()->values();
        $patientsWithOrders = $patients->whereIn('id', $orderedPatientIds->all())->values();
        $patientsWithoutOrders = $patients->reject(fn ($patient) => $orderedPatientIds->contains($patient->id))->values();
        $npoPatients = $hisPatients->filter(fn ($patient) => $this->boolean(data_get($patient, 'npo_status')))->values();
        $highRiskPatients = $hisPatients->filter(function ($patient): bool {
            $bmi = data_get($patient, 'bmi');

            return $bmi !== null && ((float) $bmi < 18.5 || (float) $bmi >= 30);
        })->values();
        $byWard = $this->groupByWard($hisPatients);
        $maleCount = $this->countCurrentHisInpatientsByWard('02');
        $femaleCount = $this->countCurrentHisInpatientsByWard('03');
        $otherCount = 0;

        return [
            'service_date' => $serviceDate,
            'active_sync_at' => $activeSyncAt?->toDateTimeString(),
            'patients' => $hisPatientCount,
            'active_patients' => $hisPatientCount,
            'male_count' => $maleCount,
            'female_count' => $femaleCount,
            'other_count' => $otherCount,
            'total_orders' => $patientsWithOrders->count(),
            'patients_with_orders' => $patientsWithOrders->count(),
            'patients_without_orders' => $patientsWithoutOrders->count(),
            'with_order' => $patientsWithOrders->count(),
            'without_order' => $patientsWithoutOrders->count(),
            'npo' => $npoPatients->count(),
            'npo_patients' => $npoPatients->count(),
            'high_bmi' => $highRiskPatients->count(),
            'high_risk_patients' => $highRiskPatients->count(),
            'stat_orders' => $serviceOrders->where('is_stat', true)->count(),
            'by_ward' => $byWard,
            'by_status' => $this->groupByStatus($serviceOrders),
        ];
    }

    protected function latestSuccessfulSyncAt(): ?CarbonImmutable
    {
        $syncLog = SyncLog::query()
            ->where('status', 'success')
            ->latest('id')
            ->first();

        if (! $syncLog?->finished_at && ! $syncLog?->started_at) {
            return null;
        }

        return CarbonImmutable::parse($syncLog->finished_at ?? $syncLog->started_at);
    }

    protected function countCurrentHisInpatients(): int
    {
        return (int) DB::connection('hosxp')
            ->table('ipt')
            ->where(function (Builder $query): void {
                $query->whereNull('dchdate')
                    ->orWhere('dchdate', '0000-00-00');
            })
            ->where(function (Builder $query): void {
                $query->whereNull('no_visit')
                    ->orWhere('no_visit', '!=', 'Y');
            })
            ->whereRaw('lpad(trim(ward), 3, "0") in (?, ?)', self::CURRENT_INPATIENT_WARD_CODES)
            ->count();
    }

    protected function countCurrentHisInpatientsByWard(string $wardCode): int
    {
        return (int) DB::connection('hosxp')
            ->table('ipt')
            ->where(function (Builder $query): void {
                $query->whereNull('dchdate')
                    ->orWhere('dchdate', '0000-00-00');
            })
            ->where(function (Builder $query): void {
                $query->whereNull('no_visit')
                    ->orWhere('no_visit', '!=', 'Y');
            })
            ->whereRaw('lpad(trim(ward), 3, "0") = ?', [str_pad($wardCode, 3, '0', STR_PAD_LEFT)])
            ->count();
    }

    protected function currentHisInpatients(): Collection
    {
        return DB::connection('hosxp')
            ->table('ipt')
            ->leftJoin('patient', 'patient.hn', '=', 'ipt.hn')
            ->leftJoin('ward', 'ward.ward', '=', 'ipt.ward')
            ->leftJoin('iptadm', function ($join): void {
                $join->on('iptadm.an', '=', 'ipt.an')
                    ->where(function ($query): void {
                        $query->whereNull('iptadm.outdate')->orWhere('iptadm.outdate', '0000-00-00');
                    });
            })
            ->leftJoin('bedno', 'bedno.bedno', '=', 'iptadm.bedno')
            ->leftJoin('opdscreen', 'opdscreen.vn', '=', 'ipt.vn')
            ->where(function (Builder $query): void {
                $query->whereNull('ipt.dchdate')
                    ->orWhere('ipt.dchdate', '0000-00-00');
            })
            ->where(function (Builder $query): void {
                $query->whereNull('ipt.no_visit')
                    ->orWhere('ipt.no_visit', '!=', 'Y');
            })
            ->whereRaw('lpad(trim(ipt.ward), 3, "0") in (?, ?)', self::CURRENT_INPATIENT_WARD_CODES)
            ->select([
                'ipt.hn',
                'ipt.an',
                DB::raw('coalesce(patient.pname, "") as prefix'),
                DB::raw('coalesce(patient.fname, "") as first_name'),
                DB::raw('coalesce(patient.lname, "") as last_name'),
                'ipt.ward as ward_code',
                'ward.name as ward_name',
                DB::raw('coalesce(iptadm.roomno, bedno.roomno) as room_code'),
                'iptadm.bedno as bed_code',
                'patient.sex',
                'patient.birthday as date_of_birth',
                'opdscreen.bw as weight_kg',
                'opdscreen.height as height_cm',
                'opdscreen.bmi as bmi',
                'opdscreen.vstdate as bmi_taken_at',
                'ipt.no_food as npo_status',
                'ipt.prediag as primary_diagnosis',
                'ipt.update_datetime as source_updated_at',
            ])
            ->orderBy('ipt.ward')
            ->orderBy('iptadm.bedno')
            ->get();
    }

    protected function groupByWard(Collection $records): array
    {
        $grouped = [
            'male' => ['key' => 'male', 'ward_name' => 'ตึกผู้ป่วยในชาย', 'total' => 0, 'npo' => 0, 'stat' => 0, 'ward_codes' => []],
            'female' => ['key' => 'female', 'ward_name' => 'ตึกผู้ป่วยในหญิง', 'total' => 0, 'npo' => 0, 'stat' => 0, 'ward_codes' => []],
            'other' => ['key' => 'other', 'ward_name' => 'พื้นที่อื่น', 'total' => 0, 'npo' => 0, 'stat' => 0, 'ward_codes' => []],
        ];

        foreach ($records as $record) {
            $wardCode = strtoupper((string) data_get($record, 'ward_code', ''));
            $groupKey = $this->resolveWardGroupKey($wardCode, (string) data_get($record, 'ward_name', ''));

            $grouped[$groupKey]['total']++;
            if ($wardCode !== '') {
                $grouped[$groupKey]['ward_codes'][] = $wardCode;
            }
            if ($this->boolean(data_get($record, 'npo_status'))) {
                $grouped[$groupKey]['npo']++;
            }
        }

        $grouped = array_map(function (array $item): array {
            $item['ward_codes'] = array_values(array_unique(array_filter($item['ward_codes'])));
            return $item;
        }, $grouped);

        return array_values($grouped);
    }

    protected function groupByStatus(Collection $orders): array
    {
        return $orders
            ->groupBy(fn ($order) => (string) $order->status)
            ->map(fn (Collection $group) => $group->count())
            ->all();
    }

    protected function resolveWardGroupKey(string $wardCode, string $wardName): string
    {
        if (in_array($wardCode, ['02', '2', '002'], true)) {
            return 'male';
        }

        if ($wardCode === '003') {
            return 'female';
        }

        if ($wardName === '') {
            return 'other';
        }

        if (str_contains($wardName, 'ชาย') || str_contains($wardName, 'พระ') || str_contains($wardName, 'สามัญ')) {
            return 'male';
        }

        if (str_contains($wardName, 'หญิง') || str_contains($wardName, 'เด็ก') || str_contains($wardName, 'พิเศษ')) {
            return 'female';
        }

        return 'other';
    }

    protected function boolean(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'y', 'yes', 'npo'], true);
    }
}
