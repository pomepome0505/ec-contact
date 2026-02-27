<script setup>
import { ref, reactive, onMounted } from 'vue';

const formRef = ref(null);
const formValid = ref(false);
const submitting = ref(false);
const submitted = ref(false);
const inquiryNumber = ref('');
const serverError = ref('');

const categories = ref([]);

onMounted(async () => {
    try {
        const response = await fetch('/api/categories');
        const data = await response.json();
        categories.value = data.map((c) => ({ label: c.name, value: c.id }));
    } catch {
        categories.value = [];
    }
});

const form = reactive({
    customer_name: '',
    customer_email: '',
    category_id: null,
    order_number: '',
    subject: '',
    body: '',
});

const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const rules = {
    customer_name: [
        (v) => !!v || 'お名前を入力してください',
        (v) => (v && v.length <= 100) || '100文字以内で入力してください',
    ],
    customer_email: [
        (v) => !!v || 'メールアドレスを入力してください',
        (v) => emailRegex.test(v) || '正しいメールアドレスを入力してください',
        (v) => (v && v.length <= 100) || '100文字以内で入力してください',
    ],
    category_id: [(v) => !!v || 'カテゴリを選択してください'],
    subject: [
        (v) => !!v || '件名を入力してください',
        (v) => (v && v.length <= 200) || '200文字以内で入力してください',
    ],
    body: [(v) => !!v || 'お問い合わせ内容を入力してください'],
};

async function submitForm() {
    const { valid } = await formRef.value.validate();
    if (!valid) return;

    submitting.value = true;
    serverError.value = '';

    try {
        const response = await fetch('/api/inquiries', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(form),
        });

        const data = await response.json();

        if (!response.ok) {
            if (response.status === 422 && data.errors) {
                const messages = Object.values(data.errors).flat();
                serverError.value = messages.join(' ');
            } else {
                serverError.value =
                    'エラーが発生しました。しばらくしてから再度お試しください。';
            }
            return;
        }

        inquiryNumber.value = data.inquiry_number;
        submitted.value = true;
    } catch {
        serverError.value = '通信エラーが発生しました。ネットワーク接続をご確認ください。';
    } finally {
        submitting.value = false;
    }
}

function resetForm() {
    submitted.value = false;
    inquiryNumber.value = '';
    form.customer_name = '';
    form.customer_email = '';
    form.category_id = null;
    form.order_number = '';
    form.subject = '';
    form.body = '';
    formRef.value?.reset();
}
</script>

<template>
    <v-app>
        <v-main>
            <v-container style="max-width: 720px" class="py-8">
                <div class="text-center mb-6">
                    <v-icon icon="mdi-email-outline" size="48" color="primary" class="mb-2" />
                    <h1 class="text-h5 font-weight-bold">お問い合わせ</h1>
                    <p class="text-body-2 text-medium-emphasis mt-1">
                        株式会社ライフスタイルマート カスタマーサポート
                    </p>
                </div>

                <!-- 送信完了 -->
                <v-card v-if="submitted" class="pa-8 text-center">
                    <v-icon
                        icon="mdi-check-circle-outline"
                        size="64"
                        color="success"
                        class="mb-4"
                    />
                    <h2 class="text-h6 font-weight-bold mb-2">お問い合わせを受け付けました</h2>
                    <p class="text-body-2 text-medium-emphasis mb-4">
                        受付番号をお控えください。ご入力いただいたメールアドレスへ回答いたしますので、しばらくお待ちください。
                    </p>
                    <v-chip color="primary" size="large" variant="tonal" class="mb-6">
                        <v-icon icon="mdi-tag-outline" start />
                        {{ inquiryNumber }}
                    </v-chip>
                    <div>
                        <v-btn color="primary" variant="outlined" @click="resetForm">
                            新しいお問い合わせ
                        </v-btn>
                    </div>
                </v-card>

                <!-- フォーム -->
                <v-card v-if="!submitted" class="pa-6">
                    <v-alert
                        type="warning"
                        variant="tonal"
                        class="mb-4"
                        icon="mdi-alert-circle-outline"
                    >
                        現在、本番環境でAmazon SESが利用できないため、問い合わせ完了メールの自動送信機能は停止しています。
                    </v-alert>
                    <v-form ref="formRef" v-model="formValid" @submit.prevent="submitForm">
                        <v-row dense>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="form.customer_name"
                                    label="お名前"
                                    :rules="rules.customer_name"
                                    prepend-inner-icon="mdi-account-outline"
                                />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="form.customer_email"
                                    label="メールアドレス"
                                    type="email"
                                    :rules="rules.customer_email"
                                    prepend-inner-icon="mdi-email-outline"
                                />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-select
                                    v-model="form.category_id"
                                    label="カテゴリ"
                                    :items="categories"
                                    item-title="label"
                                    item-value="value"
                                    :rules="rules.category_id"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    color="primary"
                                    prepend-inner-icon="mdi-shape-outline"
                                />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="form.order_number"
                                    label="注文番号（任意）"
                                    prepend-inner-icon="mdi-receipt-text-outline"
                                />
                            </v-col>
                            <v-col cols="12">
                                <v-text-field
                                    v-model="form.subject"
                                    label="件名"
                                    :rules="rules.subject"
                                    prepend-inner-icon="mdi-format-title"
                                />
                            </v-col>
                            <v-col cols="12">
                                <v-textarea
                                    v-model="form.body"
                                    label="お問い合わせ内容"
                                    :rules="rules.body"
                                    rows="6"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    color="primary"
                                    prepend-inner-icon="mdi-text-box-outline"
                                />
                            </v-col>
                        </v-row>

                        <v-alert
                            v-if="serverError"
                            type="error"
                            variant="tonal"
                            class="mb-4"
                            closable
                            @click:close="serverError = ''"
                        >
                            {{ serverError }}
                        </v-alert>

                        <v-btn
                            type="submit"
                            color="primary"
                            size="large"
                            block
                            :loading="submitting"
                            :disabled="!formValid"
                        >
                            <v-icon icon="mdi-send" start />
                            送信する
                        </v-btn>
                    </v-form>
                </v-card>
            </v-container>
        </v-main>
    </v-app>
</template>
