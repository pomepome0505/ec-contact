<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
        default: null,
    },
});

const form = useForm({
    login_id: '',
    password: '',
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="ログイン" />

        <div class="text-center pt-10 pb-2 px-8">
            <v-icon icon="mdi-account-circle" size="48" color="primary" class="mb-3" />
            <div class="text-h5" style="font-weight: 600; color: #0f172a;">ログイン</div>
            <div class="text-body-2 mt-1" style="color: #475569;">
                問い合わせ管理システム
            </div>
        </div>

        <div class="px-8 pb-10 pt-6">
            <v-alert v-if="status" type="success" variant="tonal" class="mb-6" rounded="lg">
                {{ status }}
            </v-alert>

            <v-form @submit.prevent="submit">
                <v-text-field
                    v-model="form.login_id"
                    label="ログインID"
                    type="text"
                    required
                    autofocus
                    autocomplete="username"
                    :error-messages="form.errors.login_id"
                    prepend-inner-icon="mdi-account"
                    class="mb-2"
                />

                <v-text-field
                    v-model="form.password"
                    label="パスワード"
                    type="password"
                    required
                    autocomplete="current-password"
                    :error-messages="form.errors.password"
                    prepend-inner-icon="mdi-lock"
                    class="mb-2"
                />

                <v-btn
                    type="submit"
                    color="primary"
                    size="large"
                    block
                    height="48"
                    :loading="form.processing"
                    :disabled="form.processing"
                >
                    ログイン
                </v-btn>
            </v-form>
        </div>
    </GuestLayout>
</template>
