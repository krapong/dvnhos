<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DietTemplate;
use App\Models\DietType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DietTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            DietTemplate::query()->with(['dietType', 'items'])->orderBy('name')->get()
        );
    }

    public function types(): JsonResponse
    {
        return response()->json(
            DietType::query()
                ->where('is_active', true)
                ->where('code', '!=', 'NPO')
                ->orderBy('name')
                ->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'diet_type_id' => [
                'required',
                'integer',
                Rule::exists('diet_types', 'id')->where(fn ($query) => $query->where('code', '!=', 'NPO')),
            ],
            'code' => ['required', 'string', 'max:50', Rule::unique('diet_templates', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_energy_kcal' => ['nullable', 'numeric'],
            'target_protein_g' => ['nullable', 'numeric'],
            'target_carbohydrate_g' => ['nullable', 'numeric'],
            'target_fat_g' => ['nullable', 'numeric'],
            'target_sodium_mg' => ['nullable', 'numeric'],
            'target_potassium_mg' => ['nullable', 'numeric'],
            'target_fluid_ml' => ['nullable', 'numeric'],
            'texture' => ['nullable', 'string', 'max:100'],
            'allowed_for_notes' => ['nullable', 'array'],
            'restricted_for_notes' => ['nullable', 'array'],
            'meal_scope' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'items' => ['nullable', 'array'],
            'items.*.item_type' => ['nullable', 'string', 'max:50'],
            'items.*.item_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'string', 'max:50'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $template = DB::transaction(function () use ($data): DietTemplate {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $template = DietTemplate::create($data);

            foreach (array_values($items) as $index => $item) {
                $template->items()->create([
                    'item_type' => $item['item_type'] ?? 'main',
                    'item_name' => $item['item_name'],
                    'quantity' => $item['quantity'] ?? null,
                    'notes' => $item['notes'] ?? null,
                    'sort_order' => $index + 1,
                ]);
            }

            return $template->fresh(['dietType', 'items']);
        });

        return response()->json($template, 201);
    }

    public function update(Request $request, DietTemplate $dietTemplate): JsonResponse
    {
        $dietTemplate->update($request->validate([
            'diet_type_id' => [
                'sometimes',
                'integer',
                Rule::exists('diet_types', 'id')->where(fn ($query) => $query->where('code', '!=', 'NPO')),
            ],
            'code' => ['sometimes', 'string', 'max:50'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_energy_kcal' => ['nullable', 'numeric'],
            'target_protein_g' => ['nullable', 'numeric'],
            'target_carbohydrate_g' => ['nullable', 'numeric'],
            'target_fat_g' => ['nullable', 'numeric'],
            'target_sodium_mg' => ['nullable', 'numeric'],
            'target_potassium_mg' => ['nullable', 'numeric'],
            'target_fluid_ml' => ['nullable', 'numeric'],
            'texture' => ['nullable', 'string', 'max:100'],
            'allowed_for_notes' => ['nullable', 'array'],
            'restricted_for_notes' => ['nullable', 'array'],
            'meal_scope' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]));

        return response()->json($dietTemplate->fresh(['dietType', 'items']));
    }
}
