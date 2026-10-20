<?php

namespace App\Repositories;

use App\Models\Box;
use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\BoxRound;
use App\Models\BoxRoundItem;
use Illuminate\Support\Facades\DB;

class ReportRepository
{
    /**
     * Get inventory summary (boxes, statuses, total value)
     */
    public function getInventorySummary(): array
    {
        $boxes = Box::whereNull('deleted_at')->get();
        
        $totalBoxes = $boxes->count();
        $totalValue = $boxes->sum(function ($box) {
            return (float) ($box->value ?? 0);
        });

        $statusCounts = $boxes->groupBy('status')->map(function ($group) {
            return $group->count();
        })->toArray();

        return [
            'total_boxes' => $totalBoxes,
            'total_value' => $totalValue,
            'status_counts' => $statusCounts,
        ];
    }

    /**
     * Get repack history for a specific month
     */
    public function getRepackHistory(string $monthYear): array
    {
        // $monthYear format: YYYY-MM
        return BoxRound::with(['box', 'items'])
            ->where('created_at', 'like', $monthYear . '%')
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    /**
     * Get expiry alerts summary
     */
    public function getAlertsSummary(): array
    {
        $alerts = Alert::query()->get();
        
        $activeAlerts = $alerts->whereNull('resolved_at')->count();
        $resolvedAlerts = $alerts->whereNotNull('resolved_at')->count();
        
        $alertsBySeverity = $alerts->groupBy('severity')->map->count()->toArray();
        
        return [
            'total_alerts' => $alerts->count(),
            'active_alerts' => $activeAlerts,
            'resolved_alerts' => $resolvedAlerts,
            'by_severity' => $alertsBySeverity,
        ];
    }

    /**
     * Get all alerts for reporting
     */
    public function getAlertsReport(array $filters = [])
    {
        $query = Alert::query()->orderByDesc('created_at');
        
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->whereNull('resolved_at');
            } elseif ($filters['status'] === 'resolved') {
                $query->whereNotNull('resolved_at');
            }
        }
        
        if (!empty($filters['month'])) {
            $query->where('created_at', 'like', $filters['month'] . '%');
        }

        return $query->get();
    }

    /**
     * Get recent audit logs
     */
    public function getAuditLogs(int $limit = 100)
    {
        return AuditLog::orderByDesc('created_at')->limit($limit)->get();
    }
}
