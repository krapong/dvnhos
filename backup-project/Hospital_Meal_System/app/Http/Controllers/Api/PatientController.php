<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Patient::query()->with(['ward', 'room', 'bed', 'allergies']);

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
        $patient->update([
            'clinical_note' => trim(($patient->clinical_note ? $patient->clinical_note."\n" : '').'[DISCHARGED]'),
            'npo_status' => false,
        ]);

        return response()->json(['ok' => true, 'patient' => $patient->fresh(['ward', 'room', 'bed'])]);
    }
}
