<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const user = usePage().props.auth.user;
const isEnabled = computed(() => user.google2fa_secret !== null);

const form = useForm({});

const disableTwoFactor = () => {
    if (confirm('คุณแน่ใจหรือไม่ที่จะยกเลิกการใช้งาน 2FA? ความปลอดภัยของบัญชีจะลดลง')) {
        form.delete(route('2fa.disable'));
    }
};

const setupTwoFactor = () => {
    window.location.href = route('2fa.setup');
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Two-Factor Authentication (MFA)
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                เพิ่มความปลอดภัยให้บัญชีของคุณด้วยการยืนยันตัวตนแบบสองขั้นตอนผ่าน Google Authenticator
            </p>
        </header>

        <div class="mt-6 flex items-center gap-4">
            <template v-if="isEnabled">
                <div class="text-sm text-green-600 font-medium flex items-center">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    เปิดใช้งานแล้ว
                </div>
                
                <DangerButton :disabled="form.processing" @click="disableTwoFactor">
                    ยกเลิกการใช้งาน
                </DangerButton>
            </template>
            
            <template v-else>
                <div class="text-sm text-gray-500 font-medium">
                    ยังไม่ได้เปิดใช้งาน
                </div>
                
                <PrimaryButton @click="setupTwoFactor">
                    เปิดใช้งาน
                </PrimaryButton>
            </template>
        </div>
    </section>
</template>
