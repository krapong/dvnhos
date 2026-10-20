@extends('frontend.layouts.app')

@section('title', 'เอกสารมาตรฐานการปฏิบัติงาน (SOP)')

@section('content')
<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold mb-3 border-start border-4 border-primary ps-3" style="color: var(--primary-dark);">เอกสารมาตรฐานการปฏิบัติงาน (SOP)</h2>
            <p class="text-muted">เอกสารสำหรับดาวน์โหลดเพื่อใช้เป็นมาตรฐานในการปฏิบัติงาน</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50" class="text-center py-3">#</th>
                            <th class="py-3">รายการเอกสาร</th>
                            <th class="py-3 text-center">วันที่อัปเดต</th>
                            <th width="150" class="text-end pe-4 py-3">ดาวน์โหลด</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sops as $sop)
                        <tr>
                            <td class="text-center text-muted">{{ $sops->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="d-flex align-items-center py-2">
                                    <div class="bg-danger bg-opacity-10 text-danger rounded p-2 me-3 d-flex align-items-center justify-content-center">
                                        <i class="bi bi-file-earmark-pdf-fill fs-4"></i>
                                    </div>
                                    <div>
                                        <span class="fw-medium text-dark d-block">{{ $sop->title }}</span>
                                        @if($sop->code)
                                            <span class="badge bg-secondary opacity-75 mt-1">{{ $sop->code }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-center text-muted small">
                                {{ $sop->updated_at->format('d/m/Y') }}
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('sops.download', $sop->id) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3" target="_blank">
                                    <i class="bi bi-download me-1"></i> ดาวน์โหลด
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-folder-x fs-1 d-block mb-3 text-secondary opacity-50"></i>
                                ขณะนี้ยังไม่มีเอกสาร SOP ในระบบ
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($sops->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $sops->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
