<?php

namespace App\Http\Controllers\Api\V1\DrugStock;

use App\Exports\DrugStock\DrugStockReportExport;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\AlertNotification;
use App\Models\StockMovement;
use App\Models\WarehouseStockLot;
use App\Models\SystemSetting;
use App\Http\Services\DrugStockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends BaseApiController
{
    public function __construct(private readonly DrugStockService $service)
    {
    }

    public function stockSummary(Request $request)
    {
        $stocks = WarehouseStockLot::query()
            ->with(['stock.warehouse.location', 'stock.drug.category'])
            ->when($request->integer('warehouse_id'), fn ($query, $warehouseId) => $query->whereHas('stock', fn ($stockQuery) => $stockQuery->where('warehouse_id', $warehouseId)))
            ->paginate((int) $request->integer('per_page', 15));

        return $this->ok($stocks);
    }

    public function expirySummary(Request $request)
    {
        $days = (int) $request->integer('days', SystemSetting::getValue('expiry_alert_days', config('medstock.stock_policy.expiry_alert_days', 90)));
        $items = WarehouseStockLot::query()
            ->with(['stock.warehouse.location', 'stock.drug.category'])
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)])
            ->where('qty_on_hand', '>', 0)
            ->orderBy('expiry_date')
            ->paginate((int) $request->integer('per_page', 15));

        return $this->ok($items);
    }

    public function adjustmentSummary(Request $request)
    {
        $items = StockMovement::query()
            ->with(['warehouse', 'performer', 'items.drug'])
            ->where('type', 'adjust')
            ->orderByDesc('movement_at')
            ->paginate((int) $request->integer('per_page', 15));

        return $this->ok($items);
    }

    public function alertSummary()
    {
        return $this->ok([
            'total_alerts' => AlertNotification::count(),
            'open_alerts' => AlertNotification::where('status', 'pending')->count(),
            'resolved_alerts' => AlertNotification::where('status', 'resolved')->count(),
        ]);
    }

    public function export(Request $request)
    {
        $format = strtolower($request->string('format')->toString() ?: 'xlsx');
        $report = $this->buildReportPayload($request);
        $filename = 'drugstock-report-' . now()->format('Ymd_His');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('pdf.drugstock-report', ['report' => $report])->setPaper('a4', 'landscape');

            return $pdf->download($filename . '.pdf');
        }

        return Excel::download(new DrugStockReportExport($report), $filename . '.xlsx');
    }

    private function buildReportPayload(Request $request): array
    {
        $warehouseId = $request->integer('warehouse_id') ?: null;
        $expiryDays = (int) $request->integer('days', SystemSetting::getValue('expiry_alert_days', config('medstock.stock_policy.expiry_alert_days', 90)));

        $summary = $this->service->dashboardMetrics();
        $alertSummary = [
            'total_alerts' => AlertNotification::count(),
            'open_alerts' => AlertNotification::where('status', 'pending')->count(),
            'resolved_alerts' => AlertNotification::where('status', 'resolved')->count(),
        ];

        $expiryItems = WarehouseStockLot::query()
            ->with(['stock.warehouse.location', 'stock.drug.category'])
            ->when($warehouseId, fn ($query, $value) => $query->whereHas('stock', fn ($stockQuery) => $stockQuery->where('warehouse_id', $value)))
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($expiryDays)])
            ->where('qty_on_hand', '>', 0)
            ->orderBy('expiry_date')
            ->get();

        $adjustmentItems = StockMovement::query()
            ->with(['warehouse', 'performer', 'items.drug'])
            ->when($warehouseId, fn ($query, $value) => $query->where('warehouse_id', $value))
            ->where('type', 'adjust')
            ->orderByDesc('movement_at')
            ->get();

        $alerts = AlertNotification::query()
            ->with(['rule', 'drug', 'warehouse'])
            ->when($warehouseId, fn ($query, $value) => $query->where('warehouse_id', $value))
            ->latest()
            ->limit(100)
            ->get();

        return [
            'summary_rows' => [
                ['จำนวนคลัง', $summary['warehouse_count'] ?? 0],
                ['จำนวนยา', $summary['drug_count'] ?? 0],
                ['คงเหลือต่ำ', $summary['low_stock_count'] ?? 0],
                ['ใกล้หมดอายุ', $summary['near_expiry_count'] ?? 0],
                ['หมดอายุ', $summary['expired_count'] ?? 0],
                ['รายการเคลื่อนไหววันนี้', $summary['movement_today'] ?? 0],
                ['แจ้งเตือนทั้งหมด', $alertSummary['total_alerts']],
                ['แจ้งเตือนรอดำเนินการ', $alertSummary['open_alerts']],
                ['แจ้งเตือนปิดแล้ว', $alertSummary['resolved_alerts']],
            ],
            'expiry_rows' => $expiryItems->map(function ($item) {
                return [
                    $item->stock?->drug?->name ?? '-',
                    $item->lot_no,
                    $item->stock?->warehouse?->name ?? '-',
                    $item->qty_on_hand,
                    optional($item->expiry_date)->format('d/m/Y') ?? '-',
                ];
            })->all(),
            'adjustment_rows' => $adjustmentItems->map(function ($item) {
                return [
                    $item->movement_no,
                    $item->warehouse?->name ?? '-',
                    $item->performer?->name ?? '-',
                    $item->total_qty,
                    optional($item->movement_at)->format('d/m/Y H:i') ?? '-',
                ];
            })->all(),
            'alert_rows' => $alerts->map(function ($item) {
                return [
                    $item->notification_type,
                    $item->title,
                    $item->status,
                    optional($item->created_at)->format('d/m/Y H:i') ?? '-',
                ];
            })->all(),
        ];
    }
}
