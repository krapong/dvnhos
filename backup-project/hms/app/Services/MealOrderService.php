<?php

namespace App\Services;

use App\Enums\MealType;
use App\Enums\OrderStatus;
use App\Models\Admission;
use App\Models\AuditLog;
use App\Models\DietTemplate;
use App\Models\MealOrder;
use App\Models\MealOrderBatch;
use App\Models\MealOrderChange;
use App\Models\MealOrderItem;
use App\Models\Patient;
use App\Models\Ward;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class MealOrderService
{
    protected function currentAdmissionForPatient(Patient $patient): ?Admission
    {
        return Admission::query()
            ->where('patient_id', $patient->id)
            ->where('status', 'N')
            ->latest('admitted_at')
            ->latest('id')
            ->first();
    }

    public function buildWardMatrix(Ward $ward, string $serviceDate): Collection
    {
        return Patient::query()
            ->with(['ward', 'room', 'bed', 'allergies'])
            ->where('ward_id', $ward->id)
            ->orderBy('bed_id')
            ->get()
            ->map(function (Patient $patient) use ($serviceDate) {
                $order = MealOrder::query()
                    ->with('items')
                    ->where('patient_id', $patient->id)
                    ->whereDate('meal_date', $serviceDate)
                    ->first();

                return [
                    'patient' => $patient,
                    'order' => $order,
                    'flags' => $this->buildFlags($patient),
                ];
            });
    }

    public function bulkApplyTemplate(array $patientIds, int $templateId, string $serviceDate, array $mealTypes): Collection
    {
        $template = DietTemplate::findOrFail($templateId);
        $patients = Patient::whereIn('id', $patientIds)->get();
        $orders = collect();

        foreach ($patients as $patient) {
            $orders->push($this->createOrUpdateOrder($patient, $template, $serviceDate, $mealTypes));
        }

        return $orders;
    }

    public function manualApplyTemplate(array $patientIds, int $templateId, string $serviceDate, array $mealTypes): array
    {
        $template = DietTemplate::findOrFail($templateId);
        $patients = Patient::query()
            ->with(['ward', 'room', 'bed'])
            ->whereIn('id', $patientIds)
            ->get();
        $orders = collect();
        $skipped = collect();

        foreach ($patients as $patient) {
            $blockReason = $this->mealBlockReason($patient);

            if ($blockReason !== null) {
                $skipped->push($this->buildSkippedPatient($patient, $blockReason));
                continue;
            }

            $orders->push($this->createOrUpdateOrder($patient, $template, $serviceDate, $mealTypes, 'manual'));
        }

        return [
            'created' => $orders->count(),
            'skipped' => $skipped->count(),
            'orders' => $orders->values(),
            'skipped_patients' => $skipped->values(),
        ];
    }

    public function copyFromPreviousDay(Ward $ward, string $serviceDate): Collection
    {
        $previousDate = Carbon::parse($serviceDate)->subDay()->toDateString();
        $previousOrders = MealOrder::query()
            ->where('ward_id', $ward->id)
            ->whereDate('meal_date', $previousDate)
            ->with('items', 'patient')
            ->get();

        $result = collect();

        foreach ($previousOrders as $previousOrder) {
            $patient = $previousOrder->patient;
            if (! $patient || $patient->npo_status || $previousOrder->status === OrderStatus::Cancelled) {
                continue;
            }

            $order = $previousOrder->replicate(['created_at', 'updated_at']);
            $order->meal_order_batch_id = MealOrderBatch::firstOrCreate([
                'ward_id' => $ward->id,
                'service_date' => $serviceDate,
            ], [
                'cutoff_time' => config('hospital_meal.meal_cutoffs.night'),
                'status' => OrderStatus::Draft,
                'created_by' => auth()->id(),
            ])->id;
            $order->meal_date = $serviceDate;
            $order->status = OrderStatus::Draft;
            $order->source = 'copied_from_previous_day';
            $order->save();

            foreach ($previousOrder->items as $item) {
                $order->items()->create($item->only([
                    'meal_type',
                    'diet_template_id',
                    'diet_type_id',
                    'menu_item_name',
                    'portion',
                    'status',
                    'notes',
                ]));
            }

            $result->push($order->load('items'));
        }

        return $result;
    }

    public function advanceStatus(MealOrder $mealOrder, OrderStatus $nextStatus): MealOrder
    {
        $before = $mealOrder->toArray();
        $mealOrder->status = $nextStatus;
        $mealOrder->save();

        MealOrderChange::create([
            'meal_order_id' => $mealOrder->id,
            'changed_by' => auth()->id(),
            'change_type' => 'status_advance',
            'before_payload' => $before,
            'after_payload' => $mealOrder->fresh()->toArray(),
            'reason' => null,
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'event_type' => 'meal_order_status_changed',
            'auditable_type' => MealOrder::class,
            'auditable_id' => $mealOrder->id,
            'before_payload' => $before,
            'after_payload' => $mealOrder->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $mealOrder;
    }

    public function buildStatOrder(Patient $patient, string $serviceDate, string $reason): MealOrder
    {
        $order = MealOrder::create([
            'meal_order_batch_id' => MealOrderBatch::firstOrCreate([
                'ward_id' => $patient->ward_id,
                'service_date' => $serviceDate,
            ], [
                'cutoff_time' => config('hospital_meal.meal_cutoffs.night'),
                'status' => OrderStatus::Draft,
                'created_by' => auth()->id(),
            ])->id,
            'patient_id' => $patient->id,
            'admission_id' => optional($this->currentAdmissionForPatient($patient))->id,
            'ward_id' => $patient->ward_id,
            'room_id' => $patient->room_id,
            'bed_id' => $patient->bed_id,
            'meal_date' => $serviceDate,
            'status' => OrderStatus::Stat,
            'ordered_by' => auth()->id(),
            'source' => 'stat',
            'is_stat' => true,
            'npo_locked' => (bool) $patient->npo_status,
        ]);

        $order->items()->create([
            'meal_type' => MealType::Breakfast,
            'menu_item_name' => 'STAT order',
            'portion' => 'standard',
            'status' => OrderStatus::Stat,
            'notes' => ['reason' => $reason],
        ]);

        return $order->load('items');
    }

    public function mealBlockReason(Patient $patient): ?string
    {
        if ($patient->npo_status) {
            return 'NPO: งดน้ำ/งดอาหาร';
        }

        $text = mb_strtolower(collect([
            $patient->diet_order,
            $patient->clinical_note,
            $patient->primary_diagnosis,
            $patient->secondary_diagnosis,
        ])->filter()->implode(' '));

        $blockedTerms = [
            'งดน้ำ',
            'งดอาหาร',
            'งดนํ้า',
            'npo',
            'nil per os',
            'nothing by mouth',
            'no oral',
        ];

        foreach ($blockedTerms as $term) {
            if (str_contains($text, mb_strtolower($term))) {
                return str_contains($term, 'น้ำ') || str_contains($term, 'นํ้า')
                    ? 'แพทย์สั่งงดน้ำ'
                    : 'แพทย์สั่งงดอาหาร';
            }
        }

        return null;
    }

    protected function createOrUpdateOrder(Patient $patient, DietTemplate $template, string $serviceDate, array $mealTypes, string $source = 'bulk_template'): MealOrder
    {
        $order = MealOrder::firstOrCreate([
            'patient_id' => $patient->id,
            'meal_date' => $serviceDate,
        ], [
            'meal_order_batch_id' => MealOrderBatch::firstOrCreate([
                'ward_id' => $patient->ward_id,
                'service_date' => $serviceDate,
            ], [
                'cutoff_time' => config('hospital_meal.meal_cutoffs.night'),
                'status' => OrderStatus::Draft,
                'created_by' => auth()->id(),
            ])->id,
            'admission_id' => optional($this->currentAdmissionForPatient($patient))->id,
            'ward_id' => $patient->ward_id,
            'room_id' => $patient->room_id,
            'bed_id' => $patient->bed_id,
            'status' => $patient->npo_status ? OrderStatus::NeedClarification : OrderStatus::Draft,
            'ordered_by' => auth()->id(),
            'source' => $source,
            'is_stat' => false,
            'npo_locked' => (bool) $patient->npo_status,
        ]);

        foreach ($mealTypes as $mealType) {
            MealOrderItem::updateOrCreate([
                'meal_order_id' => $order->id,
                'meal_type' => $mealType,
            ], [
                'diet_template_id' => $template->id,
                'diet_type_id' => $template->diet_type_id,
                'menu_item_name' => $template->name,
                'portion' => 'standard',
                'status' => $patient->npo_status ? OrderStatus::NeedClarification : OrderStatus::Draft,
                'notes' => [
                    'template' => $template->code,
                    'npo_status' => $patient->npo_status,
                ],
            ]);
        }

        return $order->load('items');
    }

    protected function buildSkippedPatient(Patient $patient, string $reason): array
    {
        return [
            'patient_id' => $patient->id,
            'hn' => $patient->hn,
            'an' => $patient->an,
            'name' => trim("{$patient->prefix} {$patient->first_name} {$patient->last_name}"),
            'ward' => $patient->ward?->name,
            'room' => $patient->room?->name,
            'bed' => $patient->bed?->name ?? $patient->bed?->code ?? null,
            'reason' => $reason,
        ];
    }

    protected function buildFlags(Patient $patient): array
    {
        return [
            'npo' => (bool) $patient->npo_status,
            'meal_block_reason' => $this->mealBlockReason($patient),
            'allergies' => $patient->allergies->pluck('allergen')->all(),
            'bmi_warning' => $patient->bmi !== null && ($patient->bmi < 18.5 || $patient->bmi >= 30),
        ];
    }
}
