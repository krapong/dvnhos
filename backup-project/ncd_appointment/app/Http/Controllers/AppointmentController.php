<?php

namespace App\Http\Controllers;

use App\Models\Appointment as LocalAppointment;
use App\Models\Clinic as LocalClinic;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    public function index()
    {
        // Fetch active clinics for filtering on the calendar
        $localClinics = LocalClinic::where('is_active', true)->get()->keyBy('code');
        
        $appointments = collect();
        
        try {
            // Fetch appointments from HOSxP oapp table for active clinics
            // Limit to upcoming 3 months and past 1 month
            $startDate = Carbon::now()->subMonth()->format('Y-m-d');
            $endDate = Carbon::now()->addMonths(3)->format('Y-m-d');
            
            // Group by clinic and nextdate to show clinic operating days on the calendar
            $groupedAppointments = LocalAppointment::select('clinic_code', 'appointment_date', \DB::raw('count(*) as total'))
                ->whereIn('clinic_code', $localClinics->keys())
                ->whereBetween('appointment_date', [$startDate, $endDate])
                ->groupBy('clinic_code', 'appointment_date')
                ->get();
                
            $appointments = $groupedAppointments->map(function ($group) use ($localClinics) {
                $local = $localClinics->get($group->clinic_code);
                // The name from local DB is preferred as it's already UTF-8. 
                $clinicName = $local ? $local->name : 'Clinic ' . $group->clinic_code;
                
                return [
                    'id' => $group->clinic_code . '_' . $group->appointment_date,
                    'title' => $clinicName . ' (' . $group->total . ' คิว)',
                    'start' => $group->appointment_date,
                    'allDay' => true,
                    'backgroundColor' => $local ? $local->color_code : '#3788d8',
                    'borderColor' => $local ? $local->color_code : '#3788d8',
                    'extendedProps' => [
                        'clinic_code' => $group->clinic_code,
                        'total_appointments' => $group->total,
                        'date' => $group->appointment_date
                    ]
                ];
            });
            
        } catch (\Exception $e) {
            Log::error("HIS Appointment Fetch Error: " . $e->getMessage());
        }

        return Inertia::render('Appointments/Calendar', [
            'clinics' => $localClinics->values(),
            'events' => $appointments
        ]);
    }

    public function dailyReport(Request $request)
    {
        $localClinics = LocalClinic::where('is_active', true)->get();
        
        // Default to today and the first clinic (e.g. DM) if not specified
        $today = Carbon::today()->format('Y-m-d');
        $startDateRaw = $request->input('start_date', $today);
        $endDateRaw = $request->input('end_date', $today);
        
        try {
            $startDate = Carbon::parse($startDateRaw)->format('Y-m-d');
            $endDate = Carbon::parse($endDateRaw)->format('Y-m-d');
        } catch (\Exception $e) {
            $startDate = $today;
            $endDate = $today;
        }

        $selectedClinicCode = $request->input('clinic_code', $localClinics->first()->code ?? '001');
        
        Log::info("Daily Report Request:", [
            'start_date_raw' => $startDateRaw,
            'end_date_raw' => $endDateRaw,
            'start_date_parsed' => $startDate,
            'end_date_parsed' => $endDate,
            'clinic_code' => $selectedClinicCode
        ]);
        
        $appointments = collect();
        try {
            // Auto-sync from HOSxP for the selected date range and clinic
            $hisOapps = \Illuminate\Support\Facades\DB::connection('hosxp')->table('oapp')
                ->join('patient', 'oapp.hn', '=', 'patient.hn')
                ->select('oapp.*', 'patient.pname', 'patient.fname', 'patient.lname')
                ->whereBetween('oapp.nextdate', [$startDate, $endDate])
                ->where('oapp.clinic', $selectedClinicCode)
                ->get();

            $syncCount = 0;
            $localClinic = LocalClinic::where('code', $selectedClinicCode)->first();
            if ($localClinic) {
                foreach ($hisOapps as $a) {
                    $patient = \App\Models\Patient::firstOrCreate(
                        ['hn' => $a->hn],
                        [
                            'first_name' => iconv('TIS-620', 'UTF-8//IGNORE', $a->fname),
                            'last_name' => iconv('TIS-620', 'UTF-8//IGNORE', $a->lname),
                            'cid' => \Illuminate\Support\Str::random(13)
                        ]
                    );

                    $status = 'scheduled';

                    LocalAppointment::updateOrCreate(
                        [
                            'oapp_id' => $a->oapp_id,
                        ],
                        [
                            'patient_id' => $patient->id,
                            'clinic_id' => $localClinic->id,
                            'appointment_date' => $a->nextdate,
                            'clinic_code' => $localClinic->code,
                            'hn' => $a->hn,
                            'appointment_time' => $a->nexttime ?? '08:00:00',
                            'reason' => iconv('TIS-620', 'UTF-8//IGNORE', $a->app_cause ?? ''),
                            'status' => $status
                        ]
                    );
                    $syncCount++;
                }
            }
            Log::info("Auto-synced $syncCount appointments from HOSxP for date range.");

            // Sync visits (ovst) for arrived status and walk-ins
            $hisVisits = \Illuminate\Support\Facades\DB::connection('hosxp')->table('ovst')
                ->join('clinicmember', 'ovst.hn', '=', 'clinicmember.hn')
                ->select('ovst.vn', 'ovst.hn', 'ovst.vstdate', 'clinicmember.clinic')
                ->where('clinicmember.clinic', $selectedClinicCode)
                ->whereBetween('ovst.vstdate', [$startDate, $endDate])
                ->get();

            if ($hisVisits->count() > 0) {
                $visitHns = $hisVisits->pluck('hn')->unique()->toArray();
                
                // Pre-fetch local appointments to avoid N+1
                $existingAppts = LocalAppointment::whereIn('hn', $visitHns)
                    ->where('clinic_code', $selectedClinicCode)
                    ->get()
                    ->groupBy('hn');
                    
                $localPatients = \App\Models\Patient::whereIn('hn', $visitHns)->get()->keyBy('hn');
                $hisPatients = null; // Lazy load if needed
                
                foreach ($hisVisits as $visit) {
                    $patientAppts = $existingAppts->get($visit->hn) ?? collect();
                    
                    $appt = $patientAppts->where('appointment_date', $visit->vstdate)->first();
                    
                    if ($appt) {
                        if ($appt->vn !== $visit->vn || $appt->vstdate !== $visit->vstdate) {
                            $appt->update(['vn' => $visit->vn, 'vstdate' => $visit->vstdate, 'status' => 'arrived']);
                        }
                    } else {
                        $nearestAppt = $patientAppts->where('appointment_date', '>=', $visit->vstdate)
                            ->whereNull('vstdate')
                            ->sortBy('appointment_date')
                            ->first();
                        
                        if ($nearestAppt) {
                            $nearestAppt->update(['vn' => $visit->vn, 'vstdate' => $visit->vstdate, 'status' => 'arrived']);
                        } else {
                            $patient = $localPatients->get($visit->hn);
                            if (!$patient) {
                                if (!$hisPatients) {
                                    $hisPatients = \Illuminate\Support\Facades\DB::connection('hosxp')->table('patient')->whereIn('hn', $visitHns)->get()->keyBy('hn');
                                }
                                $hisPatient = $hisPatients->get($visit->hn);
                                if ($hisPatient) {
                                    $patient = \App\Models\Patient::create([
                                        'hn' => $visit->hn,
                                        'first_name' => iconv('TIS-620', 'UTF-8//IGNORE', $hisPatient->fname),
                                        'last_name' => iconv('TIS-620', 'UTF-8//IGNORE', $hisPatient->lname),
                                        'cid' => \Illuminate\Support\Str::random(13)
                                    ]);
                                    $localPatients->put($visit->hn, $patient);
                                }
                            }
                            
                            if ($patient && $localClinic) {
                                // Check if we already created a dummy today to avoid duplicates
                                $dummyExists = $patientAppts->where('appointment_date', '1900-01-01')->where('vstdate', $visit->vstdate)->first();
                                if (!$dummyExists) {
                                    $newAppt = LocalAppointment::create([
                                        'patient_id' => $patient->id,
                                        'clinic_id' => $localClinic->id,
                                        'appointment_date' => '1900-01-01',
                                        'clinic_code' => $localClinic->code,
                                        'hn' => $visit->hn,
                                        'vn' => $visit->vn,
                                        'vstdate' => $visit->vstdate,
                                        'appointment_time' => '08:00:00',
                                        'reason' => 'Walk-in (ไม่มีนัดล่วงหน้า)',
                                        'status' => 'arrived'
                                    ]);
                                    $patientAppts->push($newAppt);
                                }
                            }
                        }
                    }
                }
            }

            $localAppointments = LocalAppointment::with([
                'hisPatient', 'clinic', 
                'labHeads' => function($q) use ($startDate, $endDate) {
                    $q->whereBetween('order_date', [$startDate, $endDate]);
                },
                'xrayHeads' => function($q) use ($startDate, $endDate) {
                    $q->whereBetween('order_date', [$startDate, $endDate]);
                }
            ])
            ->where('clinic_code', $selectedClinicCode)
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('appointment_date', [$startDate, $endDate])
                  ->orWhereBetween('vstdate', [$startDate, $endDate]);
            })
            ->get();
            
            // Pre-fetch department names for the visits
            $vns = $localAppointments->pluck('vn')->filter()->toArray();
            $departments = [];
            if (!empty($vns)) {
                $deps = \Illuminate\Support\Facades\DB::connection('hosxp')->table('ovst')
                    ->leftJoin('kskdepartment', 'ovst.main_dep', '=', 'kskdepartment.depcode')
                    ->whereIn('ovst.vn', $vns)
                    ->select('ovst.vn', 'kskdepartment.department')
                    ->get();
                foreach ($deps as $dep) {
                    $departments[$dep->vn] = iconv('TIS-620', 'UTF-8//IGNORE', $dep->department);
                }
            }
            
            Log::info("Found Local Appointments count: " . $localAppointments->count());
            
            // Get last visit dates from HOSxP
            $hns = $localAppointments->pluck('hn')->filter()->unique()->toArray();
            $lastVisits = \App\Models\His\Appointment::select('hn', \DB::raw('MAX(vstdate) as last_vstdate'))
                ->whereIn('hn', $hns)
                ->where('clinic', $selectedClinicCode)
                ->where('vstdate', '<', $startDate)
                ->groupBy('hn')
                ->get()
                ->keyBy('hn');
                
            // Fetch vsttime and rx_time for today's arrived patients
            $vns = $localAppointments->pluck('vn')->filter()->unique()->toArray();
            $visitTimes = collect();
            if (!empty($vns)) {
                $visitTimes = \Illuminate\Support\Facades\DB::connection('hosxp')->table('ovst')
                    ->leftJoin('service_time', 'ovst.vn', '=', 'service_time.vn')
                    ->whereIn('ovst.vn', $vns)
                    ->select('ovst.vn', 'ovst.vsttime', 'service_time.service6 as rx_time')
                    ->get()
                    ->keyBy('vn');
            }
                
            $appointments = $localAppointments->map(function ($appt) use ($lastVisits, $visitTimes, $departments) {
                $pname = $appt->hisPatient->pname ?? '';
                $fname = $appt->hisPatient->fname ?? '';
                $lname = $appt->hisPatient->lname ?? '';
                
                $patientNameRaw = trim($pname . $fname . ' ' . $lname);
                $patientName = $patientNameRaw !== '' 
                    ? iconv('TIS-620', 'UTF-8//IGNORE', $patientNameRaw) 
                    : 'ไม่พบชื่อผู้ป่วย';
                    
                $clinicNameRaw = $appt->clinic->name ?? '';
                $clinicName = $clinicNameRaw !== ''
                    ? $clinicNameRaw
                    : 'ไม่ระบุคลินิก';
                
                // Use the status from the model accessor
                $status = $appt->visit_status;
                if ($status === 'มาไม่ตรงนัด' && $appt->vn && isset($departments[$appt->vn])) {
                    $status .= ' (แผนก ' . trim($departments[$appt->vn]) . ')';
                }
                
                $statusColor = $appt->visit_status_color;
                
                // Lab Status Logic
                $dailyLabs = $appt->labHeads->where('order_date', $appt->appointment_date);
                $hasLab = $dailyLabs->count() > 0;
                $labResultsOut = $hasLab && $dailyLabs->every(fn($lab) => $lab->confirm_report === 'Y');
                
                $labStatus = 'ไม่มีแลป';
                $labStatusColor = 'gray';
                if ($hasLab) {
                    if ($labResultsOut) {
                        $labStatus = 'ผลออกครบแล้ว';
                        $labStatusColor = 'green';
                    } else {
                        $labStatus = 'รอผลแลป';
                        $labStatusColor = 'orange';
                    }
                }
                
                // Xray Status Logic
                $dailyXrays = $appt->xrayHeads->where('order_date', $appt->appointment_date);
                $hasXray = $dailyXrays->count() > 0;
                $xrayResultsOut = $hasXray && $dailyXrays->every(fn($xray) => $xray->confirm_report === 'Y' || $xray->confirm_read_film === 'Y');
                
                $xrayStatus = 'ไม่มี X-Ray';
                $xrayStatusColor = 'gray';
                if ($hasXray) {
                    if ($xrayResultsOut) {
                        $xrayStatus = 'ผลออกครบแล้ว';
                        $xrayStatusColor = 'green';
                    } else {
                        $xrayStatus = 'รอผล X-Ray';
                        $xrayStatusColor = 'orange';
                    }
                }
                
                $lastVisitDateRaw = $lastVisits->get($appt->hn)->last_vstdate ?? null;
                $lastVisitDate = $lastVisitDateRaw ? Carbon::parse($lastVisitDateRaw)->format('d/m/Y') : '-';
                
                $times = $appt->vn ? $visitTimes->get($appt->vn) : null;
                $vsttime = $times && $times->vsttime ? Carbon::parse($times->vsttime)->format('H:i') . ' น.' : '-';
                $finishTime = $times && $times->rx_time ? Carbon::parse($times->rx_time)->format('H:i') . ' น.' : '-';
                
                return [
                    'id' => $appt->id,
                    'patient_name' => $patientName,
                    'hn' => $appt->hn ?? '-',
                    'arrival_time' => $vsttime,
                    'finish_time' => $finishTime,
                    'last_visit_date' => $lastVisitDate,
                    'appointment_date' => $appt->appointment_date === '1900-01-01' ? '-' : Carbon::parse($appt->appointment_date)->format('d/m/Y'),
                    'status' => $status,
                    'status_color' => $statusColor,
                    'lab_status' => $labStatus,
                    'lab_status_color' => $labStatusColor,
                    'xray_status' => $xrayStatus,
                    'xray_status_color' => $xrayStatusColor,
                ];
            });
            
            Log::info("Mapped appointments count: " . $appointments->count());
            
        } catch (\Exception $e) {
            Log::error("HIS Daily Report Fetch Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
        
        $totalCount = $appointments->count();
        $arrivedCount = $appointments->whereIn('status', ['มาตามนัด', 'มาแล้ว'])->count();
        $noShowCount = $appointments->where('status', 'ผิดนัด')->count();
        $wrongDateCount = $appointments->filter(function($a) { return str_starts_with($a['status'], 'มาไม่ตรงนัด'); })->count();
        $waitingCount = $appointments->where('status', 'รอรับบริการ')->count();

        return Inertia::render('Appointments/DailyReport', [
            'clinics' => $localClinics,
            'filters' => [
                'start_date' => $startDateRaw,
                'end_date' => $endDateRaw,
                'clinic_code' => $selectedClinicCode
            ],
            'stats' => [
                'total' => $totalCount,
                'arrived' => $arrivedCount,
                'no_show' => $noShowCount,
                'wrong_date' => $wrongDateCount,
                'waiting' => $waitingCount,
            ],
            'appointments' => $appointments->values()
        ]);
    }

    public function scheduleBoard(Request $request)
    {
        $localClinics = LocalClinic::where('is_active', true)->get();
        
        $today = Carbon::today()->format('Y-m-d');
        $startDateRaw = $request->input('start_date', $today);
        $endDateRaw = $request->input('end_date', $today);
        
        try {
            $startDate = Carbon::parse($startDateRaw);
            $endDate = Carbon::parse($endDateRaw);
            
            if ($endDate->lt($startDate)) {
                $endDate = $startDate->copy();
                $endDateRaw = $startDateRaw;
            }
        } catch (\Exception $e) {
            $startDate = Carbon::today();
            $endDate = Carbon::today();
            $startDateRaw = $today;
            $endDateRaw = $today;
        }

        // Calculate total days (inclusive)
        $totalDays = $startDate->diffInDays($endDate) + 1;
        
        // Fetch HOSxP clinic capacities
        $hisClinics = \App\Models\His\Clinic::whereIn('clinic', $localClinics->pluck('code'))->get()->keyBy('clinic');

        $clinicsData = [];
        foreach ($localClinics as $clinic) {
            $hosxpClinic = $hisClinics->get($clinic->code);
            // Use local max_appointments if set, otherwise fallback to HOSxP app_limit_qty, or 50.
            $baseCapacity = $clinic->max_appointments ?? ((!empty($hosxpClinic->app_limit_qty) && $hosxpClinic->app_limit_qty > 0) ? $hosxpClinic->app_limit_qty : 50);
                            
            $clinicCapacity = $baseCapacity * $totalDays;
            
            $clinicsData[$clinic->code] = [
                'code' => $clinic->code,
                'name' => $clinic->name,
                'color_code' => $clinic->color_code,
                'base_capacity' => $baseCapacity,
                'max_capacity' => $clinicCapacity,
                'booked' => 0,
                'overbooked' => 0,
            ];
        }

        try {
            // Count total appointments per clinic directly from database
            $counts = LocalAppointment::select('clinic_code', \DB::raw('count(*) as total'))
                ->whereIn('clinic_code', $localClinics->pluck('code'))
                ->whereBetween('appointment_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->groupBy('clinic_code')
                ->get();

            foreach ($counts as $count) {
                $clinicCode = $count->clinic_code;
                if (isset($clinicsData[$clinicCode])) {
                    $booked = $count->total;
                    $clinicsData[$clinicCode]['booked'] = $booked;
                    
                    if ($booked > $clinicsData[$clinicCode]['max_capacity']) {
                        $clinicsData[$clinicCode]['overbooked'] = $booked - $clinicsData[$clinicCode]['max_capacity'];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("HIS Schedule Board Fetch Error: " . $e->getMessage());
        }

        return Inertia::render('Appointments/Schedule', [
            'filters' => [
                'start_date' => $startDateRaw,
                'end_date' => $endDateRaw,
            ],
            'clinics_data' => array_values($clinicsData),
            'total_days' => $totalDays,
        ]);
    }
}
