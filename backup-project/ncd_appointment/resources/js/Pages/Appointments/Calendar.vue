<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import { ref } from 'vue';

const props = defineProps({
    clinics: Array,
    events: Array
});

const calendarOptions = ref({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    events: props.events,
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,dayGridWeek,dayGridDay'
    },
    locale: 'th',
    eventClick: (info) => {
        // Show basic info in alert for now
        const p = info.event.extendedProps;
        alert(`คลินิก: ${info.event.title}\nวันที่: ${p.date}\nจำนวนที่นัดหมายไว้: ${p.total_appointments} คิว`);
    },
    dateClick: (info) => {
        // Just an alert for now, later we'll open a modal to add an appointment
        alert('คลิกวันที่: ' + info.dateStr + ' (ระบบเพิ่มนัดหมายจะมาในส่วนถัดไป)');
    }
});

</script>

<template>
    <Head title="Appointments Calendar" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <h2 class="text-2xl font-bold leading-tight text-teal-700">
                    ตารางนัดหมายผู้ป่วย (Calendar)
                </h2>
                <div class="mt-4 md:mt-0 flex gap-2">
                    <!-- Filter Buttons Mockup -->
                    <span v-for="clinic in clinics" :key="clinic.id" 
                          class="text-white text-xs font-semibold px-3 py-1 rounded-full shadow-sm"
                          :style="{ backgroundColor: clinic.color_code }">
                        {{ clinic.code }}
                    </span>
                    <button class="bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold px-4 py-2 rounded shadow-sm ml-2">
                        + เพิ่มนัดหมายใหม่
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8 bg-slate-50 min-h-screen">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-sm p-6 overflow-x-auto">
                    <!-- FullCalendar Component -->
                    <FullCalendar :options="calendarOptions" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* Adjust FullCalendar generic styles for a cleaner look */
.fc .fc-toolbar-title {
    font-size: 1.25rem !important;
    font-weight: 700;
    color: #374151;
}
.fc .fc-button-primary {
    background-color: #0d9488 !important;
    border-color: #0d9488 !important;
}
.fc .fc-button-primary:hover {
    background-color: #0f766e !important;
    border-color: #0f766e !important;
}
.fc-event {
    cursor: pointer;
}
</style>
