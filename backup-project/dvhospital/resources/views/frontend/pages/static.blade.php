@extends('frontend.layouts.app')
@section('title', $page->title)

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">หน้าแรก</a></li>
                        <li class="breadcrumb-item active">{{ $page->title }}</li>
                    </ol>
                </nav>

                <div class="card">
                    @if($page->featured_image)
                    <img src="{{ Storage::url($page->featured_image) }}" class="card-img-top"
                         style="max-height: 400px; object-fit: cover;" alt="{{ $page->title }}">
                    @endif
                    <div class="card-body p-4">
                        <h1 style="font-size: 1.75rem; font-weight: 700;">{{ $page->title }}</h1>
                        <hr>
                        <div style="line-height: 1.8; font-size: 1.05rem;">
                            {!! nl2br(e($page->content)) !!}
                        </div>

                        @if($page->attachments && count($page->attachments) > 0)
                        <div class="mt-4 pt-3 border-top">
                            <h6><i class="bi bi-paperclip me-1"></i>เอกสารแนบ</h6>
                            @foreach($page->attachments as $file)
                            <a href="{{ Storage::url($file['path']) }}" class="btn btn-outline-success btn-sm me-2 mb-2" target="_blank">
                                <i class="bi bi-download me-1"></i>{{ $file['name'] }}
                            </a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
