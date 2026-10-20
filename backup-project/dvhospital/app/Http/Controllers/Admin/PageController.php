<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage_pages');
    }

    public function index(Request $request)
    {
        $pages = Page::with('user')
            ->when($request->section, fn($q, $s) => $q->section($s))
            ->latest()
            ->paginate(20);

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        $sections = [
            'about' => 'เกี่ยวกับองค์กร',
            'services' => 'บริการ',
            'ita' => 'ITA',
            'ethics' => 'ชมรมจริยธรรม',
            'oit' => 'OIT',
            'general' => 'ทั่วไป',
        ];
        // Keep historical ITA sections available without changing existing records.
        foreach (['2567', '2568', '2569'] as $year) {
            $sections["ita_{$year}"] = "ITA ปีงบประมาณ {$year}";
        }
        $selectedSection = $request->query('section', 'about');
        return view('admin.pages.create', compact('sections', 'selectedSection'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'section' => ['required', 'string', 'regex:/^(about|services|ita|ita_2567|ita_2568|ita_2569|ethics|oit|general)$/'],
            'content' => 'required|string',
            'featured_image' => 'nullable|image|max:5120',
            'attachments.*' => 'nullable|file|max:10240',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        // รองรับภาษาไทยใน URL
        $slug = preg_replace('/\s+/u', '-', trim($validated['title']));
        $slug = preg_replace('/[^A-Za-z0-9ก-๙\-]/u', '', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        
        if (empty($slug)) {
            $slug = 'page';
        }

        $slug = Page::where('slug', $slug)->exists() ? $slug . '-' . time() : $slug;

        $page = new Page();
        $page->title = $validated['title'];
        $page->slug = $slug;
        $page->section = $validated['section'];
        $page->content = $validated['content'];
        $page->sort_order = $validated['sort_order'] ?? 0;
        $page->is_active = $request->boolean('is_active');
        $page->user_id = auth()->id();

        if ($request->hasFile('featured_image')) {
            $page->featured_image = $request->file('featured_image')->store('pages', 'public');
        }

        if ($request->hasFile('attachments')) {
            $attachments = [];
            foreach ($request->file('attachments') as $file) {
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $file->store('attachments', 'public'),
                    'size' => $file->getSize(),
                ];
            }
            $page->attachments = $attachments;
        }

        $page->save();

        ActivityLog::log('create', "สร้างหน้าเพจ: {$page->title}", $page);

        return redirect()->route('admin.pages.index')
            ->with('success', 'สร้างหน้าเพจสำเร็จ');
    }

    public function edit(Page $page)
    {
        $sections = [
            'about' => 'เกี่ยวกับองค์กร',
            'services' => 'บริการ',
            'ita' => 'ITA',
            'ethics' => 'ชมรมจริยธรรม',
            'oit' => 'OIT',
            'general' => 'ทั่วไป',
        ];
        foreach (['2567', '2568', '2569'] as $year) {
            $sections["ita_{$year}"] = "ITA ปีงบประมาณ {$year}";
        }
        // Preserve any older ITA section already stored in the database.
        if (str_starts_with($page->section, 'ita_') && !isset($sections[$page->section])) {
            $sections[$page->section] = $page->section;
        }
        return view('admin.pages.edit', compact('page', 'sections'));
    }

    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'section' => ['required', 'string', 'regex:/^(about|services|ita|ita_2567|ita_2568|ita_2569|ethics|oit|general)$/'],
            'content' => 'required|string',
            'featured_image' => 'nullable|image|max:5120',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $page->title = $validated['title'];
        $page->section = $validated['section'];
        $page->content = $validated['content'];
        $page->sort_order = $validated['sort_order'] ?? 0;
        $page->is_active = $request->boolean('is_active');

        if ($request->hasFile('featured_image')) {
            $page->featured_image = $request->file('featured_image')->store('pages', 'public');
        }

        $page->save();

        ActivityLog::log('update', "แก้ไขหน้าเพจ: {$page->title}", $page);

        return redirect()->route('admin.pages.index')
            ->with('success', 'แก้ไขหน้าเพจสำเร็จ');
    }

    public function destroy(Page $page)
    {
        ActivityLog::log('delete', "ลบหน้าเพจ: {$page->title}", $page);
        $page->delete();

        return redirect()->route('admin.pages.index')
            ->with('success', 'ลบหน้าเพจสำเร็จ');
    }

    public function uploadAttachment(Request $request, Page $page)
    {
        // Detect post_max_size drop (empty request body when content length is > 0)
        if (empty($request->all()) && isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
            return response()->json([
                'success' => false,
                'message' => 'ขนาดไฟล์รวมใหญ่เกินกว่าที่เซิร์ฟเวอร์รองรับ (เกินขีดจำกัดระบบ)'
            ], 422);
        }

        $validated = $request->validate([
            'attachment_file' => 'required|file|max:10240',
            'attachment_name' => 'required|string|max:500',
            'item_key' => 'required|string',
        ]);

        $file = $request->file('attachment_file');
        
        $name = $validated['attachment_name'];
        $key = $validated['item_key'];
        // Match the same item-boundary rule used by the public ITA page.
        $keyPattern = '/(?:^|\s)' . preg_quote($key, '/') . '(?!\d|\.)/u';
        if (!preg_match($keyPattern, trim($name))) {
            $name = $key . ' ' . $name;
        }

        // Add the parent MOIT label to newly uploaded display names.
        // Existing attachment names are left untouched.
        if (preg_match('/\bMOIT\s*([0-9]+)/iu', $page->title, $moitMatch)
            && !preg_match('/^MOIT\s*' . preg_quote($moitMatch[1], '/') . '\s*:/iu', trim($name))) {
            $name = 'MOIT ' . $moitMatch[1] . ' : ' . $name;
        }

        $attachments = $page->attachments ?? [];

        // ตรวจสอบชื่อไฟล์ซ้ำ
        foreach ($attachments as $att) {
            if ($att['name'] === $name) {
                return response()->json([
                    'success' => false,
                    'message' => 'มีไฟล์ชื่อนี้อยู่แล้วในระบบ กรุณาใช้ชื่ออื่น'
                ]);
            }
        }

        $path = $file->store('attachments', 'public');
        $attachments[] = [
            'name' => $name,
            'path' => $path,
            'size' => $file->getSize(),
        ];

        $page->update(['attachments' => $attachments]);

        ActivityLog::log('upload_attachment', "อัปโหลดเอกสารสำหรับข้อ {$key} ในหน้าเพจ: {$page->title}", $page);

        return response()->json([
            'success' => true,
            'message' => 'อัปโหลดเอกสารสำเร็จ',
            'attachments' => $attachments
        ]);
    }

    public function updateAttachment(Request $request, Page $page)
    {
        // Detect post_max_size drop (empty request body when content length is > 0)
        if (empty($request->all()) && isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
            return response()->json([
                'success' => false,
                'message' => 'ขนาดไฟล์รวมใหญ่เกินกว่าที่เซิร์ฟเวอร์รองรับ (เกินขีดจำกัดระบบ)'
            ], 422);
        }

        $validated = $request->validate([
            'attachment_index' => 'required|integer',
            'attachment_name' => 'required|string|max:500',
            'attachment_file' => 'nullable|file|max:10240',
        ]);

        $index = $validated['attachment_index'];
        $attachments = $page->attachments ?? [];
        $newName = $validated['attachment_name'];

        if (!isset($attachments[$index])) {
            return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูลเอกสาร'], 404);
        }

        // ตรวจสอบชื่อไฟล์ซ้ำ (ยกเว้นตัวเอง)
        foreach ($attachments as $i => $att) {
            if ($i !== (int)$index && $att['name'] === $newName) {
                return response()->json([
                    'success' => false,
                    'message' => 'มีไฟล์ชื่อนี้อยู่แล้วในระบบ กรุณาใช้ชื่ออื่น'
                ]);
            }
        }

        $path = $attachments[$index]['path'];
        $size = $attachments[$index]['size'];

        if ($request->hasFile('attachment_file')) {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
            }
            $file = $request->file('attachment_file');
            $path = $file->store('attachments', 'public');
            $size = $file->getSize();
        }

        $attachments[$index] = [
            'name' => $newName,
            'path' => $path,
            'size' => $size,
        ];

        $page->update(['attachments' => $attachments]);

        ActivityLog::log('update_attachment', "แก้ไขเอกสารแนบในหน้าเพจ: {$page->title}", $page);

        return response()->json([
            'success' => true,
            'message' => 'แก้ไขเอกสารสำเร็จ',
            'attachments' => $attachments
        ]);
    }

    public function deleteAttachment(Request $request, Page $page)
    {
        $validated = $request->validate([
            // attachment_path is stable when another attachment is removed first.
            'attachment_path' => 'nullable|string|max:1000',
            // Keep accepting the old index payload for backwards compatibility.
            'attachment_index' => 'nullable|integer|min:0',
        ]);

        $attachments = $page->attachments ?? [];

        if (isset($validated['attachment_path'])) {
            $index = collect($attachments)->search(function ($attachment) use ($validated) {
                return is_array($attachment)
                    && isset($attachment['path'])
                    && hash_equals((string) $attachment['path'], (string) $validated['attachment_path']);
            });
            if ($index === false) {
                return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูลเอกสาร'], 404);
            }
        } else {
            if (!isset($validated['attachment_index'])) {
                return response()->json(['success' => false, 'message' => 'ข้อมูลเอกสารไม่ครบถ้วน'], 422);
            }
            $index = $validated['attachment_index'];
        }

        if (!isset($attachments[$index])) {
            return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูลเอกสาร'], 404);
        }

        $path = $attachments[$index]['path'];
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
        }

        unset($attachments[$index]);
        $attachments = array_values($attachments);

        $page->update(['attachments' => $attachments]);

        ActivityLog::log('delete_attachment', "ลบเอกสารแนบในหน้าเพจ: {$page->title}", $page);

        return response()->json([
            'success' => true,
            'message' => 'ลบเอกสารสำเร็จ',
            'attachments' => $attachments
        ]);
    }
}
