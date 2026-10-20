@extends('admin.layouts.app')
@section('title', 'ตารางแพทย์ออกตรวจ')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แผงควบคุม</a></li>
<li class="breadcrumb-item active">ตารางแพทย์ออกตรวจ</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-clipboard2-pulse me-2"></i>ตารางแพทย์ออกตรวจ</h4>
    <a href="{{ route('admin.doctors.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>เพิ่มแพทย์
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>แพทย์</th>
                        <th>ความเชี่ยวชาญ</th>
                        <th>วันออกตรวจ</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($doctors as $doctor)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                @if($doctor->photo)
                                <img src="{{ Storage::url($doctor->photo) }}" class="rounded-circle me-2"
                                     style="width: 36px; height: 36px; object-fit: cover;">
                                @endif
                                <strong>{{ $doctor->full_name }}</strong>
                            </div>
                        </td>
                        <td>{{ $doctor->specialty }}</td>
                        <td>
                            @foreach($doctor->schedules as $s)
                            <span class="badge bg-success me-1">{{ $s->day_label }}</span>
                            @endforeach
                        </td>
                        <td>
                            <span class="badge {{ $doctor->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $doctor->is_active ? 'ใช้งาน' : 'ปิด' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.doctors.edit', $doctor) }}" class="btn btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.doctors.destroy', $doctor) }}" method="POST"
                                      onsubmit="return confirm('ยืนยันการลบ?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">ยังไม่มีข้อมูลแพทย์</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($doctors->hasPages())
    <div class="card-footer">{{ $doctors->links() }}</div>
    @endif
</div>
@endsection
