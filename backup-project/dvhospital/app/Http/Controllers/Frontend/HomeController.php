<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Banner;
use App\Models\Doctor;
use App\Models\Event;
use App\Models\Page;
use App\Models\Executive;
use App\Models\Popup;

class HomeController extends Controller
{
    public function index()
    {
        $banners = Banner::active()->orderBy('sort_order')->get();
        $popups = Popup::active()->orderBy('sort_order')->get();

        $latestNews = Post::news()->published()->notExpired()
            ->latest('published_at')->limit(6)->get();

        $latestJobs = Post::jobs()->published()->notExpired()
            ->latest('published_at')->limit(4)->get();

        $latestProcurement = Post::procurement()->published()->notExpired()
            ->latest('published_at')->limit(4)->get();

        $latestActivities = Post::activities()->published()
            ->latest('published_at')->limit(4)->get();

        $upcomingEvents = Event::upcoming()->limit(5)->get();

        $doctorSchedules = Doctor::active()->with(['schedules' => function ($q) {
            $q->where('is_active', true);
        }])->get();

        return view('frontend.pages.home', compact(
            'banners', 'popups', 'latestNews', 'latestJobs', 'latestProcurement',
            'latestActivities', 'upcomingEvents', 'doctorSchedules'
        ));
    }

    public function about()
    {
        $pages = Page::section('about')->active()->orderBy('sort_order')->get();
        $executives = Executive::active()->get();
        return view('frontend.pages.about', compact('pages', 'executives'));
    }

    public function services()
    {
        $pages = Page::section('services')->active()->orderBy('sort_order')->get();
        return view('frontend.pages.services', compact('pages'));
    }

    public function contact()
    {
        return view('frontend.pages.contact');
    }

    public function ita(Request $request)
    {
        $year = $request->get('year', '2569');
        $section = ($year == '2569') ? 'ita' : "ita_{$year}";

        $pages = Page::section($section)->active()->orderBy('sort_order')->get();
        $itaProcurement = Post::procurement()->published()->latest('published_at')->paginate(20);
        return view('frontend.pages.ita', compact('pages', 'itaProcurement', 'year'));
    }

    public function ethics()
    {
        $pages = Page::section('ethics')->active()->orderBy('sort_order')->get();
        return view('frontend.pages.ethics', compact('pages'));
    }

    public function doctorSchedule()
    {
        $doctors = Doctor::active()->with(['schedules' => fn($q) => $q->where('is_active', true)])
            ->get();
        return view('frontend.pages.doctor-schedule', compact('doctors'));
    }

    // ===== Blog Listings =====

    public function news()
    {
        $posts = Post::news()->published()->notExpired()
            ->latest('published_at')->paginate(12);
        return view('frontend.blog.list', [
            'posts' => $posts,
            'title' => 'ข่าวประชาสัมพันธ์',
            'type' => 'news',
        ]);
    }

    public function jobs()
    {
        $posts = Post::jobs()->published()->notExpired()
            ->latest('published_at')->paginate(12);
        return view('frontend.blog.list', [
            'posts' => $posts,
            'title' => 'ประกาศรับสมัครงาน',
            'type' => 'job',
        ]);
    }

    public function procurement()
    {
        $posts = Post::procurement()->published()->notExpired()
            ->latest('published_at')->paginate(12);
        return view('frontend.blog.list', [
            'posts' => $posts,
            'title' => 'ประกาศจัดซื้อจัดจ้าง',
            'type' => 'procurement',
        ]);
    }

    public function activities()
    {
        $posts = Post::activities()->published()
            ->latest('published_at')->paginate(12);
        return view('frontend.blog.list', [
            'posts' => $posts,
            'title' => 'กิจกรรมหน่วยงาน',
            'type' => 'activity',
        ]);
    }

    public function showPost(string $slug)
    {
        $post = Post::where('slug', $slug)->published()->firstOrFail();
        $post->incrementViewCount();

        $relatedPosts = Post::ofType($post->type)->published()
            ->where('id', '!=', $post->id)
            ->latest('published_at')
            ->limit(4)->get();

        return view('frontend.blog.show', compact('post', 'relatedPosts'));
    }

    public function showPage(string $slug)
    {
        $page = Page::where('slug', $slug)->active()->firstOrFail();
        return view('frontend.pages.static', compact('page'));
    }

    public function calendar()
    {
        return view('frontend.pages.calendar');
    }

    public function ncd()
    {
        return view('frontend.pages.ncd');
    }

    public function calendarData()
    {
        $events = Event::all()->map(fn($e) => [
            'id' => $e->id,
            'title' => $e->title,
            'start' => $e->start_date->toIso8601String(),
            'end' => $e->end_date?->toIso8601String(),
            'color' => $e->color,
            'allDay' => $e->is_all_day,
        ]);

        return response()->json($events);
    }
}
