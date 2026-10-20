<?php

namespace App\Http\Controllers;

use App\Models\Appointment as LocalAppointment;
use App\Models\His\Appointment as HisAppointment;
use App\Models\Clinic as LocalClinic;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $selectedClinicCode = $request->input('clinic_code', '');

        $localClinics = LocalClinic::where('is_active', true)->get()->keyBy('code');
        $activeClinicCodes = $localClinics->keys()->toArray();
        $clinicsToQuery = $selectedClinicCode ? [$selectedClinicCode] : $activeClinicCodes;


        // Appointment totals come from HOSxP oapp so the dashboard reflects
        // the source schedule even before the local sync has completed.
        $activeOapps = HisAppointment::where('nextdate', $date)
            ->whereIn('clinic', $clinicsToQuery)
            ->where(function ($query) {
                $query->whereNull('oapp_status_id')->orWhere('oapp_status_id', '!=', '4');
            });
        $total = (clone $activeOapps)->count();

        // HOSxP main_dep uses the same code as our local clinic code.
        $clinicMainDeps = collect($clinicsToQuery)
            ->map(fn ($code) => LocalClinic::hosxpMainDep($code))
            ->values()
            ->all();

        // 2. ส่งตรวจในวันนี้ (Arrived) - จาก ovst
        $arrivedToday = \App\Models\His\Ovst::where('vstdate', $date)
            ->whereIn('main_dep', $clinicMainDeps)
            ->where('ovstist', '02') // นัดมา
            ->count();

        // 3. ตรวจเสร็จแล้ว (Completed) - จาก ovst
        $completed = \App\Models\His\Ovst::where('vstdate', $date)
            ->whereIn('main_dep', $clinicMainDeps)
            ->where('ovstist', '02')
            ->where('ovstost', '99') // ตรวจแล้วตามสถานะ HOSxP
            ->count();
            
        // Keep the cards internally consistent: scheduled minus arrivals.
        $waiting = max($total - $arrivedToday, 0);

        $todayVisits = \App\Models\His\Ovst::where('vstdate', $date)
            ->whereIn('main_dep', $clinicMainDeps)
            ->get(['hn', 'main_dep']);

        // 5. มาไม่ตรงนัด = มีนัดคนละวัน แต่มีประวัติเข้าคลินิกวันนี้
        $wrongDateHns = HisAppointment::where('nextdate', '!=', $date)
            ->whereIn('clinic', $clinicsToQuery)
            ->whereIn('hn', $todayVisits->pluck('hn'))
            ->where(function ($query) {
                $query->whereNull('oapp_status_id')->orWhere('oapp_status_id', '!=', '4');
            })
            ->distinct()
            ->pluck('hn');
        $wrongDate = $wrongDateHns->count();

        // 6. ไม่ได้เข้าคลินิก = มีนัดวันนี้ แต่จุดบริการจริงไม่ใช่คลินิกนัด
        $scheduledHns = (clone $activeOapps)->pluck('hn');
        $didntEnter = \App\Models\His\Ovst::where('vstdate', $date)
            ->whereIn('hn', $scheduledHns)
            ->whereNotIn('main_dep', $clinicMainDeps)
            ->distinct()
            ->count('hn');

        $clinicAppointmentCounts = HisAppointment::where('nextdate', $date)
            ->whereIn('clinic', $activeClinicCodes)
            ->where(function ($query) {
                $query->whereNull('oapp_status_id')->orWhere('oapp_status_id', '!=', '4');
            })
            ->selectRaw('clinic, count(*) as total')
            ->groupBy('clinic')
            ->pluck('total', 'clinic');
        $clinicArrivalCounts = \App\Models\His\Ovst::where('vstdate', $date)
            ->whereIn('main_dep', collect($activeClinicCodes)
                ->map(fn ($code) => LocalClinic::hosxpMainDep($code))
                ->all())
            ->where('ovstist', '02')
            ->selectRaw('main_dep, count(*) as total')
            ->groupBy('main_dep')
            ->pluck('total', 'main_dep');
        $clinicBreakdown = $localClinics->map(function ($clinic) use ($clinicAppointmentCounts, $clinicArrivalCounts) {
            $mainDep = LocalClinic::hosxpMainDep($clinic->code);

            return [
                'code' => $clinic->code,
                'name' => $clinic->name,
                'appointments' => (int) ($clinicAppointmentCounts->get($clinic->code) ?? 0),
                'arrived' => (int) ($clinicArrivalCounts->get($mainDep) ?? 0),
            ];
        })->values();

        $stats = [
            'total' => $total,
            'arrived_today' => $arrivedToday,
            'completed' => $completed,
            'waiting' => $waiting,
            'wrong_date' => $wrongDate,
            'didnt_enter' => $didntEnter,
            'new_patient' => 0
        ];

        return Inertia::render('Dashboard', [
            'filters' => [
                'date' => $date,
                'clinic_code' => $selectedClinicCode,
            ],
            'clinics' => $localClinics->values(),
            'stats' => $stats,
            'clinic_breakdown' => $clinicBreakdown,
        ]);
    }
}
