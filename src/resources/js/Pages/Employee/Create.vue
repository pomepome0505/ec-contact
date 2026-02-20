<script setup>
    import { ref } from 'vue';
    import { Head, router } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

    const formRef = ref(null);
    const formValid = ref(false);
    const processing = ref(false);
    const form = ref({
        login_id: '',
        name: '',
        password: '',
        password_confirmation: '',
        is_admin: false,
    });

    const errors = ref({});
    const showPassword = ref(false);
    const showPasswordConfirmation = ref(false);

    const rules = {
        login_id: [
            (v) => !!v || 'ログインIDを入力してください',
            (v) => (v && v.length <= 50) || '50文字以内で入力してください',
        ],
        name: [
            (v) => !!v || '氏名を入力してください',
            (v) => (v && v.length <= 255) || '255文字以内で入力してください',
        ],
        password: [
            (v) => !!v || 'パスワードを入力してください',
            (v) => (v && v.length >= 8) || '8文字以上で入力してください',
        ],
        password_confirmation: [
            (v) => !!v || 'パスワード（確認用）を入力してください',
            (v) =>
                v === form.value.password ||
                'パスワードが一致しません',
        ],
    };

    async function submit() {
        const { valid } = await formRef.value.validate();
        if (!valid) return;

        processing.value = true;
        router.post(route('employees.store'), form.value, {
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
        <Head title="従業員作成" />

        <div class="d-flex align-center mb-4">
            <v-btn
                variant="text"
                color="secondary"
                icon="mdi-arrow-left"
                :href="route('employees.index')"
                class="mr-2"
            />
            <v-icon
                icon="mdi-account-group-outline"
                color="primary"
                class="mr-3"
            />
            <span class="text-h6" style="font-weight: 600; color: #0f172a">
                従業員作成
            </span>
        </div>

        <v-card class="pa-6" style="border: 1px solid #e2e8f0">
            <v-form ref="formRef" v-model="formValid" @submit.prevent="submit">
                <v-row dense>
                    <v-col cols="12" sm="6">
                        <v-text-field
                            v-model="form.login_id"
                            label="ログインID"
                            :rules="rules.login_id"
                            :error-messages="errors.login_id"
                            prepend-inner-icon="mdi-account-outline"
                        />
                    </v-col>
                    <v-col cols="12" sm="6">
                        <v-text-field
                            v-model="form.name"
                            label="氏名"
                            :rules="rules.name"
                            :error-messages="errors.name"
                            prepend-inner-icon="mdi-badge-account-horizontal-outline"
                        />
                    </v-col>
                    <v-col cols="12" sm="6">
                        <v-text-field
                            v-model="form.password"
                            label="パスワード"
                            :type="showPassword ? 'text' : 'password'"
                            :rules="rules.password"
                            :error-messages="errors.password"
                            prepend-inner-icon="mdi-lock-outline"
                            :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                            @click:append-inner="showPassword = !showPassword"
                        />
                    </v-col>
                    <v-col cols="12" sm="6">
                        <v-text-field
                            v-model="form.password_confirmation"
                            label="パスワード（確認用）"
                            :type="showPasswordConfirmation ? 'text' : 'password'"
                            :rules="rules.password_confirmation"
                            prepend-inner-icon="mdi-lock-check-outline"
                            :append-inner-icon="showPasswordConfirmation ? 'mdi-eye-off' : 'mdi-eye'"
                            @click:append-inner="showPasswordConfirmation = !showPasswordConfirmation"
                        />
                    </v-col>
                    <v-col cols="12">
                        <v-switch
                            v-model="form.is_admin"
                            label="管理者権限"
                            color="primary"
                            hide-details
                        />
                    </v-col>
                </v-row>

                <div class="d-flex justify-end mt-4">
                    <v-btn
                        variant="text"
                        color="secondary"
                        class="mr-2"
                        :href="route('employees.index')"
                    >
                        キャンセル
                    </v-btn>
                    <v-btn
                        type="submit"
                        color="primary"
                        :loading="processing"
                        :disabled="!formValid"
                    >
                        <v-icon icon="mdi-check" start />
                        作成
                    </v-btn>
                </div>
            </v-form>
        </v-card>
    </AuthenticatedLayout>
</template>
