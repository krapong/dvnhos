<?php

namespace App\Http\Controllers\Api\V1\DrugStock;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\DrugStock\StoreLocationRequest;
use App\Http\Requests\DrugStock\UpdateLocationRequest;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends BaseApiController
{
    public function index(Request $request)
    {
        $locations = Location::query()
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate((int) $request->integer('per_page', 15));

        return $this->ok($locations);
    }

    public function store(StoreLocationRequest $request)
    {
        return $this->ok(Location::create($request->validated()), 'บันทึกสถานที่เรียบร้อย');
    }

    public function update(UpdateLocationRequest $request, Location $location)
    {
        $location->update($request->validated());

        return $this->ok($location->refresh(), 'อัปเดตสถานที่เรียบร้อย');
    }

    public function destroy(Location $location)
    {
        $location->delete();

        return $this->ok(null, 'ลบสถานที่เรียบร้อย');
    }
}
