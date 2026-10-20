<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    clinics: Array,
    filters: Object,
    stats: Object,
    appointments: Array
});

const filters = ref({
    start_date: props.filters.start_date || new Date().toISOString().split('T')[0],
    end_date: props.filters.end_date || new Date().toISOString().split('T')[0],
    clinic_code: props.filters.clinic_code || props.clinics[0]?.code || '',
    status: '', // New status filter
});

const applyFilters = () => {
    router.get(route('appointments.daily'), {
        start_date: filters.value.start_date,
        end_date: filters.value.end_date,
        clinic_code: filters.value.clinic_code
    }, { preserveState: true, replace: true });
};

const sortKey = ref('');
const sortOrder = ref(1); // 1 for asc, -1 for desc

const sortBy = (key) => {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value * -1;
    } else {
        sortKey.value = key;
        sortOrder.value = 1;
    }
};

const filteredAppointments = computed(() => {
    let result = props.appointments;
    
    if (filters.value.status) {
        result = result.filter(a => a.status === filters.value.status);
    }
    
    if (sortKey.value) {
        result = [...result].sort((a, b) => {
            let valA = a[sortKey.value] || '';
            let valB = b[sortKey.value] || '';
            
            if (valA < valB) return -1 * sortOrder.value;
            if (valA > valB) return 1 * sortOrder.value;
            return 0;
        });
    }
    
    return result;
});

const itemsPerPage = ref(30);
const currentPage = ref(1);

watch([filters, sortKey, sortOrder, itemsPerPage], () => {
    currentPage.value = 1;
}, { deep: true });

const totalPages = computed(() => {
    if (itemsPerPage.value === 'all') return 1;
    return Math.ceil(filteredAppointments.value.length / itemsPerPage.value) || 1;
});

const paginatedAppointments = computed(() => {
    if (itemsPerPage.value === 'all') return filteredAppointments.value;
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredAppointments.value.slice(start, end);
});

const getStatusBadgeClass = (color) => {
    switch (color) {
        case 'green': return 'bg-green-100 text-green-800 border border-green-200';
        case 'red': return 'bg-red-100 text-red-800 border border-red-200';
        case 'orange': return 'bg-orange-100 text-orange-800 border border-orange-200';
        case 'gray': return 'bg-gray-100 text-gray-800 border border-gray-200';
        case 'blue': return 'bg-blue-100 text-blue-800 border border-blue-200';
        default: return 'bg-gray-100 text-gray-800 border border-gray-200';
    }
};
</script>

