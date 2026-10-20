<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\His\Appointment as HisAppointment;
use App\Models\Appointment as LocalAppointment;
use App\Models\Clinic;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncHosxpAppointmentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $startDate;
    protected $endDate;

    /**
     * Create a new job instance.
     * If no dates provided, defaults to syncing from 30 days ago to 90 days ahead.
     */
    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate ?? Carbon::today()->subDays(30)->format('Y-m-d');
        $this->endDate = $endDate ?? Carbon::today()->addDays(90)->format('Y-m-d');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting HOSxP Appointment Sync for range: {$this->startDate} to {$this->endDate}");
        
        $activeClinicCodes = Clinic::where('is_active', true)->pluck('code')->toArray();

        if (empty($activeClinicCodes)) {
            Log::warning("No active clinics found. Skipping sync.");
            return;
        }

        try {
            HisAppointment::with(['visit'])
                ->whereIn('clinic', $activeClinicCodes)
                ->whereBetween('nextdate', [$this->startDate, $this->endDate])
                ->chunk(500, function ($hisAppointments) {
                    
                    $upsertData = [];
                    
                    $localClinics = Clinic::where('is_active', true)->get()->keyBy('code');

                    foreach ($hisAppointments as $his) {
                        $clinicId = $localClinics->has($his->clinic) ? $localClinics->get($his->clinic)->id : null;
                        
                        $upsertData[] = [
                            'oapp_id' => $his->oapp_id,
                            'hn' => $his->hn,
                            'vn' => $his->visit_vn,
                            'clinic_id' => $clinicId,
                            'clinic_code' => $his->clinic,
                            'vstdate' => $his->visit ? $his->visit->vstdate : null,
                            'appointment_date' => $his->nextdate,
                            'status' => 'scheduled', // default
                        ];
                    }

                    if (!empty($upsertData)) {
                        LocalAppointment::upsert(
                            $upsertData, 
                            ['oapp_id'], 
                            ['hn', 'vn', 'clinic_id', 'clinic_code', 'vstdate', 'appointment_date']
                        );
                    }
                });

            Log::info("HOSxP Appointment Sync completed successfully.");

        } catch (\Exception $e) {
            Log::error("HOSxP Appointment Sync Failed: " . $e->getMessage());
        }
    }
}
