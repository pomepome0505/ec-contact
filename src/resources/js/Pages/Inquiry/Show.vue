<script setup>
    import { ref } from 'vue';
    import { Head, router } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

    const props = defineProps({
        inquiry: { type: Object, default: () => ({}) },
        statuses: { type: Array, default: () => [] },
        priorities: { type: Array, default: () => [] },
        staffs: { type: Array, default: () => [] },
    });

    const TEXT_LIMIT = 500;
    const expandedNotes = ref(false);
    const expandedMessages = ref({});
    const editing = ref(false);
    const editForm = ref({});
    const showSuccessDialog = ref(false);
    const successMessage = ref('');
    const showReplyDialog = ref(false);
    const replyForm = ref({ subject: '', body: '' });
    const replySubmitting = ref(false);
    const showCustomerMessageDialog = ref(false);
    const customerMessageForm = ref({ subject: '', body: '' });
    const customerMessageSubmitting = ref(false);

    const isLong = (text) => text && text.length > TEXT_LIMIT;
    const truncate = (text) => text.slice(0, TEXT_LIMIT) + '...';

    const toggleMessage = (id) => {
        expandedMessages.value[id] = !expandedMessages.value[id];
    };

    const startEdit = () => {
        editForm.value = {
            status: props.inquiry.status,
            priority: props.inquiry.priority,
            staff_id: props.inquiry.staff_id,
            internal_notes: props.inquiry.internal_notes || '',
        };
        editing.value = true;
    };

    const cancelEdit = () => {
        editing.value = false;
    };

    const submitEdit = () => {
        router.patch(
            route('inquiries.update', props.inquiry.id),
            editForm.value,
            {
                preserveScroll: true,
                onSuccess: () => {
                    editing.value = false;
                    showNotification('問い合わせ情報を更新しました。');
                },
            },
        );
    };

    const messageTypeLabel = (type) => {
        const labels = {
            initial_inquiry: '問い合わせ',
            customer_reply: '顧客返信',
            staff_reply: 'スタッフ返信',
        };
        return labels[type] || type;
    };

    const messageTypeColor = (type) => {
        const colors = {
            initial_inquiry: 'primary',
            customer_reply: 'warning',
            staff_reply: 'success',
        };
        return colors[type] || 'secondary';
    };

    const messageTypeIcon = (type) => {
        const icons = {
            initial_inquiry: 'mdi-email-outline',
            customer_reply: 'mdi-reply',
            staff_reply: 'mdi-reply',
        };
        return icons[type] || 'mdi-message-outline';
    };

    const showNotification = (message) => {
        successMessage.value = message;
        showSuccessDialog.value = true;
        setTimeout(() => {
            showSuccessDialog.value = false;
        }, 3000);
    };

    const openReplyDialog = () => {
        replyForm.value = { subject: '', body: '' };
        showReplyDialog.value = true;
    };

    const submitReply = () => {
        replySubmitting.value = true;
        router.post(
            route('inquiries.reply', props.inquiry.id),
            replyForm.value,
            {
                preserveScroll: true,
                onSuccess: () => {
                    showReplyDialog.value = false;
                    showNotification('返信メールを送信しました。');
                },
                onFinish: () => {
                    replySubmitting.value = false;
                },
            },
        );
    };

    const openCustomerMessageDialog = () => {
        customerMessageForm.value = { subject: '', body: '' };
        showCustomerMessageDialog.value = true;
    };

    const submitCustomerMessage = () => {
        customerMessageSubmitting.value = true;
        router.post(
            route('inquiries.customer-message', props.inquiry.id),
            customerMessageForm.value,
            {
                preserveScroll: true,
                onSuccess: () => {
                    showCustomerMessageDialog.value = false;
                    showNotification('顧客メッセージを登録しました。');
                },
                onFinish: () => {
                    customerMessageSubmitting.value = false;
                },
            },
        );
    };

</script>

