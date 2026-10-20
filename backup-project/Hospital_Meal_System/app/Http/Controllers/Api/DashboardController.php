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

        $payload = match ($scope) {
            'nurse' => [
                'scope' => $scope,
                'service_date' => $serviceDate,
                'wards' => Ward::query()->where('active', true)->count(),
                'patients' => Patient::query()->count(),
                'pending_orders' => MealOrder::query()->whereDate('meal_date', $serviceDate)->where('status', 'draft')->count(),
                'npo_count' => Patient::query()->where('npo_status', true)->count(),
            ],
            'nutrition' => [
                'scope' => $scope,
                'service_date' => $serviceDate,
                'summary' => $this->summary->buildForDate($serviceDate),
                'high_risk_patients' => Patient::query()->whereNotNull('bmi')->where(fn ($query) => $query->where('bmi', '<', 18.5)->orWhere('bmi', '>=', 30))->count(),
            ],
            'kitchen' => [
                'scope' => $scope,
                'service_date' => $serviceDate,
                'summary' => $this->summary->buildForDate($serviceDate),
                'tray_queue' => MealOrder::query()->whereDate('meal_date', $serviceDate)->whereIn('status', ['approved', 'accepted', 'preparing'])->count(),
            ],
            'admin', 'manager', 'executive' => [
                'scope' => $scope,
                'service_date' => $serviceDate,
                'summary' => $this->summary->buildForDate($serviceDate),
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
