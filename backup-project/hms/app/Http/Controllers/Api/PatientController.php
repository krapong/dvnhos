<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $currentHns = DB::connection('hosxp')
            ->table('ipt')
            ->where(function ($query): void {
                $query->whereNull('dchdate')->orWhere('dchdate', '0000-00-00');
            })
            ->pluck('hn');

        $query = Patient::query()
            ->with(['ward', 'room', 'bed', 'allergies'])
            ->whereIn('hn', $currentHns->all());

        if ($request->filled('ward_id')) {
            $query->where('ward_id', $request->integer('ward_id'));
        }

        return response()->json($query->orderBy('bed_id')->paginate((int) $request->integer('per_page', 50)));
    }

    public function show(Patient $patient): JsonResponse
    {
        return response()->json($patient->load(['ward', 'room', 'bed', 'allergies', 'admissions']));
    }

    public function transfer(Request $request, Patient $patient): JsonResponse
    {
        $data = $request->validate([
            'ward_id' => ['required', 'integer'],
            'room_id' => ['required', 'integer'],
            'bed_id' => ['required', 'integer'],
            'reason' => ['nullable', 'string'],
        ]);

        $patient->update([
            'ward_id' => $data['ward_id'],
            'room_id' => $data['room_id'],
            'bed_id' => $data['bed_id'],
        ]);

        return response()->json(['ok' => true, 'patient' => $patient->fresh(['ward', 'room', 'bed'])]);
    }

    public function discharge(Patient $patient): JsonResponse
    {
        $activeAdmission = Admission::query()
            ->where('patient_id', $patient->id)
            ->where('status', 'N')
            ->latest('admitted_at')
            ->latest('id')
            ->first();

        if ($activeAdmission) {
            $activeAdmission->update([
                'status' => 'Y',
                'discharged_at' => now(),
            ]);
        }

        $patient->update([
            'clinical_note' => trim(($patient->clinical_note ? $patient->clinical_note."\n" : '').'[DISCHARGED]'),
            'npo_status' => false,
        ]);

        return response()->json(['ok' => true, 'patient' => $patient->fresh(['ward', 'room', 'bed'])]);
    }
}
