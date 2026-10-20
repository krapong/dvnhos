<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage_banners');
    }

    public function index()
    {
        $banners = Banner::with('user')->orderBy('sort_order')->paginate(20);
        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        return view('admin.banners.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image|max:5120',
            'link' => 'nullable|url|max:500',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $validated['image'] = $request->file('image')->store('banners', 'public');
        $validated['user_id'] = auth()->id();
        $validated['is_active'] = $request->boolean('is_active');

        $banner = Banner::create($validated);
        ActivityLog::log('create', "สร้าง Banner: {$banner->title}", $banner);

        return redirect()->route('admin.banners.index')
            ->with('success', 'เพิ่ม Banner สำเร็จ');
    }

    public function edit(Banner $banner)
    {
        return view('admin.banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'nullable|image|max:5120',
            'link' => 'nullable|url|max:500',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('banners', 'public');
        } else {
            unset($validated['image']);
        }
        $validated['is_active'] = $request->boolean('is_active');

        $banner->update($validated);
        ActivityLog::log('update', "แก้ไข Banner: {$banner->title}", $banner);

        return redirect()->route('admin.banners.index')
            ->with('success', 'แก้ไข Banner สำเร็จ');
    }

    public function destroy(Banner $banner)
    {
        ActivityLog::log('delete', "ลบ Banner: {$banner->title}", $banner);
        $banner->delete();

        return redirect()->route('admin.banners.index')
            ->with('success', 'ลบ Banner สำเร็จ');
    }
}
