<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use App\Models\Banner;
use App\Models\Event;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $stats = [
            'total_news' => Post::news()->count(),
            'total_jobs' => Post::jobs()->count(),
            'total_procurement' => Post::procurement()->count(),
            'total_activities' => Post::activities()->count(),
            'total_banners' => Banner::count(),
            'total_events' => Event::count(),
            'total_users' => User::count(),
        ];

        $recentPosts = Post::with('user')
            ->latest()
            ->limit(10)
            ->get();

        $upcomingEvents = Event::upcoming()->limit(5)->get();

        return view('admin.dashboard.index', compact('stats', 'recentPosts', 'upcomingEvents'));
    }
}
