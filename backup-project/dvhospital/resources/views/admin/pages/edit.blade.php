@extends('admin.layouts.app')
@section('title', 'แก้ไขหน้าเพจ')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แผงควบคุม</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.pages.index') }}">จัดการหน้าเพจ</a></li>
<li class="breadcrumb-item active">แก้ไข</li>
@endsection

@section('content')
<h4 class="mb-4"><i class="bi bi-pencil me-2"></i>แก้ไขหน้าเพจ: {{ $page->title }}</h4>

<form action="{{ route('admin.pages.update', $page) }}" method="POST" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">หัวข้อ <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control form-control-lg" value="{{ old('title', $page->title) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">เนื้อหา <span class="text-danger">*</span></label>
                        <textarea name="content" class="form-control" rows="15" required>{{ old('content', $page->content) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">ตั้งค่า</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">หมวด</label>
                        <select name="section" class="form-select" required>
                            @foreach($sections as $key => $label)
                            <option value="{{ $key }}" {{ $page->section == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ลำดับ</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ $page->sort_order }}">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ $page->is_active ? 'checked' : '' }}>
                        <label class="form-check-label">แสดงหน้าเพจ</label>
                    </div>
                </div>
            </div>
            <div class="card mb-3" id="attachments">
                <div class="card-header">เอกสารแนบ</div>
                <div class="card-body">
                    @if($page->attachments)
                    <div class="list-group list-group-flush mb-3">
                        @foreach($page->attachments as $file)
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="small text-truncate me-2"><i class="bi bi-file-earmark-pdf text-danger me-1"></i>{{ $file['name'] }}</span>
                            <a href="{{ Storage::url($file['path']) }}" target="_blank" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a>
                        </div>
                        @endforeach
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label small text-muted">เพิ่มเอกสาร (รองรับ PDF, JPG, PNG)</label>
                        <input type="file" name="attachments[]" class="form-control" multiple>
                        <div class="form-text small">เลือกได้หลายไฟล์พร้อมกัน</div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-lg shadow-sm"><i class="bi bi-check-lg me-1"></i>บันทึกการแก้ไข</button>
        </div>
    </div>
</form>
@endsection
