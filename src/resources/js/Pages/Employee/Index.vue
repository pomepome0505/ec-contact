<script setup>
    import { ref } from 'vue';
    import { Head, router, usePage } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

    const navigateToEdit = (event, { item }) => {
        if (event.target.closest('button, a')) return;
        router.visit(route('employees.edit', item.id));
    };

    defineProps({
        employees: { type: Array, default: () => [] },
    });

    const page = usePage();

    const headers = [
        { title: 'ID', key: 'id', sortable: false, width: '80px' },
        { title: 'ログインID', key: 'login_id', sortable: false },
        { title: '氏名', key: 'name', sortable: false },
        { title: '権限', key: 'is_admin', sortable: false, width: '100px' },
        { title: 'ステータス', key: 'is_active', sortable: false, width: '120px' },
        { title: '登録日時', key: 'created_at', sortable: false },
        { title: '操作', key: 'actions', sortable: false, width: '300px' },
    ];

    const processing = ref(false);
    const errorMessage = ref('');
    const confirmDialog = ref(false);
    const targetEmployee = ref(null);
    const dialogAction = ref('toggle');

    const openConfirmDialog = (employee) => {
        if (employee.id === page.props.auth.user.id) {
            errorMessage.value = '自分自身のアカウントは無効化できません。';
            return;
        }
        errorMessage.value = '';
        targetEmployee.value = employee;
        dialogAction.value = 'toggle';
        confirmDialog.value = true;
    };

    const openDeleteDialog = (employee) => {
        if (employee.id === page.props.auth.user.id) {
            errorMessage.value = '自分自身のアカウントは削除できません。';
            return;
        }
        errorMessage.value = '';
        targetEmployee.value = employee;
        dialogAction.value = 'delete';
        confirmDialog.value = true;
    };

    const executeToggleActive = () => {
        if (processing.value || !targetEmployee.value) return;
        processing.value = true;
        confirmDialog.value = false;
        router.patch(
            route('employees.toggleActive', targetEmployee.value.id),
            {},
            {
                preserveState: true,
                onFinish: () => {
                    processing.value = false;
                    targetEmployee.value = null;
                },
                onError: (errors) => {
                    errorMessage.value =
                        errors.toggleActive || '操作に失敗しました。';
                },
            },
        );
    };

    const executeDelete = () => {
        if (processing.value || !targetEmployee.value) return;
        processing.value = true;
        confirmDialog.value = false;
        router.delete(
            route('employees.destroy', targetEmployee.value.id),
            {
                preserveState: true,
                onFinish: () => {
                    processing.value = false;
                    targetEmployee.value = null;
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
        <Head title="従業員管理" />

        <div class="d-flex align-center justify-space-between mb-4">
            <div class="d-flex align-center">
                <v-icon
                    icon="mdi-account-group-outline"
                    color="primary"
                    class="mr-3"
                />
                <span class="text-h6" style="font-weight: 600; color: #0f172a">
                    従業員管理
                </span>
            </div>
            <v-btn
                color="primary"
                prepend-icon="mdi-plus"
                :href="route('employees.create')"
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
                :items="employees"
                :items-per-page="-1"
                hide-default-footer
                @click:row="navigateToEdit"
            >
                <!-- eslint-disable-next-line vue/valid-v-slot -->
                <template #item.is_admin="{ item }">
                    <v-chip
                        :color="item.is_admin ? 'primary' : 'default'"
                        variant="tonal"
                        size="small"
                        label
                    >
                        {{ item.is_admin ? '管理者' : '一般' }}
                    </v-chip>
                </template>

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
                        :href="route('employees.edit', item.id)"
                        class="mr-3"
                    />
                    <v-btn
                        variant="outlined"
                        :color="item.is_active ? 'error' : 'success'"
                        size="small"
                        :prepend-icon="item.is_active ? 'mdi-account-off-outline' : 'mdi-account-check-outline'"
                        :loading="processing"
                        :disabled="item.id === page.props.auth.user.id"
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
                        :disabled="item.id === page.props.auth.user.id"
                        @click="openDeleteDialog(item)"
                    />
                </template>
            </v-data-table>
        </v-card>

        <v-dialog v-model="confirmDialog" max-width="440">
            <v-card class="pa-2">
                <v-card-title class="text-h6 pa-4">確認</v-card-title>
                <v-card-text v-if="targetEmployee" class="px-4 pb-4">
                    <template v-if="dialogAction === 'delete'">
                        {{ targetEmployee.name
                        }}さんのアカウントを削除しますか？この操作は取り消せません。
                    </template>
                    <template v-else>
                        {{ targetEmployee.name }}さんのアカウントを{{
                            targetEmployee.is_active ? '無効化' : '有効化'
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
                            targetEmployee?.is_active ? 'error' : 'success'
                        "
                        variant="flat"
                        :loading="processing"
                        @click="executeToggleActive"
                    >
                        {{
                            targetEmployee?.is_active
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

