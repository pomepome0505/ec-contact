<script setup>
    import { ref } from 'vue';
    import { Head, router } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

    const props = defineProps({
        category: { type: Object, required: true },
    });

    const formRef = ref(null);
    const formValid = ref(false);
    const processing = ref(false);
    const form = ref({
        name: props.category.name,
        display_order: props.category.display_order,
    });

    const errors = ref({});

    const rules = {
        name: [
            (v) => !!v || 'カテゴリ名を入力してください',
            (v) => (v && v.length <= 50) || '50文字以内で入力してください',
        ],
        display_order: [
            (v) => v !== '' && v !== null || '表示順を入力してください',
            (v) => Number.isInteger(Number(v)) || '整数を入力してください',
            (v) => Number(v) >= 0 || '0以上の値を入力してください',
        ],
    };

    async function submit() {
        const { valid } = await formRef.value.validate();
        if (!valid) return;

        processing.value = true;
        router.patch(route('categories.update', props.category.id), form.value, {
            onError: (err) => {
                errors.value = err;
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    }
</script>

<template>
    <AuthenticatedLayout>
        <Head title="カテゴリ編集" />

        <div class="d-flex align-center mb-4">
            <v-btn
                variant="text"
                color="secondary"
                icon="mdi-arrow-left"
                :href="route('categories.index')"
                class="mr-2"
            />
            <v-icon icon="mdi-shape-outline" color="primary" class="mr-3" />
            <span class="text-h6" style="font-weight: 600; color: #0f172a">
                カテゴリ編集
            </span>
        </div>

        <v-card class="pa-6" style="border: 1px solid #e2e8f0">
            <v-form ref="formRef" v-model="formValid" @submit.prevent="submit">
                <v-row dense>
                    <v-col cols="12" sm="8">
                        <v-text-field
                            v-model="form.name"
                            label="カテゴリ名"
                            :rules="rules.name"
                            :error-messages="errors.name"
                            prepend-inner-icon="mdi-shape-outline"
                        />
                    </v-col>
                    <v-col cols="12" sm="4">
                        <v-text-field
                            v-model.number="form.display_order"
                            label="表示順"
                            type="number"
                            :rules="rules.display_order"
                            :error-messages="errors.display_order"
                            prepend-inner-icon="mdi-sort-numeric-ascending"
                        />
                    </v-col>
                </v-row>

                <div class="d-flex justify-end mt-4">
                    <v-btn
                        variant="text"
                        color="secondary"
                        class="mr-2"
                        :href="route('categories.index')"
                    >
                        キャンセル
                    </v-btn>
                    <v-btn
                        type="submit"
                        color="primary"
                        :loading="processing"
                        :disabled="!formValid"
                    >
                        <v-icon icon="mdi-check" start />
                        更新
                    </v-btn>
                </div>
            </v-form>
        </v-card>
    </AuthenticatedLayout>
</template>
