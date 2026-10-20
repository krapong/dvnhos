@extends('frontend.layouts.app')
@section('title', 'ติดต่อเรา')

@section('content')
<section class="py-5">
    <div class="container">
        <h2 class="section-title">ติดต่อเรา</h2>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-primary mb-4">{{ config('hospital.name') }}</h5>

                        <div class="mb-3">
                            <h6 class="fw-bold"><i class="bi bi-geo-alt-fill text-primary me-2"></i>ที่อยู่</h6>
                            <p class="ms-4">{{ config('hospital.address') }}</p>
                        </div>

                        <div class="mb-3">
                            <h6 class="fw-bold"><i class="bi bi-telephone-fill text-primary me-2"></i>โทรศัพท์</h6>
                            <p class="ms-4">
                                ทั่วไป: <a href="tel:{{ config('hospital.phone') }}">{{ config('hospital.phone') }}</a><br>
                                <span class="text-danger fw-bold">
                                    ฉุกเฉิน: <a href="tel:1669" class="text-danger">{{ config('hospital.emergency') }}</a>
                                </span>
                            </p>
                        </div>

                        <div class="mb-3">
                            <h6 class="fw-bold"><i class="bi bi-envelope-fill text-primary me-2"></i>อีเมล</h6>
                            <p class="ms-4"><a href="mailto:{{ config('hospital.email') }}">{{ config('hospital.email') }}</a></p>
                        </div>

                        <div class="mb-3">
                            <h6 class="fw-bold"><i class="bi bi-clock-fill text-primary me-2"></i>เวลาทำการ</h6>
                            <div class="ms-4">
                                <p class="mb-1">OPD: จันทร์-ศุกร์ 08:00-16:00 น.</p>
                                <p class="mb-1 text-danger fw-bold">ER: เปิด 24 ชั่วโมง</p>
                            </div>
                        </div>

                        <hr>

                        <h6 class="fw-bold mb-3">ช่องทางโซเชียลมีเดีย</h6>
                        <div class="d-flex gap-3">
                            <a href="{{ config('hospital.facebook') }}" target="_blank" class="btn btn-primary">
                                <i class="bi bi-facebook me-1"></i> Facebook
                            </a>
                            <a href="#" class="btn btn-success">
                                <i class="bi bi-line me-1"></i> LINE: {{ config('hospital.line') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <!-- Google Maps -->
                <div class="card h-100">
                    <div class="card-body p-0">
                        <iframe
                            src="https://www.google.com/maps?q={{ config('hospital.lat') }},{{ config('hospital.lng') }}&z=15&output=embed"
                            width="100%" height="100%" style="border:0; min-height: 400px; border-radius: 12px;"
                            allowfullscreen="" loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
