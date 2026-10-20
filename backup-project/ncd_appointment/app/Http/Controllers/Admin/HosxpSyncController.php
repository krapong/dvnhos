<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Clinic as LocalClinic;
use App\Models\Appointment as LocalAppointment;
use App\Models\Patient;
use Illuminate\Support\Str;

class HosxpSyncController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/HosxpSync');
    }

    public function sync(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        try {
            $localClinics = LocalClinic::where('is_active', true)->get();
            $clinicCodes = $localClinics->pluck('code')->toArray();

            $hisOapps = DB::connection('hosxp')->table('oapp')
                ->join('patient', 'oapp.hn', '=', 'patient.hn')
                ->select('oapp.*', 'patient.pname', 'patient.fname', 'patient.lname')
                ->whereBetween('oapp.nextdate', [$startDate, $endDate])
                ->whereIn('oapp.clinic', $clinicCodes)
                ->get();

            $syncCount = 0;
            
            foreach ($hisOapps as $a) {
                $localClinic = $localClinics->where('code', $a->clinic)->first();
                if (!$localClinic) continue;

                $patient = Patient::firstOrCreate(
                    ['hn' => $a->hn],
                    [
                        'first_name' => iconv('TIS-620', 'UTF-8//IGNORE', $a->fname),
                        'last_name' => iconv('TIS-620', 'UTF-8//IGNORE', $a->lname),
                        'cid' => Str::random(13)
                    ]
                );

                $status = 'scheduled';
                if ($a->vn) {
                    $status = 'arrived';
                }

                LocalAppointment::updateOrCreate(
                    [
                        'patient_id' => $patient->id,
                        'clinic_id' => $localClinic->id,
                        'appointment_date' => $a->nextdate,
                    ],
                    [
                        'clinic_code' => $localClinic->code,
                        'hn' => $a->hn,
                        'oapp_id' => $a->oapp_id,
                        'vn' => $a->vn ?? null,
                        'appointment_time' => $a->nexttime ?? '08:00:00',
                        'reason' => iconv('TIS-620', 'UTF-8//IGNORE', $a->app_cause ?? ''),
                        'status' => $status
                    ]
                );
                $syncCount++;
            }

            Log::info("Manual HOSxP Sync triggered by admin. Synced $syncCount appointments between $startDate and $endDate.");

            return redirect()->back()->with('success', "ซิงค์ข้อมูลสำเร็จจำนวน $syncCount รายการ");

        } catch (\Exception $e) {
            Log::error("Manual HOSxP Sync Error: " . $e->getMessage());
            return redirect()->back()->with('error', "เกิดข้อผิดพลาดในการซิงค์ข้อมูล: " . $e->getMessage());
        }
    }
}
