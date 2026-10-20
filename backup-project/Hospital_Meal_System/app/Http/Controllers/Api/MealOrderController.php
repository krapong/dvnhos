<?php

namespace App\Http\Controllers\Api;

use App\Enums\MealType;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\MealOrder;
use App\Models\Patient;
use App\Models\Ward;
use App\Services\MealOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MealOrderController extends Controller
{
    public function __construct(private readonly MealOrderService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = MealOrder::query()->with(['patient', 'items', 'batch', 'ward', 'room', 'bed']);

        if ($request->filled('service_date')) {
            $query->whereDate('meal_date', $request->string('service_date'));
        }

        if ($request->filled('ward_id')) {
            $query->where('ward_id', $request->integer('ward_id'));
        }

        return response()->json($query->orderByDesc('id')->paginate((int) $request->integer('per_page', 25)));
    }

    public function bulkApply(Request $request): JsonResponse
    {
        $data = $request->validate([
            'patient_ids' => ['required', 'array'],
            'patient_ids.*' => ['integer'],
            'template_id' => ['required', 'integer'],
            'service_date' => ['required', 'date'],
            'meal_types' => ['required', 'array'],
            'meal_types.*' => ['string', 'in:breakfast,lunch,dinner'],
        ]);

        $orders = $this->service->bulkApplyTemplate($data['patient_ids'], $data['template_id'], $data['service_date'], array_map(
            fn (string $mealType) => MealType::from($mealType),
            $data['meal_types']
        ));

        return response()->json(['ok' => true, 'orders' => $orders], 201);
    }

    public function manualApply(Request $request): JsonResponse
    {
        $data = $request->validate([
            'patient_ids' => ['required', 'array', 'min:1'],
            'patient_ids.*' => ['integer', 'distinct'],
            'template_id' => ['required', 'integer', 'exists:diet_templates,id'],
            'service_date' => ['required', 'date'],
            'meal_types' => ['required', 'array', 'min:1'],
            'meal_types.*' => ['string', 'in:breakfast,lunch,dinner'],
        ]);

        $result = $this->service->manualApplyTemplate($data['patient_ids'], $data['template_id'], $data['service_date'], array_map(
            fn (string $mealType) => MealType::from($mealType),
            $data['meal_types']
        ));

        return response()->json(['ok' => true] + $result, 201);
    }

    public function copyFromPreviousDay(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ward_id' => ['required', 'integer'],
            'service_date' => ['required', 'date'],
        ]);

        $ward = Ward::findOrFail($data['ward_id']);

        return response()->json([
            'ok' => true,
            'orders' => $this->service->copyFromPreviousDay($ward, $data['service_date']),
        ]);
    }

    public function statOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'patient_hn' => ['required', 'string'],
            'service_date' => ['required', 'date'],
            'reason' => ['required', 'string'],
        ]);

        $patient = Patient::query()->where('hn', $data['patient_hn'])->firstOrFail();

        return response()->json([
            'ok' => true,
            'order' => $this->service->buildStatOrder($patient, $data['service_date'], $data['reason']),
        ], 201);
    }

    public function advance(Request $request, MealOrder $mealOrder): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:reviewing,approved,accepted,preparing,tray_ready,delivering,delivered,ward_received,completed'],
        ]);

        $updated = $this->service->advanceStatus($mealOrder, OrderStatus::from($data['status']));

        return response()->json($updated->load('items'));
    }

    public function reject(Request $request, MealOrder $mealOrder): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $mealOrder->update([
            'status' => OrderStatus::Rejected,
            'change_reason' => $data['reason'],
            'changed_by' => auth()->id(),
        ]);

        return response()->json($mealOrder->fresh('items'));
    }
}
