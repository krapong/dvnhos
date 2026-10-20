<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import { ref } from 'vue';

const props = defineProps({
    logs: Object,
    filters: Object,
});

const form = useForm({
    event: props.filters.event || '',
    subject_type: props.filters.subject_type || '',
});

const submit = () => {
    form.get(route('admin.audit-log'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    form.event = '';
    form.subject_type = '';
    submit();
};
</script>

<template>
    <Head title="Audit Log" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">ระบบบันทึกประวัติ (Audit Log)</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6">
                    <form @submit.prevent="submit" class="flex flex-col sm:flex-row gap-4 items-end">
                        <div class="w-full sm:w-1/3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เหตุการณ์ (Event)</label>
                            <select v-model="form.event" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                <option value="">ทั้งหมด</option>
                                <option value="created">สร้างใหม่ (Created)</option>
                                <option value="updated">แก้ไข (Updated)</option>
                                <option value="deleted">ลบ (Deleted)</option>
                            </select>
                        </div>
                        <div class="w-full sm:w-1/3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">โมเดลที่เกี่ยวข้อง</label>
                            <input v-model="form.subject_type" type="text" placeholder="เช่น Appointment, Clinic" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                กรองข้อมูล
                            </button>
                            <button type="button" @click="clearFilters" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                                ล้าง
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100 overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">วัน-เวลา</th>
                                    <th scope="col" class="px-6 py-3">ผู้กระทำ</th>
                                    <th scope="col" class="px-6 py-3">เหตุการณ์</th>
                                    <th scope="col" class="px-6 py-3">เป้าหมาย (Subject)</th>
                                    <th scope="col" class="px-6 py-3">คำอธิบาย</th>
                                    <th scope="col" class="px-6 py-3">ข้อมูลที่มีการเปลี่ยนแปลง (JSON)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="log in logs.data" :key="log.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-6 py-4 whitespace-nowrap">{{ log.created_at }}</td>
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ log.causer_name }}</td>
                                    <td class="px-6 py-4">
                                        <span :class="{
                                            'bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded': log.event === 'created',
                                            'bg-blue-100 text-blue-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded': log.event === 'updated',
                                            'bg-red-100 text-red-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded': log.event === 'deleted',
                                        }">
                                            {{ log.event }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">{{ log.subject_type }} #{{ log.subject_id }}</td>
                                    <td class="px-6 py-4">{{ log.description }}</td>
                                    <td class="px-6 py-4">
                                        <details class="cursor-pointer text-xs" v-if="log.properties && Object.keys(log.properties).length > 0">
                                            <summary class="text-blue-600 hover:underline">ดูรายละเอียด</summary>
                                            <pre class="mt-2 bg-gray-100 dark:bg-gray-900 p-2 rounded max-h-40 overflow-y-auto">{{ JSON.stringify(log.properties, null, 2) }}</pre>
                                        </details>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                </tr>
                                <tr v-if="logs.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">ไม่พบประวัติการแก้ไข</td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <div class="mt-4 flex justify-between items-center">
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                แสดง {{ logs.from || 0 }} ถึง {{ logs.to || 0 }} จาก {{ logs.total }} รายการ
                            </div>
                            <div class="flex space-x-1">
                                <template v-for="link in logs.links" :key="link.label">
                                    <Link 
                                        v-if="link.url"
                                        :href="link.url" 
                                        v-html="link.label"
                                        class="px-3 py-1 border rounded text-sm"
                                        :class="link.active ? 'bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'"
                                    />
                                    <span v-else v-html="link.label" class="px-3 py-1 border border-gray-300 rounded text-sm text-gray-400 bg-gray-50 cursor-not-allowed"></span>
                                </template>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
