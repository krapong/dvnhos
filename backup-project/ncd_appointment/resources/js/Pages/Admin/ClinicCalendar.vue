<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import { ref, markRaw } from 'vue';

// Force HMR update
const props = defineProps({
    clinics: Array,
    events: Array,
    flash: Object,
    canManageSchedule: Boolean
});

const isModalOpen = ref(false);
const isEditMode = ref(false);
const selectedDate = ref('');
const selectedEventId = ref(null);
const form = ref({
    clinic_id: '',
    type: 'daily'
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
    dateClick: (info) => {
        if (!props.canManageSchedule) {
            return; // Only staff can add clinics
        }

        // Check if date is a holiday
        const isHoliday = props.events.some(e => e.start === info.dateStr && e.extendedProps?.is_holiday);
        if (isHoliday) {
            alert('ไม่สามารถกำหนดคลินิกในวันหยุดราชการได้');
            return;
        }

        const dateObj = new Date(info.dateStr);
        const dayOfWeek = dateObj.getDay();
        
        if (dayOfWeek === 0 || dayOfWeek === 6) {
            if (!confirm('คุณกำลังจะเพิ่มคลินิกในวันหยุดเสาร์-อาทิตย์ ยืนยันหรือไม่?')) {
                return;
            }
        }

        // Date clicked (empty day)
        selectedDate.value = info.dateStr;
        isEditMode.value = false;
        form.value.clinic_id = '';
        form.value.type = 'daily';
        isModalOpen.value = true;
    },
    eventClick: (info) => {
        if (!props.canManageSchedule) {
            return; // Only staff can edit clinics
        }

        if (info.event.extendedProps?.is_holiday) {
            alert('ไม่สามารถแก้ไขวันหยุดราชการได้');
            return;
        }

        // Event clicked (existing clinic)
        selectedDate.value = info.event.startStr.split('T')[0];
        selectedEventId.value = parseInt(info.event.id);
        form.value.clinic_id = info.event.extendedProps.clinic_id;
        form.value.type = 'daily';
        isEditMode.value = true;
        isModalOpen.value = true;
    }
});

const closeModal = () => {
    isModalOpen.value = false;
};

const saveSchedule = () => {
    router.post(route('appointments.store'), {
        clinic_id: form.value.clinic_id,
        date: selectedDate.value,
        type: form.value.type
    }, {
        onSuccess: () => {
            closeModal();
        },
        preserveScroll: true
    });
};

const deleteSchedule = () => {
    if (confirm('คุณต้องการลบตารางเปิดคลินิกในวันนี้ใช่หรือไม่?')) {
        router.delete(route('appointments.destroy', selectedEventId.value), {
            onSuccess: () => {
                closeModal();
            },
            preserveScroll: true
        });
    }
};

</script>

