<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SopDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sops = SopDocument::latest()->paginate(20);
        return view('admin.sops.index', compact('sops'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.sops.form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'file' => 'required|mimes:pdf|max:10240', // PDF up to 10MB
        ]);

        $file = $request->file('file');
        $originalFilename = $file->getClientOriginalName();
        $filePath = $file->store('sops', 'public');

        SopDocument::create([
            'code' => $request->code,
            'title' => $request->title,
            'file_path' => $filePath,
            'original_filename' => $originalFilename,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.sops.index')->with('success', 'อัปโหลดเอกสาร SOP เรียบร้อยแล้ว');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Not used
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $sop = SopDocument::findOrFail($id);
        return view('admin.sops.form', compact('sop'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $sop = SopDocument::findOrFail($id);

        $request->validate([
            'code' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'file' => 'nullable|mimes:pdf|max:10240',
        ]);

        $data = [
            'code' => $request->code,
            'title' => $request->title,
        ];

        if ($request->hasFile('file')) {
            if ($sop->file_path && Storage::disk('public')->exists($sop->file_path)) {
                Storage::disk('public')->delete($sop->file_path);
            }
            $file = $request->file('file');
            $data['original_filename'] = $file->getClientOriginalName();
            $data['file_path'] = $file->store('sops', 'public');
        }

        $sop->update($data);

        return redirect()->route('admin.sops.index')->with('success', 'แก้ไขเอกสาร SOP เรียบร้อยแล้ว');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $sop = SopDocument::findOrFail($id);
        
        if ($sop->file_path && Storage::disk('public')->exists($sop->file_path)) {
            Storage::disk('public')->delete($sop->file_path);
        }
        
        $sop->delete();

        return redirect()->route('admin.sops.index')->with('success', 'ลบเอกสาร SOP เรียบร้อยแล้ว');
    }
}
