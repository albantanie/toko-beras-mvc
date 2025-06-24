import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.tsx',
        './resources/js/**/*.ts',
    ],

    // FORCE LIGHT MODE ONLY - NO DARK MODE
    darkMode: false, // Disable dark mode completely

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Force light mode colors
                background: '#ffffff',
                foreground: '#000000',
                card: '#ffffff',
                'card-foreground': '#000000',
                popover: '#ffffff',
                'popover-foreground': '#000000',
                primary: '#3b82f6',
                'primary-foreground': '#ffffff',
                secondary: '#f1f5f9',
                'secondary-foreground': '#0f172a',
                muted: '#f8fafc',
                'muted-foreground': '#64748b',
                accent: '#f1f5f9',
                'accent-foreground': '#0f172a',
                destructive: '#ef4444',
                'destructive-foreground': '#ffffff',
                border: '#e2e8f0',
                input: '#ffffff',
                ring: '#3b82f6',
            },
        },
    },

    plugins: [forms],
};
