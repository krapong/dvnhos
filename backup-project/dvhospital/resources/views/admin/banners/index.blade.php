@extends('admin.layouts.app')
@section('title', 'จัดการ Banner')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แผงควบคุม</a></li>
<li class="breadcrumb-item active">จัดการ Banner</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-images me-2"></i>จัดการ Banner</h4>
    <a href="{{ route('admin.banners.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>เพิ่ม Banner
    </a>
</div>

<div class="row g-3">
    @forelse($banners as $banner)
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <img src="{{ Storage::url($banner->image) }}" class="card-img-top" style="height: 180px; object-fit: cover;" alt="{{ $banner->title }}">
            <div class="card-body">
                <h6 class="card-title">{{ $banner->title }}</h6>
                <div class="small text-muted mb-2">
                    ลำดับ: {{ $banner->sort_order }} |
                    สถานะ: <span class="badge {{ $banner->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $banner->is_active ? 'แสดง' : 'ซ่อน' }}
                    </span>
                </div>
                @if($banner->link)
                <small class="text-muted d-block mb-2"><i class="bi bi-link-45deg"></i> {{ Str::limit($banner->link, 40) }}</small>
                @endif
            </div>
            <div class="card-footer bg-white d-flex gap-2">
                <a href="{{ route('admin.banners.edit', $banner) }}" class="btn btn-outline-primary btn-sm flex-fill">
                    <i class="bi bi-pencil me-1"></i>แก้ไข
                </a>
                <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" class="flex-fill"
                      onsubmit="return confirm('ยืนยันการลบ?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-trash me-1"></i>ลบ</button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="text-center py-5 text-muted">
            <i class="bi bi-images" style="font-size: 3rem;"></i>
            <p class="mt-2">ยังไม่มี Banner</p>
        </div>
    </div>
    @endforelse
</div>

@if($banners->hasPages())
<div class="mt-4">{{ $banners->links() }}</div>
@endif
@endsection
