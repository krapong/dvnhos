<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage_events');
    }

    public function index()
    {
        $events = Event::with('user')->latest('start_date')->paginate(20);
        return view('admin.events.index', compact('events'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'color' => 'nullable|string|max:7',
            'is_all_day' => 'boolean',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['is_all_day'] = $request->boolean('is_all_day');

        $event = Event::create($validated);
        ActivityLog::log('create', "สร้างกิจกรรม: {$event->title}", $event);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'event' => $event]);
        }

        return redirect()->route('admin.events.index')
            ->with('success', 'เพิ่มกิจกรรมสำเร็จ');
    }

    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'color' => 'nullable|string|max:7',
            'is_all_day' => 'boolean',
        ]);

        $validated['is_all_day'] = $request->boolean('is_all_day');
        $event->update($validated);

        ActivityLog::log('update', "แก้ไขกิจกรรม: {$event->title}", $event);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'event' => $event]);
        }

        return redirect()->route('admin.events.index')
            ->with('success', 'แก้ไขกิจกรรมสำเร็จ');
    }

    public function destroy(Event $event)
    {
        ActivityLog::log('delete', "ลบกิจกรรม: {$event->title}", $event);
        $event->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.events.index')
            ->with('success', 'ลบกิจกรรมสำเร็จ');
    }

    // API สำหรับ Calendar JSON
    public function calendarData(Request $request)
    {
        $events = Event::query()
            ->when($request->start, fn($q) => $q->where('start_date', '>=', $request->start))
            ->when($request->end, fn($q) => $q->where('start_date', '<=', $request->end))
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'start' => $e->start_date->toIso8601String(),
                'end' => $e->end_date?->toIso8601String(),
                'color' => $e->color,
                'allDay' => $e->is_all_day,
                'extendedProps' => [
                    'description' => $e->description,
                    'location' => $e->location,
                ],
            ]);

        return response()->json($events);
    }
}
