@extends('admin.layouts.app')

@section('title', "แก้ไข{$typeLabel}")

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แผงควบคุม</a></li>
<li class="breadcrumb-item"><a href="{{ route("{$routePrefix}.index") }}">{{ $typeLabel }}</a></li>
<li class="breadcrumb-item active">แก้ไข</li>
@endsection

@section('content')
<h4 class="mb-4"><i class="bi bi-pencil me-2"></i>แก้ไข{{ $typeLabel }}</h4>

<form action="{{ route("{$routePrefix}.update", $post) }}" method="POST" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">หัวข้อ <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control form-control-lg"
                               value="{{ old('title', $post->title) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">เนื้อหาย่อ</label>
                        <textarea name="excerpt" class="form-control" rows="3">{{ old('excerpt', $post->excerpt) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">เนื้อหา <span class="text-danger">*</span></label>
                        <textarea name="content" id="content" class="form-control" rows="15"
                                  required>{{ old('content', $post->content) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- อัลบั้มภาพ (Gallery) -->
            <div class="card mb-3">
                <div class="card-header">อัลบั้มภาพ (Gallery)</div>
                <div class="card-body">
                    @if($post->gallery && count($post->gallery) > 0)
                    <div class="mb-3">
                        <label class="form-label fw-medium text-muted small">รูปภาพในอัลบั้มปัจจุบัน (เลือกเพื่อลบ)</label>
                        <div class="row g-2">
                            @foreach($post->gallery as $img)
                            <div class="col-4 col-sm-3 col-md-2 position-relative">
                                <img src="{{ str_starts_with($img, 'http') ? $img : Storage::url($img) }}" class="img-fluid rounded border" alt="gallery">
                                <div class="form-check position-absolute top-0 end-0 m-1 bg-white rounded shadow-sm px-2 pb-1" style="opacity: 0.9">
                                    <input class="form-check-input mt-1" type="checkbox" name="remove_gallery[]" value="{{ $img }}" id="rm_{{ $loop->index }}">
                                    <label class="form-check-label text-danger small" for="rm_{{ $loop->index }}">ลบ</label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    <label class="form-label fw-medium text-muted small">เพิ่มรูปภาพใหม่ในอัลบั้ม</label>
                    <input type="file" name="gallery_images[]" class="form-control" multiple accept="image/*">
                </div>
            </div>

            <!-- ไฟล์แนบ -->
            <div class="card mb-3">
                <div class="card-header">ไฟล์แนบ (เอกสาร)</div>
                <div class="card-body">
                    @if($post->attachments)
                    <div class="mb-3">
                        <label class="form-label small text-muted">ไฟล์แนบปัจจุบัน (เลือกเพื่อลบ):</label>
                        @foreach($post->attachments as $index => $file)
                        <div class="d-flex align-items-center justify-content-between mb-1 p-2 bg-light rounded border">
                            <div class="text-truncate" style="max-width: 80%;">
                                <i class="bi bi-file-earmark me-2 text-primary"></i>
                                <a href="{{ Storage::url($file['path']) }}" target="_blank" class="text-decoration-none">{{ $file['name'] }}</a>
                            </div>
                            <div class="form-check m-0">
                                <input class="form-check-input" type="checkbox" name="remove_attachments[]" value="{{ $index }}" id="rm_att_{{ $index }}">
                                <label class="form-check-label text-danger small" for="rm_att_{{ $index }}">ลบ</label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                    <input type="file" name="attachments[]" class="form-control" multiple>
                    <small class="text-danger mt-1 d-block"><i class="bi bi-exclamation-triangle me-1"></i>หากอัปโหลดไฟล์ใหม่ ไฟล์เดิมทั้งหมดจะถูกแทนที่ (หากต้องการลบเฉยๆ ให้เลือกถูกที่ปุ่มลบด้านบนแทน)</small>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- กลุ่มประกาศ -->
            <div class="card mb-3">
                <div class="card-header">กลุ่มประกาศ (หมวดหมู่หลัก)</div>
                <div class="card-body">
                    <select name="post_type" class="form-select">
                        <option value="news" {{ $post->type === 'news' ? 'selected' : '' }}>ข่าวประชาสัมพันธ์</option>
                        <option value="job" {{ $post->type === 'job' ? 'selected' : '' }}>ประกาศรับสมัครงาน</option>
                        <option value="procurement" {{ $post->type === 'procurement' ? 'selected' : '' }}>จัดซื้อจัดจ้าง</option>
                        <option value="activity" {{ $post->type === 'activity' ? 'selected' : '' }}>กิจกรรม</option>
                    </select>
                    <small class="text-muted d-block mt-1">หากเปลี่ยนกลุ่ม หมวดหมู่ย่อยเดิมจะถูกล้างค่า</small>
                </div>
            </div>

            <!-- การแสดงผล -->
            <div class="card mb-3">
                <div class="card-header">การแสดงผล</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">ลำดับการแสดงผล</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $post->sort_order ?? 99) }}">
                        <small class="text-muted">ระบุตัวเลข (เช่น 1 = แสดงอันดับแรก)</small>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">การเผยแพร่</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">สถานะ</label>
                        <select name="status" class="form-select">
                            <option value="draft" {{ $post->status === 'draft' ? 'selected' : '' }}>แบบร่าง</option>
                            <option value="published" {{ $post->status === 'published' ? 'selected' : '' }}>เผยแพร่</option>
                            <option value="archived" {{ $post->status === 'archived' ? 'selected' : '' }}>เก็บถาวร</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">วันที่เผยแพร่</label>
                        <input type="datetime-local" name="published_at" class="form-control"
                               value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">วันหมดอายุ</label>
                        <input type="datetime-local" name="expired_at" class="form-control"
                               value="{{ old('expired_at', $post->expired_at?->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_pinned" value="1" class="form-check-input"
                               {{ $post->is_pinned ? 'checked' : '' }}>
                        <label class="form-check-label">ปักหมุดไว้ด้านบน</label>
                    </div>
                </div>
            </div>

            @if($categories->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header">หมวดหมู่</div>
                <div class="card-body">
                    <select name="category_id" class="form-select">
                        <option value="">-- ไม่ระบุ --</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $post->category_id == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif

            <div class="card mb-3">
                <div class="card-header">รูปภาพหลัก</div>
                <div class="card-body">
                    @if($post->featured_image)
                    <img src="{{ Storage::url($post->featured_image) }}" class="img-fluid rounded mb-2" alt="">
                    @endif
                    <input type="file" name="featured_image" class="form-control" accept="image/*">
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-lg">
                <i class="bi bi-check-lg me-2"></i>บันทึกการแก้ไข
            </button>
        </div>
    </div>
</form>
@endsection
