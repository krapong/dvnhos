<?php

namespace App\Http\Controllers\Api\V1\DrugStock;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\DrugStock\StoreWarehouseRequest;
use App\Http\Requests\DrugStock\UpdateWarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends BaseApiController
{
    public function index(Request $request)
    {
        $warehouses = Warehouse::query()
            ->with('location')
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate((int) $request->integer('per_page', 15));

        return $this->ok($warehouses);
    }

    public function show(Warehouse $warehouse)
    {
        return $this->ok(
            $warehouse->load(['location', 'stocks.drug.category', 'stocks.lots']),
            'ดึงรายละเอียดคลังย่อยเรียบร้อย'
        );
    }

    public function store(StoreWarehouseRequest $request)
    {
        return $this->ok(Warehouse::create($request->validated()), 'บันทึกคลังย่อยเรียบร้อย');
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        $warehouse->update($request->validated());

        return $this->ok($warehouse->refresh()->load('location'), 'อัปเดตคลังย่อยเรียบร้อย');
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return $this->ok(null, 'ลบคลังย่อยเรียบร้อย');
    }
}
