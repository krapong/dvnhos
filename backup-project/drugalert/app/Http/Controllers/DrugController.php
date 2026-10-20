<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Drug;
use Illuminate\Support\Str;

class DrugController extends Controller
{
    public function index(Request $request)
    {
        return response()->json($this->activeQuery(Drug::class)->get());
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['super_admin', 'pharmacist'])) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
        ]);

        $drug = new Drug();
        $drug->id = 'drug_' . Str::random(8);
        $drug->name = $request->name;
        $this->setIfColumnExists($drug, 'category', $request->category);
        $this->setIfColumnExists($drug, 'unit', $request->unit ?? '');
        $this->setIfColumnExists($drug, 'price', $request->price ?? '0.00');
        $drug->description = $request->details ?? '';
        $drug->save();
        $this->recordAudit($user->id, 'drug.create', 'drug', $drug->id, [
            'after' => $drug->getAttributes(),
        ]);

        return response()->json($drug);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['super_admin', 'pharmacist'])) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $drug = Drug::find($id);
        if (!$drug) {
            return response()->json(['error' => 'Drug not found'], 404);
        }

        $before = $drug->getAttributes();
        if ($request->has('name')) $drug->name = $request->name;
        if ($request->has('category')) $this->setIfColumnExists($drug, 'category', $request->category);
        if ($request->has('unit')) $this->setIfColumnExists($drug, 'unit', $request->unit);
        if ($request->has('price')) $this->setIfColumnExists($drug, 'price', $request->price);
        if ($request->has('details')) $drug->description = $request->details;
        $drug->save();
        $this->recordAudit($user->id, 'drug.update', 'drug', $drug->id, [
            'before' => $before,
            'after' => $drug->getAttributes(),
        ]);

        return response()->json($drug);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['super_admin', 'pharmacist'])) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $drug = Drug::find($id);
        if ($drug) {
            $this->recordAudit($user->id, 'drug.delete', 'drug', $drug->id, [
                'before' => $drug->getAttributes(),
            ]);
            if (! $this->softDeleteIfSupported($drug)) {
                return response()->json([
                    'error' => 'Soft delete is not supported by the current drugs table schema.'
                ], 409);
            }
        }

        return response()->json(['success' => true]);
    }
}
