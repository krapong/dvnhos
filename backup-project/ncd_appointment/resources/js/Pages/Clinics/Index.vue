<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    clinics: Array,
});

const showModal = ref(false);

const form = useForm({
    code: '',
    name: '',
    color_code: '#000000',
    is_active: true,
});

const openEditModal = (clinic) => {
    form.code = clinic.code;
    form.name = clinic.name;
    form.color_code = clinic.color_code || '#0f766e';
    form.is_active = clinic.is_active;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    form.put(route('clinics.update', form.code), {
        onSuccess: () => closeModal(),
    });
};

</script>

<template>
    <Head title="จับคู่สีคลินิก (HIS Mapping)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold leading-tight text-teal-700">
                    ตั้งค่าสีและคลินิก (ดึงข้อมูลจาก HIS)
                </h2>
            </div>
        </template>

        <div class="py-8 bg-slate-50 min-h-screen">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                
                <div v-if="$page.props.flash?.message" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ $page.props.flash.message }}
                </div>

                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รหัส HIS (Code)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อคลินิก HIS</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สีประจำคลินิก (UI)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">แสดงบนปฏิทิน</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="clinic in clinics" :key="clinic.code" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-700">{{ clinic.code }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ clinic.name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex items-center">
                                        <div class="w-6 h-6 rounded-full border border-gray-300 mr-2" :style="{ backgroundColor: clinic.color_code }"></div>
                                        <span class="text-gray-500">{{ clinic.color_code }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span v-if="clinic.is_active" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">เปิดแสดงผล</span>
                                    <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-500">ซ่อน</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button @click="openEditModal(clinic)" class="text-indigo-600 hover:text-indigo-900 mx-2">ตั้งค่าสี</button>
                                </td>
                            </tr>
                            <tr v-if="clinics.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-red-500 font-bold">
                                    ไม่สามารถเชื่อมต่อดึงข้อมูลจากระบบ HIS ได้ หรือไม่มีข้อมูลคลินิก
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Modal for Create/Edit -->
                <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <form @submit.prevent="submit">
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                                        ตั้งค่าคลินิก (HIS Code: {{ form.code }})
                                    </h3>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">ชื่อคลินิก (แก้ไขเพื่อให้สั้นลงหรือเข้าใจง่ายบน UI)</label>
                                            <input type="text" v-model="form.name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm" required>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">สีประจำคลินิก (Color Code)</label>
                                            <div class="flex mt-1">
                                                <input type="color" v-model="form.color_code" class="h-10 w-10 p-1 block rounded-md border-gray-300 shadow-sm mr-2">
                                                <input type="text" v-model="form.color_code" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                                            </div>
                                        </div>

                                        <div class="flex items-center mt-4">
                                            <input type="checkbox" v-model="form.is_active" id="is_active" class="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded">
                                            <label for="is_active" class="ml-2 block text-sm text-gray-900">เปิดแสดงผลในปฏิทิน</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button type="submit" :disabled="form.processing" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-teal-600 text-base font-medium text-white hover:bg-teal-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                                        บันทึกการจับคู่
                                    </button>
                                    <button type="button" @click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
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