<template>
    <Head title="รายชื่อผู้รับบริการ" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">รายชื่อผู้รับบริการ</h2>
        </template>

        <div class="py-8 bg-slate-50 min-h-screen">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                
                <!-- Filter Section -->
                <div class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-md transition-shadow duration-300 border border-gray-100">
                    <form @submit.prevent="applyFilters" class="flex flex-col md:flex-row gap-4 items-end">
                        <div class="w-full md:w-1/4 group">
                            <label class="block text-sm font-medium text-gray-700 mb-1 transition-colors group-focus-within:text-teal-600">วันที่เริ่มต้น</label>
                            <input type="date" v-model="filters.start_date" class="border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-xl shadow-sm w-full transition-all duration-300 hover:border-teal-400">
                        </div>
                        <div class="w-full md:w-1/4 group">
                            <label class="block text-sm font-medium text-gray-700 mb-1 transition-colors group-focus-within:text-teal-600">ถึงวันที่</label>
                            <input type="date" v-model="filters.end_date" class="border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-xl shadow-sm w-full transition-all duration-300 hover:border-teal-400">
                        </div>
                        <div class="w-full md:w-1/4 group">
                            <label class="block text-sm font-medium text-gray-700 mb-1 transition-colors group-focus-within:text-teal-600">คลินิก</label>
                            <select id="clinic" v-model="filters.clinic_code" @change="applyFilters"
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm rounded-xl shadow-sm transition-all duration-300 hover:border-teal-400">
                                <option v-for="clinic in clinics" :key="clinic.code" :value="clinic.code">
                                    {{ clinic.name }}
                                </option>
                            </select>
                        </div>
                        <div class="w-full md:w-1/4 group">
                            <label class="block text-sm font-medium text-gray-700 mb-1 transition-colors group-focus-within:text-teal-600">สถานะ</label>
                            <select id="status_filter" v-model="filters.status"
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm rounded-xl shadow-sm transition-all duration-300 hover:border-teal-400">
                                <option value="">ทั้งหมด</option>
                                <option value="มาตามนัด">มาตามนัด</option>
                                <option value="ผิดนัด">ผิดนัด</option>
                                <option value="มาไม่ตรงนัด">มาไม่ตรงนัด</option>
                                <option value="รอรับบริการ">รอรับบริการ</option>
                            </select>
                        </div>
                        <div class="w-full md:w-auto">
                            <button type="submit" class="w-full md:w-auto bg-gradient-to-r from-teal-500 to-emerald-600 hover:from-teal-600 hover:to-emerald-700 text-white font-semibold py-2 px-6 rounded-xl transition-all duration-300 transform hover:-translate-y-1 hover:shadow-lg shadow-sm flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                ค้นหา
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Stats Summary -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="bg-gradient-to-br from-white to-indigo-50/50 rounded-2xl shadow-sm p-5 border-l-4 border-indigo-500 transition-all duration-300 transform hover:-translate-y-1.5 hover:shadow-lg hover:border-l-8 cursor-default">
                        <div class="text-sm text-indigo-500/80 font-bold mb-1 uppercase tracking-wide">นัดหมายทั้งหมด</div>
                        <div class="text-4xl font-extrabold text-indigo-700">{{ stats.total }} <span class="text-base font-medium text-indigo-400">ราย</span></div>
                    </div>
                    <div class="bg-gradient-to-br from-white to-yellow-50/50 rounded-2xl shadow-sm p-5 border-l-4 border-yellow-500 transition-all duration-300 transform hover:-translate-y-1.5 hover:shadow-lg hover:border-l-8 cursor-default">
                        <div class="text-sm text-yellow-600/80 font-bold mb-1 uppercase tracking-wide">รอรับบริการ</div>
                        <div class="text-4xl font-extrabold text-yellow-600">{{ stats.waiting ?? 0 }} <span class="text-base font-medium text-yellow-500">ราย</span></div>
                    </div>
                    <div class="bg-gradient-to-br from-white to-emerald-50/50 rounded-2xl shadow-sm p-5 border-l-4 border-emerald-500 transition-all duration-300 transform hover:-translate-y-1.5 hover:shadow-lg hover:border-l-8 cursor-default">
                        <div class="text-sm text-emerald-600/80 font-bold mb-1 uppercase tracking-wide">มาแล้ว / ตรวจเสร็จ</div>
                        <div class="text-4xl font-extrabold text-emerald-600">{{ stats.arrived }} <span class="text-base font-medium text-emerald-500">ราย</span></div>
                    </div>
                    <div class="bg-gradient-to-br from-white to-gray-50/80 rounded-2xl shadow-sm p-5 border-l-4 border-gray-400 transition-all duration-300 transform hover:-translate-y-1.5 hover:shadow-lg hover:border-l-8 cursor-default">
                        <div class="text-sm text-gray-500 font-bold mb-1 uppercase tracking-wide">ขาดนัด</div>
                        <div class="text-4xl font-extrabold text-gray-700">{{ stats.no_show }} <span class="text-base font-medium text-gray-400">ราย</span></div>
                    </div>
                    <div class="bg-gradient-to-br from-white to-rose-50/50 rounded-2xl shadow-sm p-5 border-l-4 border-rose-500 transition-all duration-300 transform hover:-translate-y-1.5 hover:shadow-lg hover:border-l-8 cursor-default">
                        <div class="text-sm text-rose-500/80 font-bold mb-1 uppercase tracking-wide">มาไม่ตรงนัด</div>
                        <div class="text-4xl font-extrabold text-rose-600">{{ stats.wrong_date }} <span class="text-base font-medium text-rose-400">ราย</span></div>
                    </div>
                </div>

                <!-- Appointments Table -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">รายชื่อผู้ป่วย</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div class="flex items-center space-x-1 cursor-pointer" @click="sortBy('hn')">
                                            <span>HN</span>
                                            <svg v-if="sortKey === 'hn'" :class="{'rotate-180': sortOrder === -1}" class="w-4 h-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div class="flex items-center space-x-1 cursor-pointer" @click="sortBy('patient_name')">
                                            <span>ชื่อ-สกุล</span>
                                            <svg v-if="sortKey === 'patient_name'" :class="{'rotate-180': sortOrder === -1}" class="w-4 h-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div class="flex items-center space-x-1 cursor-pointer" @click="sortBy('arrival_time')">
                                            <span>เวลามาถึง</span>
                                            <svg v-if="sortKey === 'arrival_time'" :class="{'rotate-180': sortOrder === -1}" class="w-4 h-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่มาครั้งสุดท้ายของคลินิก</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่นัดจริง</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div class="flex items-center justify-center space-x-1 cursor-pointer" @click="sortBy('status')">
                                            <span>สถานะรับบริการ</span>
                                            <svg v-if="sortKey === 'status'" :class="{'rotate-180': sortOrder === -1}" class="w-4 h-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div class="flex items-center justify-center space-x-1 cursor-pointer" @click="sortBy('lab_status')">
                                            <span>สถานะแลป</span>
                                            <svg v-if="sortKey === 'lab_status'" :class="{'rotate-180': sortOrder === -1}" class="w-4 h-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div class="flex items-center justify-center space-x-1 cursor-pointer" @click="sortBy('xray_status')">
                                            <span>สถานะเอกซเรย์</span>
                                            <svg v-if="sortKey === 'xray_status'" :class="{'rotate-180': sortOrder === -1}" class="w-4 h-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        จัดการ
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="appt in paginatedAppointments" :key="appt.id" class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ appt.hn }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ appt.patient_name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <span v-if="appt.arrival_time !== '-'" class="px-2 py-1 bg-blue-50 text-blue-700 rounded-md font-semibold">{{ appt.arrival_time }}</span>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ appt.last_visit_date }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ appt.appointment_date }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span :class="[
                                            'px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border',
                                            getStatusBadgeClass(appt.status_color)
                                        ]">
                                            {{ appt.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span v-if="appt.lab_status !== 'ไม่มีแลป'" :class="[
                                            'px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border',
                                            getStatusBadgeClass(appt.lab_status_color)
                                        ]">
                                            {{ appt.lab_status }}
                                        </span>
                                        <span v-else class="text-xs text-gray-400 font-medium">-</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span v-if="appt.xray_status !== 'ไม่มี X-Ray'" :class="[
                                            'px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border',
                                            getStatusBadgeClass(appt.xray_status_color)
                                        ]">
                                            {{ appt.xray_status }}
                                        </span>
                                        <span v-else class="text-xs text-gray-400 font-medium">-</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <!-- Actions -->
                                    </td>
                                </tr>
                                <tr v-if="filteredAppointments.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                            <p class="text-lg font-medium">ไม่พบรายการนัดหมาย</p>
                                            <p class="text-sm">สำหรับวันที่และคลินิกที่เลือก</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination Controls -->
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center text-sm text-gray-700">
                            <span>แสดง</span>
                            <select v-model="itemsPerPage" class="mx-2 border-gray-300 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm rounded-md shadow-sm py-1">
                                <option :value="30">30</option>
                                <option :value="100">100</option>
                                <option :value="200">200</option>
                                <option value="all">ทั้งหมด</option>
                            </select>
                            <span>รายการ (รวม {{ filteredAppointments.length }} รายการ)</span>
                        </div>
                        
                        <div v-if="itemsPerPage !== 'all' && totalPages > 1" class="flex items-center space-x-2">
                            <button @click="currentPage--" :disabled="currentPage === 1" 
                                class="px-3 py-1 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm">
                                ก่อนหน้า
                            </button>
                            <span class="text-sm text-gray-700 font-medium">หน้า {{ currentPage }} / {{ totalPages }}</span>
                            <button @click="currentPage++" :disabled="currentPage === totalPages" 
                                class="px-3 py-1 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm">
                                ถัดไป
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
