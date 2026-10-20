@extends('admin.layouts.app')
@section('title', 'จัดการ Popup Banners')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แผงควบคุม</a></li>
<li class="breadcrumb-item active">จัดการ Popup Banners</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-window-stack me-2"></i>จัดการ Popup Banners</h4>
    <a href="{{ route('admin.popups.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>เพิ่ม Popup
    </a>
</div>

<div class="row g-3">
    @forelse($popups as $popup)
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <img src="{{ Storage::url($popup->image) }}" class="card-img-top" style="height: 180px; object-fit: cover;" alt="{{ $popup->title }}">
            <div class="card-body">
                <h6 class="card-title fw-bold">{{ $popup->title }}</h6>
                <div class="small text-muted mb-2">
                    ลำดับการแสดงผล: {{ $popup->sort_order }}
                </div>
                <div class="small mb-2">
                    สถานะ: <span class="badge {{ $popup->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $popup->is_active ? 'แสดงผล' : 'ปิดการแสดงผล' }}
                    </span>
                </div>
                @if($popup->start_date || $popup->end_date)
                <div class="small text-muted mb-2">
                    <i class="bi bi-calendar-range"></i> 
                    {{ $popup->start_date ? $popup->start_date->format('d/m/Y') : 'เริ่มทันที' }} 
                    ถึง 
                    {{ $popup->end_date ? $popup->end_date->format('d/m/Y') : 'ไม่มีกำหนดสิ้นสุด' }}
                </div>
                @endif
                @if($popup->link)
                <small class="text-muted d-block mb-2 text-truncate"><i class="bi bi-link-45deg"></i> {{ $popup->link }}</small>
                @endif
            </div>
            <div class="card-footer bg-white d-flex gap-2">
                <a href="{{ route('admin.popups.edit', $popup) }}" class="btn btn-outline-primary btn-sm flex-fill">
                    <i class="bi bi-pencil me-1"></i>แก้ไข
                </a>
                <form action="{{ route('admin.popups.destroy', $popup) }}" method="POST" class="flex-fill"
                      onsubmit="return confirm('ยืนยันการลบ Popup นี้?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-trash me-1"></i>ลบ</button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="text-center py-5 text-muted">
            <i class="bi bi-window-stack" style="font-size: 3rem;"></i>
            <p class="mt-2">ยังไม่มีแบนเนอร์ Popup ประชาสัมพันธ์ในขณะนี้</p>
            <a href="{{ route('admin.popups.create') }}" class="btn btn-outline-primary mt-2">
                <i class="bi bi-plus-lg me-1"></i>สร้าง Popup แบนเนอร์แรก
            </a>
        </div>
    </div>
    @endforelse
</div>

@if($popups->hasPages())
<div class="mt-4">{{ $popups->links() }}</div>
@endif
@endsection
