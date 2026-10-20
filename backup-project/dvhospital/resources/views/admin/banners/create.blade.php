@extends('admin.layouts.app')
@section('title', 'เพิ่ม Banner')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แผงควบคุม</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.banners.index') }}">จัดการ Banner</a></li>
<li class="breadcrumb-item active">เพิ่ม Banner</li>
@endsection

@section('content')
<h4 class="mb-4"><i class="bi bi-plus-circle me-2"></i>เพิ่ม Banner</h4>

<div class="card" style="max-width: 700px;">
    <div class="card-body">
        <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-medium">ชื่อ Banner <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-medium">รูปภาพ <span class="text-danger">*</span></label>
                <input type="file" name="image" class="form-control" accept="image/*" required>
                <small class="text-muted">แนะนำขนาด 1920x600px (สูงสุด 5MB)</small>
            </div>
            <div class="mb-3">
                <label class="form-label fw-medium">ลิงก์เมื่อคลิก</label>
                <input type="url" name="link" class="form-control" value="{{ old('link') }}" placeholder="https://...">
            </div>
            <div class="mb-3">
                <label class="form-label fw-medium">คำอธิบาย</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-medium">ลำดับการแสดง</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-medium">เริ่มแสดง</label>
                    <input type="datetime-local" name="start_date" class="form-control" value="{{ old('start_date') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-medium">หยุดแสดง</label>
                    <input type="datetime-local" name="end_date" class="form-control" value="{{ old('end_date') }}">
                </div>
            </div>
            <div class="form-check form-switch mb-4">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
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
