@extends('admin.layouts.app')

@section('title', 'เอกสาร SOP')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แผงควบคุม</a></li>
    <li class="breadcrumb-item active" aria-current="page">เอกสาร SOP</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">จัดการเอกสาร SOP</h4>
    <a href="{{ route('admin.sops.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> เพิ่มเอกสารใหม่
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="50" class="text-center">#</th>
                        <th width="120">รหัสเอกสาร</th>
                        <th>ชื่อเอกสาร</th>
                        <th>วันที่อัปโหลด</th>
                        <th width="150" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sops as $sop)
                    <tr>
                        <td class="text-center">{{ $sops->firstItem() + $loop->index }}</td>
                        <td>
                            @if($sop->code)
                                <span class="badge bg-secondary">{{ $sop->code }}</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>
                            <i class="bi bi-file-pdf text-danger me-2"></i>
                            {{ $sop->title }}
                        </td>
                        <td>{{ $sop->created_at->format('d/m/Y H:i') }}</td>
                        <td class="text-center">
                            <a href="{{ route('admin.sops.edit', $sop->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('admin.sops.destroy', $sop->id) }}" method="POST" class="d-inline" onsubmit="return confirm('ยืนยันการลบเอกสารนี้?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">
                            ไม่มีข้อมูลเอกสาร SOP
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($sops->hasPages())
    <div class="card-footer bg-white">
        {{ $sops->links() }}
    </div>
    @endif
</div>
@endsection
