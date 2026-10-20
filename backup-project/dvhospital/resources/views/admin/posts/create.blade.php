@extends('admin.layouts.app')

@section('title', "เพิ่ม{$typeLabel}")

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แผงควบคุม</a></li>
<li class="breadcrumb-item"><a href="{{ route("{$routePrefix}.index") }}">{{ $typeLabel }}</a></li>
<li class="breadcrumb-item active">เพิ่มใหม่</li>
@endsection

@section('content')
<h4 class="mb-4"><i class="bi bi-plus-circle me-2"></i>เพิ่ม{{ $typeLabel }}</h4>

<form action="{{ route("{$routePrefix}.store") }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">หัวข้อ <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control form-control-lg"
                               value="{{ old('title') }}" required placeholder="พิมพ์หัวข้อ...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">เนื้อหาย่อ</label>
                        <textarea name="excerpt" class="form-control" rows="3"
                                  placeholder="สรุปเนื้อหาสั้นๆ...">{{ old('excerpt') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">เนื้อหา <span class="text-danger">*</span></label>
                        <textarea name="content" id="content" class="form-control" rows="15"
                                  required>{{ old('content') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- อัลบั้มภาพ (Gallery) -->
            <div class="card mb-3">
                <div class="card-header">อัลบั้มภาพ (Gallery)</div>
                <div class="card-body">
                    <input type="file" name="gallery_images[]" class="form-control" multiple accept="image/*">
                    <small class="text-muted">รองรับไฟล์รูปภาพ สามารถเลือกได้หลายไฟล์พร้อมกัน</small>
                </div>
            </div>

            <!-- ไฟล์แนบ -->
            <div class="card mb-3">
                <div class="card-header">ไฟล์แนบ (เอกสาร)</div>
                <div class="card-body">
                    <input type="file" name="attachments[]" class="form-control" multiple
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                    <small class="text-muted">รองรับ PDF, Word, Excel, PowerPoint (สูงสุด 10MB ต่อไฟล์)</small>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- การแสดงผล -->
            <div class="card mb-3">
                <div class="card-header">การแสดงผล</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">ลำดับการแสดงผล</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 99) }}">
                        <small class="text-muted">ระบุตัวเลข (เช่น 1 = แสดงอันดับแรก)</small>
                    </div>
                </div>
            </div>

            <!-- เผยแพร่ -->
            <div class="card mb-3">
                <div class="card-header">การเผยแพร่</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">สถานะ</label>
                        <select name="status" class="form-select">
                            <option value="draft">แบบร่าง</option>
                            <option value="published" selected>เผยแพร่</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">วันที่เผยแพร่</label>
                        <input type="datetime-local" name="published_at" class="form-control"
                               value="{{ old('published_at', now()->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">วันหมดอายุ (ถ้ามี)</label>
                        <input type="datetime-local" name="expired_at" class="form-control"
                               value="{{ old('expired_at') }}">
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_pinned" value="1" class="form-check-input" id="isPinned">
                        <label class="form-check-label" for="isPinned">ปักหมุดไว้ด้านบน</label>
                    </div>
                </div>
            </div>

            <!-- หมวดหมู่ -->
            @if($categories->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header">หมวดหมู่</div>
                <div class="card-body">
                    <select name="category_id" class="form-select">
                        <option value="">-- ไม่ระบุ --</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif

            <!-- รูปภาพหลัก -->
            <div class="card mb-3">
                <div class="card-header">รูปภาพหลัก</div>
                <div class="card-body">
                    <input type="file" name="featured_image" class="form-control" accept="image/*">
                    <small class="text-muted">แนะนำขนาด 1200x630px (สูงสุด 5MB)</small>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-lg">
                <i class="bi bi-check-lg me-2"></i>บันทึก
            </button>
        </div>
    </div>
</form>
@endsection
