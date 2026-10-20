<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReportService;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        // Old index route was just pulling boxes and alerts for the dashboard
        // We will keep backward compatibility for now
        $user = $request->user();
        
        $boxes = $this->getBoxesQueryForUser($user)->get();
        $alerts = $this->getAlertsQueryForUser($user)->get();
        
        return response()->json([
            'boxes' => $boxes,
            'alerts' => $alerts,
            'metrics' => $this->reportService->getDashboardMetrics(),
        ]);
    }

    public function usage(Request $request)
    {
        $legacyData = $this->reportService->getLegacyUsageData();

        return response()->json([
            'usage' => $legacyData['usage'],
            'summary' => $legacyData['summary'],
            'metrics' => $this->reportService->getDashboardMetrics(),
        ]);
    }
}
