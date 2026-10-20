<?php

namespace App\Http\Controllers\Api\V1\DrugStock;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\DrugStock\StoreDrugRequest;
use App\Http\Requests\DrugStock\UpdateDrugRequest;
use App\Models\DrugCategory;
use App\Models\Drug;
use App\Models\Unit;
use Illuminate\Http\Request;

class DrugController extends BaseApiController
{
    public function index(Request $request)
    {
        $drugs = Drug::query()
            ->with(['category', 'unit'])
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('generic_name', 'like', "%{$search}%");
                });
            })
            ->when($request->boolean('active_only'), fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->paginate((int) $request->integer('per_page', 15));

        return $this->ok($drugs);
    }

    public function store(StoreDrugRequest $request)
    {
        return $this->ok(Drug::create($request->validated()), 'บันทึกข้อมูลยาเรียบร้อย');
    }

    public function options()
    {
        return $this->ok([
            'categories' => DrugCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($item) => ['title' => $item->name, 'value' => $item->id]),
            'units' => Unit::query()
                ->orderBy('name')
                ->get(['id', 'name', 'symbol'])
                ->map(fn ($item) => ['title' => trim($item->name . ' (' . $item->symbol . ')'), 'value' => $item->id]),
        ]);
    }

    public function update(UpdateDrugRequest $request, Drug $drug)
    {
        $drug->update($request->validated());

        return $this->ok($drug->refresh(), 'อัปเดตข้อมูลยาเรียบร้อย');
    }

    public function destroy(Drug $drug)
    {
        $drug->delete();

        return $this->ok(null, 'ลบข้อมูลยาเรียบร้อย');
    }
}
