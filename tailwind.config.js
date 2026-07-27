import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['"Outfit"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                gaming: {
                    dark: '#0a0e1a',
                    card: '#111827',
                    border: '#1e293b',
                    cyan: '#00f0ff',
                    purple: '#7c3aed',
                    gold: '#f59e0b',
                    green: '#10b981',
                    red: '#ef4444',
                },
            },
            boxShadow: {
                'glow': '0 0 20px rgba(0, 240, 255, 0.15)',
                'glow-lg': '0 0 40px rgba(0, 240, 255, 0.1)',
                'glow-gold': '0 0 30px rgba(245, 158, 11, 0.2)',
            },
        },
    },

    plugins: [forms],
};
