@extends('frontend.layouts.app')
@section('title', $title)

@section('content')
<section class="py-5">
    <div class="container">
        <h2 class="section-title">{{ $title }}</h2>

        <div class="row">
            <div class="col-lg-10 mx-auto">
                @forelse($posts as $post)
                <a href="{{ route('post.show', $post->slug) }}" class="post-list-item">
                    <div class="post-list-date">
                        <span class="day">{{ $post->published_at?->format('d') }}</span>
                        <span class="month-year">{{ $post->published_at?->locale('th')->translatedFormat('M Y') }}</span>
                    </div>
                    <div class="post-list-content">
                        <h3 class="post-list-title">{{ $post->title }}</h3>
                        <div class="post-list-meta">
                            <i class="bi bi-clock me-1"></i> {{ $post->published_at?->diffForHumans() }}
                            <span class="mx-2">|</span>
                            <i class="bi bi-eye me-1"></i> {{ $post->view_count ?? 0 }} เข้าชม
                            @if($post->is_pinned)
                            <span class="badge bg-warning text-dark ms-2"><i class="bi bi-pin-angle"></i> ปักหมุด</span>
                            @endif
                        </div>
                    </div>
                    <div class="post-list-action d-none d-md-block">
                        <span class="btn btn-outline-primary btn-sm rounded-pill px-3">อ่านต่อ <i class="bi bi-arrow-right ms-1"></i></span>
                    </div>
                </a>
                @empty
                <div class="text-center py-5 text-muted bg-white rounded-4 shadow-sm">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-2">ไม่พบข้อมูล{{ $title }}ในขณะนี้</p>
                </div>
                @endforelse
            </div>
        </div>

        @if($posts->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $posts->links() }}
        </div>
        @endif
    </div>
</section>
@endsection
