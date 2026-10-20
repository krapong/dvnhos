<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { Bar, Pie } from 'vue-chartjs';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Tooltip,
} from 'chart.js';

ChartJS.register(ArcElement, BarElement, CategoryScale, Legend, LinearScale, Tooltip);

const props = defineProps({
    stats: Object,
    clinics: Array,
    filters: Object,
    clinic_breakdown: Array,
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const chartColors = ['#0f766e', '#0284c7', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6', '#f97316', '#64748b', '#84cc16', '#ec4899', '#06b6d4', '#a855f7'];
const chartLabels = computed(() => (props.clinic_breakdown || []).map((clinic) => clinic.name));
const appointmentChartData = computed(() => ({
    labels: chartLabels.value,
    datasets: [{
        label: 'นัดหมายทั้งหมด',
        data: (props.clinic_breakdown || []).map((clinic) => clinic.appointments),
        backgroundColor: chartColors,
        borderColor: '#ffffff',
        borderWidth: 2,
    }],
}));
const comparisonChartData = computed(() => ({
    labels: chartLabels.value,
    datasets: [
        {
            label: 'นัดหมายทั้งหมด',
            data: (props.clinic_breakdown || []).map((clinic) => clinic.appointments),
            backgroundColor: '#ccfbf1',
            borderColor: '#0f766e',
            borderWidth: 1,
            borderRadius: 6,
        },
        {
            label: 'ส่งตรวจวันนี้',
            data: (props.clinic_breakdown || []).map((clinic) => clinic.arrived),
            backgroundColor: '#99f6e4',
            borderColor: '#0d9488',
            borderWidth: 1,
            borderRadius: 6,
        },
    ],
}));
const pieChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' } },
};
const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
    plugins: { legend: { position: 'bottom' } },
};

const formatThaiDate = (dateValue) => {
    if (!dateValue) return '-';

    const [year, month, day] = dateValue.split('-').map(Number);
    return new Date(year, month - 1, day).toLocaleDateString('th-TH', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    });
};

const thaiMonths = [
    'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
    'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
];
const thaiWeekdays = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];
const datePickerOpen = ref(false);
const calendarMonth = ref(new Date());

const localIsoDate = (date = new Date()) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const calendarTitle = computed(() => {
    const month = calendarMonth.value.getMonth();
    const year = calendarMonth.value.getFullYear() + 543;
    return `${thaiMonths[month]} ${year}`;
});

const calendarDays = computed(() => {
    const year = calendarMonth.value.getFullYear();
    const month = calendarMonth.value.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const days = Array(firstDay).fill(null);

    for (let day = 1; day <= daysInMonth; day += 1) {
        days.push({
            day,
            iso: localIsoDate(new Date(year, month, day)),
        });
    }

    return days;
});

const openDatePicker = () => {
    const [year, month, day] = formFilters.value.date.split('-').map(Number);
    calendarMonth.value = new Date(year, month - 1, day);
    datePickerOpen.value = true;
};

const changeCalendarMonth = (offset) => {
    calendarMonth.value = new Date(
        calendarMonth.value.getFullYear(),
        calendarMonth.value.getMonth() + offset,
        1
    );
};

const selectCalendarDate = (dateValue) => {
    formFilters.value.date = dateValue;
    datePickerOpen.value = false;
    applyFilters();
};

const selectToday = () => selectCalendarDate(localIsoDate());

const formFilters = ref({
    date: props.filters?.date || new Date().toISOString().split('T')[0],
    clinic_code: props.filters?.clinic_code || '',
});

const applyFilters = () => {
    router.get(route('dashboard'), formFilters.value, { 
        preserveState: true, 
        preserveScroll: true,
        onSuccess: () => {
            // Re-fetch details for the currently active status when filters change
            fetchDetails(activeStatus.value);
        }
    });
};

const activeStatus = ref('ทั้งหมด');
const detailData = ref([]);
const isLoadingDetails = ref(false);
const searchQuery = ref('');
const sortColumn = ref('hn');
const sortDirection = ref('asc');

const toggleSort = (column) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
};

