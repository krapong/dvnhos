<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    capacities: Array,
});

const showModal = ref(false);

const form = useForm({
    id: null,
    max_appointments: null,
    clinic_name: '',
});

const openModal = (clinic) => {
    form.id = clinic.id;
    form.max_appointments = clinic.local_max;
    form.clinic_name = clinic.name;
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const submit = () => {
    form.put(route('clinics.capacity.update', form.id), {
        onSuccess: () => closeModal(),
    });
};
</script>

<template>
    <Head title="ความจุของคลินิก" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold leading-tight text-teal-700">
                    ความจุของคลินิก (Capacity)
                </h2>
            </div>
        </template>

        <div class="py-8 bg-slate-50 min-h-screen">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                
                <div v-if="$page.props.flash?.message" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl relative shadow-sm">
                    {{ $page.props.flash.message }}
                </div>

                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-xl shadow-sm mb-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">การคำนวณความจุคลินิก</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>หากมีการกำหนดความจุบนระบบหน้าเว็บ ค่าที่กำหนดนี้จะถูกนำไปใช้เป็นลำดับแรก</p>
                                <p class="mt-1">หากไม่ได้กำหนดระบบหน้าเว็บไว้ ระบบจะดึงค่า <code>app_limit_qty</code> จากฐานข้อมูล HOSxP มาใช้แทน</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">รหัสคลินิก</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ชื่อคลินิก</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">ความจุจาก HOSxP</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">ความจุ (หน้าเว็บ)</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-for="clinic in capacities" :key="clinic.id" class="hover:bg-teal-50/50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-700">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 rounded-full mr-2" :style="{ backgroundColor: clinic.color_code }"></div>
                                        {{ clinic.code }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ clinic.name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                    <span class="text-gray-500">
                                        {{ clinic.hosxp_capacity }} {{ clinic.hosxp_capacity !== 'ไม่จำกัด' ? 'ราย' : '' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold">
                                    <span v-if="!clinic.local_max" class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-500">
                                        ไม่ได้กำหนด
                                    </span>
                                    <span v-else class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-teal-100 text-teal-800 border border-teal-200">
                                        {{ clinic.local_max }} ราย
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <button @click="openModal(clinic)" class="text-teal-600 hover:text-teal-900 transition-colors px-3 py-1 bg-teal-50 rounded-lg hover:bg-teal-100">
                                        จัดการ
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="capacities.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    ไม่มีข้อมูลคลินิก
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Modal for Create/Edit -->
                <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm transition-opacity" @click="closeModal"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <form @submit.prevent="submit">
                                <div class="bg-white px-6 pt-6 pb-6">
                                    <h3 class="text-xl leading-6 font-bold text-gray-900 mb-6" id="modal-title">
                                        จัดการความจุคลินิก
                                    </h3>
                                    
                                    <div class="mb-4">
                                        <p class="text-sm text-gray-500">คลินิก: <span class="font-bold text-gray-800">{{ form.clinic_name }}</span></p>
                                    </div>

                                    <div class="space-y-5">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">จำนวนคิวรับผู้ป่วยสูงสุด (ต่อวัน)</label>
                                            <input type="number" v-model="form.max_appointments" min="0" placeholder="ปล่อยว่างเพื่อใช้ค่าจาก HOSxP" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm transition-colors">
                                            <p class="text-xs text-gray-500 mt-2">หากปล่อยว่าง ระบบจะดึงความจุจาก HOSxP มาใช้แทนโดยอัตโนมัติ</p>
                                            <div v-if="form.errors.max_appointments" class="text-rose-500 text-xs mt-1">{{ form.errors.max_appointments }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-3 rounded-b-2xl">
                                    <button type="submit" :disabled="form.processing" class="inline-flex justify-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-teal-600 text-sm font-semibold text-white hover:bg-teal-700 focus:outline-none disabled:opacity-50 transition-colors">
                                        บันทึก
                                    </button>
                                    <button type="button" @click="closeModal" class="inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-5 py-2.5 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors">
                                        ยกเลิก
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
