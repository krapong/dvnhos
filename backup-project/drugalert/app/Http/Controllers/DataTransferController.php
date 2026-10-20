<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Box;
use App\Models\Drug;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BoxInventoryExport;
use App\Exports\RepackHistoryExport;

class DataTransferController extends Controller
{
    public function export(Request $request, string $resource)
    {
        $filename = $resource . '-' . now()->format('Y-m-d') . '.xlsx';

        if ($resource === 'boxes') {
            return Excel::download(new BoxInventoryExport(app(\App\Repositories\ReportRepository::class)), $filename, \Maatwebsite\Excel\Excel::XLSX);
        }

        if ($resource === 'repack_history') {
            $month = $request->query('month', now()->format('Y-m'));
            return Excel::download(new RepackHistoryExport($month), $filename, \Maatwebsite\Excel\Excel::XLSX);
        }

        // Fallback for old resources
        $rows = match ($resource) {
            'drugs' => $this->exportDrugs(),
            'locations' => $this->exportLocations(),
            'alerts' => $this->exportAlerts(),
            default => abort(404, 'Unsupported export resource.'),
        };

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function reportPdf(Request $request)
    {
        $repository = app(\App\Repositories\ReportRepository::class);
        $inventory = $repository->getInventorySummary();
        $alerts = $repository->getAlertsSummary();

        $data = [
            'date' => now()->format('Y-m-d H:i'),
            'total_boxes' => $inventory['total_boxes'],
            'total_value' => $inventory['total_value'],
            'active_alerts' => $alerts['active_alerts'],
            'total_drugs' => \App\Models\Drug::count(),
            'locations' => \App\Models\Location::count(),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.summary', $data);

        return $pdf->stream('drug-alert-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function import(Request $request, string $resource)
    {
        $user = $request->user();
        if (! $user || $user->role !== 'super_admin') {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $request->validate([
            'file' => 'required|file',
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);

        if (! $headers) {
            return response()->json(['error' => 'Invalid CSV file'], 422);
        }

        $created = 0;
        $updated = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $record = array_combine($headers, $row);
            if (! is_array($record)) {
                continue;
            }

            [$isNew, $wasUpdated] = match ($resource) {
                'boxes' => $this->importBox($record),
                'drugs' => $this->importDrug($record),
                'locations' => $this->importLocation($record),
                default => [false, false],
            };

            $created += $isNew ? 1 : 0;
            $updated += $wasUpdated ? 1 : 0;
        }

        fclose($handle);

        $this->recordAudit($user->id, 'import.' . $resource, $resource, null, [
            'created' => $created,
            'updated' => $updated,
        ]);

        if (! $request->expectsJson()) {
            return back()->with('status', "Imported {$resource}: created={$created}, updated={$updated}");
        }

        return response()->json([
            'message' => 'Import completed',
            'created' => $created,
            'updated' => $updated,
        ]);
    }

    private function exportBoxes(): array
    {
        $rows = [['id', 'name', 'location_id', 'status', 'value', 'drug_count']];

        foreach (Box::query()->orderBy('name')->get() as $box) {
            $rows[] = [
                $box->id,
                $box->name,
                $box->location_id,
                $box->status,
                $box->value,
                $box->drug_count,
            ];
        }

        return $rows;
    }

    private function exportDrugs(): array
    {
        $rows = [['id', 'name', 'category', 'unit', 'price', 'deleted_at']];

        foreach (Drug::query()->withTrashed()->orderBy('name')->get() as $drug) {
            $rows[] = [
                $drug->id,
                $drug->name,
                $drug->category ?? '',
                $drug->unit ?? '',
                $drug->price ?? '',
                optional($drug->deleted_at)->format('Y-m-d H:i:s'),
            ];
        }

        return $rows;
    }

    private function exportLocations(): array
    {
        $rows = [['id', 'name', 'detail', 'status']];

        foreach (Location::query()->orderBy('name')->get() as $location) {
            $rows[] = [
                $location->id,
                $location->name,
                $location->detail ?? '',
                $location->status ?? '',
            ];
        }

        return $rows;
    }

    private function exportAlerts(): array
    {
        $rows = [['id', 'box_id', 'drug_name', 'quantity', 'unit', 'severity', 'status', 'category', 'expiry_date', 'resolved_at']];

        foreach (Alert::query()->orderByDesc('created_at')->get() as $alert) {
            $rows[] = [
                $alert->id,
                $alert->box_id,
                $alert->drug_name,
                $alert->quantity,
                $alert->unit,
                $alert->severity,
                $alert->status,
                $alert->category,
                $alert->expiry_date,
                optional($alert->resolved_at)->format('Y-m-d H:i:s'),
            ];
        }

        return $rows;
    }

    private function importBox(array $record): array
    {
        $box = !empty($record['id']) ? Box::find($record['id']) : null;
        $isNew = false;

        if (! $box) {
            $box = new Box();
            $box->id = $record['id'] ?: 'box_' . Str::random(8);
            $isNew = true;
        }

        $box->name = $record['name'] ?? $box->name;
        $box->location_id = $record['location_id'] ?? $box->location_id;
        $box->status = $record['status'] ?? $box->status ?? 'normal';
        $box->value = $record['value'] ?? $box->value ?? 0;
        $box->drug_count = $record['drug_count'] ?? $box->drug_count ?? 0;
        $box->save();

        return [$isNew, ! $isNew];
    }

    private function importDrug(array $record): array
    {
        $drug = !empty($record['id']) ? Drug::withTrashed()->find($record['id']) : null;
        $isNew = false;

        if (! $drug) {
            $drug = new Drug();
            $drug->id = $record['id'] ?: 'drug_' . Str::random(8);
            $isNew = true;
        }

        $drug->name = $record['name'] ?? $drug->name;
        $drug->category = $record['category'] ?? $drug->category ?? 'general';
        $drug->unit = $record['unit'] ?? $drug->unit ?? '';
        $drug->price = $record['price'] ?? $drug->price ?? '0.00';
        $drug->deleted_at = $this->parseNullableTimestamp($record['deleted_at'] ?? null);
        $drug->save();

        return [$isNew, ! $isNew];
    }

    private function importLocation(array $record): array
    {
        $location = !empty($record['id']) ? Location::find($record['id']) : null;
        $isNew = false;

        if (! $location) {
            $location = new Location();
            $location->id = $record['id'] ?: 'loc_' . Str::random(8);
            $isNew = true;
        }

        $location->name = $record['name'] ?? $location->name;
        $location->detail = $record['detail'] ?? $location->detail ?? '';
        $location->status = $record['status'] ?? $location->status ?? 'active';
        $location->save();

        return [$isNew, ! $isNew];
    }

    private function parseNullableTimestamp(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return null;
        }

        try {
            return Carbon::parse($trimmed)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }


}
