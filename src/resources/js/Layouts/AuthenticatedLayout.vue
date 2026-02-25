<script setup>
    import { router, usePage } from '@inertiajs/vue3';

    const page = usePage();

    const logout = () => {
        router.post(route('logout'));
    };

    const isAdmin = page.props.auth.user.is_admin;

    const allMenuItems = [
        {
            title: 'ダッシュボード',
            icon: 'mdi-view-dashboard-outline',
            href: '/',
            active: () => page.url === '/',
            adminOnly: false,
        },
        {
            title: '問い合わせ一覧',
            icon: 'mdi-format-list-bulleted',
            href: route('inquiries.index'),
            active: () => route().current('inquiries.*'),
            adminOnly: false,
        },
        {
            title: 'カテゴリ管理',
            icon: 'mdi-shape-outline',
            href: route('categories.index'),
            active: () => route().current('categories.*'),
            adminOnly: true,
        },
        {
            title: '従業員管理',
            icon: 'mdi-account-group-outline',
            href: route('employees.index'),
            active: () => route().current('employees.*'),
            adminOnly: true,
        },
    ];

    const menuItems = allMenuItems.filter(
        (item) => !item.adminOnly || isAdmin,
    );
</script>

<template>
    <v-app>
        <v-navigation-drawer permanent width="260" style="border-right: 1px solid #e2e8f0">
            <div class="pa-5 pb-3">
                <div
                    class="text-subtitle-1"
                    style="font-weight: 700; color: #0f172a; line-height: 1.4"
                >
                    問い合わせ管理システム
                </div>
            </div>

            <v-divider />

            <v-list density="compact" nav class="pa-3">
                <v-list-item
                    v-for="item in menuItems"
                    :key="item.title"
                    :prepend-icon="item.icon"
                    :title="item.title"
                    :href="item.href"
                    :active="item.active()"
                    color="primary"
                    rounded="lg"
                    class="mb-1"
                />
            </v-list>

            <template #append>
                <v-divider />
                <div class="pa-4">
                    <div class="d-flex align-center mb-3">
                        <v-icon
                            icon="mdi-account-circle-outline"
                            size="20"
                            color="secondary"
                            class="mr-2"
                        />
                        <span class="text-body-2" style="color: #475569">
                            {{ page.props.auth.user.name }}
                        </span>
                    </div>
                    <v-btn
                        block
                        variant="text"
                        color="secondary"
                        size="small"
                        prepend-icon="mdi-lock-outline"
                        :href="route('password.edit')"
                        class="mb-2"
                    >
                        パスワード変更
                    </v-btn>
                    <v-btn
                        block
                        variant="outlined"
                        color="secondary"
                        size="small"
                        prepend-icon="mdi-logout"
                        @click="logout"
                    >
                        ログアウト
                    </v-btn>
                </div>
            </template>
        </v-navigation-drawer>

        <v-main>
            <v-container v-if="$slots.header" class="py-8">
                <slot name="header" />
            </v-container>

            <v-container style="max-width: 960px">
                <slot />
            </v-container>
        </v-main>
    </v-app>
</template>
