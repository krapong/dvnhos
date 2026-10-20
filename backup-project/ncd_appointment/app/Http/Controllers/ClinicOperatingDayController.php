<?php

namespace App\Http\Controllers;

use App\Models\ClinicOperatingDay;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ClinicOperatingDayController extends Controller
{
    public function index()
    {
        $clinics = Clinic::where('is_active', true)->get();
        $operatingDays = ClinicOperatingDay::with('clinic')->get();
        
        $events = $operatingDays->map(function($day) {
            return [
                'id' => $day->id,
                'title' => $day->clinic->name,
                'start' => $day->date,
                'allDay' => true,
                'backgroundColor' => $day->clinic->color_code,
                'borderColor' => $day->clinic->color_code,
                'extendedProps' => [
                    'clinic_id' => $day->clinic_id,
                    'is_holiday' => false
                ]
            ];
        })->values()->toArray();

        // Add holidays to events as background blocks
        $holidays = \App\Models\Holiday::all();
        foreach ($holidays as $holiday) {
            $events[] = [
                'id' => 'h_' . $holiday->id,
                'title' => $holiday->name,
                'start' => $holiday->date,
                'allDay' => true,
                'display' => 'background',
                'backgroundColor' => '#fecaca', // red-200
                'extendedProps' => [
                    'is_holiday' => true,
                    'name' => $holiday->name
                ]
            ];
        }

        return Inertia::render('Admin/ClinicCalendar', [
            'clinics' => $clinics,
            'events' => $events,
            'canManageSchedule' => auth()->user()->can('manage clinics') || auth()->user()->hasRole('admin')
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'clinic_id' => 'required|exists:clinics,id',
            'date' => 'required|date',
            'type' => 'required|in:daily,monthly'
        ]);

        $clinicId = $request->clinic_id;
        $baseDate = Carbon::parse($request->date);
        $dates = [];

        if ($request->type === 'monthly') {
            // Get all days in the month that match the day of week of baseDate
            $dayOfWeek = $baseDate->dayOfWeek;
            $startOfMonth = $baseDate->copy()->startOfMonth();
            $endOfMonth = $baseDate->copy()->endOfMonth();
            
            for ($date = $startOfMonth; $date->lte($endOfMonth); $date->addDay()) {
                if ($date->dayOfWeek === $dayOfWeek) {
                    $dates[] = $date->format('Y-m-d');
                }
            }
        } else {
            $dates[] = $baseDate->format('Y-m-d');
        }

        // Check against holidays and store
        $skipped = 0;
        foreach ($dates as $date) {
            if (\App\Models\Holiday::where('date', $date)->exists()) {
                $skipped++;
                continue; // Skip holidays
            }
            
            ClinicOperatingDay::updateOrCreate(
                ['date' => $date],
                ['clinic_id' => $clinicId]
            );
        }

        if ($skipped > 0 && count($dates) === 1) {
            return redirect()->back()->withErrors(['date' => 'ไม่สามารถกำหนดคลินิกในวันหยุดราชการได้']);
        }

        if ($skipped > 0) {
            return redirect()->back()->with('success', 'บันทึกวันเปิดคลินิกเรียบร้อยแล้ว (ข้ามวันหยุดราชการ ' . $skipped . ' วัน)');
        }

        return redirect()->back()->with('success', 'บันทึกวันเปิดคลินิกเรียบร้อยแล้ว');
    }

    public function destroy($id)
    {
        $operatingDay = ClinicOperatingDay::findOrFail($id);
        $operatingDay->delete();
        
        return redirect()->back()->with('success', 'ลบวันเปิดคลินิกเรียบร้อยแล้ว');
    }
}
