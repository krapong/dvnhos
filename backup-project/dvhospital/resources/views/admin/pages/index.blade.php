@extends('admin.layouts.app')
@section('title', 'จัดการหน้าเพจ')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แผงควบคุม</a></li>
<li class="breadcrumb-item active">จัดการหน้าเพจ</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>จัดการหน้าเพจ</h4>
    <a href="{{ route('admin.pages.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>เพิ่มหน้าเพจ
    </a>
</div>

<!-- Section Filter -->
<div class="mb-3">
    <div class="btn-group btn-group-sm">
        <a href="{{ route('admin.pages.index') }}" class="btn {{ !request('section') ? 'btn-primary' : 'btn-outline-primary' }}">ทั้งหมด</a>
        <a href="{{ route('admin.pages.index', ['section' => 'about']) }}" class="btn {{ request('section')=='about' ? 'btn-primary' : 'btn-outline-primary' }}">เกี่ยวกับองค์กร</a>
        <a href="{{ route('admin.pages.index', ['section' => 'services']) }}" class="btn {{ request('section')=='services' ? 'btn-primary' : 'btn-outline-primary' }}">บริการ</a>
        <a href="{{ route('admin.pages.index', ['section' => 'ita']) }}" class="btn {{ request('section')=='ita' ? 'btn-primary' : 'btn-outline-primary' }}">ITA</a>
        <a href="{{ route('admin.pages.index', ['section' => 'ethics']) }}" class="btn {{ request('section')=='ethics' ? 'btn-primary' : 'btn-outline-primary' }}">ชมรมจริยธรรม</a>
        <a href="{{ route('admin.pages.index', ['section' => 'oit']) }}" class="btn {{ request('section')=='oit' ? 'btn-primary' : 'btn-outline-primary' }}">OIT</a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>หัวข้อ</th>
                        <th>หมวด</th>
                        <th>ลำดับ</th>
                        <th>สถานะ</th>
                        <th>ผู้สร้าง</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $page)
                    <tr>
                        <td class="fw-medium">{{ Str::limit($page->title, 60) }}</td>
                        <td><span class="badge bg-secondary">{{ $page->section }}</span></td>
                        <td>{{ $page->sort_order }}</td>
                        <td>
                            <span class="badge {{ $page->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $page->is_active ? 'แสดง' : 'ซ่อน' }}
                            </span>
                        </td>
                        <td class="small text-muted">{{ $page->user?->name }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('admin.pages.destroy', $page) }}" method="POST"
                                      onsubmit="return confirm('ยืนยันการลบ?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">ยังไม่มีหน้าเพจ</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($pages->hasPages())
    <div class="card-footer">{{ $pages->links() }}</div>
    @endif
</div>
@endsection
