<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    roles: Array,
    permissions: Array
});

const showModal = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null,
    name: '',
    permissions: []
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (role) => {
    isEditing.value = true;
    form.reset();
    form.clearErrors();
    form.id = role.id;
    form.name = role.name;
    form.permissions = role.permissions.map(p => p.name);
    showModal.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.roles.update', form.id), {
            onSuccess: () => showModal.value = false
        });
    } else {
        form.post(route('admin.roles.store'), {
            onSuccess: () => showModal.value = false
        });
    }
};

const deleteRole = (role) => {
    if (role.name === 'admin') {
        alert('ไม่อนุญาตให้ลบสิทธิ์ admin');
        return;
    }
    if (confirm(`คุณต้องการลบกลุ่มสิทธิ์ ${role.name} ใช่หรือไม่?`)) {
        form.delete(route('admin.roles.destroy', role.id));
    }
};
</script>

<template>
    <Head title="สิทธิ์ (Roles)" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">จัดการกลุ่มสิทธิ์ (Roles)</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="mb-4 flex justify-end">
                    <button @click="openCreateModal" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded transition-colors inline-flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        เพิ่มกลุ่มสิทธิ์
                    </button>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อกลุ่มสิทธิ์</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สิทธิ์การใช้งาน (Permissions)</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="role in roles" :key="role.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ role.name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <div class="flex flex-wrap gap-1">
                                            <span v-for="permission in role.permissions" :key="permission.id" class="px-2 py-1 inline-flex text-xs leading-4 font-semibold rounded bg-gray-100 text-gray-700">
                                                {{ permission.name }}
                                            </span>
                                            <span v-if="role.permissions.length === 0" class="text-gray-400 italic">ไม่มีสิทธิ์พิเศษ</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openEditModal(role)" class="text-indigo-600 hover:text-indigo-900 mr-4">แก้ไข</button>
                                        <button @click="deleteRole(role)" :class="{'text-gray-400 cursor-not-allowed': role.name === 'admin', 'text-red-600 hover:text-red-900': role.name !== 'admin'}" :disabled="role.name === 'admin'">ลบ</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="showModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <form @submit.prevent="submit">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                                {{ isEditing ? 'แก้ไขกลุ่มสิทธิ์' : 'เพิ่มกลุ่มสิทธิ์' }}
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">ชื่อกลุ่มสิทธิ์ (อักษรภาษาอังกฤษ เช่น doctor, nurse)</label>
                                    <input type="text" v-model="form.name" :disabled="form.name === 'admin' && isEditing" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm disabled:bg-gray-100" required>
                                    <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                                </div>
                                
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">เลือกสิทธิ์การใช้งาน</label>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-60 overflow-y-auto p-2 border border-gray-200 rounded-md bg-gray-50">
                                        <div v-for="permission in permissions" :key="permission.id" class="flex items-center p-2 hover:bg-white rounded border border-transparent hover:border-gray-200 transition-colors">
                                            <input :id="'perm-'+permission.id" type="checkbox" :value="permission.name" v-model="form.permissions" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                                            <label :for="'perm-'+permission.id" class="ml-2 block text-sm text-gray-900 cursor-pointer w-full">
                                                {{ permission.name }}
                                            </label>
                                        </div>
                                        <div v-if="permissions.length === 0" class="col-span-2 text-sm text-gray-500 text-center py-4">
                                            ยังไม่มีสิทธิ์ (Permissions) ในระบบ กรุณาเพิ่มผ่าน Database
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-emerald-600 text-base font-medium text-white hover:bg-emerald-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm" :disabled="form.processing">
                                บันทึก
                            </button>
                            <button type="button" @click="showModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                ยกเลิก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
