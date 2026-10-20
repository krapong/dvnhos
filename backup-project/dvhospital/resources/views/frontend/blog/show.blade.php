@extends('frontend.layouts.app')
@section('title', $post->title)

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">หน้าแรก</a></li>
                        <li class="breadcrumb-item">
                            <a href="{{ route($post->type === 'news' ? 'news.index' : ($post->type === 'job' ? 'jobs.index' : ($post->type === 'procurement' ? 'procurement.index' : 'activities.index'))) }}">
                                {{ $post->type_label }}
                            </a>
                        </li>
                        <li class="breadcrumb-item active">{{ Str::limit($post->title, 40) }}</li>
                    </ol>
                </nav>

                <!-- Article -->
                <article class="card">
                    @if($post->featured_image)
                    <img src="{{ str_starts_with($post->featured_image, 'http') ? $post->featured_image : Storage::url($post->featured_image) }}" class="card-img-top" alt="{{ $post->title }}"
                         style="max-height: 400px; object-fit: cover;">
                    @endif
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <span class="badge bg-success">{{ $post->type_label }}</span>
                            @if($post->category)
                            <span class="badge bg-light text-dark">{{ $post->category->name }}</span>
                            @endif
                        </div>

                        <h1 style="font-size: 1.75rem; font-weight: 700; line-height: 1.4;">{{ $post->title }}</h1>

                        <div class="d-flex gap-3 text-muted small mb-4 pb-3 border-bottom">
                            <span><i class="bi bi-calendar3 me-1"></i>{{ $post->published_at?->format('d/m/Y H:i') }}</span>
                            <span><i class="bi bi-person me-1"></i>{{ $post->user?->name }}</span>
                            <span><i class="bi bi-eye me-1"></i>{{ number_format($post->view_count) }} ครั้ง</span>
                        </div>

                        <div class="post-content" style="font-size: 1.05rem; line-height: 1.8;">
                            {!! nl2br(e($post->content)) !!}
                        </div>

                        <!-- ภาพประมวลกิจกรรม (สำหรับกิจกรรมที่มีรูปภาพเพิ่มเติม) -->
                        <!-- ภาพประมวลกิจกรรม (สำหรับกิจกรรมที่มีรูปภาพเพิ่มเติม) -->
                        @if($post->type === 'activity' && $post->gallery && count($post->gallery) > 0)
                        <div class="mt-5 pt-4 border-top">
                            <h5 class="fw-bold mb-4 text-primary" style="border-left: 4px solid var(--primary); padding-left: 10px;">
                                <i class="bi bi-images me-2"></i>ภาพประมวลกิจกรรม ({{ count($post->gallery) }} ภาพ)
                            </h5>
                            <div class="row g-3">
                                @foreach($post->gallery as $img)
                                <div class="col-6 col-md-4">
                                    <div class="gallery-item position-relative overflow-hidden rounded-3 shadow-sm" style="cursor: pointer; aspect-ratio: 4/3;">
                                        <a href="{{ str_starts_with($img, 'http') ? $img : Storage::url($img) }}" target="_blank">
                                            <img src="{{ str_starts_with($img, 'http') ? $img : Storage::url($img) }}" alt="ภาพกิจกรรม" class="w-100 h-100" style="object-fit: cover; transition: transform 0.3s ease;">
                                            <div class="gallery-overlay position-absolute start-0 top-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-40 opacity-0" style="transition: opacity 0.3s ease;">
                                                <i class="bi bi-zoom-in text-white fs-3"></i>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <style>
                            .gallery-item:hover img {
                                transform: scale(1.06);
                            }
                            .gallery-item:hover .gallery-overlay {
                                opacity: 1 !important;
                            }
                        </style>
                        @endif

                        <!-- ไฟล์แนบ -->
                        @if($post->attachments && count($post->attachments) > 0)
                        <div class="mt-4 pt-3 border-top">
                            <h6><i class="bi bi-paperclip me-1"></i>ไฟล์แนบ</h6>
                            @foreach($post->attachments as $file)
                            <a href="{{ Storage::url($file['path']) }}" class="d-block mb-1" target="_blank">
                                <i class="bi bi-file-earmark-arrow-down me-1"></i>
                                {{ $file['name'] }}
                                <small class="text-muted">({{ number_format($file['size'] / 1024, 1) }} KB)</small>
                            </a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </article>

                <!-- บทความที่เกี่ยวข้อง -->
                @if($relatedPosts->isNotEmpty())
                <div class="mt-5">
                    <h5 class="fw-bold mb-3">บทความที่เกี่ยวข้อง</h5>
                    <div class="row g-3">
                        @foreach($relatedPosts as $related)
                        <div class="col-md-6">
                            <a href="{{ route('post.show', $related->slug) }}" class="text-decoration-none">
                                <div class="news-card">
                                    <div class="card-body">
                                        <small class="card-date"><i class="bi bi-calendar3"></i> {{ $related->published_at?->format('d/m/Y') }}</small>
                                        <h6 class="card-title text-dark mt-1">{{ Str::limit($related->title, 60) }}</h6>
                                    </div>
                                </div>
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
