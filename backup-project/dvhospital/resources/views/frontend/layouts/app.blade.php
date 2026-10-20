<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="เว็บไซต์โรงพยาบาลเทพรัตนเวชชานุกูล เฉลิมพระเกียรติ ๖๐ พรรษา - โรงพยาบาลชุมชนระดับ F2 ขนาด 60 เตียง">
    <title>@yield('title', 'หน้าแรก') - {{ config('hospital.name') }}</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Sarabun & Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Styles -->
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">

    <style>
        #backToTopBtn {
            display: none;
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 999;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: var(--primary, #0d6efd);
            color: white;
            border: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            cursor: pointer;
        }
        #backToTopBtn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.3);
            background-color: var(--primary-dark, #0b5ed7);
            color: white;
        }
        
        /* Floating Facebook Widget */
        .floating-fb-widget {
            position: fixed;
            right: -300px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 1000;
            display: flex;
            transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: -2px 0 15px rgba(0,0,0,0.15);
            background: white;
            border-radius: 8px 0 0 8px;
        }
        .floating-fb-widget:hover {
            right: 0;
        }
        .fb-widget-tab {
            background: #1877F2;
            color: white;
            width: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 15px 0;
            border-radius: 8px 0 0 8px;
            cursor: pointer;
            font-weight: 600;
            letter-spacing: 2px;
            box-shadow: -2px 0 5px rgba(0,0,0,0.1);
        }
        .fb-widget-tab span {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            margin-top: 5px;
        }
        .fb-widget-content {
            width: 300px;
            height: 400px;
            background: white;
            overflow: hidden;
        }
        .floating-fb-mobile {
            position: fixed;
            right: 20px;
            bottom: 85px;
            z-index: 998;
            width: 45px;
            height: 45px;
            background-color: #1877F2;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            transition: transform 0.2s;
        }
        .floating-fb-mobile:hover {
            transform: scale(1.1);
            color: white;
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Top Bar -->
    <div class="top-bar d-none d-md-block" style="background: var(--primary-dark); color: white; padding: 5px 0; font-size: 0.85rem;">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-telephone me-1"></i> โทร: {{ config('hospital.phone') }}
                <span class="mx-2">|</span>
                <i class="bi bi-envelope me-1"></i> {{ config('hospital.email') }}
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="font-size-controls d-flex align-items-center me-2">
                    <span class="me-2 opacity-75">ขนาดตัวอักษร:</span>
                    <button class="font-ctrl" onclick="changeFontSize(-1)">ก-</button>
                    <button class="font-ctrl" onclick="changeFontSize(0)">ก</button>
                    <button class="font-ctrl" onclick="changeFontSize(1)">ก+</button>
                </div>
                <div class="auth-controls d-flex align-items-center gap-2 border-start ps-3">
                    @auth
                        <div class="dropdown">
                            <a href="#" class="text-white text-decoration-none dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i> {{ auth()->user()->name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i> แผงควบคุม</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i> ออกจากระบบ</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="text-white text-decoration-none btn-login-top">
                            <i class="bi bi-lock-fill me-1"></i> เข้าสู่ระบบ
                        </a>
                    @endauth
                </div>
                <div class="social-links d-flex gap-2 ms-2 border-start ps-3">
                    <a href="{{ config('hospital.facebook') }}" target="_blank" class="text-white"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-white"><i class="bi bi-line"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Header & Nav -->
    <header class="main-header">
        <div class="container">
            <nav class="navbar navbar-expand-lg">
                <a href="{{ route('home') }}" class="navbar-brand d-flex align-items-center">
                    <div class="me-2" style="background: white; border-radius: 50%; padding: 4px; display: flex; align-items: center; justify-content: center; width: 50px; height: 50px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                        <img src="{{ asset('images/logo.png') }}" alt="MOPH Logo" style="height: 40px; width: auto;">
                    </div>
                    <div class="hospital-name">
                        <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 1.2rem; line-height: 1.1; color: var(--primary-dark);">
                            โรงพยาบาลเทพรัตนเวชชานุกูล เฉลิมพระเกียรติ ๖๐ พรรษา
                        </div>
                        <small style="font-size: 0.75rem; color: #666; font-weight: 400;">Smart Hospital | ระดับ F2 60 เตียง</small>
                    </div>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav ms-auto me-3">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">หน้าแรก</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">รู้จักองค์กร</a>
                            <ul class="dropdown-menu border-0 shadow-sm">
                                <li><a class="dropdown-item" href="{{ route('about') }}">ประวัติโรงพยาบาล</a></li>
                                <li><a class="dropdown-item" href="{{ route('about') }}#executives">ผู้บริหาร</a></li>
                                <li><a class="dropdown-item" href="{{ asset('files/org-structure.pdf') }}" target="_blank">โครงสร้างองค์กร</a></li>
                                <li><a class="dropdown-item" href="{{ asset('files/staff-chart.pdf') }}" target="_blank">ผังบุคลากร</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item {{ request()->routeIs('ethics') ? 'active' : '' }}" href="{{ route('ethics') }}"><i class="bi bi-heart-fill text-danger me-1"></i> ชมรมจริยธรรม</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">บริการ</a>
                            <ul class="dropdown-menu border-0 shadow-sm">
                                <li><a class="dropdown-item" href="{{ route('services') }}">บริการของเรา</a></li>
                                <li><a class="dropdown-item" href="{{ route('doctor-schedule') }}">ตารางแพทย์</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">ข่าวสาร</a>
                            <ul class="dropdown-menu border-0 shadow-sm">
                                <li><a class="dropdown-item" href="{{ route('news.index') }}">ข่าวประชาสัมพันธ์</a></li>
                                <li><a class="dropdown-item" href="{{ route('jobs.index') }}">รับสมัครงาน</a></li>
                                <li><a class="dropdown-item" href="{{ route('procurement.index') }}">จัดซื้อจัดจ้าง</a></li>
                                <li><a class="dropdown-item" href="{{ route('activities.index') }}">กิจกรรม</a></li>
                            </ul>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('ita') }}">ITA</a></li>

                        <li class="nav-item"><a class="nav-link" href="{{ route('contact') }}">ติดต่อเรา</a></li>
                    </ul>
                    <a href="tel:1669" class="btn btn-emergency d-flex align-items-center">
                        <i class="bi bi-telephone-fill me-2"></i>ฉุกเฉิน 1669
                    </a>
                </div>
            </nav>
        </div>
    </header>


    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5><i class="bi bi-hospital me-2"></i>{{ config('hospital.name') }}</h5>
                    <p>{{ config('hospital.type') }} ระดับ {{ config('hospital.level') }} ขนาด {{ config('hospital.beds') }} เตียง</p>
                    <p><i class="bi bi-geo-alt me-2"></i>{{ config('hospital.address') }}</p>
                </div>
                <div class="col-lg-4">
                    <h5>ติดต่อเรา</h5>
                    <p><i class="bi bi-telephone me-2"></i>{{ config('hospital.phone') }}</p>
                    <p><i class="bi bi-telephone-fill me-2 text-danger"></i>ฉุกเฉิน: {{ config('hospital.emergency') }}</p>
                    <p><i class="bi bi-envelope me-2"></i>{{ config('hospital.email') }}</p>
                    <div class="d-flex gap-3 mt-2">
                        <a href="{{ config('hospital.facebook') }}" target="_blank" class="fs-4"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="fs-4"><i class="bi bi-line"></i></a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <h5>ลิงก์ด่วน</h5>
                    <ul class="list-unstyled">
                        <li class="mb-1"><a href="{{ route('doctor-schedule') }}"><i class="bi bi-chevron-right me-1"></i>ตารางแพทย์ออกตรวจ</a></li>
                        <li class="mb-1"><a href="{{ asset('files/org-structure.pdf') }}" target="_blank"><i class="bi bi-chevron-right me-1"></i>โครงสร้างองค์กร</a></li>
                        <li class="mb-1"><a href="{{ route('services') }}"><i class="bi bi-chevron-right me-1"></i>บริการของเรา</a></li>
                        <li class="mb-1"><a href="{{ route('news.index') }}"><i class="bi bi-chevron-right me-1"></i>ข่าวประชาสัมพันธ์</a></li>
                        <li class="mb-1"><a href="{{ asset('files/staff-chart.pdf') }}" target="_blank"><i class="bi bi-chevron-right me-1"></i>ผังบุคลากร</a></li>
                        <li class="mb-1"><a href="{{ route('ita') }}"><i class="bi bi-chevron-right me-1"></i>ITA</a></li>
                        <li class="mb-1"><a href="{{ route('contact') }}"><i class="bi bi-chevron-right me-1"></i>ติดต่อเรา</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center align-items-md-end">
                <div class="mb-4 mb-md-0 text-center text-md-start">
                    <p class="mb-1">&copy; {{ date('Y') }} {{ config('hospital.name') }} | สงวนลิขสิทธิ์</p>
                    <p class="mb-0">
                        <a href="#" class="me-3">นโยบายความเป็นส่วนตัว (PDPA)</a>
                        <a href="#">นโยบายคุกกี้</a>
                    </p>
                </div>
                <div class="text-center text-md-end" style="font-size: 0.85rem;">
                    <div class="d-inline-block text-start p-3 rounded" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255,255,255,0.1);">
                        <p class="mb-1 fw-bold text-warning">ออกแบบและพัฒนาเว็บไซต์โดย</p>
                        <p class="mb-1 text-light">นายศุภชัย นะที <span style="opacity: 0.7;">(กลุ่มงานสุขภาพดิจิทัล)</span></p>
                        <p class="mb-0 text-light" style="opacity: 0.9;">
                            <i class="bi bi-envelope-fill me-1"></i> natee.krapong@gmail.com <br class="d-md-none">
                            <i class="bi bi-telephone-fill ms-md-2 me-1"></i> 053-485371-16 ต่อ 119
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Cookie Consent -->
    <div class="cookie-consent" id="cookieConsent">
        <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
            <p class="mb-0 small">
                <i class="bi bi-shield-lock me-1"></i>
                เว็บไซต์นี้ใช้คุกกี้เพื่อปรับปรุงประสบการณ์การใช้งาน
                <a href="#" class="text-warning">อ่านนโยบาย</a>
            </p>
            <div>
                <button class="btn btn-sm btn-outline-light me-1" onclick="declineCookies()">ปฏิเสธ</button>
                <button class="btn btn-sm btn-warning" onclick="acceptCookies()">ยอมรับ</button>
            </div>
        </div>
    </div>

    <!-- Floating Facebook Widget (Desktop) -->
    <div class="floating-fb-widget d-none d-lg-flex">
        <div class="fb-widget-tab">
            <i class="bi bi-facebook fs-5"></i>
            <span>Facebook</span>
        </div>
        <div class="fb-widget-content">
            <iframe src="https://www.facebook.com/plugins/page.php?href=https%3A%2F%2Fwww.facebook.com%2Fprofile.php%3Fid%3D100064373484553&tabs=timeline&width=300&height=400&small_header=true&adapt_container_width=true&hide_cover=false&show_facepile=true&appId" width="300" height="400" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowfullscreen="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"></iframe>
        </div>
    </div>

    <!-- Floating Facebook Icon (Mobile) -->
    <a href="https://www.facebook.com/profile.php?id=100064373484553" target="_blank" class="floating-fb-mobile d-lg-none">
        <i class="bi bi-facebook"></i>
    </a>

    <!-- Back to Top Button -->
    <button onclick="scrollToTop()" id="backToTopBtn" title="กลับไปด้านบนสุด">
        <i class="bi bi-arrow-up"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Font Size Accessibility
        let currentFontSize = 0;
        function changeFontSize(direction) {
            if (direction === 0) { currentFontSize = 0; }
            else { currentFontSize = Math.max(-2, Math.min(4, currentFontSize + direction)); }
            document.body.style.fontSize = (16 + currentFontSize * 2) + 'px';
        }

        // Cookie Consent
        if (!localStorage.getItem('cookieConsent')) {
            document.getElementById('cookieConsent').style.display = 'block';
        }
        function acceptCookies() {
            localStorage.setItem('cookieConsent', 'accepted');
            document.getElementById('cookieConsent').style.display = 'none';
        }
        function declineCookies() {
            localStorage.setItem('cookieConsent', 'declined');
            document.getElementById('cookieConsent').style.display = 'none';
        }

        // Back to Top functionality
        const backToTopBtn = document.getElementById("backToTopBtn");
        window.addEventListener('scroll', function() {
            if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                backToTopBtn.style.display = "flex";
            } else {
                backToTopBtn.style.display = "none";
            }
        });
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
