import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import eslintConfigPrettier from 'eslint-config-prettier';
import globals from 'globals';

export default [
    {
        ignores: ['public/**', 'vendor/**', 'node_modules/**'],
    },
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    eslintConfigPrettier,
    {
        languageOptions: {
            globals: {
                ...globals.browser,
                route: 'readonly',
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            'vue/no-v-model-argument': 'off',
        },
    },
];
