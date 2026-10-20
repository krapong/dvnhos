<?php

namespace App\Http\Controllers;

use App\Models\Appointment as LocalAppointment;
use App\Models\Clinic as LocalClinic;
use App\Models\His\Appointment as HisAppointment;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardDetailController extends Controller
{
    public function getDetails(Request $request)
    {
        // Support both 'date' and 'start_date' / 'end_date' for backward compatibility
        $singleDate = $request->input('date');
        $startDate = $request->input('start_date', $singleDate ?: Carbon::today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', $singleDate ?: Carbon::today()->format('Y-m-d'));
        
        $clinicCode = $request->input('clinic_code', '');
        $statusFilter = $request->input('status', 'ทั้งหมด');

        $localClinics = LocalClinic::where('is_active', true)->get()->keyBy('code');
        $activeClinicCodes = $localClinics->keys()->toArray();
        $clinicsToQuery = $clinicCode ? [$clinicCode] : $activeClinicCodes;
        // HOSxP main_dep uses the same code as our local clinic code.
        $clinicMainDeps = collect($clinicsToQuery)
            ->map(fn ($code) => LocalClinic::hosxpMainDep($code))
            ->values()
            ->all();
        $localClinicByMainDep = collect($clinicsToQuery)
            ->mapWithKeys(fn ($code) => [
                LocalClinic::hosxpMainDep($code) => $localClinics->get($code),
            ])
            ->filter();
        $departmentNames = \Illuminate\Support\Facades\DB::connection('hosxp')
            ->table('kskdepartment')
            ->select('depcode', 'department')
            ->get()
            ->mapWithKeys(fn ($department) => [
                $department->depcode => iconv('TIS-620', 'UTF-8//IGNORE', $department->department ?? ''),
            ]);
        $departmentLabel = fn ($code) => $departmentNames->get($code, $code ?: '-');

        $results = collect();

        if ($statusFilter === 'ส่งตรวจในวันนี้' || $statusFilter === 'ตรวจเสร็จแล้ว') {
            // ดึงจากตาราง ovst โดยตรงตามที่ผู้ใช้ร้องขอ
            $query = \App\Models\His\Ovst::whereBetween('vstdate', [$startDate, $endDate])
                ->whereIn('main_dep', $clinicMainDeps)
                ->where('ovstist', '02'); // นัดมา

            if ($statusFilter === 'ตรวจเสร็จแล้ว') {
                $query->where('ovstost', '99');
            }

            $ovsts = $query->get();

            foreach ($ovsts as $ovst) {
                $patient = \App\Models\His\Patient::where('hn', $ovst->hn)->first();
                $patientNameRaw = trim(($patient->pname ?? '') . ($patient->fname ?? '') . ' ' . ($patient->lname ?? ''));
                $patientName = $patientNameRaw !== '' ? iconv('TIS-620', 'UTF-8//IGNORE', $patientNameRaw) : 'ไม่พบชื่อผู้ป่วย';

                $clinic = $localClinicByMainDep->get($ovst->main_dep);

                $results->push([
                    'id' => $ovst->vn, // Use vn as id since there's no oapp_id
                    'hn' => $ovst->hn ?? '-',
                    'patient_name' => $patientName,
                    'clinic_name' => $clinic ? $clinic->name : '-',
                    'date' => Carbon::parse($ovst->vstdate)->format('d/m/Y'),
                    'actual_department' => $departmentLabel($ovst->main_dep),
                    'phone' => '-',
                    'status' => $statusFilter,
                    'status_color' => $statusFilter === 'ตรวจเสร็จแล้ว' ? 'emerald' : 'green'
                ]);
            }
        } elseif ($statusFilter === 'ทั้งหมด' || $statusFilter === 'ยังไม่มา') {
            // Use HOSxP oapp for the same source as the dashboard total.
            $appointments = HisAppointment::with('patient')
                ->whereBetween('nextdate', [$startDate, $endDate])
                ->whereIn('clinic', $clinicsToQuery)
                ->where(function ($query) {
                    $query->whereNull('oapp_status_id')->orWhere('oapp_status_id', '!=', '4');
                })
                ->get();
            $todayVisits = \App\Models\His\Ovst::whereBetween('vstdate', [$startDate, $endDate])
                ->whereIn('main_dep', $clinicMainDeps)
                ->where('ovstist', '02')
                ->get()
                ->keyBy('hn');

            foreach ($appointments as $appt) {
                $visit = $todayVisits->get($appt->hn);
                $statusStr = $visit
                    ? ($visit->ovstost === '99' ? 'ตรวจเสร็จแล้ว' : 'ส่งตรวจในวันนี้')
                    : 'ยังไม่มา';

                if ($statusFilter === 'ยังไม่มา' && $visit) {
                    continue;
                }

                $patient = $appt->patient;
                $patientNameRaw = trim(($patient->pname ?? '') . ($patient->fname ?? '') . ' ' . ($patient->lname ?? ''));
                $patientName = $patientNameRaw !== '' ? iconv('TIS-620', 'UTF-8//IGNORE', $patientNameRaw) : 'ไม่พบชื่อผู้ป่วย';
                $local = $localClinics->get($appt->clinic);

                $results->push([
                    'id' => $appt->oapp_id,
                    'hn' => $appt->hn ?? '-',
                    'patient_name' => $patientName,
                    'clinic_name' => $local ? $local->name : '-',
                    'date' => Carbon::parse($appt->nextdate)->format('d/m/Y'),
                    'actual_department' => $departmentLabel($visit?->main_dep),
                    'phone' => '-',
                    'status' => $statusStr,
                    'status_color' => $visit ? ($visit->ovstost === '99' ? 'emerald' : 'green') : 'orange',
                ]);
            }
        } elseif ($statusFilter === 'มาไม่ตรงนัด' || $statusFilter === 'ไม่ได้เข้าคลินิก') {
            $activeAppointments = HisAppointment::with('patient')
                ->whereIn('clinic', $clinicsToQuery)
                ->where(function ($query) {
                    $query->whereNull('oapp_status_id')->orWhere('oapp_status_id', '!=', '4');
                });
            $todayVisits = \App\Models\His\Ovst::whereBetween('vstdate', [$startDate, $endDate])
                ->get(['hn', 'vn', 'vstdate', 'main_dep', 'ovstost'])
                ->groupBy('hn');

            if ($statusFilter === 'มาไม่ตรงนัด') {
                // This status is strictly based on visits made on the selected day.
                $todayVisits = \App\Models\His\Ovst::where('vstdate', $startDate)
                    ->get(['hn', 'vn', 'vstdate', 'main_dep', 'ovstost'])
                    ->groupBy('hn');
                $appointments = (clone $activeAppointments)
                    ->where('nextdate', '!=', $startDate)
                    ->whereIn('hn', $todayVisits->keys())
                    ->orderByDesc('nextdate')
                    ->get();
                // A patient may have many historical appointments; show only
                // the latest appointment associated with today's visit.
                $appointments = $appointments->unique('hn')->values();
            } else {
                $appointments = (clone $activeAppointments)
                    ->whereBetween('nextdate', [$startDate, $endDate])
                    ->get();
            }

            foreach ($appointments as $appt) {
                $visits = $todayVisits->get($appt->hn, collect());
                $expectedMainDep = LocalClinic::hosxpMainDep($appt->clinic);
                $visit = $statusFilter === 'มาไม่ตรงนัด'
                    ? $visits->first(fn ($row) => $row->main_dep === $expectedMainDep)
                    : $visits->first(fn ($row) => $row->main_dep !== $expectedMainDep);

                if (!$visit) {
                    continue;
                }

                $patient = $appt->patient;
                $patientNameRaw = trim(($patient->pname ?? '') . ($patient->fname ?? '') . ' ' . ($patient->lname ?? ''));
                $patientName = $patientNameRaw !== '' ? iconv('TIS-620', 'UTF-8//IGNORE', $patientNameRaw) : 'ไม่พบชื่อผู้ป่วย';
                $local = $localClinics->get($appt->clinic);

                $results->push([
                    'id' => $appt->oapp_id,
                    'hn' => $appt->hn ?? '-',
                    'patient_name' => $patientName,
                    'clinic_name' => $local ? $local->name : '-',
                    'date' => Carbon::parse($appt->nextdate)->format('d/m/Y'),
                    'actual_department' => $departmentLabel($visit->main_dep),
                    'phone' => '-',
                    'status' => $statusFilter,
                    'status_color' => $statusFilter === 'มาไม่ตรงนัด' ? 'purple' : 'rose',
                ]);
            }
        } else {
            // ดึงจากตาราง oapp (LocalAppointment)
            $appointments = LocalAppointment::with(['hisPatient', 'ovst'])
                ->whereIn('clinic_code', $clinicsToQuery)
                // สำหรับมาไม่ตรงนัด ต้องเช็ค vstdate ด้วย
                ->where(function($q) use ($startDate, $endDate, $statusFilter) {
                    if ($statusFilter === 'มาไม่ตรงนัด') {
                        $q->whereBetween('vstdate', [$startDate, $endDate])
                          ->whereNotBetween('appointment_date', [$startDate, $endDate]);
                    } else {
                        $q->whereBetween('appointment_date', [$startDate, $endDate]);
                    }
                })
                ->get();

            foreach ($appointments as $appt) {
                // Filter logic for other statuses
                $keep = false;
                if ($statusFilter === 'ทั้งหมด') {
                    $keep = true;
                    $statusStr = $appt->visit_status;
                    $colorStr = $appt->visit_status_color;
                } elseif ($statusFilter === 'ยังไม่มา') {
                    if (empty($appt->vn)) {
                        $keep = true;
                        $statusStr = 'ยังไม่มา';
                        $colorStr = 'orange';
                    }
                } elseif ($statusFilter === 'มาไม่ตรงนัด') {
                    if (!empty($appt->vn) && $appt->vstdate && $appt->vstdate !== $appt->appointment_date) {
                        $keep = true;
                        $statusStr = 'มาไม่ตรงนัด';
                        $colorStr = 'purple';
                    }
                } elseif ($statusFilter === 'ไม่ได้เข้าคลินิก') {
                    if ($appt->ovst && $appt->ovst->main_dep !== $appt->clinic_code) {
                        $keep = true;
                        $statusStr = 'ไม่ได้เข้าคลินิก';
                        $colorStr = 'rose';
                    }
                }

                if ($keep) {
                    $hisPatient = $appt->hisPatient;
                    $patientNameRaw = trim(($hisPatient->pname ?? '') . ($hisPatient->fname ?? '') . ' ' . ($hisPatient->lname ?? ''));
                    $patientName = $patientNameRaw !== '' ? iconv('TIS-620', 'UTF-8//IGNORE', $patientNameRaw) : 'ไม่พบชื่อผู้ป่วย';
                    
                    $local = $localClinics->get($appt->clinic_code);
                    
                    $results->push([
                        'id' => $appt->oapp_id,
                        'hn' => $appt->hn ?? '-',
                        'patient_name' => $patientName,
                        'clinic_name' => $local ? $local->name : '-',
                        'date' => Carbon::parse($appt->appointment_date)->format('d/m/Y'),
                        'actual_department' => $departmentLabel($appt->ovst?->main_dep),
                        'phone' => '-',
                        'status' => $statusStr,
                        'status_color' => $colorStr
                    ]);
                }
            }
        }

        $filteredAppointments = $results;

        return response()->json([
            'status' => $statusFilter,
            'count' => $filteredAppointments->count(),
            'data' => $filteredAppointments
        ]);
    }

    public function export(Request $request)
    {
        $statusFilter = $request->input('status', 'ทั้งหมด');
        
        // We can just call getDetails internally to get the same formatted data
        $response = $this->getDetails($request);
        $data = json_decode($response->getContent(), true)['data'];

        $fileName = 'patients_' . ($statusFilter !== 'ทั้งหมด' ? $statusFilter : 'all') . '_' . Carbon::now()->format('Ymd_His') . '.xlsx';
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\PatientListExport($data, $statusFilter), 
            $fileName
        );
    }
}
