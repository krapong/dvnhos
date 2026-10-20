@extends('admin.layouts.app')

@section('title', $typeLabel)

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แผงควบคุม</a></li>
<li class="breadcrumb-item active">{{ $typeLabel }}</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-newspaper me-2"></i>{{ $typeLabel }}</h4>
    <a href="{{ route("{$routePrefix}.create") }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>เพิ่ม{{ $typeLabel }}
    </a>
</div>

<!-- Sorting & Filter Bar -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="form-label mb-0 small fw-bold text-muted">จัดเรียงตาม:</label>
            </div>
            <div class="col-auto">
                <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="latest" {{ request('sort', 'latest') == 'latest' ? 'selected' : '' }}>ใหม่ล่าสุด</option>
                    <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>เก่าสุด</option>
                    <option value="title" {{ request('sort') == 'title' ? 'selected' : '' }}>ชื่อ ก-ฮ</option>
                    <option value="views" {{ request('sort') == 'views' ? 'selected' : '' }}>ยอดชมสูงสุด</option>
                    <option value="pinned" {{ request('sort') == 'pinned' ? 'selected' : '' }}>ปักหมุดก่อน</option>
                </select>
            </div>
            <div class="col-auto">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="" {{ !request('status') ? 'selected' : '' }}>สถานะทั้งหมด</option>
                    <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>เผยแพร่แล้ว</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>ฉบับร่าง</option>
                    <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>จัดเก็บ</option>
                </select>
            </div>
            <div class="col-auto">
                <span class="badge bg-secondary">{{ $posts->total() }} รายการ</span>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 5%;" class="text-center">#</th>
                        <th style="width: 38%;">หัวข้อ</th>
                        <th>สถานะ</th>
                        <th>ผู้สร้าง</th>
                        <th>วันที่เผยแพร่</th>
                        <th>ยอดชม</th>
                        <th style="width: 120px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                    <tr>
                        <td class="text-center text-muted small">{{ $loop->iteration + ($posts->currentPage() - 1) * $posts->perPage() }}</td>
                        <td>
                            <div class="fw-medium">{{ Str::limit($post->title, 55) }}</div>
                            @if($post->is_pinned)
                            <span class="badge bg-warning text-dark"><i class="bi bi-pin-angle"></i> ปักหมุด</span>
                            @endif
                            @if($post->category)
                            <span class="badge bg-light text-dark">{{ $post->category->name }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-status-{{ $post->status }}">{{ $post->status_label }}</span>
                        </td>
                        <td class="small text-muted">{{ $post->user?->name }}</td>
                        <td class="small text-muted">
                            {{ $post->published_at?->format('d/m/Y H:i') ?? '-' }}
                        </td>
                        <td class="small text-muted">{{ number_format($post->view_count) }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route("{$routePrefix}.edit", $post) }}" class="btn btn-outline-primary btn-sm" title="แก้ไข">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-outline-danger btn-sm" title="ลบ"
                                    onclick="confirmDelete({{ $post->id }}, '{{ addslashes(Str::limit($post->title, 30)) }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <form id="delete-form-{{ $post->id }}" action="{{ route("{$routePrefix}.destroy", $post) }}" method="POST" class="d-none">
                                    @csrf @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            ยังไม่มี{{ $typeLabel }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($posts->hasPages())
    <div class="card-footer">
        {{ $posts->withQueryString()->links() }}
    </div>
    @endif
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-2"></i>ยืนยันการลบ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>คุณต้องการลบรายการนี้หรือไม่?</p>
                <p class="fw-bold text-dark" id="deleteItemName"></p>
                <div class="alert alert-warning small mb-0">
                    <i class="bi bi-info-circle me-1"></i>การลบข้อมูลไม่สามารถกู้คืนได้
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bi bi-trash me-1"></i>ยืนยันลบ
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let deleteFormId = null;

function confirmDelete(id, title) {
    deleteFormId = id;
    document.getElementById('deleteItemName').textContent = title;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (deleteFormId) {
        document.getElementById('delete-form-' + deleteFormId).submit();
    }
});
</script>
@endpush
