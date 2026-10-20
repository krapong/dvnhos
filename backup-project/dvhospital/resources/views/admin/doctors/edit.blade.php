@extends('admin.layouts.app')
@section('title', 'แก้ไขข้อมูลแพทย์')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แผงควบคุม</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.doctors.index') }}">ตารางแพทย์</a></li>
<li class="breadcrumb-item active">แก้ไข</li>
@endsection

@section('content')
<h4 class="mb-4"><i class="bi bi-pencil me-2"></i>แก้ไขข้อมูล: {{ $doctor->full_name }}</h4>

<form action="{{ route('admin.doctors.update', $doctor) }}" method="POST" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header">ข้อมูลแพทย์</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">คำนำหน้า</label>
                            <select name="title" class="form-select">
                                <option value="">--</option>
                                @foreach(['นพ.','พญ.','ทพ.','ทพญ.','ภก.','ภญ.'] as $t)
                                <option value="{{ $t }}" {{ $doctor->title == $t ? 'selected' : '' }}>{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $doctor->name) }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ความเชี่ยวชาญ <span class="text-danger">*</span></label>
                        <input type="text" name="specialty" class="form-control" value="{{ old('specialty', $doctor->specialty) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รูปภาพ</label>
                        @if($doctor->photo)
                        <img src="{{ Storage::url($doctor->photo) }}" class="d-block mb-2 rounded-circle" style="width:80px;height:80px;object-fit:cover;">
                        @endif
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ประวัติย่อ</label>
                        <textarea name="bio" class="form-control" rows="3">{{ old('bio', $doctor->bio) }}</textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ $doctor->is_active ? 'checked' : '' }}>
                        <label class="form-check-label">เปิดใช้งาน</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header">ตารางออกตรวจ</div>
                <div class="card-body">
                    <div id="scheduleContainer">
                        @foreach($doctor->schedules as $i => $schedule)
                        <div class="schedule-row border rounded p-3 mb-2">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <select name="schedules[{{ $i }}][day_of_week]" class="form-select form-select-sm" required>
                                        @foreach(['monday'=>'จันทร์','tuesday'=>'อังคาร','wednesday'=>'พุธ','thursday'=>'พฤหัสบดี','friday'=>'ศุกร์','saturday'=>'เสาร์','sunday'=>'อาทิตย์'] as $val => $label)
                                        <option value="{{ $val }}" {{ $schedule->day_of_week == $val ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="time" name="schedules[{{ $i }}][start_time]" class="form-control form-control-sm"
                                           value="{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="time" name="schedules[{{ $i }}][end_time]" class="form-control form-control-sm"
                                           value="{{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.closest('.schedule-row').remove()">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <div class="col-12">
                                    <input type="text" name="schedules[{{ $i }}][clinic]" class="form-control form-control-sm"
                                           value="{{ $schedule->clinic }}" placeholder="คลินิก/ห้องตรวจ">
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="addScheduleRow()">
                        <i class="bi bi-plus me-1"></i>เพิ่มวันออกตรวจ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check-lg me-1"></i>บันทึก</button>
        <a href="{{ route('admin.doctors.index') }}" class="btn btn-outline-secondary btn-lg">ยกเลิก</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
let scheduleIndex = {{ $doctor->schedules->count() }};
function addScheduleRow() {
    const container = document.getElementById('scheduleContainer');
    const html = `
    <div class="schedule-row border rounded p-3 mb-2">
        <div class="row g-2">
            <div class="col-md-4">
                <select name="schedules[${scheduleIndex}][day_of_week]" class="form-select form-select-sm" required>
                    <option value="monday">จันทร์</option><option value="tuesday">อังคาร</option>
                    <option value="wednesday">พุธ</option><option value="thursday">พฤหัสบดี</option>
                    <option value="friday">ศุกร์</option><option value="saturday">เสาร์</option>
                    <option value="sunday">อาทิตย์</option>
                </select>
            </div>
            <div class="col-md-3"><input type="time" name="schedules[${scheduleIndex}][start_time]" class="form-control form-control-sm" value="08:30" required></div>
            <div class="col-md-3"><input type="time" name="schedules[${scheduleIndex}][end_time]" class="form-control form-control-sm" value="16:30" required></div>
            <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.closest('.schedule-row').remove()"><i class="bi bi-trash"></i></button></div>
            <div class="col-12"><input type="text" name="schedules[${scheduleIndex}][clinic]" class="form-control form-control-sm" placeholder="คลินิก/ห้องตรวจ"></div>
        </div>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
    scheduleIndex++;
}
</script>
@endpush
