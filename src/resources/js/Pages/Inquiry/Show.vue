<script setup>
    import { ref } from 'vue';
    import { Head, router } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

    defineProps({
        inquiry: { type: Object, default: () => ({}) },
    });

    const TEXT_LIMIT = 500;
    const expandedNotes = ref(false);
    const expandedMessages = ref({});

    const isLong = (text) => text && text.length > TEXT_LIMIT;
    const truncate = (text) => text.slice(0, TEXT_LIMIT) + '...';

    const toggleMessage = (id) => {
        expandedMessages.value[id] = !expandedMessages.value[id];
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

    const goBack = () => {
        router.get(route('inquiries.index'));
    };
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="`問い合わせ ${inquiry.inquiry_number}`" />

        <div class="d-flex align-center justify-space-between mb-4">
            <div class="d-flex align-center">
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
            <v-btn
                variant="outlined"
                color="secondary"
                size="small"
                prepend-icon="mdi-arrow-left"
                @click="goBack"
            >
                一覧に戻る
            </v-btn>
        </div>

        <!-- 基本情報 -->
        <v-card class="mb-4" style="border: 1px solid #e2e8f0">
            <v-card-title class="text-subtitle-1 font-weight-bold pa-4 pb-2">
                <v-icon icon="mdi-information-outline" class="mr-2" />
                基本情報
            </v-card-title>
            <v-divider />
            <v-card-text class="pa-4">
                <v-row dense>
                    <v-col cols="12" sm="6" md="4">
                        <div class="text-caption text-medium-emphasis mb-1">
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
                        <div class="text-caption text-medium-emphasis mb-1">
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
                        <div class="text-caption text-medium-emphasis mb-1">
                            担当者
                        </div>
                        <span class="text-body-2">
                            {{ inquiry.staff_name || '未割当' }}
                        </span>
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
                <div v-if="inquiry.internal_notes" class="mt-4">
                    <div class="text-caption text-medium-emphasis mb-1">
                        社内メモ
                    </div>
                    <div
                        class="text-body-2 pa-3 rounded-lg"
                        style="
                            background-color: #f8fafc;
                            border: 1px solid #e2e8f0;
                            white-space: pre-wrap;
                        "
                    >
                        {{
                            isLong(inquiry.internal_notes) && !expandedNotes
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
                            {{ expandedNotes ? '閉じる' : 'もっと見る' }}
                        </v-btn>
                    </div>
                </div>
            </v-card-text>
        </v-card>

        <!-- メッセージ履歴 -->
        <v-card style="border: 1px solid #e2e8f0">
            <v-card-title class="text-subtitle-1 font-weight-bold pa-4 pb-2">
                <v-icon icon="mdi-message-text-outline" class="mr-2" />
                メッセージ履歴
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
    </AuthenticatedLayout>
</template>
