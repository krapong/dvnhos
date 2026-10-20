<?php

namespace App\Services;

use App\Models\Bed;
use App\Models\Admission;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\Room;
use App\Models\SyncLog;
use App\Models\Ward;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class HosxpSyncService
{
    public function syncPatientCache(): array
    {
        $log = SyncLog::create([
            'source' => 'hosxp',
            'sync_type' => 'patient_cache',
            'status' => 'running',
            'started_at' => now(),
            'payload' => ['mode' => config('hosxp.mode')],
        ]);

        try {
            $count = config('hosxp.mode') === 'read_only' && config('hosxp.api_base_url')
                ? $this->syncFromApi()
                : $this->syncFromDatabase();

            $message = "ซิงก์ข้อมูลผู้ป่วยจาก HIS สำเร็จ {$count} รายการ";

            $log->update([
                'status' => 'success',
                'finished_at' => now(),
                'message' => $message,
            ]);

            return [
                'ok' => true,
                'synced' => $count,
                'message' => $message,
                'finished_at' => $log->fresh()->finished_at,
            ];
        } catch (Throwable $e) {
            $log->update([
                'status' => 'failed',
                'finished_at' => now(),
                'message' => $e->getMessage(),
            ]);

            Log::error('HOSxP sync failed', ['exception' => $e]);

            return [
                'ok' => false,
                'message' => 'เชื่อมต่อหรือซิงก์ข้อมูล HIS ไม่สำเร็จ: '.$e->getMessage(),
                'finished_at' => $log->fresh()->finished_at,
            ];
        }
    }

    protected function syncFromDatabase(): int
    {
        $view = config('hosxp.patient_view', 'v_patient_meal_ready');

        if (! $this->tableExists($view)) {
            return $this->syncFromHosxpTables();
        }

        $rows = DB::connection('hosxp')->table($view)->limit(500)->get();

        foreach ($rows as $row) {
            $this->upsertPatientFromHisRow((array) $row);
        }

        return (int) $rows->count();
    }

    private function syncFromHosxpTables(): int
    {
        $allergies = DB::connection('hosxp')
            ->table('opd_allergy')
            ->select('hn', DB::raw('group_concat(agent separator ", ") as allergies'))
            ->whereNotNull('agent')
            ->groupBy('hn');

        $chronicDiseases = DB::connection('hosxp')
            ->table('clinicmember')
            ->leftJoin('clinic', 'clinic.clinic', '=', 'clinicmember.clinic')
            ->select('clinicmember.hn', DB::raw('group_concat(distinct coalesce(clinic.name, clinicmember.other_chronic_text, clinicmember.clinic) separator ", ") as chronic_diseases'))
            ->where(function ($query): void {
                $query->whereNull('clinicmember.dchdate')->orWhere('clinicmember.dchdate', '0000-00-00');
            })
            ->where(function ($query): void {
                $query->whereNull('clinicmember.discharge')->orWhere('clinicmember.discharge', 'N')->orWhere('clinicmember.discharge', '');
            })
            ->groupBy('clinicmember.hn');

        $rows = DB::connection('hosxp')
            ->table('ipt')
            ->join('patient', 'patient.hn', '=', 'ipt.hn')
            ->leftJoin('ward', 'ward.ward', '=', 'ipt.ward')
            ->leftJoin('iptadm', function ($join): void {
                $join->on('iptadm.an', '=', 'ipt.an')
                    ->where(function ($query): void {
                        $query->whereNull('iptadm.outdate')->orWhere('iptadm.outdate', '0000-00-00');
                    });
            })
            ->leftJoin('bedno', 'bedno.bedno', '=', 'iptadm.bedno')
            ->leftJoin('opdscreen', 'opdscreen.vn', '=', 'ipt.vn')
            ->leftJoinSub($allergies, 'allergy_summary', 'allergy_summary.hn', '=', 'ipt.hn')
            ->leftJoinSub($chronicDiseases, 'chronic_summary', 'chronic_summary.hn', '=', 'ipt.hn')
            ->whereNull('ipt.dchdate')
            ->select([
                'ipt.hn',
                'ipt.an',
                'patient.pname as prefix',
                'patient.fname as first_name',
                'patient.lname as last_name',
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
                'chronic_summary.chronic_diseases as secondary_diagnosis',
                'patient.drugallergy as drugallergy',
                'allergy_summary.allergies as allergies',
                'ipt.update_datetime as source_updated_at',
            ])
            ->orderBy('ipt.ward')
            ->orderBy('iptadm.bedno')
            ->limit(500)
            ->get();

        $nutritionSummaries = $this->buildNutritionSummaries($rows);

        foreach ($rows as $row) {
            $data = (array) $row;

            if (! $data['allergies'] && ! empty($data['drugallergy'])) {
                $data['allergies'] = $data['drugallergy'];
            }

            $nutrition = $nutritionSummaries[$this->nutritionKey($data['an'] ?? null, $data['hn'] ?? null)] ?? null;
            $data['diet_order'] = $this->boolean($data['npo_status'] ?? null) ? 'NPO' : ($nutrition['diet_order'] ?? null);
            $data['therapeutic_diet'] = $nutrition['therapeutic_diet'] ?? null;
            $data['specific_group_diet'] = $nutrition['specific_group_diet'] ?? null;
            $data['food_allergy'] = $nutrition['food_allergy'] ?? null;
            $data['clinical_note'] = $nutrition['clinical_note'] ?? null;

            $this->upsertPatientFromHisRow($data);
        }

        return (int) $rows->count();
    }

    private function buildNutritionSummaries($admissions): array
    {
        if (! $this->tableExists('nutrition_food_ord') || ! $this->tableExists('nutrition_food_ord_detail')) {
            return [];
        }

        $ans = $admissions->pluck('an')->filter()->unique()->values();
        $hns = $admissions->pluck('hn')->filter()->unique()->values();

        if ($ans->isEmpty() && $hns->isEmpty()) {
            return [];
        }

        $details = DB::connection('hosxp')
            ->table('nutrition_food_ord as ord')
            ->join('nutrition_food_ord_detail as detail', 'detail.nutrition_food_ord_id', '=', 'ord.nutrition_food_ord_id')
            ->leftJoin('nutrition_food as food', 'food.nutrition_food_id', '=', 'detail.nutrition_food_id')
            ->leftJoin('nutrition_food_sub_type as subtype', 'subtype.nutrition_food_sub_type_id', '=', 'detail.nutrition_food_sub_type_id')
            ->leftJoin('nutrition_food_day as food_day', 'food_day.nutrition_food_day_id', '=', 'detail.nutrition_food_day_id')
            ->leftJoin('nutrition_food_sub_day as sub_day', 'sub_day.nutrition_food_sub_day_id', '=', 'detail.nutrition_food_sub_day_id')
            ->where(function ($query) use ($ans, $hns): void {
                if ($ans->isNotEmpty()) {
                    $query->orWhereIn('ord.an', $ans);
                }

                if ($hns->isNotEmpty()) {
                    $query->orWhereIn('ord.hn', $hns);
                }
            })
            ->where(function ($query): void {
                $query->whereNull('detail.change_status')->orWhere('detail.change_status', '!=', 'Y');
            })
            ->select([
                'ord.an',
                'ord.hn',
                'detail.nutrition_food_ord_detail_date',
                'detail.nutrition_food_ord_detail_time',
                'detail.nutrition_food_ord_detail',
                'detail.food_allergy',
                'detail.food_disease',
                'detail.food_special',
                'food.nutrition_food_name',
                'subtype.nutrition_food_sub_type_name',
                'food_day.nutrition_food_day_name',
                'sub_day.nutrition_food_sub_day_name',
            ])
            ->orderByDesc('ord.nutrition_food_ord_date')
            ->orderByDesc('detail.nutrition_food_ord_detail_date')
            ->orderBy('detail.nutrition_food_ord_detail_time')
            ->limit(2000)
            ->get();

        $summaries = [];

        foreach ($details as $detail) {
            $row = (array) $detail;
            $key = $this->nutritionKey($row['an'] ?? null, $row['hn'] ?? null);

            if (! $key) {
                continue;
            }

            $meal = $this->mealLabel($row);
            $foodName = $row['nutrition_food_name']
                ?: $row['nutrition_food_ord_detail']
                ?: $row['nutrition_food_sub_type_name']
                ?: 'ไม่ระบุรายการอาหาร';

            $modifiers = collect([
                $row['nutrition_food_sub_day_name'] ?? null,
                $row['nutrition_food_sub_type_name'] ?? null,
                $row['food_disease'] ?? null,
                $row['food_special'] ?? null,
                $row['food_allergy'] ? 'แพ้/งด: '.$row['food_allergy'] : null,
            ])->filter()->unique()->implode(', ');

            $line = $modifiers ? "{$foodName} ({$modifiers})" : $foodName;
            $date = $row['nutrition_food_ord_detail_date'] ?? null;
            $datePrefix = $date ? '['.$date.'] ' : '';
            $summaries[$key]['meals'][$meal][] = $datePrefix.$line;
            $summaries[$key]['types'][] = $row['nutrition_food_sub_type_name'] ?: $foodName;
            $summaries[$key]['therapeutic_diets'][] = $row['food_disease'] ?? null;
            $summaries[$key]['specific_group_diets'][] = $row['food_special'] ?? null;
            $summaries[$key]['food_allergies'][] = $row['food_allergy'] ?? null;
        }

        foreach ($summaries as $key => $summary) {
            $mealLines = collect($summary['meals'] ?? [])
                ->sortKeysUsing(fn (string $left, string $right): int => $this->mealOrder($left) <=> $this->mealOrder($right))
                ->map(fn (array $foods, string $meal): string => $meal.': '.collect($foods)->unique()->take(8)->implode(', '))
                ->values()
                ->implode(' | ');

            $summaries[$key] = [
                'diet_order' => collect($summary['types'] ?? [])->filter()->unique()->take(4)->implode(', ') ?: null,
                'therapeutic_diet' => collect($summary['therapeutic_diets'] ?? [])->filter()->unique()->take(6)->implode(', ') ?: null,
                'specific_group_diet' => collect($summary['specific_group_diets'] ?? [])->filter()->unique()->take(6)->implode(', ') ?: null,
                'food_allergy' => collect($summary['food_allergies'] ?? [])->filter()->unique()->take(6)->implode(', ') ?: null,
                'clinical_note' => $mealLines ? 'รายการอาหาร HIS: '.$mealLines : null,
            ];
        }

        return $summaries;
    }

    private function tableExists(string $table): bool
    {
        return DB::connection('hosxp')
            ->table('information_schema.tables')
            ->whereRaw('table_schema = database()')
            ->where('table_name', $table)
            ->exists();
    }

    protected function syncFromApi(): int
    {
        return 0;
    }

    private function upsertPatientFromHisRow(array $row): void
    {
        $hn = $this->value($row, ['hn', 'HN', 'patient_hn']);

        if (! $hn) {
            return;
        }

        $ward = $this->resolveWard($row);
        $room = $this->resolveRoom($row, $ward);
        $bed = $this->resolveBed($row, $ward, $room);
        $height = $this->decimal($this->value($row, ['height_cm', 'height', 'pt_height']), 0, 9999.99);
        $weight = $this->decimal($this->value($row, ['weight_kg', 'weight', 'pt_weight']), 0, 9999.99);
        $bmi = $this->decimal($this->value($row, ['bmi']), 0, 9999.99);

        if (! $bmi && $height && $weight) {
            $heightMeter = $height / 100;
            if ($heightMeter > 0) {
                $bmi = $this->decimal(round($weight / ($heightMeter * $heightMeter), 2), 0, 9999.99);
            }
        }

        $patient = Patient::updateOrCreate(
            ['hn' => (string) $hn],
            [
                'an' => $this->value($row, ['an', 'AN', 'vn']),
                'prefix' => $this->value($row, ['prefix', 'pname', 'title']),
                'first_name' => $this->value($row, ['first_name', 'fname', 'name']) ?: '-',
                'last_name' => $this->value($row, ['last_name', 'lname', 'surname']) ?: '-',
                'ward_id' => $ward?->id,
                'room_id' => $room?->id,
                'bed_id' => $bed?->id,
                'sex' => $this->value($row, ['sex', 'gender']),
                'date_of_birth' => $this->date($this->value($row, ['date_of_birth', 'birthday', 'birthdate'])),
                'weight_kg' => $weight,
                'height_cm' => $height,
                'bmi' => $bmi,
                'bmi_taken_at' => $this->dateTime($this->value($row, ['bmi_taken_at', 'vstdate', 'updated_at'])),
                'npo_status' => $this->boolean($this->value($row, ['npo_status', 'npo', 'is_npo'])),
                'diet_order' => $this->value($row, ['diet_order', 'diet_name', 'food_type']),
                'therapeutic_diet' => $this->value($row, ['therapeutic_diet', 'food_disease']),
                'specific_group_diet' => $this->value($row, ['specific_group_diet', 'food_special']),
                'food_allergy' => $this->value($row, ['food_allergy']),
                'primary_diagnosis' => $this->value($row, ['primary_diagnosis', 'diagnosis', 'dx']),
                'secondary_diagnosis' => $this->value($row, ['secondary_diagnosis', 'secondary_dx', 'chronic_diseases']),
                'clinical_note' => $this->value($row, ['clinical_note', 'note', 'remark']),
                'source_updated_at' => $this->dateTime($this->value($row, ['source_updated_at', 'updated_at', 'last_update'])),
                'synced_at' => now(),
            ],
        );

        $this->syncAllergies($patient, $this->value($row, ['allergies', 'allergy', 'drugallergy', 'food_allergy']));
        $this->upsertAdmissionFromHisRow($patient, $row, $ward, $room, $bed);
    }

    private function upsertAdmissionFromHisRow(Patient $patient, array $row, ?Ward $ward, ?Room $room, ?Bed $bed): void
    {
        $status = strtoupper((string) $this->value($row, ['status', 'discharge', 'admission_status', 'discharge_status']));
        $status = $status !== '' ? $status : 'N';

        if (! in_array($status, ['N', 'Y'], true)) {
            $status = 'N';
        }

        $admittedAt = $this->dateTime($this->value($row, ['admitted_at', 'admit_date', 'in_date', 'regdate', 'admission_date']));
        $dischargedAt = $this->dateTime($this->value($row, ['discharged_at', 'dchdate', 'out_date', 'discharge_date']));

        if ($status === 'N') {
            $dischargedAt = null;
        }

        Admission::updateOrCreate(
            ['patient_id' => $patient->id],
            [
                'ward_id' => $ward?->id,
                'room_id' => $room?->id,
                'bed_id' => $bed?->id,
                'admitted_at' => $admittedAt,
                'discharged_at' => $dischargedAt,
                'status' => $status,
                'transfer_reason' => $this->value($row, ['transfer_reason', 'movement_reason']),
            ],
        );
    }

    private function resolveWard(array $row): ?Ward
    {
        $code = $this->value($row, ['ward_code', 'ward']);
        $name = $this->value($row, ['ward_name', 'department']);

        if (! $code && ! $name) {
            return null;
        }

        return Ward::firstOrCreate(
            ['code' => (string) ($code ?: Str::slug($name))],
            ['name' => (string) ($name ?: $code), 'active' => true],
        );
    }

    private function resolveRoom(array $row, ?Ward $ward): ?Room
    {
        $code = $this->value($row, ['room_code', 'room', 'room_name']);

        if (! $code || ! $ward) {
            return null;
        }

        return Room::firstOrCreate(
            ['ward_id' => $ward->id, 'code' => (string) $code],
            ['name' => (string) $code],
        );
    }

    private function resolveBed(array $row, ?Ward $ward, ?Room $room): ?Bed
    {
        $code = $this->value($row, ['bed_code', 'bed', 'bedno']);

        if (! $code || ! $ward) {
            return null;
        }

        return Bed::firstOrCreate(
            ['ward_id' => $ward->id, 'room_id' => $room?->id, 'code' => (string) $code],
            ['name' => (string) $code, 'active' => true],
        );
    }

    private function syncAllergies(Patient $patient, mixed $rawAllergies): void
    {
        if (! $rawAllergies) {
            return;
        }

        $allergies = collect(preg_split('/[,;|]/', (string) $rawAllergies))
            ->map(fn (string $allergen): string => trim($allergen))
            ->filter()
            ->unique();

        foreach ($allergies as $allergen) {
            PatientAllergy::firstOrCreate([
                'patient_id' => $patient->id,
                'allergen' => $allergen,
            ]);
        }
    }

    private function nutritionKey(mixed $an, mixed $hn): ?string
    {
        if ($an) {
            return 'an:'.$an;
        }

        if ($hn) {
            return 'hn:'.$hn;
        }

        return null;
    }

    private function mealLabel(array $row): string
    {
        $label = $row['nutrition_food_day_name'] ?: null;

        if ($label) {
            if (Str::contains($label, ['เช้า'])) {
                return 'เช้า';
            }

            if (Str::contains($label, ['เที่ยง', 'กลางวัน'])) {
                return 'กลางวัน';
            }

            if (Str::contains($label, ['เย็น'])) {
                return 'เย็น';
            }

            return (string) $label;
        }

        $time = (string) ($row['nutrition_food_ord_detail_time'] ?? '');

        if ($time >= '04:00:00' && $time < '10:30:00') {
            return 'เช้า';
        }

        if ($time >= '10:30:00' && $time < '15:30:00') {
            return 'กลางวัน';
        }

        return $time ? 'เย็น' : 'รายการอาหาร';
    }

    private function mealOrder(string $meal): int
    {
        return match ($meal) {
            'เช้า' => 1,
            'กลางวัน' => 2,
            'เย็น' => 3,
            default => 4,
        };
    }

    private function value(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }

        return null;
    }

    private function decimal(mixed $value, ?float $min = null, ?float $max = null): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $number = round((float) $value, 2);

        if ($min !== null && $number < $min) {
            return null;
        }

        if ($max !== null && $number > $max) {
            return null;
        }

        return $number;
    }

    private function boolean(mixed $value): bool
    {
        return in_array(Str::lower((string) $value), ['1', 'true', 'y', 'yes', 'npo', 'งดอาหาร'], true);
    }

    private function date(mixed $value): ?string
    {
        return $this->dateTime($value)?->toDateString();
    }

    private function dateTime(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
