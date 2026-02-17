<script setup></script>

<template>
    <v-app>
        <v-main class="d-flex align-center justify-center" style="min-height: 100vh">
            <v-container class="d-flex flex-column align-center">
                <v-card class="w-100" max-width="420" elevation="2" rounded="xl">
                    <slot />
                </v-card>
            </v-container>
        </v-main>
    </v-app>
</template>
