<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use Illuminate\Support\Str;

class LocationController extends Controller
{
    private function resolveLocationPayload(Location $location): array
    {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'detail' => $location->description ?? '',
            'details' => $location->description ?? '',
            'status' => $location->is_active ? 'ใช้งาน' : 'ยกเลิก',
            'updatedAt' => $location->updated_at ? $location->updated_at->toISOString() : null,
            'updated_at' => $location->updated_at ? $location->updated_at->toISOString() : null,
        ];
    }

    public function index(Request $request)
    {
        $query = $this->activeQuery(Location::class);

        return response()->json($query->get()->map(fn (Location $location) => $this->resolveLocationPayload($location))->values());
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'super_admin') {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $request->validate([
            'name' => 'required|string',
            'type' => 'nullable|string',
        ]);

        $location = new Location();
        $location->id = 'loc_' . Str::random(8);
        $location->name = $request->name;
        $this->setIfColumnExists($location, 'type', $request->type ?? null);
        $location->description = $request->detail ?? $request->details ?? '';
        $location->is_active = ($request->status ?? 'ใช้งาน') === 'ใช้งาน';
        $location->save();
        $this->recordAudit($user->id, 'location.create', 'location', $location->id, [
            'after' => $location->getAttributes(),
        ]);

        return response()->json($this->resolveLocationPayload($location));
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'super_admin') {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $location = Location::find($id);
        if (!$location) {
            return response()->json(['error' => 'Location not found'], 404);
        }

        $before = $location->getAttributes();

        if ($request->has('name')) $location->name = $request->name;
        if ($request->has('type')) $this->setIfColumnExists($location, 'type', $request->type);
        if ($request->has('detail') || $request->has('details')) {
            $location->description = $request->detail ?? $request->details ?? '';
        }
        if ($request->has('status')) {
            $location->is_active = $request->status === 'ใช้งาน';
        }
        $location->save();
        $this->recordAudit($user->id, 'location.update', 'location', $location->id, [
            'before' => $before,
            'after' => $location->getAttributes(),
        ]);

        return response()->json($this->resolveLocationPayload($location));
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'super_admin') {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $location = Location::find($id);
        if ($location) {
            $this->recordAudit($user->id, 'location.delete', 'location', $location->id, [
                'before' => $location->getAttributes(),
            ]);
            if (! $this->softDeleteIfSupported($location)) {
                return response()->json([
                    'error' => 'Soft delete is not supported by the current locations table schema.'
                ], 409);
            }
        }

        return response()->json(['success' => true]);
    }
}