const filteredDetailData = computed(() => {
    let data = detailData.value;
    
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        data = data.filter(patient => {
            return (patient.hn && patient.hn.toLowerCase().includes(query)) ||
                   (patient.patient_name && patient.patient_name.toLowerCase().includes(query));
        });
    }
    
    return data.slice().sort((a, b) => {
        let valA = a[sortColumn.value] || '';
        let valB = b[sortColumn.value] || '';

        // Compare ISO dates instead of the displayed Thai date string.
        if (sortColumn.value === 'date' && valA.includes('/') && valB.includes('/')) {
            const toSortableDate = (value) => {
                const [day, month, year] = value.split('/');
                return `${year}-${month}-${day}`;
            };
            valA = toSortableDate(valA);
            valB = toSortableDate(valB);
        }

        valA = String(valA).toLocaleLowerCase('th');
        valB = String(valB).toLocaleLowerCase('th');
        
        if (valA < valB) return sortDirection.value === 'asc' ? -1 : 1;
        if (valA > valB) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });
});

const currentPage = ref(1);
const itemsPerPage = 30;

const totalPages = computed(() => Math.ceil(filteredDetailData.value.length / itemsPerPage));

const paginatedDetailData = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredDetailData.value.slice(start, end);
});

// Reset page when search or sort changes
watch([searchQuery, sortColumn, sortDirection, activeStatus, () => formFilters.value.date, () => formFilters.value.clinic_code], () => {
    currentPage.value = 1;
});

