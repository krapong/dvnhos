<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    filters: Object,
    clinics_data: Array,
    total_days: Number
});

const filters = ref({
    start_date: props.filters.start_date || new Date().toISOString().split('T')[0],
    end_date: props.filters.end_date || new Date().toISOString().split('T')[0],
});

const applyFilters = () => {
    router.get(route('appointments.schedule'), filters.value, { preserveState: true, replace: true });
};

const getProgressBarWidth = (booked, max) => {
    if (max === 0) return '100%';
    const percentage = (booked / max) * 100;
    return `${Math.min(percentage, 100)}%`;
};

const getProgressBarColor = (booked, max) => {
    if (booked > max) return 'bg-rose-500'; // Over limit = Red
    if (max > 0 && booked >= max * 0.9) return 'bg-amber-500'; // >= 90% = Orange
    return 'bg-emerald-500'; // Normal = Green
};
</script>

<template>
    <Head title="ตารางนัด" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">ภาพรวมตารางนัด (คิว)</h2>
        </template>

        <div class="py-8 bg-slate-50 min-h-screen">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                
                <!-- Filter Section -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <form @submit.prevent="applyFilters" class="flex flex-col md:flex-row gap-4 items-end">
                        <div class="w-full md:w-1/3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">ตั้งแต่วันที่</label>
                            <input type="date" v-model="filters.start_date" class="border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm w-full">
                        </div>
                        <div class="w-full md:w-1/3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">ถึงวันที่</label>
                            <input type="date" v-model="filters.end_date" class="border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm w-full">
                        </div>
                        <div class="w-full md:w-auto flex-1">
                            <button type="submit" class="w-full md:w-auto bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2 px-8 rounded-md transition duration-150 shadow-sm flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                ค้นหาข้อมูล
                            </button>
                        </div>
                    </form>
                    <div class="mt-4 text-sm text-gray-500">
                        * แสดงจำนวนนัดหมายรวมในช่วงเวลา <span class="font-bold text-teal-600">{{ total_days }}</span> วันที่เลือก
                    </div>
                </div>

                <!-- Clinics Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="clinic in clinics_data" :key="clinic.code" class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 hover:shadow-md transition-shadow duration-200">
                        
                        <!-- Card Header -->
                        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center" :style="`border-top: 4px solid ${clinic.color_code || '#0d9488'}`">
                            <h3 class="font-bold text-gray-800 text-lg truncate">{{ clinic.name }}</h3>
                            <span class="text-xs font-semibold text-gray-400 bg-gray-100 px-2 py-1 rounded-full">{{ clinic.code }}</span>
                        </div>
                        
                        <!-- Card Body -->
                        <div class="p-5">
                            
                            <!-- Main Stats -->
                            <div class="flex justify-between items-end mb-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500 mb-1">นัดแล้วทั้งหมด</p>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-4xl font-extrabold" :class="clinic.booked > clinic.max_capacity ? 'text-rose-600' : 'text-gray-800'">
                                            {{ clinic.booked }}
                                        </span>
                                        <span class="text-sm text-gray-400">/ {{ clinic.max_capacity }} คิว</span>
                                    </div>
                                </div>
                                
                                <div v-if="clinic.overbooked > 0" class="flex flex-col items-end">
                                    <span class="text-xs font-bold text-rose-500 bg-rose-50 px-2 py-1 rounded-md border border-rose-100 mb-1">
                                        เกินลิมิต!
                                    </span>
                                    <span class="text-lg font-bold text-rose-600">+{{ clinic.overbooked }}</span>
                                </div>
                                <div v-else class="flex flex-col items-end">
                                    <span class="text-xs font-medium text-emerald-500 bg-emerald-50 px-2 py-1 rounded-md border border-emerald-100 mb-1">
                                        ยังว่าง
                                    </span>
                                    <span class="text-lg font-bold text-emerald-600">{{ clinic.max_capacity - clinic.booked }}</span>
                                </div>
                            </div>
                            
                            <!-- Progress Bar -->
                            <div class="relative pt-1">
                                <div class="overflow-hidden h-2.5 mb-4 text-xs flex rounded-full bg-slate-100">
                                    <div :style="`width: ${getProgressBarWidth(clinic.booked, clinic.max_capacity)}`" 
                                         :class="getProgressBarColor(clinic.booked, clinic.max_capacity)"
                                         class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center transition-all duration-500">
                                    </div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-400 font-medium">
                                    <span>0%</span>
                                    <span>{{ Math.round((clinic.booked / (clinic.max_capacity || 1)) * 100) }}%</span>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="clinics_data.length === 0" class="bg-white rounded-xl shadow-sm p-12 text-center border border-gray-100">
                    <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">ยังไม่มีข้อมูลคลินิก</h3>
                    <p class="mt-1 text-gray-500">โปรดตั้งค่าคลินิกในเมนูจัดการระบบคลินิก</p>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