<template>
    <Head title="จัดตารางวันเปิดคลินิก" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">จัดตารางวันเปิดคลินิก</h2>
                <div class="text-sm text-gray-500 bg-white px-3 py-1 rounded-full border shadow-sm">
                    คลิกที่ปฏิทินเพื่อจัดการ
                </div>
            </div>
        </template>

        <div class="py-8 bg-slate-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="flash?.success" class="mb-4 bg-green-50 border-l-4 border-green-400 p-4 rounded shadow-sm">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700 font-medium">{{ flash.success }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl p-6">
                    <FullCalendar :options="calendarOptions" />
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-teal-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    {{ isEditMode ? 'จัดการตารางเปิดคลินิก' : 'เพิ่มตารางเปิดคลินิก' }}
                                </h3>
                                <div class="mt-2 text-sm text-gray-500">
                                    วันที่เลือก: <span class="font-bold text-gray-700">{{ selectedDate }}</span>
                                </div>
                                
                                <div class="mt-4 space-y-4">
                                    <!-- Clinic Selection -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">เลือกคลินิก</label>
                                        <select v-model="form.clinic_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm rounded-md shadow-sm">
                                            <option value="" disabled>-- กรุณาเลือก --</option>
                                            <option v-for="clinic in clinics" :key="clinic.id" :value="clinic.id">
                                                {{ clinic.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Assignment Type -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">รูปแบบการกำหนด</label>
                                        <div class="space-y-2">
                                            <div class="flex items-center">
                                                <input id="type_daily" v-model="form.type" value="daily" type="radio" class="focus:ring-teal-500 h-4 w-4 text-teal-600 border-gray-300">
                                                <label for="type_daily" class="ml-3 block text-sm font-medium text-gray-700">
                                                    เฉพาะวันนี้ (รายวัน)
                                                </label>
                                            </div>
                                            <div class="flex items-center">
                                                <input id="type_monthly" v-model="form.type" value="monthly" type="radio" class="focus:ring-teal-500 h-4 w-4 text-teal-600 border-gray-300">
                                                <label for="type_monthly" class="ml-3 block text-sm font-medium text-gray-700">
                                                    ทุกวันเดียวกันนี้ในเดือนปัจจุบัน (รายเดือน)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="saveSchedule" :disabled="!form.clinic_id" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-teal-600 text-base font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                            บันทึก
                        </button>
                        <button v-if="isEditMode" type="button" @click="deleteSchedule" class="mt-3 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            ลบตารางวันนี้
                        </button>
                        <button type="button" @click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            ยกเลิก
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
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
    padding: 2px 4px;
    border-radius: 4px;
    margin-bottom: 2px;
}
.fc-daygrid-day-frame {
    cursor: pointer;
}
.fc-daygrid-day-frame:hover {
    background-color: #f8fafc;
}

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
.fc .fc-col-header-cell.fc-day-sun {
    background-color: #fee2e2 !important; /* red-100 */
}
.fc .fc-col-header-cell.fc-day-sun .fc-col-header-cell-cushion {
    color: #b91c1c !important; /* red-700 */
}

/* Monday (Yellow) */
.fc .fc-col-header-cell.fc-day-mon {
    background-color: #fef9c3 !important; /* yellow-100 */
}
.fc .fc-col-header-cell.fc-day-mon .fc-col-header-cell-cushion {
    color: #a16207 !important; /* yellow-700 */
}

/* Tuesday (Pink) */
.fc .fc-col-header-cell.fc-day-tue {
    background-color: #fce7f3 !important; /* pink-100 */
}
.fc .fc-col-header-cell.fc-day-tue .fc-col-header-cell-cushion {
    color: #be185d !important; /* pink-700 */
}

/* Wednesday (Green) */
.fc .fc-col-header-cell.fc-day-wed {
    background-color: #dcfce7 !important; /* green-100 */
}
.fc .fc-col-header-cell.fc-day-wed .fc-col-header-cell-cushion {
    color: #15803d !important; /* green-700 */
}

/* Thursday (Orange) */
.fc .fc-col-header-cell.fc-day-thu {
    background-color: #ffedd5 !important; /* orange-100 */
}
.fc .fc-col-header-cell.fc-day-thu .fc-col-header-cell-cushion {
    color: #c2410c !important; /* orange-700 */
}

/* Friday (Blue) */
.fc .fc-col-header-cell.fc-day-fri {
    background-color: #dbeafe !important; /* blue-100 */
}
.fc .fc-col-header-cell.fc-day-fri .fc-col-header-cell-cushion {
    color: #1d4ed8 !important; /* blue-700 */
}

/* Saturday (Purple) */
.fc .fc-col-header-cell.fc-day-sat {
    background-color: #f3e8ff !important; /* purple-100 */
}
.fc .fc-col-header-cell.fc-day-sat .fc-col-header-cell-cushion {
    color: #7e22ce !important; /* purple-700 */
}

/* Weekend Body Cells */
.fc .fc-daygrid-day.fc-day-sat, .fc .fc-daygrid-day.fc-day-sun {
    background-color: #f3f4f6 !important; /* gray-100 */
}
.fc .fc-daygrid-day.fc-day-sat .fc-daygrid-day-number, .fc .fc-daygrid-day.fc-day-sun .fc-daygrid-day-number {
    color: #dc2626 !important; /* red-600 */
    font-weight: bold;
}
.fc .fc-daygrid-day.fc-day-sat .fc-daygrid-day-top, .fc .fc-daygrid-day.fc-day-sun .fc-daygrid-day-top {
    opacity: 0.8;
}
</style>
