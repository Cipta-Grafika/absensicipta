const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                'gray-750': '#252e3e',
            },
            fontFamily: {
                sans: [
                    'SF Pro Display',
                    'SF Pro Text',
                    'SF Pro',
                    '-apple-system',
                    'BlinkMacSystemFont',
                    'San Francisco',
                    'Helvetica Neue',
                    'Helvetica',
                    'Arial',
                    ...defaultTheme.fontFamily.sans
                ],
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
};
