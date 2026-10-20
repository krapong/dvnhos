<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Box;
use App\Models\Alert;
use Illuminate\Support\Str;

class BoxController extends Controller
{
    private function resolveBoxPayload(Box $box): array
    {
        $locationId = $box->location_id ?? $box->locationId ?? null;
        $drugCount = (int) ($box->drug_count ?? $box->drugCount ?? 0);
        $code = $box->code ?? $box->name ?? '';
        $name = $box->name ?? $box->code ?? '';

        return [
            'id' => $box->id,
            'code' => $code,
            'name' => $name,
            'locationId' => $locationId,
            'location_id' => $locationId,
            'status' => $box->status,
            'value' => $box->value ?? 0,
            'drugCount' => $drugCount,
            'drug_count' => $drugCount,
            'qrCode' => $box->qr_code ?? null,
            'qr_code' => $box->qr_code ?? null,
            'updatedAt' => $box->updated_at ? $box->updated_at->toISOString() : null,
            'updated_at' => $box->updated_at ? $box->updated_at->toISOString() : null,
        ];
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = $this->getBoxesQueryForUser($user);

        return response()->json($query->get()->map(fn (Box $box) => $this->resolveBoxPayload($box))->values());
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $box = Box::find($id);
        if (!$box) {
            return response()->json(['error' => 'Box not found'], 404);
        }

        if (in_array($user->role, ['dept_admin', 'viewer']) && $box->location_id !== $user->location_id) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        return response()->json($this->resolveBoxPayload($box));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['super_admin', 'pharmacist', 'dept_admin'])) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $request->validate([
            'code' => 'nullable|string',
            'name' => 'required|string',
            'locationId' => 'nullable|string',
            'location_id' => 'nullable|string',
            'drugCount' => 'nullable|integer|min:0',
            'drug_count' => 'nullable|integer|min:0',
        ]);

        $locationId = $request->input('locationId', $request->input('location_id'));
        $drugCount = $request->input('drugCount', $request->input('drug_count', 0));

        $box = new Box();
        $box->id = 'box_' . Str::random(8);
        $box->name = $request->name;
        $box->location_id = $locationId ?: null;
        $this->setIfColumnExists($box, 'code', $request->code ?? null);
        $this->setIfColumnExists($box, 'status', $request->status ?? 'normal');
        $this->setIfColumnExists($box, 'value', $request->value ?? 0);
        $this->setIfColumnExists($box, 'drug_count', max(0, (int) $drugCount));
        $this->setIfColumnExists($box, 'qr_code', $request->qr_code ?? '');
        $box->save();
        $this->recordAudit($user->id, 'box.create', 'box', $box->id, [
            'after' => $box->getAttributes(),
        ]);

        return response()->json($this->resolveBoxPayload($box));
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $box = Box::find($id);
        if (!$box) {
            return response()->json(['error' => 'Box not found'], 404);
        }

        if (in_array($user->role, ['dept_admin', 'viewer']) && $box->location_id !== $user->location_id) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $before = $box->getAttributes();

        if ($request->has('code')) $this->setIfColumnExists($box, 'code', $request->code);
        if ($request->has('name')) $box->name = $request->name;

        $hasLocationId = $request->has('locationId') || $request->has('location_id');
        if ($hasLocationId && in_array($user->role, ['super_admin', 'pharmacist'])) {
            $locationId = $request->input('locationId', $request->input('location_id'));
            $box->location_id = $locationId ?: null;
        }
        if ($request->has('status')) $this->setIfColumnExists($box, 'status', $request->status);
        if ($request->has('value')) $this->setIfColumnExists($box, 'value', $request->value);

        $hasDrugCount = $request->has('drugCount') || $request->has('drug_count');
        if ($hasDrugCount) {
            $drugCount = $request->input('drugCount', $request->input('drug_count'));
            $this->setIfColumnExists($box, 'drug_count', max(0, (int) $drugCount));
        }

        if ($request->has('qr_code')) $this->setIfColumnExists($box, 'qr_code', $request->qr_code);
        
        $box->save();

        // Also update alerts if status changed
        if ($request->has('status') && $request->status === 'normal') {
            Alert::where('box_id', $box->id)->where('status', 'active')->update(['status' => 'resolved']);
        }

        $this->recordAudit($user->id, 'box.update', 'box', $box->id, [
            'before' => $before,
            'after' => $box->getAttributes(),
        ]);

        return response()->json($this->resolveBoxPayload($box));
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['super_admin', 'pharmacist'])) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $box = Box::find($id);
        if ($box) {
            $this->recordAudit($user->id, 'box.delete', 'box', $box->id, [
                'before' => $box->getAttributes(),
            ]);
            if (! $this->softDeleteIfSupported($box)) {
                return response()->json([
                    'error' => 'Soft delete is not supported by the current boxes table schema.'
                ], 409);
            }
        }

        return response()->json(['success' => true]);
    }
}
