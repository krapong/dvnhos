<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MealOrder;
use App\Models\Patient;
use App\Models\Ward;
use App\Services\DailySummaryService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly DailySummaryService $summary)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'nurse' => $this->show('nurse')->getData(true),
            'nutrition' => $this->show('nutrition')->getData(true),
            'kitchen' => $this->show('kitchen')->getData(true),
            'executive' => $this->show('executive')->getData(true),
        ]);
    }

    public function show(string $scope): JsonResponse
    {
        $serviceDate = request('service_date', now()->toDateString());
        $summary = $this->summary->buildForDate($serviceDate);

        $payload = match ($scope) {
            'nurse' => [
                'scope' => $scope,
                'service_date' => $serviceDate,
                'wards' => Ward::query()->where('active', true)->count(),
                'patients' => $summary['patients'],
                'male_count' => $summary['male_count'] ?? 0,
                'female_count' => $summary['female_count'] ?? 0,
                'other_count' => $summary['other_count'] ?? 0,
                'pending_orders' => MealOrder::query()->whereDate('meal_date', $serviceDate)->where('status', 'draft')->count(),
                'npo_count' => $summary['npo'],
                'summary' => $summary,
            ],
            'nutrition' => [
                'scope' => $scope,
                'service_date' => $serviceDate,
                'male_count' => $summary['male_count'] ?? 0,
                'female_count' => $summary['female_count'] ?? 0,
                'other_count' => $summary['other_count'] ?? 0,
                'summary' => $summary,
                'high_risk_patients' => $summary['high_risk_patients'],
            ],
            'kitchen' => [
                'scope' => $scope,
                'service_date' => $serviceDate,
                'male_count' => $summary['male_count'] ?? 0,
                'female_count' => $summary['female_count'] ?? 0,
                'other_count' => $summary['other_count'] ?? 0,
                'summary' => $summary,
                'tray_queue' => MealOrder::query()->whereDate('meal_date', $serviceDate)->whereIn('status', ['approved', 'accepted', 'preparing'])->count(),
            ],
            'admin', 'manager', 'executive' => [
                'scope' => $scope,
                'service_date' => $serviceDate,
                'male_count' => $summary['male_count'] ?? 0,
                'female_count' => $summary['female_count'] ?? 0,
                'other_count' => $summary['other_count'] ?? 0,
                'summary' => $summary,
                'sync_health' => [
                    'last_sync' => optional(\App\Models\SyncLog::query()->latest('id')->first())->finished_at,
                    'failed_last_24h' => \App\Models\SyncLog::query()->where('status', 'failed')->where('created_at', '>=', now()->subDay())->count(),
                ],
            ],
            default => ['scope' => $scope, 'message' => 'Unknown dashboard scope'],
        };

        return response()->json($payload);
    }
}
