@extends('admin.layouts.app')

@section('title', 'แผงควบคุม')

@section('breadcrumb')
<li class="breadcrumb-item active">แผงควบคุม</li>
@endsection

@section('content')
<h4 class="mb-4">
    <i class="bi bi-speedometer2 me-2"></i>แผงควบคุม
    <small class="text-muted fs-6">สวัสดี, {{ auth()->user()->name }}</small>
</h4>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    @can('manage_news')
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number">{{ number_format($stats['total_news']) }}</div>
                    <div class="stat-label">ข่าวประชาสัมพันธ์</div>
                </div>
                <div class="stat-icon"><i class="bi bi-newspaper"></i></div>
            </div>
        </div>
    </div>
    @endcan

    @can('manage_jobs')
    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-left-color: #1976D2;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number" style="color: #1976D2;">{{ number_format($stats['total_jobs']) }}</div>
                    <div class="stat-label">ประกาศรับสมัครงาน</div>
                </div>
                <div class="stat-icon" style="color: #1976D2;"><i class="bi bi-person-badge"></i></div>
            </div>
        </div>
    </div>
    @endcan

    @can('manage_procurement')
    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-left-color: #FF6F00;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number" style="color: #FF6F00;">{{ number_format($stats['total_procurement']) }}</div>
                    <div class="stat-label">จัดซื้อจัดจ้าง</div>
                </div>
                <div class="stat-icon" style="color: #FF6F00;"><i class="bi bi-cart-check"></i></div>
            </div>
        </div>
    </div>
    @endcan

    @can('manage_activities')
    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-left-color: #7B1FA2;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number" style="color: #7B1FA2;">{{ number_format($stats['total_activities']) }}</div>
                    <div class="stat-label">กิจกรรม</div>
                </div>
                <div class="stat-icon" style="color: #7B1FA2;"><i class="bi bi-camera"></i></div>
            </div>
        </div>
    </div>
    @endcan
</div>

<!-- Recent Posts & Upcoming Events -->
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-2"></i>โพสต์ล่าสุด</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>หัวข้อ</th>
                                <th>ประเภท</th>
                                <th>สถานะ</th>
                                <th>โดย</th>
                                <th>วันที่</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPosts as $post)
                            <tr>
                                <td>
                                    <span class="fw-medium">{{ Str::limit($post->title, 50) }}</span>
                                </td>
                                <td><span class="badge bg-secondary">{{ $post->type_label }}</span></td>
                                <td>
                                    <span class="badge badge-status-{{ $post->status }}">{{ $post->status_label }}</span>
                                </td>
                                <td class="text-muted small">{{ $post->user?->name }}</td>
                                <td class="text-muted small">{{ $post->created_at->diffForHumans() }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีโพสต์</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-calendar-event me-2"></i>กิจกรรมที่กำลังจะมาถึง
            </div>
            <div class="card-body">
                @forelse($upcomingEvents as $event)
                <div class="d-flex mb-3 pb-3 border-bottom">
                    <div class="text-center me-3" style="min-width: 50px;">
                        <div class="fw-bold text-primary" style="font-size: 1.5rem;">{{ $event->start_date->format('d') }}</div>
                        <div class="small text-muted">{{ $event->start_date->locale('th')->translatedFormat('M') }}</div>
                    </div>
                    <div>
                        <div class="fw-medium">{{ $event->title }}</div>
                        @if($event->location)
                        <small class="text-muted"><i class="bi bi-geo-alt"></i> {{ $event->location }}</small>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-muted text-center mb-0">ไม่มีกิจกรรมที่กำลังจะมาถึง</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