<template>
    <AuthenticatedLayout>
        <Head :title="`問い合わせ ${inquiry.inquiry_number}`" />

        <div class="d-flex align-center mb-4">
            <v-btn
                variant="text"
                color="secondary"
                icon="mdi-arrow-left"
                :href="route('inquiries.index')"
                class="mr-2"
            />
            <v-icon
                icon="mdi-text-box-outline"
                color="primary"
                class="mr-3"
            />
            <span
                class="text-h6"
                style="font-weight: 600; color: #0f172a"
            >
                {{ inquiry.inquiry_number }}
            </span>
        </div>

        <!-- 基本情報 -->
        <v-card class="mb-4" style="border: 1px solid #e2e8f0">
            <v-card-title
                class="d-flex align-center justify-space-between pa-4 pb-2"
            >
                <div class="text-subtitle-1 font-weight-bold">
                    <v-icon icon="mdi-information-outline" class="mr-2" />
                    基本情報
                </div>
                <v-btn
                    v-if="!editing"
                    variant="text"
                    color="secondary"
                    size="small"
                    icon="mdi-pencil"
                    @click="startEdit"
                />
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-4">
                <v-row dense>
                    <v-col cols="12" sm="6" md="4">
                        <template v-if="editing">
                            <v-select
                                v-model="editForm.status"
                                :items="statuses"
                                item-title="label"
                                item-value="value"
                                label="ステータス"
                                variant="outlined"
                                density="compact"
                                rounded="lg"
                                color="primary"
                                hide-details
                            />
                        </template>
                        <template v-else>
                            <div
                                class="text-caption text-medium-emphasis mb-1"
                            >
                                ステータス
                            </div>
                            <v-chip
                                :color="inquiry.status_color"
                                variant="tonal"
                                size="small"
                                label
                            >
                                {{ inquiry.status_label }}
                            </v-chip>
                        </template>
                    </v-col>
                    <v-col cols="12" sm="6" md="4">
                        <div class="text-caption text-medium-emphasis mb-1">
                            受付区分
                        </div>
                        <v-chip
                            :color="inquiry.channel_color"
                            variant="tonal"
                            size="small"
                            label
                        >
                            {{ inquiry.channel_label }}
                        </v-chip>
                    </v-col>
                    <v-col cols="12" sm="6" md="4">
                        <div class="text-caption text-medium-emphasis mb-1">
                            カテゴリ
                        </div>
                        <span class="text-body-2">
                            {{ inquiry.category_label }}
                        </span>
                    </v-col>
                    <v-col cols="12" sm="6" md="4">
                        <template v-if="editing">
                            <v-select
                                v-model="editForm.priority"
                                :items="priorities"
                                item-title="label"
                                item-value="value"
                                label="優先度"
                                variant="outlined"
                                density="compact"
                                rounded="lg"
                                color="primary"
                                hide-details
                            />
                        </template>
                        <template v-else>
                            <div
                                class="text-caption text-medium-emphasis mb-1"
                            >
                                優先度
                            </div>
                            <v-chip
                                :color="inquiry.priority_color"
                                variant="tonal"
                                size="small"
                                label
                            >
                                {{ inquiry.priority_label }}
                            </v-chip>
                        </template>
                    </v-col>
                    <v-col cols="12" sm="6" md="4">
                        <div class="text-caption text-medium-emphasis mb-1">
                            顧客名
                        </div>
                        <span class="text-body-2">
                            {{ inquiry.customer_name }}
                        </span>
                    </v-col>
                    <v-col cols="12" sm="6" md="4">
                        <div class="text-caption text-medium-emphasis mb-1">
                            メールアドレス
                        </div>
                        <span class="text-body-2">
                            {{ inquiry.customer_email }}
                        </span>
                    </v-col>
                    <v-col cols="12" sm="6" md="4">
                        <div class="text-caption text-medium-emphasis mb-1">
                            注文番号
                        </div>
                        <span class="text-body-2">
                            {{ inquiry.order_number || '—' }}
                        </span>
                    </v-col>
                    <v-col cols="12" sm="6" md="4">
                        <template v-if="editing">
                            <v-select
                                v-model="editForm.staff_id"
                                :items="staffs"
                                item-title="name"
                                item-value="id"
                                label="担当者"
                                variant="outlined"
                                density="compact"
                                rounded="lg"
                                color="primary"
                                hide-details
                                clearable
                            />
                        </template>
                        <template v-else>
                            <div
                                class="text-caption text-medium-emphasis mb-1"
                            >
                                担当者
                            </div>
                            <span class="text-body-2">
                                {{ inquiry.staff_name || '未割当' }}
                            </span>
                        </template>
                    </v-col>
                    <v-col cols="12" sm="6" md="4">
                        <div class="text-caption text-medium-emphasis mb-1">
                            受付日時
                        </div>
                        <span class="text-body-2">
                            {{ inquiry.created_at }}
                        </span>
                    </v-col>
                    <v-col cols="12" sm="6" md="4">
                        <div class="text-caption text-medium-emphasis mb-1">
                            更新日時
                        </div>
                        <span class="text-body-2">
                            {{ inquiry.updated_at }}
                        </span>
                    </v-col>
                </v-row>
                <div v-if="editing" class="d-flex justify-end mt-4" style="gap: 8px">
                    <v-btn
                        variant="outlined"
                        color="secondary"
                        size="small"
                        @click="cancelEdit"
                    >
                        キャンセル
                    </v-btn>
                    <v-btn
                        variant="flat"
                        color="primary"
                        size="small"
                        @click="submitEdit"
                    >
                        確定
                    </v-btn>
                </div>
                <div class="mt-4">
                    <div class="text-caption text-medium-emphasis mb-1">
                        社内メモ
                    </div>
                    <template v-if="editing">
                        <v-textarea
                            v-model="editForm.internal_notes"
                            variant="outlined"
                            density="compact"
                            rounded="lg"
                            color="primary"
                            hide-details
                            rows="3"
                            auto-grow
                            placeholder="社内メモを入力..."
                        />
                    </template>
                    <template v-else>
                        <div
                            class="text-body-2 pa-3 rounded-lg"
                            style="
                                background-color: #f8fafc;
                                border: 1px solid #e2e8f0;
                                white-space: pre-wrap;
                            "
                        >
                            <template v-if="inquiry.internal_notes">
                                {{
                                    isLong(inquiry.internal_notes) &&
                                    !expandedNotes
                                        ? truncate(inquiry.internal_notes)
                                        : inquiry.internal_notes
                                }}
                                <v-btn
                                    v-if="isLong(inquiry.internal_notes)"
                                    variant="text"
                                    color="primary"
                                    size="x-small"
                                    class="ml-1"
                                    @click="expandedNotes = !expandedNotes"
                                >
                                    {{
                                        expandedNotes
                                            ? '閉じる'
                                            : 'もっと見る'
                                    }}
                                </v-btn>
                            </template>
                            <span
                                v-else
                                class="text-medium-emphasis"
                            >
                                メモはありません
                            </span>
                        </div>
                    </template>
                </div>
            </v-card-text>
        </v-card>

        <!-- メッセージ履歴 -->
        <v-card style="border: 1px solid #e2e8f0">
            <v-card-title
                class="d-flex align-center justify-space-between pa-4 pb-2"
            >
                <div class="text-subtitle-1 font-weight-bold">
                    <v-icon icon="mdi-message-text-outline" class="mr-2" />
                    メッセージ履歴
                </div>
                <div class="d-flex" style="gap: 8px">
                    <v-tooltip
                        :disabled="!!inquiry.customer_email"
                        text="メールアドレスが未登録のため顧客メッセージを登録できません"
                        location="bottom"
                    >
                        <template #activator="{ props: tooltipProps }">
                            <span
                                v-bind="tooltipProps"
                                class="d-inline-flex"
                            >
                                <v-btn
                                    variant="outlined"
                                    color="warning"
                                    size="small"
                                    prepend-icon="mdi-email-plus-outline"
                                    :disabled="!inquiry.customer_email"
                                    @click="openCustomerMessageDialog"
                                >
                                    顧客メッセージ登録
                                </v-btn>
                            </span>
                        </template>
                    </v-tooltip>
                    <v-tooltip
                        :disabled="!!inquiry.customer_email"
                        text="メールアドレスが未登録のため返信できません"
                        location="bottom"
                    >
                        <template #activator="{ props: tooltipProps }">
                            <span
                                v-bind="tooltipProps"
                                class="d-inline-flex"
                            >
                                <v-btn
                                    variant="flat"
                                    color="primary"
                                    size="small"
                                    prepend-icon="mdi-reply"
                                    :disabled="!inquiry.customer_email"
                                    @click="openReplyDialog"
                                >
                                    返信
                                </v-btn>
                            </span>
                        </template>
                    </v-tooltip>
                </div>
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-4">
                <div
                    v-if="inquiry.messages && inquiry.messages.length > 0"
                    class="d-flex flex-column"
                    style="gap: 16px"
                >
                    <v-card
                        v-for="message in inquiry.messages"
                        :key="message.id"
                        variant="outlined"
                        :style="{
                            'border-left':
                                message.message_type === 'staff_reply'
                                    ? '4px solid #16A34A'
                                    : message.message_type === 'customer_reply'
                                      ? '4px solid #D97706'
                                      : '4px solid #2563EB',
                        }"
                    >
                        <v-card-text class="pa-4">
                            <div
                                class="d-flex align-center justify-space-between mb-2"
                            >
                                <div class="d-flex align-center">
                                    <v-icon
                                        :icon="
                                            messageTypeIcon(
                                                message.message_type,
                                            )
                                        "
                                        :color="
                                            messageTypeColor(
                                                message.message_type,
                                            )
                                        "
                                        size="small"
                                        class="mr-2"
                                    />
                                    <v-chip
                                        :color="
                                            messageTypeColor(
                                                message.message_type,
                                            )
                                        "
                                        variant="tonal"
                                        size="x-small"
                                        label
                                    >
                                        {{
                                            messageTypeLabel(
                                                message.message_type,
                                            )
                                        }}
                                    </v-chip>
                                    <span
                                        v-if="message.staff_name"
                                        class="text-body-2 text-medium-emphasis ml-2"
                                    >
                                        {{ message.staff_name }}
                                    </span>
                                </div>
                                <span
                                    class="text-caption text-medium-emphasis"
                                >
                                    {{ message.created_at }}
                                </span>
                            </div>
                            <div
                                class="text-subtitle-2 font-weight-bold mb-1"
                            >
                                {{ message.subject }}
                            </div>
                            <div
                                class="text-body-2"
                                style="white-space: pre-wrap"
                            >
                                {{
                                    isLong(message.body) &&
                                    !expandedMessages[message.id]
                                        ? truncate(message.body)
                                        : message.body
                                }}
                            </div>
                            <v-btn
                                v-if="isLong(message.body)"
                                variant="text"
                                color="primary"
                                size="x-small"
                                class="mt-1"
                                @click="toggleMessage(message.id)"
                            >
                                {{
                                    expandedMessages[message.id]
                                        ? '閉じる'
                                        : 'もっと見る'
                                }}
                            </v-btn>
                        </v-card-text>
                    </v-card>
                </div>
                <div v-else class="text-center text-medium-emphasis pa-4">
                    メッセージはありません
                </div>
            </v-card-text>
        </v-card>
        <!-- 返信ダイアログ -->
        <v-dialog v-model="showReplyDialog" max-width="600" persistent>
            <v-card>
                <v-card-title class="text-subtitle-1 font-weight-bold pa-4">
                    <v-icon icon="mdi-reply" class="mr-2" />
                    返信メール送信
                </v-card-title>
                <v-divider />
                <v-card-text class="pa-4">
                    <v-alert
                        type="warning"
                        variant="tonal"
                        icon="mdi-alert-circle-outline"
                        class="mb-4"
                    >
                        現在、本番環境でAmazon SESが利用できないため、メールは送信されません。
                    </v-alert>
                    <v-text-field
                        v-model="replyForm.subject"
                        label="件名"
                        variant="outlined"
                        density="compact"
                        rounded="lg"
                        color="primary"
                        class="mb-3"
                        hide-details
                    />
                    <v-textarea
                        v-model="replyForm.body"
                        label="本文"
                        variant="outlined"
                        density="compact"
                        rounded="lg"
                        color="primary"
                        rows="8"
                        hide-details
                    />
                </v-card-text>
                <v-divider />
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn
                        variant="outlined"
                        color="secondary"
                        size="small"
                        :disabled="replySubmitting"
                        @click="showReplyDialog = false"
                    >
                        キャンセル
                    </v-btn>
                    <v-btn
                        variant="flat"
                        color="primary"
                        size="small"
                        prepend-icon="mdi-send"
                        :loading="replySubmitting"
                        :disabled="!replyForm.subject || !replyForm.body"
                        @click="submitReply"
                    >
                        送信
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- 顧客メッセージ登録ダイアログ -->
        <v-dialog v-model="showCustomerMessageDialog" max-width="600" persistent>
            <v-card>
                <v-card-title class="text-subtitle-1 font-weight-bold pa-4">
                    <v-icon icon="mdi-email-plus-outline" class="mr-2" />
                    顧客メッセージ登録
                </v-card-title>
                <v-divider />
                <v-card-text class="pa-4">
                    <v-text-field
                        v-model="customerMessageForm.subject"
                        label="件名"
                        variant="outlined"
                        density="compact"
                        rounded="lg"
                        color="primary"
                        class="mb-3"
                        hide-details
                    />
                    <v-textarea
                        v-model="customerMessageForm.body"
                        label="本文"
                        variant="outlined"
                        density="compact"
                        rounded="lg"
                        color="primary"
                        rows="8"
                        hide-details
                    />
                </v-card-text>
                <v-divider />
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn
                        variant="outlined"
                        color="secondary"
                        size="small"
                        :disabled="customerMessageSubmitting"
                        @click="showCustomerMessageDialog = false"
                    >
                        キャンセル
                    </v-btn>
                    <v-btn
                        variant="flat"
                        color="warning"
                        size="small"
                        prepend-icon="mdi-check"
                        :loading="customerMessageSubmitting"
                        :disabled="!customerMessageForm.subject || !customerMessageForm.body"
                        @click="submitCustomerMessage"
                    >
                        登録
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- 通知 -->
        <v-snackbar
            v-model="showSuccessDialog"
            :timeout="3000"
            color="success"
            location="top"
        >
            <div class="d-flex align-center">
                <v-icon icon="mdi-check-circle-outline" class="mr-2" />
                {{ successMessage }}
            </div>
        </v-snackbar>
    </AuthenticatedLayout>
</template>
