<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage_doctors');
    }

    public function index()
    {
        $doctors = Doctor::with('schedules')->latest()->paginate(20);
        return view('admin.doctors.index', compact('doctors'));
    }

    public function create()
    {
        return view('admin.doctors.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:50',
            'specialty' => 'required|string|max:255',
            'photo' => 'nullable|image|max:2048',
            'bio' => 'nullable|string',
            'schedules' => 'nullable|array',
            'schedules.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i|after:schedules.*.start_time',
            'schedules.*.clinic' => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('doctors', 'public');
        }

        $doctor = Doctor::create($validated);

        if (!empty($validated['schedules'])) {
            foreach ($validated['schedules'] as $schedule) {
                $doctor->schedules()->create($schedule);
            }
        }

        ActivityLog::log('create', "เพิ่มข้อมูลแพทย์: {$doctor->full_name}", $doctor);

        return redirect()->route('admin.doctors.index')
            ->with('success', 'เพิ่มข้อมูลแพทย์สำเร็จ');
    }

    public function edit(Doctor $doctor)
    {
        $doctor->load('schedules');
        return view('admin.doctors.edit', compact('doctor'));
    }

    public function update(Request $request, Doctor $doctor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:50',
            'specialty' => 'required|string|max:255',
            'photo' => 'nullable|image|max:2048',
            'bio' => 'nullable|string',
            'is_active' => 'boolean',
            'schedules' => 'nullable|array',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('doctors', 'public');
        }
        $validated['is_active'] = $request->boolean('is_active');

        $doctor->update($validated);

        // อัปเดตตารางออกตรวจ
        if (isset($validated['schedules'])) {
            $doctor->schedules()->delete();
            foreach ($validated['schedules'] as $schedule) {
                $doctor->schedules()->create($schedule);
            }
        }

        ActivityLog::log('update', "แก้ไขข้อมูลแพทย์: {$doctor->full_name}", $doctor);

        return redirect()->route('admin.doctors.index')
            ->with('success', 'แก้ไขข้อมูลแพทย์สำเร็จ');
    }

    public function destroy(Doctor $doctor)
    {
        ActivityLog::log('delete', "ลบข้อมูลแพทย์: {$doctor->full_name}", $doctor);
        $doctor->delete();

        return redirect()->route('admin.doctors.index')
            ->with('success', 'ลบข้อมูลแพทย์สำเร็จ');
    }
}
