@extends('admin.layouts.app')
@section('title', 'สร้างหน้าเพจ')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แผงควบคุม</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.pages.index') }}">จัดการหน้าเพจ</a></li>
<li class="breadcrumb-item active">สร้างหน้าเพจ</li>
@endsection

@section('content')
<h4 class="mb-4"><i class="bi bi-plus-circle me-2"></i>สร้างหน้าเพจ</h4>

<form action="{{ route('admin.pages.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">หัวข้อ <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control form-control-lg" value="{{ old('title') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">เนื้อหา <span class="text-danger">*</span></label>
                        <textarea name="content" class="form-control" rows="15" required>{{ old('content') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header">ไฟล์แนบ</div>
                <div class="card-body">
                    <input type="file" name="attachments[]" class="form-control" multiple>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">ตั้งค่า</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">หมวด <span class="text-danger">*</span></label>
                        <select name="section" class="form-select" required>
                            @foreach($sections as $key => $label)
                            <option value="{{ $key }}" {{ ($selectedSection ?? 'about') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ลำดับการแสดง</label>
                        <input type="number" name="sort_order" class="form-control" value="0" min="0">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
                        <label class="form-check-label">แสดงหน้าเพจ</label>
                    </div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header">รูปภาพหลัก</div>
                <div class="card-body">
                    <input type="file" name="featured_image" class="form-control" accept="image/*">
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-lg"><i class="bi bi-check-lg me-1"></i>บันทึก</button>
        </div>
    </div>
</form>
@endsection
