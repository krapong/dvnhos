@extends('admin.layouts.app')
@section('title', 'ปฏิทินกิจกรรม')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แผงควบคุม</a></li>
<li class="breadcrumb-item active">ปฏิทินกิจกรรม</li>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-calendar-event me-2"></i>ปฏิทินกิจกรรม</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#eventModal">
        <i class="bi bi-plus-lg me-1"></i>เพิ่มกิจกรรม
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div id="adminCalendar"></div>
    </div>
</div>

<!-- Event Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="eventForm" method="POST">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">เพิ่มกิจกรรม</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ชื่อกิจกรรม <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="eventTitle" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รายละเอียด</label>
                        <textarea name="description" id="eventDesc" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">สถานที่</label>
                        <input type="text" name="location" id="eventLocation" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">วันเริ่มต้น <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="start_date" id="eventStart" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">วันสิ้นสุด</label>
                            <input type="datetime-local" name="end_date" id="eventEnd" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">สี</label>
                            <input type="color" name="color" id="eventColor" class="form-control form-control-color" value="#00695C">
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="is_all_day" id="eventAllDay" class="form-check-input" value="1">
                                <label class="form-check-label">ทั้งวัน</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger d-none" id="deleteEventBtn">ลบ</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Event List -->
<div class="card mt-4">
    <div class="card-header">รายการกิจกรรมทั้งหมด</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>กิจกรรม</th><th>วันที่</th><th>สถานที่</th><th>โดย</th><th>จัดการ</th></tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                    <tr>
                        <td>
                            <span class="badge me-1" style="background:{{ $event->color }};">&nbsp;</span>
                            {{ $event->title }}
                        </td>
                        <td class="small">{{ $event->start_date->format('d/m/Y H:i') }}</td>
                        <td class="small text-muted">{{ $event->location ?? '-' }}</td>
                        <td class="small text-muted">{{ $event->user?->name }}</td>
                        <td>
                            <form action="{{ route('admin.events.destroy', $event) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('ยืนยันการลบ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">ยังไม่มีกิจกรรม</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($events->hasPages())
    <div class="card-footer">{{ $events->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('adminCalendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'th',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek' },
        events: '{{ route("admin.events.calendar-data") }}',
        dateClick: function(info) {
            document.getElementById('eventStart').value = info.dateStr + 'T09:00';
            document.getElementById('eventForm').action = '{{ route("admin.events.store") }}';
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('modalTitle').textContent = 'เพิ่มกิจกรรม';
            document.getElementById('deleteEventBtn').classList.add('d-none');
            new bootstrap.Modal(document.getElementById('eventModal')).show();
        },
        height: 'auto',
    });
    calendar.render();
});
</script>
@endpush
