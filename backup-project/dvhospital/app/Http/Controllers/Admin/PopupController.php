<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Popup;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class PopupController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage_banners');
    }

    public function index()
    {
        $popups = Popup::with('user')->orderBy('sort_order')->paginate(20);
        return view('admin.popups.index', compact('popups'));
    }

    public function create()
    {
        return view('admin.popups.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image|max:5120',
            'link' => 'nullable|url|max:500',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $validated['image'] = $request->file('image')->store('popups', 'public');
        $validated['user_id'] = auth()->id();
        $validated['is_active'] = $request->boolean('is_active');

        $popup = Popup::create($validated);
        ActivityLog::log('create', "สร้าง Popup: {$popup->title}", $popup);

        return redirect()->route('admin.popups.index')
            ->with('success', 'เพิ่ม Popup สำเร็จ');
    }

    public function edit(Popup $popup)
    {
        return view('admin.popups.edit', compact('popup'));
    }

    public function update(Request $request, Popup $popup)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'nullable|image|max:5120',
            'link' => 'nullable|url|max:500',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('popups', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active');

        $popup->update($validated);
        ActivityLog::log('update', "แก้ไข Popup: {$popup->title}", $popup);

        return redirect()->route('admin.popups.index')
            ->with('success', 'แก้ไข Popup สำเร็จ');
    }

    public function destroy(Popup $popup)
    {
        ActivityLog::log('delete', "ลบ Popup: {$popup->title}", $popup);
        $popup->delete();

        return redirect()->route('admin.popups.index')
            ->with('success', 'ลบ Popup สำเร็จ');
    }
}
