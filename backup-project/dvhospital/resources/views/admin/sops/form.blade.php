@extends('admin.layouts.app')

@section('title', isset($sop) ? 'แก้ไขเอกสาร SOP' : 'เพิ่มเอกสาร SOP')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แผงควบคุม</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.sops.index') }}">เอกสาร SOP</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ isset($sop) ? 'แก้ไข' : 'เพิ่ม' }}</li>
@endsection

@section('content')
<div class="card max-w-2xl mx-auto" style="max-width: 800px;">
    <div class="card-header">
        <h5 class="mb-0">{{ isset($sop) ? 'แก้ไขเอกสาร SOP' : 'เพิ่มเอกสาร SOP' }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ isset($sop) ? route('admin.sops.update', $sop->id) : route('admin.sops.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if(isset($sop))
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="code" class="form-label">รหัสเอกสาร</label>
                    <input type="text" class="form-control" id="code" name="code" value="{{ old('code', $sop->code ?? '') }}" placeholder="เช่น SOP-001">
                </div>
                <div class="col-md-8 mb-3">
                    <label for="title" class="form-label">ชื่อเอกสาร <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $sop->title ?? '') }}" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="file" class="form-label">ไฟล์ PDF {{ isset($sop) ? '(เลือกหากต้องการเปลี่ยนไฟล์)' : '<span class="text-danger">*</span>' }}</label>
                <input type="file" class="form-control" id="file" name="file" accept="application/pdf" {{ isset($sop) ? '' : 'required' }}>
                <div class="form-text">รองรับไฟล์ .pdf ขนาดไม่เกิน 10MB</div>
                @if(isset($sop) && $sop->file_path)
                    <div class="mt-2 text-muted small">
                        <i class="bi bi-file-pdf"></i> ไฟล์ปัจจุบัน: {{ basename($sop->file_path) }}
                    </div>
                @endif
            </div>

            <div class="text-end">
                <a href="{{ route('admin.sops.index') }}" class="btn btn-secondary me-2">ยกเลิก</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
