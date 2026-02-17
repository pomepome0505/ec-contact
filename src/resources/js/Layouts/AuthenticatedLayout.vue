<script setup>
    import { Link, router, usePage } from '@inertiajs/vue3';

    const page = usePage();

    const logout = () => {
        router.post(route('logout'));
    };
</script>

<template>
    <v-app>
        <v-app-bar elevation="0" style="border-bottom: 1px solid #e2e8f0">
            <v-app-bar-title>
                <Link
                    href="/"
                    class="text-decoration-none"
                    style="color: #0f172a; font-weight: 600"
                >
                    問い合わせ管理システム
                </Link>
            </v-app-bar-title>

            <v-btn
                variant="text"
                :color="
                    route().current('inquiries.index') ? 'primary' : 'secondary'
                "
                :href="route('inquiries.index')"
                class="ml-4"
                prepend-icon="mdi-format-list-bulleted"
            >
                問い合わせ一覧
            </v-btn>

            <template #append>
                <span class="text-body-2 mr-3" style="color: #475569">
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
            <v-container v-if="$slots.header" class="py-8">
                <slot name="header" />
            </v-container>

            <v-container style="max-width: 960px">
                <slot />
            </v-container>
        </v-main>
    </v-app>
</template>
