@extends('frontend.layouts.app')
@section('title', 'บริการของเรา')

@section('content')
<section class="py-5">
    <div class="container">
        <h2 class="section-title">บริการของเรา</h2>
        <p class="text-muted mb-4">โรงพยาบาลชุมชนระดับ F2 ขนาด 60 เตียง ให้บริการระดับปฐมภูมิและทุติยภูมิเบื้องต้น</p>

        <!-- Department Quick Links -->
        <!-- Row 1 -->
        <div class="row g-4 justify-content-center mb-4">
            <div class="col-6 col-md-3">
                <a href="{{ route('page.show', 'dentistry') }}" class="glass-card d-block p-4 text-center text-decoration-none h-100">
                    <div class="mb-3 d-flex align-items-center justify-content-center rounded-circle mx-auto" style="width:70px;height:70px;background:linear-gradient(135deg,#cffafe,#a5f3fc);">
                        <i class="bi bi-capsule-pill fs-1" style="color:#0891b2;"></i>
                    </div>
                    <span class="d-block fw-bold text-dark">กลุ่มงานทันตกรรม</span>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="{{ route('page.show', 'rehabilitation') }}" class="glass-card d-block p-4 text-center text-decoration-none h-100">
                    <div class="mb-3 d-flex align-items-center justify-content-center rounded-circle mx-auto" style="width:70px;height:70px;background:linear-gradient(135deg,#ffedd5,#fed7aa);">
                        <i class="bi bi-person-walking fs-1" style="color:#ea580c;"></i>
                    </div>
                    <span class="d-block fw-bold text-dark">กลุ่มงานเวชศาสตร์ฟื้นฟู</span>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="{{ route('ncd') }}" class="glass-card d-block p-4 text-center text-decoration-none h-100">
                    <div class="mb-3 d-flex align-items-center justify-content-center rounded-circle mx-auto" style="width:70px;height:70px;background:linear-gradient(135deg,#fce7f3,#fbcfe8);">
                        <i class="bi bi-heart-pulse fs-1" style="color:#db2777;"></i>
                    </div>
                    <span class="d-block fw-bold text-dark">งานคลินิก NCD</span>
                    <small class="text-muted d-block mt-1">คลิกเพื่อดูคลินิกทั้งหมด</small>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="{{ route('page.show', 'primary-care') }}" class="glass-card d-block p-4 text-center text-decoration-none h-100">
                    <div class="mb-3 d-flex align-items-center justify-content-center rounded-circle mx-auto" style="width:70px;height:70px;background:linear-gradient(135deg,#dcfce7,#bbf7d0);">
                        <i class="bi bi-house-heart fs-1" style="color:#16a34a;"></i>
                    </div>
                    <span class="d-block fw-bold text-dark">กลุ่มงานปฐมภูมิครอบครัวและชุมชน</span>
                </a>
            </div>
        </div>

        <!-- Row 2 -->
        <div class="row g-4 justify-content-center mb-5">
            <div class="col-6 col-md-4 col-lg">
                <a href="{{ route('page.show', 'psychiatry') }}" class="glass-card d-block p-4 text-center text-decoration-none h-100">
                    <div class="mb-3 d-flex align-items-center justify-content-center rounded-circle mx-auto" style="width:70px;height:70px;background:linear-gradient(135deg,#ede9fe,#ddd6fe);">
                        <i class="bi bi-brain fs-1" style="color:#7c3aed;"></i>
                    </div>
                    <span class="d-block fw-bold text-dark">กลุ่มงานจิตเวชและยาเสพติด</span>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg">
                <a href="{{ route('page.show', 'emergency') }}" class="glass-card d-block p-4 text-center text-decoration-none h-100">
                    <div class="mb-3 d-flex align-items-center justify-content-center rounded-circle mx-auto" style="width:70px;height:70px;background:linear-gradient(135deg,#fee2e2,#fecaca);">
                        <i class="bi bi-activity fs-1" style="color:#dc2626;"></i>
                    </div>
                    <span class="d-block fw-bold text-dark">อุบัติเหตุและฉุกเฉิน</span>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg">
                <a href="{{ route('page.show', 'outpatient') }}" class="glass-card d-block p-4 text-center text-decoration-none h-100">
                    <div class="mb-3 d-flex align-items-center justify-content-center rounded-circle mx-auto" style="width:70px;height:70px;background:linear-gradient(135deg,#e0f2fe,#bae6fd);">
                        <i class="bi bi-person-badge fs-1" style="color:#0284c7;"></i>
                    </div>
                    <span class="d-block fw-bold text-dark">ผู้ป่วยนอก</span>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg">
                <a href="{{ route('page.show', 'health-insurance') }}" class="glass-card d-block p-4 text-center text-decoration-none h-100">
                    <div class="mb-3 d-flex align-items-center justify-content-center rounded-circle mx-auto" style="width:70px;height:70px;background:linear-gradient(135deg,#fef3c7,#fde68a);">
                        <i class="bi bi-file-earmark-check fs-1" style="color:#d97706;"></i>
                    </div>
                    <span class="d-block fw-bold text-dark">งานประกันสุขภาพ</span>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg">
                <a href="{{ route('page.show', 'annual-health-checkup') }}" class="glass-card d-block p-4 text-center text-decoration-none h-100">
                    <div class="mb-3 d-flex align-items-center justify-content-center rounded-circle mx-auto" style="width:70px;height:70px;background:linear-gradient(135deg,#dcfce7,#bbf7d0);">
                        <i class="bi bi-clipboard-check fs-1" style="color:#16a34a;"></i>
                    </div>
                    <span class="d-block fw-bold text-dark">งานตรวจสุขภาพประจำปี</span>
                </a>
            </div>
        </div>

        <!-- เนื้อหาจาก Pages -->
        @foreach($pages as $page)
        <div class="card mb-4">
            <div class="card-body p-4">
                <h4 class="fw-bold text-primary mb-3">{{ $page->title }}</h4>
                <div style="line-height: 1.8;">{!! nl2br(e($page->content)) !!}</div>
            </div>
        </div>
        @endforeach

        <!-- ข้อมูลการส่งต่อ -->
        <div class="card border-warning mt-4">
            <div class="card-header bg-warning text-dark fw-bold">
                <i class="bi bi-arrow-left-right me-2"></i>ข้อมูลเครือข่ายการส่งต่อ (Referral)
            </div>
            <div class="card-body">
                <p>ในกรณีที่ผู้ป่วยต้องได้รับการรักษาเฉพาะทางที่เกินขีดความสามารถ โรงพยาบาลจะส่งต่อผู้ป่วยไปยังโรงพยาบาลแม่ข่ายในเครือข่าย ได้แก่ โรงพยาบาลจังหวัดและโรงพยาบาลศูนย์ที่เกี่ยวข้อง</p>
            </div>
        </div>
    </div>
</section>
@endsection
