@extends('admin.layouts.app')
@section('title', 'เพิ่ม Popup Banner')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แผงควบคุม</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.popups.index') }}">จัดการ Popup Banners</a></li>
<li class="breadcrumb-item active">เพิ่ม Popup Banner</li>
@endsection

@section('content')
<h4 class="mb-4"><i class="bi bi-plus-circle me-2"></i>เพิ่ม Popup Banner</h4>

<div class="card" style="max-width: 700px;">
    <div class="card-body">
        <form action="{{ route('admin.popups.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-medium">ชื่อหัวข้อ / รายละเอียดเรื่องประชาสัมพันธ์ <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="ตัวอย่าง: ประกาศปรับปรุงระบบเครือข่าย, ขอเชิญร่วมกิจกรรม..." required>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-medium">รูปภาพแบนเนอร์ Popup <span class="text-danger">*</span></label>
                <input type="file" name="image" class="form-control" accept="image/*" required>
                <small class="text-muted">แนะนำขนาด 800x800px หรืออัตราส่วน 1:1 หรือ 4:3 (ขนาดไม่เกิน 5MB)</small>
            </div>

            <div class="mb-3">
                <label class="form-label fw-medium">ลิงก์ภายนอกที่ต้องการเชื่อมโยง (ถ้ามี)</label>
                <input type="url" name="link" class="form-control" value="{{ old('link') }}" placeholder="เช่น https://dvhospital.go.th/... หรือ หน้าเนื้อหาเป้าหมาย">
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-medium">ลำดับความสำคัญ (แสดงผล)</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0" placeholder="เลขน้อยจะแสดงก่อน">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-medium">วันที่เริ่มต้นแสดงผล</label>
                    <input type="datetime-local" name="start_date" class="form-control" value="{{ old('start_date') }}">
                    <small class="text-muted">ปล่อยว่างเพื่อแสดงผลทันที</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-medium">วันที่สิ้นสุดแสดงผล</label>
                    <input type="datetime-local" name="end_date" class="form-control" value="{{ old('end_date') }}">
                    <small class="text-muted">ปล่อยว่างเพื่อแสดงเรื่อยๆ</small>
                </div>
            </div>

            <div class="form-check form-switch mb-4">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" checked id="isActiveSwitch">
                <label class="form-check-label" for="isActiveSwitch">เปิดการแสดงผลของแบนเนอร์นี้ในหน้าหลัก</label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>บันทึก</button>
                <a href="{{ route('admin.popups.index') }}" class="btn btn-outline-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>
@endsection
