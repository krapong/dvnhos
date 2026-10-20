<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'แผงควบคุม') - ระบบจัดการเว็บไซต์</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts - Sarabun (Thai) -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            /* Default Theme */
            --primary: #00695C;
            --primary-light: #439889;
            --primary-dark: #003D33;
            --secondary: #E8F5E9;
            --accent: #FF6F00;
            --sidebar-width: 260px;
            
            /* Role Specific Accent */
            --admin-theme: var(--primary);
        }

        /* Staff Role Colors */
        .role-staff-1 { --admin-theme: #10B981; } /* PR - Green */
        .role-staff-2 { --admin-theme: #0288D1; } /* Procurement - Blue */
        .role-staff-3 { --admin-theme: #F59E0B; } /* HR - Orange */
        .role-staff-4, .role-admin { --admin-theme: #6366F1; } /* Admin - Indigo */

        * { font-family: 'Sarabun', sans-serif; }

        body { 
            background: #f4f6f9; 
            color: #0F172A; /* High contrast text */
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, #1E293B 0%, #0F172A 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s;
            border-right: 5px solid var(--admin-theme); /* Role accent */
        }

        .sidebar-header {
            padding: 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            background: var(--admin-theme); /* Role accent */
        }

        .sidebar-header h5 {
            font-size: 0.85rem;
            font-weight: 600;
            margin: 0;
            line-height: 1.4;
        }

        .sidebar-nav { padding: 0.5rem 0; }

        .sidebar-nav .nav-section {
            padding: 0.75rem 1.25rem 0.25rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.5);
            font-weight: 600;
        }

        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            padding: 0.6rem 1.25rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--admin-theme);
        }

        .sidebar-nav .nav-link i { margin-right: 0.75rem; font-size: 1.1rem; width: 20px; text-align: center; }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .top-bar {
            background: white;
            padding: 0.75rem 1.5rem;
            border-bottom: 2px solid var(--admin-theme); /* Role accent */
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .content-area { padding: 1.5rem; }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border-left: 5px solid var(--admin-theme); /* Role accent */
            transition: transform 0.2s;
        }

        .stat-card .stat-icon { font-size: 2rem; color: var(--admin-theme); }
        .stat-card .stat-number { font-size: 1.75rem; font-weight: 700; color: #0F172A; }

        .card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .card-header { background: white; border-bottom: 1px solid #eee; font-weight: 600; }

        .btn-primary { background: var(--admin-theme); border-color: var(--admin-theme); }
        .btn-primary:hover { opacity: 0.9; background: var(--admin-theme); border-color: var(--admin-theme); }
        
        /* Emergency Red Lock */
        .btn-emergency-red { background-color: #D9383A !important; color: white !important; font-weight: 700; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }

        /* Status Badges */
        .badge-status-published {
            background-color: #059669;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.78rem;
            padding: 0.35em 0.65em;
        }
        .badge-status-draft {
            background-color: #D97706;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.78rem;
            padding: 0.35em 0.65em;
        }
        .badge-status-archived {
            background-color: #6B7280;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.78rem;
            padding: 0.35em 0.65em;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
    </style>
    @stack('styles')
</head>
<body class="role-{{ strtolower(str_replace(' ', '-', auth()->user()->primary_role)) }}">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-hospital" style="font-size: 1.5rem;"></i>
            <h5 class="mt-2">ระบบจัดการเว็บไซต์<br>รพ.เทพรัตนเวชชานุกูลฯ</h5>
        </div>

        <nav class="sidebar-nav">
            <!-- Dashboard -->
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> แผงควบคุม
            </a>

            @can('manage_news')
            <div class="nav-section">ประชาสัมพันธ์</div>
            <a href="{{ route('admin.news.index') }}" class="nav-link {{ request()->routeIs('admin.news.*') ? 'active' : '' }}">
                <i class="bi bi-newspaper"></i> ข่าวประชาสัมพันธ์
            </a>
            @endcan

            @can('manage_banners')
            <a href="{{ route('admin.banners.index') }}" class="nav-link {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}">
                <i class="bi bi-images"></i> จัดการ Banner
            </a>
            <a href="{{ route('admin.popups.index') }}" class="nav-link {{ request()->routeIs('admin.popups.*') ? 'active' : '' }}">
                <i class="bi bi-window-stack"></i> จัดการ Popup Banners
            </a>
            @endcan

            @can('manage_jobs')
            <a href="{{ route('admin.jobs.index') }}" class="nav-link {{ request()->routeIs('admin.jobs.*') ? 'active' : '' }}">
                <i class="bi bi-person-badge"></i> ประกาศรับสมัครงาน
            </a>
            @endcan

            @can('manage_procurement')
            <div class="nav-section">จัดซื้อจัดจ้าง</div>
            <a href="{{ route('admin.procurement.index') }}" class="nav-link {{ request()->routeIs('admin.procurement.*') ? 'active' : '' }}">
                <i class="bi bi-cart-check"></i> ประกาศจัดซื้อจัดจ้าง
            </a>
            @endcan

            @can('manage_ita')
            <div class="nav-section">ITA & จริยธรรม</div>
            <a href="{{ route('admin.pages.index', ['section' => 'ita']) }}" class="nav-link {{ request()->is('admin/pages*') && request()->get('section') == 'ita' ? 'active' : '' }}">
                <i class="bi bi-shield-check"></i> ITA
            </a>
            @endcan

            @can('manage_ethics')
            <a href="{{ route('admin.pages.index', ['section' => 'ethics']) }}" class="nav-link {{ request()->is('admin/pages*') && request()->get('section') == 'ethics' ? 'active' : '' }}">
                <i class="bi bi-heart"></i> ชมรมจริยธรรม
            </a>
            @endcan

            @can('manage_activities')
            <div class="nav-section">กิจกรรม</div>
            <a href="{{ route('admin.activities.index') }}" class="nav-link {{ request()->routeIs('admin.activities.*') ? 'active' : '' }}">
                <i class="bi bi-camera"></i> ข่าวกิจกรรม
            </a>
            @endcan

            @can('manage_events')
            <a href="{{ route('admin.events.index') }}" class="nav-link {{ request()->routeIs('admin.events.*') ? 'active' : '' }}">
                <i class="bi bi-calendar-event"></i> ปฏิทินกิจกรรม
            </a>
            @endcan

            @can('manage_doctors')
            <div class="nav-section">ข้อมูลทั่วไป</div>
            <a href="{{ route('admin.doctors.index') }}" class="nav-link {{ request()->routeIs('admin.doctors.*') ? 'active' : '' }}">
                <i class="bi bi-clipboard2-pulse"></i> ตารางแพทย์ออกตรวจ
            </a>
            @endcan

            @can('manage_pages')
            <a href="{{ route('admin.pages.index') }}" class="nav-link {{ request()->routeIs('admin.pages.*') && !request()->has('section') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text"></i> จัดการหน้าเพจ
            </a>
            @endcan

            @can('manage_sops')
            <a href="{{ route('admin.sops.index') }}" class="nav-link {{ request()->routeIs('admin.sops.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-pdf"></i> เอกสาร SOP
            </a>
            @endcan

            @can('manage_users')
            <div class="nav-section">ระบบ</div>
            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> จัดการผู้ใช้งาน
            </a>
            @endcan

            @can('view_logs')
            <a href="{{ route('admin.logs.index') }}" class="nav-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> ประวัติการใช้งาน
            </a>
            @endcan
        </nav>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <div class="d-flex align-items-center">
                <button class="btn btn-link d-md-none text-dark" id="sidebarToggle">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        @yield('breadcrumb')
                    </ol>
                </nav>
            </div>
            <div class="d-flex align-items-center">
                <a href="{{ route('home') }}" target="_blank" class="btn btn-outline-success btn-sm me-3">
                    <i class="bi bi-globe"></i> ดูเว็บไซต์
                </a>
                <div class="dropdown">
                    <button class="btn btn-link text-dark dropdown-toggle text-decoration-none" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        {{ auth()->user()->name }}
                        <span class="badge bg-success ms-1">{{ auth()->user()->primary_role }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small">{{ auth()->user()->email }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>ออกจากระบบ</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="content-area">
            <!-- Flash Messages -->
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>กรุณาตรวจสอบข้อมูล
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @yield('content')
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Sidebar Toggle for Mobile
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggle = document.getElementById('sidebarToggle');

        if (toggle) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                overlay.style.display = sidebar.classList.contains('show') ? 'block' : 'none';
            });
        }

        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('show');
                overlay.style.display = 'none';
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
