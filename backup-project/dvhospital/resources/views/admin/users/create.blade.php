@extends('admin.layouts.app')
@section('title', 'เพิ่มผู้ใช้งาน')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แผงควบคุม</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">จัดการผู้ใช้งาน</a></li>
<li class="breadcrumb-item active">เพิ่มผู้ใช้</li>
@endsection

@section('content')
<h4 class="mb-4"><i class="bi bi-person-plus me-2"></i>เพิ่มผู้ใช้งาน</h4>

<div class="card" style="max-width: 700px;">
    <div class="card-body">
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-medium">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-medium">อีเมล <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-medium">รหัสผ่าน <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-medium">ยืนยันรหัสผ่าน <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-medium">เบอร์โทร</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-medium">ตำแหน่ง</label>
                    <input type="text" name="position" class="form-control" value="{{ old('position') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-medium">แผนก/กลุ่มงาน</label>
                    <input type="text" name="department" class="form-control" value="{{ old('department') }}">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-medium">บทบาท (Role) <span class="text-danger">*</span></label>
                <select name="role" class="form-select" required>
                    <option value="">-- เลือก Role --</option>
                    @foreach($roles as $role)
                    <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                        {{ config("hospital.roles.{$role->name}", $role->name) }}
                        @if($role->description) - {{ $role->description }} @endif
                    </option>
                    @endforeach
                </select>
                <div class="mt-2 small text-muted">
                    <strong>คำอธิบาย Role:</strong><br>
                    <span class="text-primary">Super Admin</span> - เข้าถึงได้ทุกเมนู จัดการผู้ใช้ ตั้งค่าระบบ<br>
                    <span class="text-primary">Staff PR</span> - จัดการข่าว Banner ประกาศรับสมัครงาน<br>
                    <span class="text-primary">Staff Procurement</span> - จัดการประกาศจัดซื้อจัดจ้าง<br>
                    <span class="text-primary">Staff ITA</span> - จัดการ ITA ชมรมจริยธรรม OIT<br>
                    <span class="text-primary">Staff Events</span> - จัดการกิจกรรม ปฏิทิน
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>บันทึก</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>
@endsection
