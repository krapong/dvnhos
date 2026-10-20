@extends('admin.layouts.app')
@section('title', 'แก้ไข Banner')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แผงควบคุม</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.banners.index') }}">จัดการ Banner</a></li>
<li class="breadcrumb-item active">แก้ไข</li>
@endsection

@section('content')
<h4 class="mb-4"><i class="bi bi-pencil me-2"></i>แก้ไข Banner</h4>

<div class="card" style="max-width: 700px;">
    <div class="card-body">
        <form action="{{ route('admin.banners.update', $banner) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-medium">ชื่อ Banner <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $banner->title) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-medium">รูปภาพ</label>
                @if($banner->image)
                <img src="{{ Storage::url($banner->image) }}" class="d-block mb-2 rounded" style="max-height: 150px;" alt="">
                @endif
                <input type="file" name="image" class="form-control" accept="image/*">
                <small class="text-muted">เว้นว่างถ้าไม่ต้องการเปลี่ยนรูป</small>
            </div>
            <div class="mb-3">
                <label class="form-label fw-medium">ลิงก์เมื่อคลิก</label>
                <input type="url" name="link" class="form-control" value="{{ old('link', $banner->link) }}">
            </div>
            <div class="mb-3">
                <label class="form-label fw-medium">คำอธิบาย</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $banner->description) }}</textarea>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-medium">ลำดับ</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $banner->sort_order) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-medium">เริ่มแสดง</label>
                    <input type="datetime-local" name="start_date" class="form-control"
                           value="{{ old('start_date', $banner->start_date?->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-medium">หยุดแสดง</label>
                    <input type="datetime-local" name="end_date" class="form-control"
                           value="{{ old('end_date', $banner->end_date?->format('Y-m-d\TH:i')) }}">
                </div>
            </div>
            <div class="form-check form-switch mb-4">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="form-check-input"
                       {{ $banner->is_active ? 'checked' : '' }}>
                <label class="form-check-label">แสดง Banner</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>บันทึก</button>
                <a href="{{ route('admin.banners.index') }}" class="btn btn-outline-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>
@endsection
