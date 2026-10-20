<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    fiscal_year: Number,
    clinics: Array,
    stats: Array,
    monthsStructure: Array,
});

const selectedYear = ref(props.fiscal_year);

const fiscalYearsList = computed(() => {
    const currentFY = new Date().getFullYear() + 543;
    return [currentFY - 2, currentFY - 1, currentFY, currentFY + 1, currentFY + 2];
});

const applyFilter = () => {
    router.get(route('ncd-stats.summary'), {
        fiscal_year: selectedYear.value,
    }, { preserveState: true });
};

const statsMap = computed(() => {
    const map = {};
    props.clinics.forEach(c => {
        map[c.id] = {};
        props.monthsStructure.forEach(m => {
            map[c.id][m.month] = {};
        });
    });
    
    props.stats.forEach(s => {
        if (!map[s.clinic_id]) map[s.clinic_id] = {};
        if (!map[s.clinic_id][s.month]) map[s.clinic_id][s.month] = {};
        map[s.clinic_id][s.month][s.week_no] = s.patient_count;
    });
    
    return map;
});

const clinicTotals = computed(() => {
    const totals = {};
    props.clinics.forEach(c => {
        let sum = 0;
        props.monthsStructure.forEach(m => {
            for (let w = 1; w <= m.totalWeeks; w++) {
                sum += statsMap.value[c.id][m.month][w] || 0;
            }
        });
        totals[c.id] = sum;
    });
    return totals;
});

</script>

<template>
    <Head title="สรุปสถิติผู้ป่วย NCD" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                สรุปผลการให้บริการคลินิกโรคไม่ติดต่อเรื้อรัง ปีงบประมาณ {{ fiscal_year }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-[98%] mx-auto sm:px-6 lg:px-8">
                <!-- Filter -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6 flex items-center space-x-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ปีงบประมาณ</label>
                        <select v-model="selectedYear" @change="applyFilter" class="mt-1 block w-48 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option v-for="fy in fiscalYearsList" :key="fy" :value="fy">{{ fy }}</option>
                        </select>
                    </div>
                </div>

                <!-- Summary Table -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="overflow-x-auto custom-scrollbar pb-4">
                        <table class="min-w-max divide-y divide-gray-200 dark:divide-gray-700 border dark:border-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <!-- Months Row -->
                                <tr>
                                    <th rowspan="2" class="px-4 py-3 text-left text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider border-r dark:border-gray-600 bg-gray-100 dark:bg-gray-600 sticky left-0 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                        คลินิก
                                    </th>
                                    <th v-for="m in monthsStructure" :key="'month-'+m.month" :colspan="m.totalWeeks" class="px-2 py-2 text-center text-sm font-bold text-gray-700 dark:text-gray-200 border-r dark:border-gray-600 border-b">
                                        {{ m.name }}
                                    </th>
                                    <th rowspan="2" class="px-4 py-3 text-center text-sm font-bold text-gray-700 dark:text-gray-200 border-l dark:border-gray-600 bg-blue-50 dark:bg-blue-900/20">
                                        รวมทั้งปี
                                    </th>
                                </tr>
                                <!-- Weeks Row -->
                                <tr>
                                    <template v-for="m in monthsStructure" :key="'week-'+m.month">
                                        <th v-for="w in m.totalWeeks" :key="w" class="px-2 py-1 text-center text-xs font-medium text-gray-500 dark:text-gray-300 border-r dark:border-gray-600 min-w-[3rem]">
                                            {{ w }}
                                        </th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="clinic in clinics" :key="clinic.id" class="hover:bg-gray-50 dark:hover:bg-gray-750">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-100 border-r dark:border-gray-700 bg-white dark:bg-gray-800 sticky left-0 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                        {{ clinic.name }}
                                    </td>
                                    
                                    <template v-for="m in monthsStructure" :key="'data-'+clinic.id+'-'+m.month">
                                        <td v-for="w in m.totalWeeks" :key="w" class="px-2 py-2 text-center text-sm text-gray-700 dark:text-gray-300 border-r dark:border-gray-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors">
                                            {{ statsMap[clinic.id][m.month][w] || '-' }}
                                        </td>
                                    </template>
                                    
                                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-bold text-blue-600 dark:text-blue-400 border-l dark:border-gray-700 bg-blue-50 dark:bg-blue-900/10">
                                        {{ clinicTotals[clinic.id] }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    height: 12px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 6px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 6px;
    border: 3px solid #f1f1f1;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
:global(.dark) .custom-scrollbar::-webkit-scrollbar-track {
    background: #1e293b;
}
:global(.dark) .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #475569;
    border-color: #1e293b;
}
</style>