const statusMap = {
    'ทั้งหมด': { label: 'นัดหมายทั้งหมด', color: 'blue', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    'ส่งตรวจในวันนี้': { label: 'จำนวนที่มารับบริการ(ส่งตรวจ)', color: 'green', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
    'ตรวจเสร็จแล้ว': { label: 'ตรวจเสร็จแล้ว', color: 'emerald', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    'ยังไม่มา': { label: 'ยังไม่มา', color: 'orange', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    'มาไม่ตรงนัด': { label: 'มาไม่ตรงนัด', color: 'purple', icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' },
    'ผู้รับบริการรายใหม่': { label: 'ผู้รับบริการรายใหม่', color: 'teal', icon: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z' },
    'ไม่ได้เข้าคลินิก': { label: 'ไม่ได้เข้าคลินิก', color: 'rose', icon: 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' },
};

const getStatusColorClass = (color) => {
    const map = {
        blue: 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100 ring-blue-500',
        emerald: 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100 ring-emerald-500',
        green: 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100 ring-green-500',
        orange: 'bg-orange-50 text-orange-700 border-orange-200 hover:bg-orange-100 ring-orange-500',
        red: 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100 ring-red-500',
        purple: 'bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100 ring-purple-500',
        rose: 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100 ring-rose-500',
        teal: 'bg-teal-50 text-teal-700 border-teal-200 hover:bg-teal-100 ring-teal-500',
    };
    return map[color] || map.blue;
};

const getStatusIconClass = (color) => {
    const map = {
        blue: 'text-blue-500 bg-blue-100',
        emerald: 'text-emerald-500 bg-emerald-100',
        green: 'text-green-500 bg-green-100',
        orange: 'text-orange-500 bg-orange-100',
        red: 'text-red-500 bg-red-100',
        purple: 'text-purple-600 bg-purple-50',
        rose: 'text-rose-600 bg-rose-50',
        teal: 'text-teal-600 bg-teal-50',
    };
    return map[color] || map.blue;
};

const getStatusBadgeClass = (statusText) => {
    if (statusText === 'ตรวจเสร็จแล้ว') return 'bg-emerald-100 text-emerald-800';
    if (['มาตามนัด', 'มารับบริการแล้ว', 'ส่งตรวจในวันนี้'].includes(statusText)) return 'bg-green-100 text-green-800';
    if (statusText === 'รอรับบริการ' || statusText === 'ยังไม่มา') return 'bg-orange-100 text-orange-800';
    if (statusText === 'มาไม่ตรงนัด') return 'bg-purple-100 text-purple-800';
    if (statusText === 'ไม่ได้เข้าคลินิก') return 'bg-rose-100 text-rose-800';
    if (statusText === 'ผู้รับบริการรายใหม่') return 'bg-teal-100 text-teal-800';
    return 'bg-blue-100 text-blue-800';
};

const fetchDetails = async (status) => {
    activeStatus.value = status;
    isLoadingDetails.value = true;
    
    try {
        const response = await axios.get(route('dashboard.details'), {
            params: {
                status: status,
                date: formFilters.value.date,
                clinic_code: formFilters.value.clinic_code,
            }
        });
        detailData.value = response.data.data;
    } catch (error) {
        console.error("Error fetching details", error);
        detailData.value = [];
    } finally {
        isLoadingDetails.value = false;
    }
};

const exportExcel = () => {
    const url = new URL(route('dashboard.export'), window.location.origin);
    url.searchParams.append('status', activeStatus.value);
    url.searchParams.append('date', formFilters.value.date);
    url.searchParams.append('clinic_code', formFilters.value.clinic_code);
    
    window.open(url.toString(), '_blank');
};

onMounted(() => {
    fetchDetails('ทั้งหมด');
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <h2 class="text-2xl font-bold leading-tight text-gray-800">
                        Dashboard
                    </h2>
                    <p class="text-gray-500 mt-1 text-sm">ตรวจสอบสถานะและสถิติผู้ป่วย NCD ประจำวัน</p>
                </div>
                <div class="mt-4 md:mt-0 flex gap-3">
                        <span class="bg-blue-50 text-blue-800 text-sm font-semibold px-4 py-2 rounded-xl border border-blue-100">
                        📅 {{ formatThaiDate(formFilters.date) }}
                    </span>
                </div>
            </div>
        </template>

        <div class="py-12 bg-slate-50 min-h-screen">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                
                <!-- Filters Section -->
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                    <form @submit.prevent="applyFilters" class="flex flex-col md:flex-row gap-4 items-end">
                        <div class="w-full md:w-1/2 group">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5 transition-colors group-focus-within:text-teal-600">วันที่ (Date)</label>
                            <div class="relative w-full">
                                <button type="button" @click="openDatePicker"
                                    class="w-full border border-gray-300 bg-white rounded-xl shadow-sm transition-all duration-300 hover:border-teal-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 py-2.5 px-3.5 text-left text-gray-700">
                                    {{ formatThaiDate(formFilters.date) }}
                                </button>

                                <div v-if="datePickerOpen" class="absolute z-30 mt-2 w-full max-w-sm rounded-2xl border border-gray-200 bg-white p-4 shadow-xl">
                                    <div class="mb-3 flex items-center justify-between">
                                        <button type="button" @click="changeCalendarMonth(-1)" class="rounded-lg px-3 py-1 text-lg text-gray-500 hover:bg-gray-100" aria-label="เดือนก่อนหน้า">‹</button>
                                        <div class="font-semibold text-gray-800">{{ calendarTitle }}</div>
                                        <button type="button" @click="changeCalendarMonth(1)" class="rounded-lg px-3 py-1 text-lg text-gray-500 hover:bg-gray-100" aria-label="เดือนถัดไป">›</button>
                                    </div>
                                    <div class="mb-2 grid grid-cols-7 text-center text-xs font-semibold text-gray-400">
                                        <span v-for="weekday in thaiWeekdays" :key="weekday">{{ weekday }}</span>
                                    </div>
                                    <div class="grid grid-cols-7 gap-1 text-center">
                                        <span v-for="(day, index) in calendarDays" :key="day?.iso || `empty-${index}`" class="flex h-9 items-center justify-center">
                                            <button v-if="day" type="button" @click="selectCalendarDate(day.iso)"
                                                class="h-9 w-9 rounded-full text-sm transition-colors"
                                                :class="formFilters.date === day.iso ? 'bg-teal-600 font-semibold text-white' : 'text-gray-700 hover:bg-teal-50 hover:text-teal-700'">
                                                {{ day.day }}
                                            </button>
                                        </span>
                                    </div>
                                    <div class="mt-3 border-t border-gray-100 pt-3 text-center">
                                        <button type="button" @click="selectToday" class="text-sm font-semibold text-teal-600 hover:text-teal-700">วันนี้</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/2 group">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5 transition-colors group-focus-within:text-teal-600">คลินิก (Clinic)</label>
                            <select v-model="formFilters.clinic_code" @change="applyFilters"
                                class="w-full border-gray-300 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm rounded-xl shadow-sm transition-all duration-300 hover:border-teal-400 py-2.5">
                                <option value="">รวมทุกคลินิก</option>
                                <option v-for="clinic in clinics" :key="clinic.code" :value="clinic.code">
                                    {{ clinic.name }}
                                </option>
                            </select>
                        </div>
                    </form>
                </div>

                <!-- Stats Cards Grid -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <!-- Total -->
                    <button @click="fetchDetails('ทั้งหมด')" 
                        class="relative flex flex-col p-4 rounded-2xl border-2 transition-all duration-200 text-left focus:outline-none focus:ring-2 focus:ring-offset-2"
                        :class="[getStatusColorClass('blue'), activeStatus === 'ทั้งหมด' ? 'ring-2 ring-offset-2 shadow-md scale-105 z-10' : 'border-transparent shadow-sm hover:shadow-md']">
                        <div class="flex items-center justify-between w-full mb-3">
                            <div class="p-2 rounded-xl" :class="getStatusIconClass('blue')">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="`<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='${statusMap['ทั้งหมด'].icon}'></path>`"></svg>
                            </div>
                        </div>
                        <h3 class="text-sm font-bold uppercase tracking-wider opacity-80">{{ statusMap['ทั้งหมด'].label }}</h3>
                        <p class="text-3xl font-extrabold mt-1">{{ stats.total }} <span class="text-sm font-medium opacity-70">คน</span></p>
                    </button>
                    
                    <!-- Arrived Today (ส่งตรวจในวันนี้) -->
                    <button @click="fetchDetails('ส่งตรวจในวันนี้')" 
                        class="relative flex flex-col p-4 rounded-2xl border-2 transition-all duration-200 text-left focus:outline-none focus:ring-2 focus:ring-offset-2"
                        :class="[getStatusColorClass('green'), activeStatus === 'ส่งตรวจในวันนี้' ? 'ring-2 ring-offset-2 shadow-md scale-105 z-10' : 'border-transparent shadow-sm hover:shadow-md']">
                        <div class="flex items-center justify-between w-full mb-3">
                            <div class="p-2 rounded-xl" :class="getStatusIconClass('green')">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="`<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='${statusMap['ส่งตรวจในวันนี้'].icon}'></path>`"></svg>
                            </div>
                        </div>
                        <h3 class="text-sm font-bold uppercase tracking-wider opacity-80">{{ statusMap['ส่งตรวจในวันนี้'].label }}</h3>
                        <p class="text-3xl font-extrabold mt-1">{{ stats.arrived_today }} <span class="text-sm font-medium opacity-70">คน</span></p>
                    </button>

                    <!-- Completed -->
                    <button @click="fetchDetails('ตรวจเสร็จแล้ว')" 
                        class="relative flex flex-col p-4 rounded-2xl border-2 transition-all duration-200 text-left focus:outline-none focus:ring-2 focus:ring-offset-2"
                        :class="[getStatusColorClass('emerald'), activeStatus === 'ตรวจเสร็จแล้ว' ? 'ring-2 ring-offset-2 shadow-md scale-105 z-10' : 'border-transparent shadow-sm hover:shadow-md']">
                        <div class="flex items-center justify-between w-full mb-3">
                            <div class="p-2 rounded-xl" :class="getStatusIconClass('emerald')">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="`<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='${statusMap['ตรวจเสร็จแล้ว'].icon}'></path>`"></svg>
                            </div>
                        </div>
                        <h3 class="text-sm font-bold uppercase tracking-wider opacity-80">{{ statusMap['ตรวจเสร็จแล้ว'].label }}</h3>
                        <p class="text-3xl font-extrabold mt-1">{{ stats.completed }} <span class="text-sm font-medium opacity-70">คน</span></p>
                    </button>

                    <!-- Waiting -->
                    <button @click="fetchDetails('ยังไม่มา')" 
                        class="relative flex flex-col p-4 rounded-2xl border-2 transition-all duration-200 text-left focus:outline-none focus:ring-2 focus:ring-offset-2"
                        :class="[getStatusColorClass('orange'), activeStatus === 'ยังไม่มา' ? 'ring-2 ring-offset-2 shadow-md scale-105 z-10' : 'border-transparent shadow-sm hover:shadow-md']">
                        <div class="flex items-center justify-between w-full mb-3">
                            <div class="p-2 rounded-xl" :class="getStatusIconClass('orange')">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="`<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='${statusMap['ยังไม่มา'].icon}'></path>`"></svg>
                            </div>
                        </div>
                        <h3 class="text-sm font-bold uppercase tracking-wider opacity-80">{{ statusMap['ยังไม่มา'].label }}</h3>
                        <p class="text-3xl font-extrabold mt-1">{{ stats.waiting }} <span class="text-sm font-medium opacity-70">คน</span></p>
                    </button>

                    <!-- Didnt Enter Clinic -->
                    <button @click="fetchDetails('ไม่ได้เข้าคลินิก')" 
                        class="col-span-1 relative flex flex-col p-4 rounded-2xl border-2 transition-all duration-200 text-left focus:outline-none focus:ring-2 focus:ring-offset-2"
                        :class="[getStatusColorClass('rose'), activeStatus === 'ไม่ได้เข้าคลินิก' ? 'ring-2 ring-offset-2 shadow-md scale-105 z-10' : 'border-transparent shadow-sm hover:shadow-md']">
                        <div class="flex items-center justify-between w-full mb-3">
                            <div class="p-2 rounded-xl" :class="getStatusIconClass('rose')">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="`<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='${statusMap['ไม่ได้เข้าคลินิก'].icon}'></path>`"></svg>
                            </div>
                        </div>
                        <h3 class="text-xs font-bold uppercase tracking-wider opacity-80" style="font-size: 11px;">{{ statusMap['ไม่ได้เข้าคลินิก'].label }}</h3>
                        <p class="text-3xl font-extrabold mt-1">{{ stats.didnt_enter }} <span class="text-sm font-medium opacity-70">คน</span></p>
                    </button>

                    <!-- New Patient (ผู้รับบริการรายใหม่) -->
                    <button @click="fetchDetails('ผู้รับบริการรายใหม่')" 
                        class="col-span-1 relative flex flex-col p-4 rounded-2xl border-2 transition-all duration-200 text-left focus:outline-none focus:ring-2 focus:ring-offset-2"
                        :class="[getStatusColorClass('teal'), activeStatus === 'ผู้รับบริการรายใหม่' ? 'ring-2 ring-offset-2 shadow-md scale-105 z-10' : 'border-transparent shadow-sm hover:shadow-md']">
                        <div class="flex items-center justify-between w-full mb-3">
                            <div class="p-2 rounded-xl" :class="getStatusIconClass('teal')">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="`<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='${statusMap['ผู้รับบริการรายใหม่'].icon}'></path>`"></svg>
                            </div>
                        </div>
                        <h3 class="text-xs font-bold uppercase tracking-wider opacity-80" style="font-size: 11px;">{{ statusMap['ผู้รับบริการรายใหม่'].label }}</h3>
                        <p class="text-3xl font-extrabold mt-1">{{ stats.new_patient || 0 }} <span class="text-sm font-medium opacity-70">คน</span></p>
                    </button>
                </div>

                <!-- Patient List Section -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mt-6 transition-all">
                    <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center bg-gray-50/50">
                        <h2 class="text-xl font-bold text-gray-900 flex items-center">
                            รายชื่อผู้ป่วย: 
                            <span class="ml-2 px-3 py-1 rounded-xl text-sm shadow-sm font-semibold border"
                                :class="getStatusBadgeClass(activeStatus)">
                                {{ activeStatus }}
                            </span>
                            <span class="ml-2 text-gray-500 text-base font-normal">({{ filteredDetailData.length }} คน)</span>
                        </h2>
                        
                        <div class="flex flex-col sm:flex-row items-center gap-3 mt-3 sm:mt-0 w-full sm:w-auto">
                            <!-- Search Input -->
                            <div class="relative w-full sm:w-64">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input v-model="searchQuery" type="text" placeholder="ค้นหา HN, ชื่อ-สกุล..." 
                                    class="pl-10 w-full rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 text-sm shadow-sm transition-all" />
                            </div>
                            
                            <button @click="exportExcel" :disabled="isLoadingDetails || detailData.length === 0" 
                                class="w-full sm:w-auto px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-all duration-300 transform hover:-translate-y-0.5 flex justify-center items-center shadow-sm text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                ส่งออก Excel
                            </button>
                        </div>
                    </div>
                    
                    <div v-if="isLoadingDetails" class="flex justify-center p-20">
                        <svg class="animate-spin h-10 w-10 text-teal-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-white">
                                <tr>
                                    <th @click="toggleSort('hn')" scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-24 cursor-pointer hover:bg-gray-50 transition-colors group">
                                        <div class="flex items-center">
                                            HN
                                            <span class="ml-1" :class="sortColumn === 'hn' ? 'text-teal-600' : 'text-gray-300 group-hover:text-gray-400'">
                                                <svg v-if="sortColumn === 'hn' && sortDirection === 'desc'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                            </span>
                                        </div>
                                    </th>
                                    <th @click="toggleSort('patient_name')" scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-50 transition-colors group">
                                        <div class="flex items-center">
                                            ชื่อ-สกุล
                                            <span class="ml-1" :class="sortColumn === 'patient_name' ? 'text-teal-600' : 'text-gray-300 group-hover:text-gray-400'">
                                                <svg v-if="sortColumn === 'patient_name' && sortDirection === 'desc'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                            </span>
                                        </div>
                                    </th>
                                    <th @click="toggleSort('clinic_name')" scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-50 transition-colors group">
                                        <div class="flex items-center">
                                            คลินิก
                                            <span class="ml-1" :class="sortColumn === 'clinic_name' ? 'text-teal-600' : 'text-gray-300 group-hover:text-gray-400'">
                                                <svg v-if="sortColumn === 'clinic_name' && sortDirection === 'desc'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                            </span>
                                        </div>
                                    </th>
                                    <th @click="toggleSort('date')" scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-50 transition-colors group">
                                        <div class="flex items-center">
                                            {{ activeStatus === 'มาไม่ตรงนัด' ? 'วันที่นัดหมายคลินิก' : 'วันที่นัดหมาย' }}
                                            <span class="ml-1" :class="sortColumn === 'date' ? 'text-teal-600' : 'text-gray-300 group-hover:text-gray-400'">
                                                <svg v-if="sortColumn === 'date' && sortDirection === 'desc'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                            </span>
                                        </div>
                                    </th>
                                    <th @click="toggleSort('status')" scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-50 transition-colors group">
                                        <div class="flex items-center">
                                            สถานะ
                                            <span class="ml-1" :class="sortColumn === 'status' ? 'text-teal-600' : 'text-gray-300 group-hover:text-gray-400'">
                                                <svg v-if="sortColumn === 'status' && sortDirection === 'desc'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                            </span>
                                        </div>
                                    </th>
                                    <th @click="toggleSort('actual_department')" v-if="activeStatus === 'มาไม่ตรงนัด' || activeStatus === 'ไม่ได้เข้าคลินิก' || activeStatus === 'ทั้งหมด'" scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-50 transition-colors group">
                                        <div class="flex items-center">
                                            จุดบริการที่ไป
                                            <span class="ml-1" :class="sortColumn === 'actual_department' ? 'text-teal-600' : 'text-gray-300 group-hover:text-gray-400'">
                                                <svg v-if="sortColumn === 'actual_department' && sortDirection === 'desc'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                            </span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="patient in paginatedDetailData" :key="patient.id" class="hover:bg-slate-50 transition-colors group">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 group-hover:text-teal-700 transition-colors">{{ patient.hn }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 font-medium">{{ patient.patient_name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                        <div class="flex items-center">
                                            <div class="w-1.5 h-1.5 rounded-full bg-teal-500 mr-2"></div>
                                            {{ patient.clinic_name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-mono">{{ patient.date }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-3 py-1 text-xs rounded-full font-semibold border shadow-sm"
                                            :class="getStatusBadgeClass(patient.status)">
                                            {{ patient.status }}
                                        </span>
                                    </td>
                                    <td v-if="activeStatus === 'มาไม่ตรงนัด' || activeStatus === 'ไม่ได้เข้าคลินิก' || activeStatus === 'ทั้งหมด'" class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                        <span v-if="patient.actual_department && patient.actual_department !== '-'" class="px-2 py-1 bg-slate-100 rounded text-xs">
                                            {{ patient.actual_department }}
                                        </span>
                                        <span v-else class="text-gray-300">-</span>
                                    </td>
                                </tr>
                                <tr v-if="filteredDetailData.length === 0">
                                    <td :colspan="(activeStatus === 'มาไม่ตรงนัด' || activeStatus === 'ไม่ได้เข้าคลินิก' || activeStatus === 'ทั้งหมด') ? 6 : 5" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-400">
                                            <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                            <p class="text-base font-medium text-gray-500">ไม่มีข้อมูลผู้ป่วยในสถานะนี้</p>
                                            <p class="text-sm mt-1">ลองเปลี่ยนวันที่หรือคลินิกที่ต้องการค้นหา</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination Controls -->
                    <div v-if="totalPages > 1" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <div class="text-sm text-gray-500 font-medium">
                            แสดง {{(currentPage - 1) * itemsPerPage + 1}} ถึง {{Math.min(currentPage * itemsPerPage, filteredDetailData.length)}} จากทั้งหมด {{filteredDetailData.length}} รายการ
                        </div>
                        <div class="flex items-center gap-1">
                            <button @click="currentPage--" :disabled="currentPage === 1"
                                class="px-3 py-1.5 border border-gray-200 rounded-lg bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors disabled:opacity-40 disabled:cursor-not-allowed shadow-sm text-sm font-medium">
                                ก่อนหน้า
                            </button>
                            
                            <div class="flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg shadow-sm mx-1">
                                หน้า {{currentPage}} / {{totalPages}}
                            </div>
                            
                            <button @click="currentPage++" :disabled="currentPage === totalPages"
                                class="px-3 py-1.5 border border-gray-200 rounded-lg bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors disabled:opacity-40 disabled:cursor-not-allowed shadow-sm text-sm font-medium">
                                ถัดไป
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Clinic Distribution Charts -->
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="mb-4">
                            <h3 class="text-lg font-bold text-gray-900">สัดส่วนจำนวนนัดหมายรายคลินิก</h3>
                            <p class="mt-1 text-sm text-gray-500">ข้อมูลวันที่ {{ formatThaiDate(formFilters.date) }}</p>
                        </div>
                        <div class="relative h-80">
                            <Pie v-if="(clinic_breakdown || []).some((clinic) => clinic.appointments > 0)" :key="`pie-${formFilters.date}`" class="!h-full !w-full" :data="appointmentChartData" :options="pieChartOptions" />
                            <div v-else class="flex h-full items-center justify-center text-sm text-gray-400">ไม่มีข้อมูลนัดหมายในวันที่เลือก</div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="mb-4">
                            <h3 class="text-lg font-bold text-gray-900">นัดหมายเทียบกับส่งตรวจรายคลินิก</h3>
                            <p class="mt-1 text-sm text-gray-500">เปรียบเทียบจำนวนผู้มีนัดกับผู้มารับบริการ</p>
                        </div>
                        <div class="relative h-80">
                            <Bar v-if="(clinic_breakdown || []).some((clinic) => clinic.appointments > 0 || clinic.arrived > 0)" :key="`bar-${formFilters.date}`" class="!h-full !w-full" :data="comparisonChartData" :options="barChartOptions" />
                            <div v-else class="flex h-full items-center justify-center text-sm text-gray-400">ไม่มีข้อมูลในวันที่เลือก</div>
                        </div>
                    </section>
                </div>


            </div>
        </div>
    </AuthenticatedLayout>
</template>
