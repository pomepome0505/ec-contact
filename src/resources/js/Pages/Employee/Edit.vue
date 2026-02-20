<script setup>
    import { ref } from 'vue';
    import { Head, router, usePage } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

    const props = defineProps({
        employee: { type: Object, required: true },
    });

    const page = usePage();
    const isSelf = props.employee.id === page.props.auth.user.id;

    const formRef = ref(null);
    const formValid = ref(false);
    const processing = ref(false);
    const form = ref({
        name: props.employee.name,
        is_admin: props.employee.is_admin,
    });

    const errors = ref({});

    const rules = {
        name: [
            (v) => !!v || '氏名を入力してください',
            (v) => (v && v.length <= 255) || '255文字以内で入力してください',
        ],
    };

    async function submit() {
        const { valid } = await formRef.value.validate();
        if (!valid) return;

        processing.value = true;
        router.patch(route('employees.update', props.employee.id), form.value, {
            onError: (err) => {
                errors.value = err;
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    }

    const resetProcessing = ref(false);
    const confirmDialog = ref(false);
    const resetDialog = ref(false);
    const generatedPassword = ref('');
    const copied = ref(false);

    async function resetPassword() {
        confirmDialog.value = false;
        resetProcessing.value = true;
        try {
            const response = await fetch(
                route('employees.resetPassword', props.employee.id),
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': page.props.csrf_token,
                    },
                },
            );
            if (!response.ok) throw new Error('リセットに失敗しました');
            const data = await response.json();
            generatedPassword.value = data.password;
            copied.value = false;
            resetDialog.value = true;
        } catch {
            alert('パスワードリセットに失敗しました。');
        } finally {
            resetProcessing.value = false;
        }
    }

    async function copyToClipboard() {
        await navigator.clipboard.writeText(generatedPassword.value);
        copied.value = true;
    }
</script>

<template>
    <AuthenticatedLayout>
        <Head title="従業員編集" />

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
                従業員編集
            </span>
        </div>

        <v-card class="pa-6" style="border: 1px solid #e2e8f0">
            <v-form ref="formRef" v-model="formValid" @submit.prevent="submit">
                <v-row dense>
                    <v-col cols="12" sm="6">
                        <v-text-field
                            :model-value="props.employee.login_id"
                            label="ログインID"
                            prepend-inner-icon="mdi-account-outline"
                            readonly
                            bg-color="grey-lighten-3"
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
                    <v-col cols="12">
                        <v-switch
                            v-model="form.is_admin"
                            label="管理者権限"
                            color="primary"
                            :disabled="isSelf"
                            :hint="
                                isSelf
                                    ? '自分自身の管理者権限は変更できません'
                                    : ''
                            "
                            :persistent-hint="isSelf"
                            hide-details="auto"
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
                        更新
                    </v-btn>
                </div>
            </v-form>
        </v-card>

        <v-card
            v-if="!isSelf"
            class="pa-6 mt-4"
            style="border: 1px solid #e2e8f0"
        >
            <div
                class="text-subtitle-1 mb-3"
                style="font-weight: 600; color: #0f172a"
            >
                パスワードリセット
            </div>
            <p class="text-body-2 mb-4" style="color: #475569">
                一時パスワードを生成します。有効期限は7日間です。
            </p>
            <v-btn
                color="warning"
                variant="outlined"
                prepend-icon="mdi-lock-reset"
                :loading="resetProcessing"
                @click="confirmDialog = true"
            >
                パスワードをリセット
            </v-btn>
        </v-card>

        <v-dialog v-model="confirmDialog" max-width="440">
            <v-card class="pa-2">
                <v-card-title class="pa-4">
                    パスワードリセットの確認
                </v-card-title>
                <v-card-text class="pa-4">
                    <v-alert
                        type="warning"
                        variant="tonal"
                        class="mb-3"
                        rounded="lg"
                    >
                        現在のパスワードは無効になり、使用できなくなります
                    </v-alert>
                    <p class="text-body-2" style="color: #475569">
                        新しい一時パスワード（有効期限7日間）を発行します。この操作は取り消せません。
                    </p>
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn
                        variant="text"
                        color="secondary"
                        @click="confirmDialog = false"
                    >
                        キャンセル
                    </v-btn>
                    <v-btn
                        color="warning"
                        @click="resetPassword"
                    >
                        リセットする
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="resetDialog" max-width="480" persistent>
            <v-card class="pa-6">
                <div class="d-flex align-center mb-4">
                    <v-icon
                        icon="mdi-alert-circle-outline"
                        color="warning"
                        class="mr-2"
                    />
                    <span class="text-h6" style="font-weight: 600">
                        一時パスワードを発行しました
                    </span>
                </div>

                <v-alert
                    type="warning"
                    variant="tonal"
                    class="mb-4"
                    rounded="lg"
                >
                    この画面を閉じると二度と表示されません
                </v-alert>

                <div
                    class="d-flex align-center mb-4"
                    style="
                        background: #f8fafc;
                        border-radius: 8px;
                        padding: 12px 16px;
                    "
                >
                    <code
                        class="text-body-1 flex-grow-1"
                        style="
                            font-family: monospace;
                            color: #0f172a;
                            letter-spacing: 0.05em;
                        "
                    >
                        {{ generatedPassword }}
                    </code>
                    <v-btn
                        variant="text"
                        :color="copied ? 'success' : 'primary'"
                        size="small"
                        :prepend-icon="
                            copied ? 'mdi-check' : 'mdi-content-copy'
                        "
                        @click="copyToClipboard"
                    >
                        {{ copied ? 'コピー済み' : 'コピー' }}
                    </v-btn>
                </div>

                <div class="d-flex justify-end">
                    <v-btn color="primary" @click="resetDialog = false">
                        閉じる
                    </v-btn>
                </div>
            </v-card>
        </v-dialog>
    </AuthenticatedLayout>
</template>
