<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    code: '',
});

const submit = () => {
    form.post(route('2fa.verify'), {
        onFinish: () => form.reset('code'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Two-Factor Authentication Challenge" />

        <div class="mb-4 text-sm text-gray-600">
            โปรดยืนยันตัวตนด้วยรหัส 6 หลักจากแอป Google Authenticator ของคุณ
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="code" value="รหัสยืนยัน (Authentication Code)" />

                <TextInput
                    id="code"
                    type="text"
                    inputmode="numeric"
                    class="mt-1 block w-full"
                    v-model="form.code"
                    required
                    autofocus
                    autocomplete="one-time-code"
                />

                <InputError class="mt-2" :message="form.errors.code" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    ยืนยันตัวตน
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
