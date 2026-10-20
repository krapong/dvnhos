@extends('admin.layouts.app')
@section('title', 'แก้ไขผู้ใช้งาน')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แผงควบคุม</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">จัดการผู้ใช้งาน</a></li>
<li class="breadcrumb-item active">แก้ไข</li>
@endsection

@section('content')
<h4 class="mb-4"><i class="bi bi-pencil me-2"></i>แก้ไขผู้ใช้งาน: {{ $user->name }}</h4>

<div class="card" style="max-width: 700px;">
    <div class="card-body">
        <form action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-medium">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-medium">อีเมล <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-medium">รหัสผ่านใหม่</label>
                    <input type="password" name="password" class="form-control" minlength="8">
                    <small class="text-muted">เว้นว่างถ้าไม่ต้องการเปลี่ยน</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-medium">ยืนยันรหัสผ่าน</label>
                    <input type="password" name="password_confirmation" class="form-control">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-medium">เบอร์โทร</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-medium">ตำแหน่ง</label>
                    <input type="text" name="position" class="form-control" value="{{ old('position', $user->position) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-medium">แผนก/กลุ่มงาน</label>
                    <input type="text" name="department" class="form-control" value="{{ old('department', $user->department) }}">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-medium">บทบาท (Role) <span class="text-danger">*</span></label>
                <select name="role" class="form-select" required>
                    @foreach($roles as $role)
                    <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                        {{ config("hospital.roles.{$role->name}", $role->name) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-check form-switch mb-4">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="form-check-input"
                       {{ $user->is_active ? 'checked' : '' }}>
                <label class="form-check-label">เปิดใช้งานบัญชี</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>บันทึก</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>
@endsection
