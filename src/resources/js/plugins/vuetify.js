import 'vuetify/styles';
import '@mdi/font/css/materialdesignicons.css';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';

const customTheme = {
    dark: false,
    colors: {
        background: '#F5F7FA',
        surface: '#FFFFFF',
        primary: '#2563EB',
        'primary-darken-1': '#1D4ED8',
        'primary-darken-2': '#1E40AF',
        secondary: '#64748B',
        'secondary-darken-1': '#475569',
        'on-background': '#0F172A',
        'on-surface': '#0F172A',
        error: '#DC2626',
        success: '#16A34A',
        warning: '#D97706',
        info: '#2563EB',
    },
    variables: {
        'border-color': '#E2E8F0',
        'border-opacity': 1,
        'medium-emphasis-opacity': 0.68,
    },
};

export default createVuetify({
    components,
    directives,
    icons: {
        defaultSet: 'mdi',
    },
    theme: {
        defaultTheme: 'customTheme',
        themes: {
            customTheme,
        },
    },
    defaults: {
        VCard: {
            rounded: 'lg',
            elevation: 1,
        },
        VBtn: {
            rounded: 'lg',
        },
        VTextField: {
            variant: 'outlined',
            density: 'comfortable',
            rounded: 'lg',
            color: 'primary',
        },
    },
});
