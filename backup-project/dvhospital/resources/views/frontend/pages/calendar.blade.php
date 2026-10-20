@extends('frontend.layouts.app')
@section('title', 'ปฏิทินกิจกรรม')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css" rel="stylesheet">
<style>
    .fc { font-family: 'Sarabun', sans-serif; }
    .fc .fc-toolbar-title { font-size: 1.25rem; font-weight: 700; }
    .fc .fc-button-primary {
        background: var(--primary) !important;
        border-color: var(--primary) !important;
    }
    .fc .fc-day-today { background: rgba(0,105,92,0.05) !important; }
</style>
@endpush

@section('content')
<section class="py-5">
    <div class="container">
        <h2 class="section-title">ปฏิทินกิจกรรมโรงพยาบาล</h2>
        <div class="card">
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.9/locales/th.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'th',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listWeek'
        },
        events: '{{ route("api.calendar") }}',
        eventClick: function(info) {
            var props = info.event.extendedProps;
            var msg = info.event.title;
            if (props.description) msg += '\n\n' + props.description;
            if (props.location) msg += '\n\nสถานที่: ' + props.location;
            alert(msg);
        },
        height: 'auto',
        dayMaxEvents: 3,
    });
    calendar.render();
});
</script>
@endpush
