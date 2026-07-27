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
                sans: ['"Inter"', ...defaultTheme.fontFamily.sans],
                display: ['"Bebas Neue"', '"Oswald"', '"Impact"', ...defaultTheme.fontFamily.sans],
                condensed: ['"Bebas Neue"', '"Oswald"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                ucl: {
                    navy: '#0B1124',
                    'navy-light': '#111B3A',
                    'navy-card': '#0E1630',
                    'navy-hover': '#121C3E',
                    cyan: '#00D4FF',
                    'cyan-dim': '#0099CC',
                    gold: '#FFD700',
                    'gold-dim': '#B8960F',
                    silver: '#E8E8E8',
                    'silver-dim': '#8892A8',
                    star: 'rgba(255,255,255,0.04)',
                },
            },
            backgroundImage: {
                'gradient-ucl': 'radial-gradient(ellipse at 30% 20%, rgba(0, 212, 255, 0.08) 0%, transparent 60%), radial-gradient(ellipse at 70% 80%, rgba(255, 215, 0, 0.04) 0%, transparent 50%)',
                'gradient-card': 'linear-gradient(135deg, rgba(14, 22, 48, 0.95), rgba(11, 17, 36, 0.98))',
                'gradient-cyan': 'linear-gradient(135deg, #00D4FF, #0099CC)',
                'gradient-gold': 'linear-gradient(135deg, #FFD700, #B8960F)',
                'gradient-progress': 'linear-gradient(90deg, #00D4FF, #0099CC, #FFD700)',
                'stars': "url(\"data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M15 0l4.5 9.1L30 11.2l-7.5 7.3L24.5 30 15 24.5 5.5 30 7.5 18.5 0 11.2l10.5-2.1L15 0z' fill='rgba(255,255,255,0.03)'/%3E%3C/svg%3E\")",
            },
            boxShadow: {
                'ucl': '0 4px 20px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05)',
                'ucl-hover': '0 8px 32px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(0, 212, 255, 0.2), 0 0 20px rgba(0, 212, 255, 0.08)',
                'ucl-glow': '0 0 30px rgba(0, 212, 255, 0.12), 0 0 0 1px rgba(0, 212, 255, 0.3)',
                'ucl-gold': '0 0 40px rgba(255, 215, 0, 0.15), 0 0 0 1px rgba(255, 215, 0, 0.3)',
                'ucl-input': '0 0 0 1px rgba(255,255,255,0.08)',
                'ucl-input-focus': '0 0 0 1px rgba(0, 212, 255, 0.5), 0 0 16px rgba(0, 212, 255, 0.08)',
            },
            spacing: {
                '18': '4.5rem',
                '22': '5.5rem',
            },
            minHeight: {
                'touch': '44px',
            },
            minWidth: {
                'touch': '44px',
            },
        },
    },

    plugins: [forms],
};
