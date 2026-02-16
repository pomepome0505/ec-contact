<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';

const page = usePage();

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <v-app>
        <Head title="ホーム" />

        <v-app-bar elevation="0" style="border-bottom: 1px solid #e2e8f0;">
            <v-app-bar-title style="color: #0f172a; font-weight: 600;">
                問い合わせ管理システム
            </v-app-bar-title>

            <template #append>
                <span class="text-body-2 mr-3" style="color: #475569;">
                    {{ page.props.auth.user.name }}
                </span>
                <v-btn
                    variant="outlined"
                    color="secondary"
                    size="small"
                    prepend-icon="mdi-logout"
                    @click="logout"
                >
                    ログアウト
                </v-btn>
            </template>
        </v-app-bar>

        <v-main>
            <v-container style="max-width: 960px;" class="py-10">
                <v-card class="pa-8" style="border: 1px solid #e2e8f0;">
                    <div class="d-flex align-center mb-2">
                        <v-icon icon="mdi-view-dashboard-outline" color="primary" class="mr-3" />
                        <span class="text-h6" style="font-weight: 600; color: #0f172a;">
                            ダッシュボード
                        </span>
                    </div>
                    <div class="text-body-2" style="color: #475569;">
                        株式会社ライフスタイルマート カスタマーサポート
                    </div>
                </v-card>
            </v-container>
        </v-main>
    </v-app>
</template>
