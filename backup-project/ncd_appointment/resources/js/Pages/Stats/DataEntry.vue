<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    filters: Object,
    clinics: Array,
    totalWeeks: Number,
    stats: Array,
});

const fiscalYear = ref(props.filters.fiscal_year);
const month = ref(props.filters.month);

const monthsList = [
    { value: 10, label: 'ตุลาคม' },
    { value: 11, label: 'พฤศจิกายน' },
    { value: 12, label: 'ธันวาคม' },
    { value: 1, label: 'มกราคม' },
    { value: 2, label: 'กุมภาพันธ์' },
    { value: 3, label: 'มีนาคม' },
    { value: 4, label: 'เมษายน' },
    { value: 5, label: 'พฤษภาคม' },
    { value: 6, label: 'มิถุนายน' },
    { value: 7, label: 'กรกฎาคม' },
    { value: 8, label: 'สิงหาคม' },
    { value: 9, label: 'กันยายน' },
];

const fiscalYearsList = computed(() => {
    const currentFY = new Date().getFullYear() + 543;
    return [currentFY - 1, currentFY, currentFY + 1, currentFY + 2];
});

const applyFilters = () => {
    router.get(route('ncd-stats.data-entry'), {
        fiscal_year: fiscalYear.value,
        month: month.value,
    }, { preserveState: true });
};

const form = useForm({
    fiscal_year: fiscalYear.value,
    month: month.value,
    stats: [],
});

const initFormStats = () => {
    form.fiscal_year = fiscalYear.value;
    form.month = month.value;
    form.stats = props.clinics.map(clinic => {
        const weeksData = {};
        for (let i = 1; i <= props.totalWeeks; i++) {
            const existing = props.stats.find(s => s.clinic_id === clinic.id && s.week_no === i);
            weeksData[i] = existing ? existing.patient_count : '';
        }
        return {
            clinic_id: clinic.id,
            clinic_name: clinic.name,
            weeks: weeksData
        };
    });
};

initFormStats();

watch(() => props.stats, () => {
    initFormStats();
}, { deep: true });

const rowTotals = computed(() => {
    const totals = {};
    form.stats.forEach(stat => {
        let sum = 0;
        for (let i = 1; i <= props.totalWeeks; i++) {
            sum += Number(stat.weeks[i]) || 0;
        }
        totals[stat.clinic_id] = sum;
    });
    return totals;
});

const colTotals = computed(() => {
    const totals = {};
    for (let i = 1; i <= props.totalWeeks; i++) {
        let sum = 0;
        form.stats.forEach(stat => {
            sum += Number(stat.weeks[i]) || 0;
        });
        totals[i] = sum;
    }
    return totals;
});

const grandTotal = computed(() => {
    return Object.values(colTotals.value).reduce((a, b) => a + b, 0);
});

const submit = () => {
    form.post(route('ncd-stats.store'), {
        preserveScroll: true,
        onSuccess: () => {
            alert('บันทึกข้อมูลเรียบร้อยแล้ว');
        }
    });
};
</script>

<template>
    <Head title="บันทึกข้อมูลสถิติผู้ป่วย NCD" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                บันทึกข้อมูลสถิติผู้ป่วย NCD รายสัปดาห์
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6 flex flex-col md:flex-row md:items-center space-y-4 md:space-y-0 md:space-x-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ปีงบประมาณ</label>
                        <select v-model="fiscalYear" @change="applyFilters" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option v-for="fy in fiscalYearsList" :key="fy" :value="fy">{{ fy }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">เดือน</label>
                        <select v-model="month" @change="applyFilters" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option v-for="m in monthsList" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>
                </div>

                <!-- Data Entry Form -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border dark:border-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider border-r dark:border-gray-600">
                                            คลินิก
                                        </th>
                                        <th v-for="i in totalWeeks" :key="i" scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider border-r dark:border-gray-600">
                                            สัปดาห์ที่ {{ i }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider bg-blue-50 dark:bg-blue-900/20">
                                            รวม
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="(stat, index) in form.stats" :key="stat.clinic_id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100 border-r dark:border-gray-700">
                                            {{ stat.clinic_name }}
                                        </td>
                                        <td v-for="i in totalWeeks" :key="i" class="px-2 py-2 border-r dark:border-gray-700 text-center">
                                            <input type="number" min="0" v-model="stat.weeks[i]" class="w-20 md:w-24 text-center rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-100 text-center bg-blue-50 dark:bg-blue-900/10">
                                            {{ rowTotals[stat.clinic_id] }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-gray-100 border-r dark:border-gray-600">
                                            รวมทั้งหมด
                                        </th>
                                        <th v-for="i in totalWeeks" :key="i" class="px-4 py-4 text-center text-sm font-bold text-gray-900 dark:text-gray-100 border-r dark:border-gray-600">
                                            {{ colTotals[i] }}
                                        </th>
                                        <th class="px-6 py-4 text-center text-lg font-extrabold text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/40">
                                            {{ grandTotal }}
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" :disabled="form.processing" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-md">
                                <span v-if="form.processing">กำลังบันทึก...</span>
                                <span v-else>บันทึกข้อมูล</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
