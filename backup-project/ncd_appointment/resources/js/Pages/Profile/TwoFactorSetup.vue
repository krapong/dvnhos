<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    qrCodeSvg: String,
    secret: String,
});

const form = useForm({
    code: '',
});

const submit = () => {
    form.post(route('2fa.confirm'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="ตั้งค่า Two-Factor Authentication" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                ตั้งค่า Two-Factor Authentication
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">เปิดใช้งาน MFA ด้วย Google Authenticator</h3>
                    
                    <div class="mb-4 text-sm text-gray-600">
                        1. สแกน QR Code ด้านล่างนี้ด้วยแอป Google Authenticator หรือแอป 2FA อื่นๆ ของคุณ
                    </div>

                    <div class="mb-6 flex justify-center bg-gray-50 p-4 rounded-lg inline-block">
                        <div v-html="qrCodeSvg"></div>
                    </div>

                    <div class="mb-4 text-sm text-gray-600">
                        หรือกรอกรหัสนี้แบบ Manual: <strong class="text-gray-900">{{ secret }}</strong>
                    </div>

                    <div class="mb-4 text-sm text-gray-600">
                        2. กรอกรหัส 6 หลักที่ปรากฏในแอปเพื่อยืนยันการตั้งค่า
                    </div>

                    <form @submit.prevent="submit" class="max-w-xl">
                        <div>
                            <InputLabel for="code" value="รหัสยืนยัน (Code)" />

                            <TextInput
                                id="code"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.code"
                                required
                                autofocus
                            />

                            <InputError class="mt-2" :message="form.errors.code" />
                        </div>

                        <div class="flex items-center gap-4 mt-4">
                            <PrimaryButton :disabled="form.processing">
                                ยืนยันรหัสและเปิดใช้งาน
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
