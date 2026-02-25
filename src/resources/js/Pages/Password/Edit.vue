<script setup>
    import { ref } from 'vue';
    import { Head, router, usePage } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

    const props = defineProps({
        requiresPasswordChange: { type: Boolean, default: false },
    });

    const page = usePage();

    const formRef = ref(null);
    const formValid = ref(false);
    const processing = ref(false);
    const form = ref({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const errors = ref({});
    const showCurrentPassword = ref(false);
    const showPassword = ref(false);
    const showPasswordConfirmation = ref(false);

    const rules = {
        current_password: [
            (v) => !!v || '現在のパスワードを入力してください',
        ],
        password: [
            (v) => !!v || '新しいパスワードを入力してください',
            (v) => (v && v.length >= 8) || '8文字以上で入力してください',
        ],
        password_confirmation: [
            (v) => !!v || '新しいパスワード（確認用）を入力してください',
            (v) =>
                v === form.value.password || 'パスワードが一致しません',
        ],
    };

    async function submit() {
        const { valid } = await formRef.value.validate();
        if (!valid) return;

        processing.value = true;
        router.patch(route('password.update'), form.value, {
            onSuccess: () => {
                form.value.current_password = '';
                form.value.password = '';
                form.value.password_confirmation = '';
                errors.value = {};
                formRef.value?.resetValidation();
            },
            onError: (err) => {
                errors.value = err;
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    }
</script>

<template>
    <AuthenticatedLayout>
        <Head title="パスワード変更" />

        <div class="d-flex align-center mb-4">
            <v-icon icon="mdi-lock-outline" color="primary" class="mr-3" />
            <span class="text-h6" style="font-weight: 600; color: #0f172a">
                パスワード変更
            </span>
        </div>

        <v-alert
            v-if="props.requiresPasswordChange"
            type="warning"
            variant="tonal"
            class="mb-4"
        >
            一時パスワードでログインしています。セキュリティのため、パスワードを変更してください。
        </v-alert>

        <v-alert
            v-if="page.props.flash?.success"
            type="success"
            variant="tonal"
            class="mb-4"
            closable
        >
            {{ page.props.flash.success }}
        </v-alert>

        <v-card class="pa-6" style="border: 1px solid #e2e8f0">
            <v-form ref="formRef" v-model="formValid" @submit.prevent="submit">
                <v-row dense>
                    <v-col cols="12" sm="6">
                        <v-text-field
                            v-model="form.current_password"
                            label="現在のパスワード"
                            :type="showCurrentPassword ? 'text' : 'password'"
                            :rules="rules.current_password"
                            :error-messages="errors.current_password"
                            prepend-inner-icon="mdi-lock-outline"
                            :append-inner-icon="showCurrentPassword ? 'mdi-eye-off' : 'mdi-eye'"
                            @click:append-inner="showCurrentPassword = !showCurrentPassword"
                        />
                    </v-col>
                </v-row>
                <v-row dense>
                    <v-col cols="12" sm="6">
                        <v-text-field
                            v-model="form.password"
                            label="新しいパスワード"
                            :type="showPassword ? 'text' : 'password'"
                            :rules="rules.password"
                            :error-messages="errors.password"
                            prepend-inner-icon="mdi-lock-plus-outline"
                            :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                            @click:append-inner="showPassword = !showPassword"
                        />
                    </v-col>
                    <v-col cols="12" sm="6">
                        <v-text-field
                            v-model="form.password_confirmation"
                            label="新しいパスワード（確認用）"
                            :type="showPasswordConfirmation ? 'text' : 'password'"
                            :rules="rules.password_confirmation"
                            prepend-inner-icon="mdi-lock-check-outline"
                            :append-inner-icon="showPasswordConfirmation ? 'mdi-eye-off' : 'mdi-eye'"
                            @click:append-inner="showPasswordConfirmation = !showPasswordConfirmation"
                        />
                    </v-col>
                </v-row>

                <div class="d-flex justify-end mt-4">
                    <v-btn
                        type="submit"
                        color="primary"
                        :loading="processing"
                        :disabled="!formValid"
                    >
                        <v-icon icon="mdi-check" start />
                        パスワードを変更
                    </v-btn>
                </div>
            </v-form>
        </v-card>
    </AuthenticatedLayout>
</template>
