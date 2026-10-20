<?php

namespace App\Http\Services;

class DrugStockDashboardService
{
    public function __construct(private readonly DrugStockService $stockService)
    {
    }

    public function overview(?int $fiscalYear = null): array
    {
        [$startDate, $endDate] = $this->stockService->getFiscalYearDates($fiscalYear);

        return [
            'metrics' => $this->stockService->dashboardMetrics($fiscalYear),
            'alerts' => $this->stockService->alertItems()->take(5)->values(),
            'recent_movements' => $this->stockService->movements([
                'per_page' => 5,
                'date_from' => $startDate,
                'date_to' => $endDate,
            ])->items(),
        ];
    }
}
