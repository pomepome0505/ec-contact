<script setup>
    import { Head } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
</script>

<template>
    <AuthenticatedLayout>
        <Head title="ダッシュボード" />

        <v-card class="pa-8" style="border: 1px solid #e2e8f0">
            <div class="d-flex align-center mb-4">
                <v-icon
                    icon="mdi-view-dashboard-outline"
                    color="primary"
                    class="mr-3"
                />
                <span
                    class="text-h6"
                    style="font-weight: 600; color: #0f172a"
                >
                    ダッシュボード
                </span>
            </div>
            <v-alert type="info" variant="tonal" class="mb-0">
                <div class="text-body-2">
                    ダッシュボードは第4フェーズで実装予定です。<br />
                    問い合わせ件数の集計やステータス別のグラフなどを表示する想定です。
                </div>
            </v-alert>
        </v-card>
    </AuthenticatedLayout>
</template>
