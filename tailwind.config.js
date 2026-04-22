import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php', // <-- ¡ESTA LÍNEA ES CRUCIAL!
        './vendor/filament/**/*.blade.php', // <-- Y esta para Filament
    ],

    darkMode: 'class', // <-- CRÍTICO: Habilita dark mode basado en clase 'dark'

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};