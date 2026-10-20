<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Appointment;
use App\Models\Clinic;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AppointmentsExport;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Render the Reports Vue page
     */
    public function index()
    {
        $clinics = Clinic::where('is_active', true)->get();
        return Inertia::render('Reports/Index', [
            'clinics' => $clinics
        ]);
    }

    /**
     * Fetch preview data via JSON
     */
    public function preview(Request $request)
    {
        \Illuminate\Support\Facades\Log::info("Report Preview Request: " . json_encode($request->all()));
        $data = $this->getReportData($request);
        return response()->json(['data' => $data]);
    }

    /**
     * Export as Excel
     */
    public function exportExcel(Request $request)
    {
        $data = $this->getReportData($request);
        $filename = 'report_' . Carbon::now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new AppointmentsExport($data), $filename);
    }

    /**
     * Export as PDF
     */
    public function exportPdf(Request $request)
    {
        $data = $this->getReportData($request);
        $filename = 'report_' . Carbon::now()->format('Ymd_His') . '.pdf';
        
        $pdf = Pdf::loadView('reports.pdf', ['data' => $data])
                ->setOption(['isRemoteEnabled' => true]);
        
        // Use custom Thai font configuration
        return $pdf->download($filename);
    }

    /**
     * Query data based on filters
     */
    private function getReportData(Request $request)
    {
        $query = Appointment::with(['clinic', 'patient']);
        
        if ($request->filled('start_date')) {
            $query->where('appointment_date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->where('appointment_date', '<=', $request->end_date);
        }
        
        if ($request->filled('clinic_code')) {
            $query->whereHas('clinic', function($q) use ($request) {
                $q->where('code', $request->clinic_code);
            });
        }

        return $query->orderBy('appointment_date', 'asc')->get();
    }
}
