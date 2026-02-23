<script setup>
    import { computed, ref, watch } from 'vue';
    import { Head, router } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

    defineProps({
        categories: { type: Array, default: () => [] },
        channels: { type: Array, default: () => [] },
        statuses: { type: Array, default: () => [] },
        priorities: { type: Array, default: () => [] },
        staffs: { type: Array, default: () => [] },
    });

    const formRef = ref(null);
    const formValid = ref(false);
    const processing = ref(false);
    const errors = ref({});

    const form = ref({
        channel: 'phone',
        category_id: null,
        customer_name: '',
        customer_email: '',
        order_number: '',
        subject: '',
        body: '',
        internal_notes: '',
        staff_id: null,
        status: 'pending',
        priority: 'medium',
    });

    const isForm = computed(() => form.value.channel === 'form');

    const customerEmailRules = computed(() => {
        const base = [
            (v) =>
                !v ||
                /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ||
                '正しいメールアドレスを入力してください',
            (v) =>
                !v ||
                v.length <= 100 ||
                '100文字以内で入力してください',
        ];
        if (isForm.value) {
            return [
                (v) => !!v || 'メールアドレスを入力してください',
                ...base,
            ];
        }
        return base;
    });

    const subjectRules = computed(() => {
        if (isForm.value) {
            return [
                (v) => !!v || '件名を入力してください',
                (v) =>
                    (v && v.length <= 200) ||
                    '200文字以内で入力してください',
            ];
        }
        return [];
    });

    const bodyRules = computed(() => {
        if (isForm.value) {
            return [(v) => !!v || '本文を入力してください'];
        }
        return [];
    });

    watch(
        () => form.value.channel,
        () => {
            if (formRef.value) {
                formRef.value.validate();
            }
        },
    );

    const rules = {
        channel: [(v) => !!v || '受付区分を選択してください'],
        category_id: [(v) => !!v || 'カテゴリを選択してください'],
        customer_name: [
            (v) => !!v || '顧客名を入力してください',
            (v) => (v && v.length <= 100) || '100文字以内で入力してください',
        ],
        order_number: [
            (v) =>
                !v ||
                v.length <= 50 ||
                '50文字以内で入力してください',
        ],
        status: [(v) => !!v || 'ステータスを選択してください'],
        priority: [(v) => !!v || '優先度を選択してください'],
    };

    async function submit() {
        const { valid } = await formRef.value.validate();
        if (!valid) return;

        processing.value = true;
        router.post(route('inquiries.store'), form.value, {
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
        <Head title="問い合わせ作成" />

        <div class="d-flex align-center mb-4">
            <v-btn
                variant="text"
                color="secondary"
                icon="mdi-arrow-left"
                :href="route('inquiries.index')"
                class="mr-2"
            />
            <v-icon
                icon="mdi-plus-circle-outline"
                color="primary"
                class="mr-3"
            />
            <span class="text-h6" style="font-weight: 600; color: #0f172a">
                問い合わせ作成
            </span>
        </div>

        <v-card class="pa-6" style="border: 1px solid #e2e8f0">
            <v-form
                ref="formRef"
                v-model="formValid"
                @submit.prevent="submit"
            >
                <v-row dense>
                    <v-col cols="12" sm="6" md="4">
                        <v-select
                            v-model="form.channel"
                            :items="channels"
                            item-title="label"
                            item-value="value"
                            label="受付区分"
                            :rules="rules.channel"
                            :error-messages="errors.channel"
                            variant="outlined"
                            density="compact"
                            rounded="lg"
                            color="primary"
                        />
                    </v-col>
                    <v-col cols="12" sm="6" md="4">
                        <v-select
                            v-model="form.category_id"
                            :items="categories"
                            item-title="label"
                            item-value="value"
                            label="カテゴリ"
                            :rules="rules.category_id"
                            :error-messages="errors.category_id"
                            variant="outlined"
                            density="compact"
                            rounded="lg"
                            color="primary"
                        />
                    </v-col>
                    <v-col cols="12" sm="6" md="4">
                        <v-select
                            v-model="form.staff_id"
                            :items="staffs"
                            item-title="name"
                            item-value="id"
                            label="担当者"
                            :error-messages="errors.staff_id"
                            variant="outlined"
                            density="compact"
                            rounded="lg"
                            color="primary"
                            clearable
                        />
                    </v-col>
                    <v-col cols="12" sm="6">
                        <v-text-field
                            v-model="form.customer_name"
                            label="顧客名"
                            :rules="rules.customer_name"
                            :error-messages="errors.customer_name"
                            variant="outlined"
                            density="compact"
                            rounded="lg"
                            color="primary"
                            prepend-inner-icon="mdi-account-outline"
                        />
                    </v-col>
                    <v-col cols="12" sm="6">
                        <v-text-field
                            v-model="form.customer_email"
                            :label="isForm ? 'メールアドレス' : 'メールアドレス（任意）'"
                            :rules="customerEmailRules"
                            :error-messages="errors.customer_email"
                            variant="outlined"
                            density="compact"
                            rounded="lg"
                            color="primary"
                            prepend-inner-icon="mdi-email-outline"
                        />
                    </v-col>
                    <v-col cols="12" sm="6" md="4">
                        <v-text-field
                            v-model="form.order_number"
                            label="注文番号（任意）"
                            :rules="rules.order_number"
                            :error-messages="errors.order_number"
                            variant="outlined"
                            density="compact"
                            rounded="lg"
                            color="primary"
                            prepend-inner-icon="mdi-package-variant"
                        />
                    </v-col>
                    <v-col cols="12" sm="6" md="4">
                        <v-select
                            v-model="form.status"
                            :items="statuses"
                            item-title="label"
                            item-value="value"
                            label="ステータス"
                            :rules="rules.status"
                            :error-messages="errors.status"
                            variant="outlined"
                            density="compact"
                            rounded="lg"
                            color="primary"
                        />
                    </v-col>
                    <v-col cols="12" sm="6" md="4">
                        <v-select
                            v-model="form.priority"
                            :items="priorities"
                            item-title="label"
                            item-value="value"
                            label="優先度"
                            :rules="rules.priority"
                            :error-messages="errors.priority"
                            variant="outlined"
                            density="compact"
                            rounded="lg"
                            color="primary"
                        />
                    </v-col>

                    <!-- フォーム経由: 件名・本文 -->
                    <template v-if="isForm">
                        <v-col cols="12">
                            <v-text-field
                                v-model="form.subject"
                                label="件名"
                                :rules="subjectRules"
                                :error-messages="errors.subject"
                                variant="outlined"
                                density="compact"
                                rounded="lg"
                                color="primary"
                            />
                        </v-col>
                        <v-col cols="12">
                            <v-textarea
                                v-model="form.body"
                                label="本文"
                                :rules="bodyRules"
                                :error-messages="errors.body"
                                variant="outlined"
                                density="compact"
                                rounded="lg"
                                color="primary"
                                rows="6"
                                auto-grow
                            />
                        </v-col>
                    </template>

                    <!-- 電話経由: 社内メモ -->
                    <v-col v-if="!isForm" cols="12">
                        <v-textarea
                            v-model="form.internal_notes"
                            label="社内メモ"
                            :error-messages="errors.internal_notes"
                            variant="outlined"
                            density="compact"
                            rounded="lg"
                            color="primary"
                            rows="6"
                            auto-grow
                            placeholder="電話の内容をメモ..."
                        />
                    </v-col>
                </v-row>

                <div class="d-flex justify-end mt-4">
                    <v-btn
                        variant="text"
                        color="secondary"
                        class="mr-2"
                        :href="route('inquiries.index')"
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
