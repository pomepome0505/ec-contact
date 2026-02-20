<script setup>
    import { computed } from 'vue';
    import { Head, router } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

    const props = defineProps({
        inquiries: { type: Object, default: () => ({}) },
        categories: { type: Array, default: () => [] },
        statuses: { type: Array, default: () => [] },
        priorities: { type: Array, default: () => [] },
    });

    const headers = [
        { title: '受付番号', key: 'inquiry_number', sortable: false },
        { title: 'ステータス', key: 'status_label', sortable: false },
        { title: 'カテゴリ', key: 'category_label', sortable: false },
        { title: '優先度', key: 'priority_label', sortable: false },
        { title: '顧客名', key: 'customer_name', sortable: false },
        { title: '担当者', key: 'staff_name', sortable: false },
        { title: '受付日時', key: 'created_at', sortable: false },
    ];

    const currentPage = computed(() => props.inquiries.current_page);
    const lastPage = computed(() => props.inquiries.last_page);

    const changePage = (page) => {
        router.get(
            route('inquiries.index'),
            { page },
            { preserveState: true },
        );
    };

    const onClickRow = (event, { item }) => {
        router.get(`/inquiries/${item.id}`);
    };
</script>

<template>
    <AuthenticatedLayout>
        <Head title="問い合わせ一覧" />

        <div class="d-flex align-center justify-space-between mb-4">
            <div class="d-flex align-center">
                <v-icon
                    icon="mdi-format-list-bulleted"
                    color="primary"
                    class="mr-3"
                />
                <span
                    class="text-h6"
                    style="font-weight: 600; color: #0f172a"
                >
                    問い合わせ一覧
                </span>
            </div>
            <v-chip variant="tonal" color="secondary" size="small">
                全 {{ inquiries.total }} 件
            </v-chip>
        </div>

        <v-card style="border: 1px solid #e2e8f0">
            <v-data-table
                :headers="headers"
                :items="inquiries.data"
                :items-per-page="inquiries.per_page"
                hide-default-footer
                @click:row="onClickRow"
            >
                <!-- eslint-disable-next-line vue/valid-v-slot -->
                <template #item.status_label="{ item }">
                    <v-chip
                        :color="item.status_color"
                        variant="tonal"
                        size="small"
                        label
                    >
                        {{ item.status_label }}
                    </v-chip>
                </template>

                <!-- eslint-disable-next-line vue/valid-v-slot -->
                <template #item.priority_label="{ item }">
                    <v-chip
                        :color="item.priority_color"
                        variant="tonal"
                        size="small"
                        label
                    >
                        {{ item.priority_label }}
                    </v-chip>
                </template>

                <!-- eslint-disable-next-line vue/valid-v-slot -->
                <template #item.staff_name="{ item }">
                    {{ item.staff_name || '未割当' }}
                </template>

                <template #bottom>
                    <div
                        v-if="lastPage > 1"
                        class="d-flex justify-center pa-4"
                    >
                        <v-pagination
                            :model-value="currentPage"
                            :length="lastPage"
                            :total-visible="7"
                            density="comfortable"
                            rounded="lg"
                            @update:model-value="changePage"
                        />
                    </div>
                </template>
            </v-data-table>
        </v-card>
    </AuthenticatedLayout>
</template>

<style scoped>
    :deep(.v-data-table tbody tr:hover) {
        background-color: rgba(0, 0, 0, 0.04);
    }
</style>