<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Category;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostController extends Controller
{
    protected string $type;
    protected string $permissionPrefix;
    protected string $routePrefix;
    protected string $viewPrefix;
    protected string $typeLabel;

    public function __construct()
    {
        // กำหนดค่าตาม route prefix
        $this->middleware(function ($request, $next) {
            $routeName = $request->route()->getName();

            if (str_contains($routeName, 'news')) {
                $this->type = 'news';
                $this->permissionPrefix = 'news';
                $this->routePrefix = 'admin.news';
                $this->viewPrefix = 'admin.news';
                $this->typeLabel = 'ข่าวประชาสัมพันธ์';
            } elseif (str_contains($routeName, 'jobs')) {
                $this->type = 'job';
                $this->permissionPrefix = 'job';
                $this->routePrefix = 'admin.jobs';
                $this->viewPrefix = 'admin.jobs';
                $this->typeLabel = 'ประกาศรับสมัครงาน';
            } elseif (str_contains($routeName, 'procurement')) {
                $this->type = 'procurement';
                $this->permissionPrefix = 'procurement';
                $this->routePrefix = 'admin.procurement';
                $this->viewPrefix = 'admin.procurement';
                $this->typeLabel = 'จัดซื้อจัดจ้าง';
            } elseif (str_contains($routeName, 'activities')) {
                $this->type = 'activity';
                $this->permissionPrefix = 'activity';
                $this->routePrefix = 'admin.activities';
                $this->viewPrefix = 'admin.activities';
                $this->typeLabel = 'กิจกรรม';
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = Post::ofType($this->type)->with(['user', 'category']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sorting
        switch ($request->get('sort', 'latest')) {
            case 'oldest':
                $query->oldest('published_at');
                break;
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            case 'views':
                $query->orderByDesc('view_count');
                break;
            case 'pinned':
                $query->orderByDesc('is_pinned')->latest('published_at');
                break;
            default: // latest
                $query->latest('published_at');
                break;
        }

        $posts = $query->paginate(20);

        return view('admin.posts.index', [
            'posts' => $posts,
            'type' => $this->type,
            'typeLabel' => $this->typeLabel,
            'routePrefix' => $this->routePrefix,
        ]);
    }

    public function create()
    {
        $categories = Category::ofType($this->type)->active()->get();

        return view('admin.posts.create', [
            'categories' => $categories,
            'type' => $this->type,
            'typeLabel' => $this->typeLabel,
            'routePrefix' => $this->routePrefix,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:1000',
            'category_id' => 'nullable|exists:categories,id',
            'featured_image' => 'nullable|image|max:5120',
            'attachments.*' => 'nullable|file|max:10240',
            'status' => 'required|in:draft,published',
            'published_at' => 'nullable|date',
            'expired_at' => 'nullable|date|after:published_at',
            'is_pinned' => 'boolean',
            'sort_order' => 'nullable|integer',
            'meta' => 'nullable|array',
            'gallery_images.*' => 'nullable|image|max:5120',
        ]);

        // รองรับภาษาไทยใน URL
        $slug = preg_replace('/\s+/u', '-', trim($validated['title']));
        $slug = preg_replace('/[^A-Za-z0-9ก-๙\-]/u', '', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        
        if (empty($slug)) {
            $slug = 'post';
        }

        $slug = Post::where('slug', $slug)->exists()
            ? $slug . '-' . time()
            : $slug;

        $post = new Post();
        $post->title = $validated['title'];
        $post->slug = $slug;
        $post->type = $this->type;
        $post->user_id = auth()->id();
        $post->content = $validated['content'];
        $post->excerpt = $validated['excerpt'] ?? null;
        $post->category_id = $validated['category_id'] ?? null;
        $post->status = $validated['status'];
        $post->published_at = $validated['status'] === 'published'
            ? ($validated['published_at'] ?? now())
            : null;
        $post->expired_at = $validated['expired_at'] ?? null;
        $post->is_pinned = $request->boolean('is_pinned');
        $post->sort_order = $validated['sort_order'] ?? 99;
        $post->meta = $validated['meta'] ?? null;

        // จัดการรูปภาพแกลลอรี่
        if ($request->hasFile('gallery_images')) {
            $gallery = [];
            foreach ($request->file('gallery_images') as $image) {
                $gallery[] = $image->store('posts/gallery', 'public');
            }
            $post->gallery = $gallery;
        }

        // จัดการรูปภาพหลัก
        if ($request->hasFile('featured_image')) {
            $post->featured_image = $request->file('featured_image')
                ->store('posts', 'public');
        }

        // จัดการไฟล์แนบ
        if ($request->hasFile('attachments')) {
            $attachments = [];
            foreach ($request->file('attachments') as $file) {
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $file->store('attachments', 'public'),
                    'size' => $file->getSize(),
                ];
            }
            $post->attachments = $attachments;
        }

        $post->save();

        ActivityLog::log('create', "สร้าง{$this->typeLabel}: {$post->title}", $post);

        return redirect()->route("{$this->routePrefix}.index")
            ->with('success', "สร้าง{$this->typeLabel}สำเร็จ");
    }

    public function edit(Post $post)
    {
        $categories = Category::ofType($this->type)->active()->get();

        return view('admin.posts.edit', [
            'post' => $post,
            'categories' => $categories,
            'type' => $this->type,
            'typeLabel' => $this->typeLabel,
            'routePrefix' => $this->routePrefix,
        ]);
    }

    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:1000',
            'category_id' => 'nullable|exists:categories,id',
            'post_type' => 'nullable|in:news,job,procurement,activity',
            'featured_image' => 'nullable|image|max:5120',
            'attachments.*' => 'nullable|file|max:10240',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            'expired_at' => 'nullable|date',
            'is_pinned' => 'boolean',
            'sort_order' => 'nullable|integer',
            'meta' => 'nullable|array',
            'gallery_images.*' => 'nullable|image|max:5120',
            'remove_gallery' => 'nullable|array',
            'remove_attachments' => 'nullable|array',
        ]);

        $post->title = $validated['title'];
        $post->content = $validated['content'];
        $post->excerpt = $validated['excerpt'] ?? null;
        $post->category_id = $validated['category_id'] ?? null;

        if (!empty($validated['post_type']) && $validated['post_type'] !== $post->type) {
            $post->type = $validated['post_type'];
            $post->category_id = null; // Clear category since the group changed
        }
        $post->status = $validated['status'];
        $post->published_at = $validated['published_at'] ?? $post->published_at;
        $post->expired_at = $validated['expired_at'] ?? null;
        $post->is_pinned = $request->boolean('is_pinned');
        $post->sort_order = $validated['sort_order'] ?? 99;
        $post->meta = $validated['meta'] ?? $post->meta;

        // ลบรูปแกลลอรี่เดิมที่ถูกเลือก
        $currentGallery = $post->gallery ?? [];
        if ($request->has('remove_gallery')) {
            foreach ($request->remove_gallery as $removePath) {
                if (($key = array_search($removePath, $currentGallery)) !== false) {
                    unset($currentGallery[$key]);
                    // optionally delete the file from storage here: Storage::disk('public')->delete($removePath);
                }
            }
            $currentGallery = array_values($currentGallery); // reindex
        }

        // อัปโหลดรูปแกลลอรี่ใหม่
        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $image) {
                $currentGallery[] = $image->store('posts/gallery', 'public');
            }
        }
        $post->gallery = $currentGallery;

        if ($request->hasFile('featured_image')) {
            $post->featured_image = $request->file('featured_image')
                ->store('posts', 'public');
        }

        // จัดการไฟล์แนบ
        $currentAttachments = $post->attachments ?? [];
        if ($request->has('remove_attachments')) {
            $removeKeys = $request->remove_attachments;
            rsort($removeKeys); // Sort in descending order to avoid reindexing issues during unset
            foreach ($removeKeys as $index) {
                if (isset($currentAttachments[$index])) {
                    $path = $currentAttachments[$index]['path'];
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                    }
                    unset($currentAttachments[$index]);
                }
            }
            $currentAttachments = array_values($currentAttachments);
        }

        if ($request->hasFile('attachments')) {
            // หากมีการอัปโหลดไฟล์ใหม่ ให้ลบไฟล์แนบเดิมทั้งหมดทิ้ง เพื่อแทนที่
            foreach ($currentAttachments as $oldFile) {
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($oldFile['path'])) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldFile['path']);
                }
            }
            $currentAttachments = []; // ล้างอาเรย์ไฟล์เดิม

            foreach ($request->file('attachments') as $file) {
                $currentAttachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $file->store('attachments', 'public'),
                    'size' => $file->getSize(),
                ];
            }
        }
        $post->attachments = $currentAttachments;

        $post->save();

        ActivityLog::log('update', "แก้ไข{$this->typeLabel}: {$post->title}", $post);

        $redirectRoute = $this->routePrefix . '.index';
        if (!empty($validated['post_type']) && $validated['post_type'] !== $this->type) {
            $typeRoutes = [
                'news' => 'admin.news.index',
                'job' => 'admin.jobs.index',
                'procurement' => 'admin.procurement.index',
                'activity' => 'admin.activities.index',
            ];
            $redirectRoute = $typeRoutes[$validated['post_type']] ?? $redirectRoute;
        }

        return redirect()->route($redirectRoute)
            ->with('success', "แก้ไข{$this->typeLabel}สำเร็จ");
    }

    public function destroy(Post $post)
    {
        ActivityLog::log('delete', "ลบ{$this->typeLabel}: {$post->title}", $post);
        $post->delete();

        return redirect()->route("{$this->routePrefix}.index")
            ->with('success', "ลบ{$this->typeLabel}สำเร็จ");
    }
}
