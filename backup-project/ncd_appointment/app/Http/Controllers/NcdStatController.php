<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Clinic;
use App\Models\NcdWeeklyStat;
use Carbon\Carbon;

class NcdStatController extends Controller
{
    public function dataEntry(Request $request)
    {
        $fiscalYear = (int) $request->query('fiscal_year', date('Y') + 543 + (date('n') >= 10 ? 1 : 0));
        $month = (int) $request->query('month', date('n'));
        
        $clinics = Clinic::where('is_active', true)->get();
        
        // Calculate weeks for the selected month/year
        $calendarYear = $fiscalYear - 543;
        if ($month >= 10) {
            $calendarYear -= 1; // Oct-Dec belongs to previous calendar year
        }
        
        $startDate = Carbon::create($calendarYear, $month, 1);
        $startDayOfWeek = $startDate->dayOfWeek; // 0 (Sun) to 6 (Sat)
        $daysInMonth = $startDate->daysInMonth;
        
        // Calculate how many distinct calendar weeks are in this month
        $totalWeeks = (int) ceil(($daysInMonth + $startDayOfWeek) / 7);
        
        $stats = NcdWeeklyStat::where('fiscal_year', $fiscalYear)
            ->where('month', $month)
            ->get();
            
        return Inertia::render('Stats/DataEntry', [
            'filters' => [
                'fiscal_year' => $fiscalYear,
                'month' => $month,
            ],
            'clinics' => $clinics,
            'totalWeeks' => $totalWeeks,
            'stats' => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fiscal_year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'stats' => 'required|array',
        ]);
        
        $fiscalYear = $request->fiscal_year;
        $month = $request->month;
        
        foreach ($request->stats as $stat) {
            if (isset($stat['weeks'])) {
                foreach ($stat['weeks'] as $weekNo => $count) {
                    if ($count !== null && $count !== '') {
                        NcdWeeklyStat::updateOrCreate(
                            [
                                'clinic_id' => $stat['clinic_id'],
                                'fiscal_year' => $fiscalYear,
                                'month' => $month,
                                'week_no' => $weekNo,
                            ],
                            [
                                'patient_count' => (int) $count,
                            ]
                        );
                    }
                }
            }
        }
        
        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อยแล้ว');
    }
    
    public function summary(Request $request)
    {
        $fiscalYear = (int) $request->query('fiscal_year', date('Y') + 543 + (date('n') >= 10 ? 1 : 0));
        
        $clinics = Clinic::where('is_active', true)->get();
        $stats = NcdWeeklyStat::where('fiscal_year', $fiscalYear)->get();
        
        $monthsStructure = [];
        $monthOrder = [10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        
        foreach ($monthOrder as $m) {
            $calendarYear = $fiscalYear - 543;
            if ($m >= 10) $calendarYear -= 1;
            
            $startDate = Carbon::create($calendarYear, $m, 1);
            $startDayOfWeek = $startDate->dayOfWeek;
            $daysInMonth = $startDate->daysInMonth;
            
            $totalWeeks = (int) ceil(($daysInMonth + $startDayOfWeek) / 7);
            
            $monthsStructure[] = [
                'month' => $m,
                'name' => $this->getThaiMonthName($m),
                'totalWeeks' => $totalWeeks,
            ];
        }
        
        return Inertia::render('Stats/Summary', [
            'fiscal_year' => $fiscalYear,
            'clinics' => $clinics,
            'stats' => $stats,
            'monthsStructure' => $monthsStructure,
        ]);
    }
    
    private function getThaiMonthName($m) {
        $names = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
            5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
            9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
        ];
        return $names[$m] ?? '';
    }
}
