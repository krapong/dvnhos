<?php

namespace App\Http\Controllers;

use App\Models\Clinic as LocalClinic;
use App\Models\His\Clinic as HisClinic;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class CapacityController extends Controller
{
    public function index()
    {
        $localClinics = LocalClinic::where('is_active', true)->get();
        $hisClinics = HisClinic::whereIn('clinic', $localClinics->pluck('code'))->get()->keyBy('clinic');

        $capacities = $localClinics->map(function ($clinic) use ($hisClinics) {
            $hosxpClinic = $hisClinics->get($clinic->code);
            $appLimitQty = $hosxpClinic ? $hosxpClinic->app_limit_qty : 0;
            
            return [
                'id' => $clinic->id,
                'code' => $clinic->code,
                'name' => $clinic->name,
                'color_code' => $clinic->color_code,
                'hosxp_capacity' => $appLimitQty > 0 ? $appLimitQty : 'ไม่จำกัด',
                'local_max' => $clinic->max_appointments,
                'active_capacity' => $appLimitQty > 0 ? $appLimitQty : ($clinic->max_appointments ?? 'ไม่จำกัด'),
            ];
        });

        return Inertia::render('Clinics/Capacity', [
            'capacities' => $capacities,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'max_appointments' => 'nullable|integer|min:0',
        ]);

        $clinic = LocalClinic::findOrFail($id);
        $clinic->update([
            'max_appointments' => $request->max_appointments,
        ]);

        return redirect()->back()->with('message', 'อัปเดตความจุคลินิกสำเร็จ');
    }
}
