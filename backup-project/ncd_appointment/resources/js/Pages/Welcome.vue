<script setup>
import { Head, Link } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import { ref, markRaw } from 'vue';

const props = defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
    clinics: {
        type: Array,
        default: () => []
    },
    events: {
        type: Array,
        default: () => []
    }
});

const calendarOptions = ref({
    plugins: [markRaw(dayGridPlugin), markRaw(interactionPlugin)],
    initialView: 'dayGridMonth',
    events: props.events,
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth'
    },
    buttonText: {
        today: 'วันนี้',
        month: 'เดือน',
        week: 'สัปดาห์',
        day: 'วัน',
        list: 'รายการ'
    },
    locale: 'th',
    eventClick: (info) => {
        if (info.event.extendedProps?.is_holiday) {
            alert(`วันหยุดราชการ: ${info.event.title}`);
        } else {
            alert(`คลินิก: ${info.event.title}`);
        }
    }
});
</script>

<template>
    <Head title="ตารางคลินิกโรงพยาบาล" />
    <div class="min-h-screen bg-slate-50">
        <!-- Top Navigation -->
        <nav class="bg-teal-700 shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <span class="text-2xl font-bold text-white tracking-tight">
                            NCD System
                        </span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <Link
                            v-if="$page.props.auth?.user"
                            :href="route('dashboard')"
                            class="text-white hover:text-teal-100 font-semibold transition"
                        >
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link
                                v-if="canLogin"
                                :href="route('login')"
                                class="text-white hover:text-teal-100 font-semibold transition"
                            >
                                สำหรับเจ้าหน้าที่ (Log in)
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-8 text-center">
                    <h1 class="text-4xl font-extrabold text-teal-800 tracking-tight sm:text-5xl">
                        ตารางเปิดบริการคลินิกเฉพาะโรค
                    </h1>
                    <p class="mt-4 text-xl text-gray-500">
                        ตรวจสอบวันที่เปิดให้บริการของแต่ละคลินิกได้จากปฏิทินด้านล่าง
                    </p>
                </div>

                <!-- Clinic Legend -->
                <div class="mb-6 flex flex-wrap justify-center gap-3">
                    <div v-for="clinic in clinics" :key="clinic.id" 
                         class="flex items-center bg-white px-4 py-2 rounded-full shadow-sm border border-gray-100">
                        <span class="w-4 h-4 rounded-full mr-2" :style="{ backgroundColor: clinic.color_code }"></span>
                        <span class="text-sm font-medium text-gray-700">{{ clinic.name }}</span>
                    </div>
                    <div class="flex items-center bg-white px-4 py-2 rounded-full shadow-sm border border-gray-100">
                        <span class="w-4 h-4 rounded-full mr-2 bg-red-200"></span>
                        <span class="text-sm font-medium text-gray-700">วันหยุดราชการ</span>
                    </div>
                </div>

                <!-- Calendar -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100">
                    <div class="p-6">
                        <FullCalendar :options="calendarOptions" />
                    </div>
                </div>
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="mt-auto py-8 text-center text-sm text-gray-500">
            NCD System &copy; {{ new Date().getFullYear() }} | Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})
        </footer>
    </div>
</template>

<style>
/* Base Day Header Styles */
.fc .fc-col-header-cell {
    padding: 8px 0;
}
.fc .fc-col-header-cell-cushion {
    font-weight: 700;
    font-size: 1rem;
    padding: 4px 8px !important;
    text-decoration: none !important;
}

/* Sunday (Red) */
.fc .fc-col-header-cell.fc-day-sun { background-color: #fee2e2 !important; }
.fc .fc-col-header-cell.fc-day-sun .fc-col-header-cell-cushion { color: #b91c1c !important; }

/* Monday (Yellow) */
.fc .fc-col-header-cell.fc-day-mon { background-color: #fef9c3 !important; }
.fc .fc-col-header-cell.fc-day-mon .fc-col-header-cell-cushion { color: #a16207 !important; }

/* Tuesday (Pink) */
.fc .fc-col-header-cell.fc-day-tue { background-color: #fce7f3 !important; }
.fc .fc-col-header-cell.fc-day-tue .fc-col-header-cell-cushion { color: #be185d !important; }

/* Wednesday (Green) */
.fc .fc-col-header-cell.fc-day-wed { background-color: #dcfce7 !important; }
.fc .fc-col-header-cell.fc-day-wed .fc-col-header-cell-cushion { color: #15803d !important; }

/* Thursday (Orange) */
.fc .fc-col-header-cell.fc-day-thu { background-color: #ffedd5 !important; }
.fc .fc-col-header-cell.fc-day-thu .fc-col-header-cell-cushion { color: #c2410c !important; }

/* Friday (Blue) */
.fc .fc-col-header-cell.fc-day-fri { background-color: #dbeafe !important; }
.fc .fc-col-header-cell.fc-day-fri .fc-col-header-cell-cushion { color: #1d4ed8 !important; }

/* Saturday (Purple) */
.fc .fc-col-header-cell.fc-day-sat { background-color: #f3e8ff !important; }
.fc .fc-col-header-cell.fc-day-sat .fc-col-header-cell-cushion { color: #7e22ce !important; }

/* General FullCalendar Styles */
.fc .fc-toolbar-title {
    font-size: 1.5rem !important;
    font-weight: 800;
    color: #115e59;
}
.fc .fc-button-primary {
    background-color: #0d9488 !important;
    border-color: #0d9488 !important;
    font-weight: 600;
}
.fc .fc-button-primary:hover {
    background-color: #0f766e !important;
    border-color: #0f766e !important;
}
.fc-event {
    cursor: pointer;
    padding: 3px 6px;
    border-radius: 6px;
    margin-bottom: 3px;
    font-weight: 600;
    border: none;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
.fc-daygrid-day-frame:hover {
    background-color: #f8fafc;
}
</style>
