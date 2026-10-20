<?php

namespace App\Http\Controllers;

use App\Models\Clinic as LocalClinic;
use App\Models\His\Clinic as HisClinic;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class ClinicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clinics = collect();
        
        try {
            // NCD Main Clinics
            $mainClinicCodes = ['001', '002', '003', '004', '005', '006'];
            
            // Fetch only main clinics from HOSxP
            $hisClinics = HisClinic::whereIn('clinic', $mainClinicCodes)->get();
            
            // Fetch our local mapping
            $localClinics = LocalClinic::all()->keyBy('code');
            
            // Map them together
            $clinics = $hisClinics->map(function ($his) use ($localClinics) {
                $local = $localClinics->get($his->clinic);
                // HOSxP data is in TIS-620. We must convert it to UTF-8 so json_encode doesn't fail.
                // Using iconv since mbstring might not support TIS-620 on this server
                $nameUtf8 = iconv('TIS-620', 'UTF-8//IGNORE', $his->name);
                
                return [
                    'id' => $local ? $local->id : null,
                    'code' => $his->clinic,
                    'name' => $nameUtf8,
                    'color_code' => $local ? $local->color_code : '#0f766e',
                    'is_active' => $local ? $local->is_active : false,
                ];
            });
            
        } catch (\Exception $e) {
            Log::error("HIS Connection Error: " . $e->getMessage());
            // Fallback to local only if HIS is unreachable
            $clinics = LocalClinic::all()->map(function($local) {
                return [
                    'id' => $local->id,
                    'code' => $local->code,
                    'name' => $local->name . ' (Local Only)',
                    'color_code' => $local->color_code,
                    'is_active' => $local->is_active,
                ];
            });
        }

        return Inertia::render('Clinics/Index', [
            'clinics' => $clinics
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $code)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color_code' => 'nullable|string|max:10',
            'is_active' => 'boolean'
        ]);

        // Find or create the local mapping
        $localClinic = LocalClinic::firstOrNew(['code' => $code]);
        $localClinic->name = $validated['name'];
        $localClinic->color_code = $validated['color_code'];
        $localClinic->is_active = $validated['is_active'];
        $localClinic->save();

        return redirect()->route('clinics.index')->with('message', 'บันทึกการตั้งค่าสีคลินิกสำเร็จ');
    }
}
