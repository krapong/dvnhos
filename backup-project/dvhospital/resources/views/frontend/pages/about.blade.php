@extends('frontend.layouts.app')
@section('title', 'รู้จักองค์กร')

@section('content')
<!-- Hero Section -->
<section class="about-hero py-5 text-white" style="background: linear-gradient(rgba(30, 64, 175, 0.8), rgba(30, 64, 175, 0.8)), url('{{ asset('storage/banners/banner1.png') }}'); background-size: cover; background-position: center;">
    <div class="container py-5 text-center">
        <h1 class="display-4 fw-bold mb-3">รู้จักองค์กร</h1>
        <p class="lead">โรงพยาบาลเทพรัตนเวชชานุกูล เฉลิมพระเกียรติ ๖๐ พรรษา</p>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <!-- Quick Facts -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="glass-card p-4 text-center h-100 border-primary" style="border-left: 5px solid var(--primary-color);">
                    <i class="bi bi-hospital fs-1 text-primary mb-2"></i>
                    <h3 class="fw-bold">60</h3>
                    <p class="text-muted mb-0">จำนวนเตียง</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card p-4 text-center h-100 border-primary" style="border-left: 5px solid var(--primary-color);">
                    <i class="bi bi-people fs-1 text-primary mb-2"></i>
                    <h3 class="fw-bold">62,267</h3>
                    <p class="text-muted mb-0">ประชากรในพื้นที่</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card p-4 text-center h-100 border-primary" style="border-left: 5px solid var(--primary-color);">
                    <i class="bi bi-person-vcard fs-1 text-primary mb-2"></i>
                    <h3 class="fw-bold">140</h3>
                    <p class="text-muted mb-0">บุคลากรทั้งหมด</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card p-4 text-center h-100 border-primary" style="border-left: 5px solid var(--primary-color);">
                    <i class="bi bi-geo-alt fs-1 text-primary mb-2"></i>
                    <h3 class="fw-bold">แม่แจ่ม</h3>
                    <p class="text-muted mb-0">พื้นที่ให้บริการ</p>
                </div>
            </div>
        </div>

        <div class="row g-5">
            <div class="col-lg-8">
                @foreach($pages as $page)
                    @if($page->slug == 'history')
                    <div class="mb-5" id="history">
                        <h3 class="section-title text-primary"><i class="bi bi-clock-history me-2"></i>{{ $page->title }}</h3>
                        <div class="glass-card p-4 shadow-sm border-0">
                            <div class="content-text" style="line-height: 2; text-align: justify; font-size: 1.1rem;">
                                {!! nl2br(e($page->content)) !!}
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($page->slug == 'vision')
                    <div class="mb-5" id="vision">
                        <h3 class="section-title text-primary"><i class="bi bi-eye me-2"></i>{{ $page->title }}</h3>
                        <div class="p-4 rounded-4 text-white shadow-lg" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));">
                            <div class="content-text fs-5" style="line-height: 1.8;">
                                {!! nl2br(e($page->content)) !!}
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($page->slug == 'values')
                    <div class="mb-5" id="values">
                        <h3 class="section-title text-primary"><i class="bi bi-heart-fill me-2"></i>{{ $page->title }}</h3>
                        <div class="glass-card p-4 border-0 shadow-sm bg-white">
                            <div class="content-text" style="line-height: 1.8;">
                                {!! nl2br(e($page->content)) !!}
                            </div>
                        </div>
                    </div>
                    @endif

                    @if(!in_array($page->slug, ['history', 'vision', 'values']))
                    <div class="mb-5">
                        <h4 class="fw-bold text-primary mb-3">{{ $page->title }}</h4>
                        <div class="glass-card p-4 shadow-sm border-0">
                            <div class="content-text">
                                {!! nl2br(e($page->content)) !!}
                            </div>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>

            <div class="col-lg-4">
                <div class="sticky-top" style="top: 100px;">
                    <div class="glass-card p-4 mb-4 border-0 shadow-sm bg-white">
                        <h5 class="fw-bold border-bottom pb-2 mb-3">ค่านิยม DVH</h5>
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; min-width: 40px;">D</div>
                            <div><strong>Do the Best</strong><br><small class="text-muted">บริการดีที่สุด</small></div>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; min-width: 40px;">V</div>
                            <div><strong>Volunteer</strong><br><small class="text-muted">มีจิตอาสา เสียสละ</small></div>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; min-width: 40px;">H</div>
                            <div><strong>Hospitality</strong><br><small class="text-muted">โอบอ้อมอารี เป็นมิตร</small></div>
                        </div>
                    </div>

                    <div class="glass-card p-4 border-0 shadow-sm" style="background: var(--primary-light); border: 1px solid var(--primary-color) !important;">
                        <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>ข้อมูลเพิ่มเติม</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><a href="{{ asset('files/org-structure.pdf') }}" target="_blank" class="text-decoration-none text-dark"><i class="bi bi-file-pdf text-danger me-2"></i>โครงสร้างองค์กร</a></li>
                            <li class="mb-2"><a href="{{ asset('files/staff-chart.pdf') }}" target="_blank" class="text-decoration-none text-dark"><i class="bi bi-file-pdf text-danger me-2"></i>ผังบุคลากร</a></li>
                            <li><a href="{{ route('contact') }}" class="text-decoration-none text-dark"><i class="bi bi-map text-primary me-2"></i>แผนที่และการเดินทาง</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- ผู้บริหาร -->
        @if($executives->isNotEmpty())
        <div id="executives" class="mt-5 pt-5 border-top">
            <h3 class="section-title text-center text-primary mb-5">ทีมผู้บริหาร</h3>
            <div class="row g-4 justify-content-center">
                @foreach($executives as $exec)
                <div class="col-6 col-md-4 col-lg-3 text-center">
                    <div class="executive-card p-3 h-100">
                        <div class="glass-card p-4 border-0 shadow-sm hover-up h-100">
                            @if($exec->photo)
                            <img src="{{ Storage::url($exec->photo) }}" class="rounded-circle mb-3 border border-4 border-white shadow-sm"
                                 style="width: 140px; height: 140px; object-fit: cover;" alt="{{ $exec->name }}">
                            @else
                            <div class="rounded-circle bg-light mx-auto mb-3 d-flex align-items-center justify-content-center border border-4 border-white shadow-sm"
                                 style="width: 140px; height: 140px;">
                                <i class="bi bi-person-circle text-muted" style="font-size: 4rem;"></i>
                            </div>
                            @endif
                            <h5 class="fw-bold mb-1 text-primary">{{ $exec->name }}</h5>
                            <p class="text-muted small mb-0">{{ $exec->title }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</section>
@endsection
