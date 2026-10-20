<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    settings: Object
});

// Flatten settings into an array for the form
const flatSettings = [];
for (const group in props.settings) {
    props.settings[group].forEach(setting => {
        let value = setting.value;
        if (setting.type === 'boolean') {
            value = setting.value === '1' || setting.value === 'true' || setting.value === 1;
        }
        flatSettings.push({
            id: setting.id,
            key: setting.key,
            value: value,
            type: setting.type,
            description: setting.description,
            group: setting.group
        });
    });
}

const form = useForm({
    settings: flatSettings
});

const submit = () => {
    form.put(route('admin.settings.update'));
};
</script>

<template>
    <Head title="ตั้งค่าระบบ (Settings)" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">ตั้งค่าระบบ (System Settings)</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 text-gray-900">
                        
                        <div v-if="Object.keys(settings).length === 0" class="text-center py-8 text-gray-500">
                            ยังไม่มีการตั้งค่าระบบในฐานข้อมูล
                        </div>

                        <div v-for="(groupSettings, groupName) in settings" :key="groupName" class="mb-8 last:mb-0">
                            <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4 capitalize">{{ groupName || 'General' }}</h3>
                            
                            <div class="space-y-6">
                                <div v-for="(setting, index) in groupSettings" :key="setting.id" class="flex flex-col">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ setting.description || setting.key }}
                                    </label>
                                    
                                    <template v-if="setting.type === 'boolean'">
                                        <div class="flex items-center mt-1">
                                            <input type="checkbox" v-model="form.settings.find(s => s.id === setting.id).value" class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                            <span class="ml-2 text-sm text-gray-600">{{ form.settings.find(s => s.id === setting.id).value ? 'เปิดใช้งาน (Enabled)' : 'ปิดใช้งาน (Disabled)' }}</span>
                                        </div>
                                    </template>
                                    
                                    <template v-else-if="setting.type === 'integer'">
                                        <input type="number" v-model="form.settings.find(s => s.id === setting.id).value" class="mt-1 block w-full md:w-1/2 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </template>

                                    <template v-else-if="setting.type === 'text'">
                                        <textarea v-model="form.settings.find(s => s.id === setting.id).value" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                    </template>
                                    
                                    <template v-else>
                                        <input type="text" v-model="form.settings.find(s => s.id === setting.id).value" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </template>
                                    
                                    <p class="text-xs text-gray-400 mt-1 font-mono">{{ setting.key }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="Object.keys(settings).length > 0" class="mt-6 pt-5 border-t border-gray-200 flex justify-end">
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" :disabled="form.processing">
                                <span v-if="form.processing">กำลังบันทึก...</span>
                                <span v-else>บันทึกการตั้งค่า</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
