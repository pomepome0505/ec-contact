<script setup>
    import { ref } from 'vue';
    import { Head, router } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

    const navigateToEdit = (event, { item }) => {
        if (event.target.closest('button, a')) return;
        router.visit(route('categories.edit', item.id));
    };

    defineProps({
        categories: { type: Array, default: () => [] },
    });

    const headers = [
        { title: 'ID', key: 'id', sortable: false, width: '80px' },
        { title: 'カテゴリ名', key: 'name', sortable: false },
        { title: '表示順', key: 'display_order', sortable: false, width: '100px' },
        { title: 'ステータス', key: 'is_active', sortable: false, width: '120px' },
        { title: '操作', key: 'actions', sortable: false, width: '300px' },
    ];

    const processing = ref(false);
    const errorMessage = ref('');
    const confirmDialog = ref(false);
    const targetCategory = ref(null);
    const dialogAction = ref('toggle');

    const openConfirmDialog = (category) => {
        errorMessage.value = '';
        targetCategory.value = category;
        dialogAction.value = 'toggle';
        confirmDialog.value = true;
    };

    const openDeleteDialog = (category) => {
        errorMessage.value = '';
        targetCategory.value = category;
        dialogAction.value = 'delete';
        confirmDialog.value = true;
    };

    const executeToggleActive = () => {
        if (processing.value || !targetCategory.value) return;
        processing.value = true;
        confirmDialog.value = false;
        router.patch(
            route('categories.toggleActive', targetCategory.value.id),
            {},
            {
                preserveState: true,
                onFinish: () => {
                    processing.value = false;
                    targetCategory.value = null;
                },
            },
        );
    };

    const executeDelete = () => {
        if (processing.value || !targetCategory.value) return;
        processing.value = true;
        confirmDialog.value = false;
        router.delete(
            route('categories.destroy', targetCategory.value.id),
            {
                preserveState: true,
                onFinish: () => {
                    processing.value = false;
                    targetCategory.value = null;
                },
                onError: (errors) => {
                    errorMessage.value =
                        errors.delete || '削除に失敗しました。';
                },
            },
        );
    };
</script>

<template>
    <AuthenticatedLayout>
        <Head title="カテゴリ管理" />

        <div class="d-flex align-center justify-space-between mb-4">
            <div class="d-flex align-center">
                <v-icon icon="mdi-shape-outline" color="primary" class="mr-3" />
                <span class="text-h6" style="font-weight: 600; color: #0f172a">
                    カテゴリ管理
                </span>
            </div>
            <v-btn
                color="primary"
                prepend-icon="mdi-plus"
                :href="route('categories.create')"
            >
                新規作成
            </v-btn>
        </div>

        <v-alert
            v-if="errorMessage"
            type="error"
            variant="tonal"
            class="mb-4"
            closable
            @click:close="errorMessage = ''"
        >
            {{ errorMessage }}
        </v-alert>

        <v-card style="border: 1px solid #e2e8f0">
            <v-data-table
                :headers="headers"
                :items="categories"
                :items-per-page="-1"
                hide-default-footer
                @click:row="navigateToEdit"
            >
                <!-- eslint-disable-next-line vue/valid-v-slot -->
                <template #item.is_active="{ item }">
                    <v-chip
                        :color="item.is_active ? 'success' : 'error'"
                        variant="tonal"
                        size="small"
                        label
                    >
                        {{ item.is_active ? '有効' : '無効' }}
                    </v-chip>
                </template>

                <!-- eslint-disable-next-line vue/valid-v-slot -->
                <template #item.actions="{ item }">
                    <v-btn
                        variant="text"
                        color="primary"
                        size="small"
                        icon="mdi-pencil-outline"
                        :href="route('categories.edit', item.id)"
                        class="mr-3"
                    />
                    <v-btn
                        variant="outlined"
                        :color="item.is_active ? 'error' : 'success'"
                        size="small"
                        :prepend-icon="item.is_active ? 'mdi-close-circle-outline' : 'mdi-check-circle-outline'"
                        :loading="processing"
                        class="mr-3"
                        @click="openConfirmDialog(item)"
                    >
                        {{ item.is_active ? '無効化' : '有効化' }}
                    </v-btn>
                    <v-btn
                        variant="text"
                        color="error"
                        size="small"
                        icon="mdi-delete-outline"
                        :loading="processing"
                        @click="openDeleteDialog(item)"
                    />
                </template>
            </v-data-table>
        </v-card>

        <v-dialog v-model="confirmDialog" max-width="440">
            <v-card class="pa-2">
                <v-card-title class="text-h6 pa-4">確認</v-card-title>
                <v-card-text v-if="targetCategory" class="px-4 pb-4">
                    <template v-if="dialogAction === 'delete'">
                        カテゴリ「{{ targetCategory.name
                        }}」を削除しますか？この操作は取り消せません。
                    </template>
                    <template v-else>
                        カテゴリ「{{ targetCategory.name }}」を{{
                            targetCategory.is_active ? '無効化' : '有効化'
                        }}しますか？
                    </template>
                </v-card-text>
                <v-card-actions class="pa-4 pt-0">
                    <v-spacer />
                    <v-btn
                        variant="text"
                        color="secondary"
                        @click="confirmDialog = false"
                    >
                        キャンセル
                    </v-btn>
                    <v-btn
                        v-if="dialogAction === 'delete'"
                        color="error"
                        variant="flat"
                        :loading="processing"
                        @click="executeDelete"
                    >
                        削除する
                    </v-btn>
                    <v-btn
                        v-else
                        :color="
                            targetCategory?.is_active ? 'error' : 'success'
                        "
                        variant="flat"
                        :loading="processing"
                        @click="executeToggleActive"
                    >
                        {{
                            targetCategory?.is_active
                                ? '無効化する'
                                : '有効化する'
                        }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </AuthenticatedLayout>
</template>

<style scoped>
    :deep(.v-data-table tbody tr:hover) {
        background-color: rgba(0, 0, 0, 0.04);
    }
</style>
