@extends('admin.layouts.app')
@section('title', 'เพิ่มข้อมูลแพทย์')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แผงควบคุม</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.doctors.index') }}">ตารางแพทย์</a></li>
<li class="breadcrumb-item active">เพิ่มแพทย์</li>
@endsection

@section('content')
<h4 class="mb-4"><i class="bi bi-person-plus me-2"></i>เพิ่มข้อมูลแพทย์</h4>

<form action="{{ route('admin.doctors.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
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
                                <option value="นพ.">นพ.</option>
                                <option value="พญ.">พญ.</option>
                                <option value="ทพ.">ทพ.</option>
                                <option value="ทพญ.">ทพญ.</option>
                                <option value="ภก.">ภก.</option>
                                <option value="ภญ.">ภญ.</option>
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ความเชี่ยวชาญ / สาขา <span class="text-danger">*</span></label>
                        <input type="text" name="specialty" class="form-control" value="{{ old('specialty') }}" required
                               placeholder="เช่น อายุรกรรม, สูตินรีเวช, กุมารเวชกรรม">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รูปภาพ</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ประวัติย่อ</label>
                        <textarea name="bio" class="form-control" rows="3">{{ old('bio') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header">ตารางออกตรวจ</div>
                <div class="card-body">
                    <div id="scheduleContainer">
                        <div class="schedule-row border rounded p-3 mb-2">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small">วัน</label>
                                    <select name="schedules[0][day_of_week]" class="form-select form-select-sm" required>
                                        <option value="monday">จันทร์</option>
                                        <option value="tuesday">อังคาร</option>
                                        <option value="wednesday">พุธ</option>
                                        <option value="thursday">พฤหัสบดี</option>
                                        <option value="friday">ศุกร์</option>
                                        <option value="saturday">เสาร์</option>
                                        <option value="sunday">อาทิตย์</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">เริ่ม</label>
                                    <input type="time" name="schedules[0][start_time]" class="form-control form-control-sm" value="08:30" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">สิ้นสุด</label>
                                    <input type="time" name="schedules[0][end_time]" class="form-control form-control-sm" value="16:30" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small">คลินิก/ห้องตรวจ</label>
                                    <input type="text" name="schedules[0][clinic]" class="form-control form-control-sm" placeholder="เช่น ห้องตรวจ 1">
                                </div>
                            </div>
                        </div>
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
let scheduleIndex = 1;
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
            <div class="col-md-3">
                <input type="time" name="schedules[${scheduleIndex}][start_time]" class="form-control form-control-sm" value="08:30" required>
            </div>
            <div class="col-md-3">
                <input type="time" name="schedules[${scheduleIndex}][end_time]" class="form-control form-control-sm" value="16:30" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.closest('.schedule-row').remove()">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <div class="col-12">
                <input type="text" name="schedules[${scheduleIndex}][clinic]" class="form-control form-control-sm" placeholder="คลินิก/ห้องตรวจ">
            </div>
        </div>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
    scheduleIndex++;
}
</script>
@endpush
