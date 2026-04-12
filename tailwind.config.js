/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    50: '#e6eef5',
                    100: '#c2d9e9',
                    200: '#9bc2db',
                    300: '#6fa7cb',
                    400: '#4a8ebd',
                    500: '#2c7db3',
                    600: '#1a365d', // Main primary
                    700: '#162d4d',
                    800: '#12233d',
                    900: '#0e1a2e',
                },
                gold: {
                    50: '#faf7e8',
                    100: '#f5edd1',
                    200: '#eedba3',
                    300: '#e6c875',
                    400: '#e0ba4f',
                    500: '#D4AF37', // Main gold
                    600: '#b8942e',
                    700: '#8f7024',
                    800: '#664d1a',
                    900: '#3d2a0f',
                },
            },
            fontFamily: {
                heading: ['Merriweather', 'Georgia', 'serif'],
                body: ['Source Sans 3', '-apple-system', 'sans-serif'],
                mono: ['JetBrains Mono', 'Fira Code', 'monospace'],
            },
            borderRadius: {
                'sm': '4px',
                'md': '6px',
                'lg': '8px',
                'xl': '12px',
                '2xl': '16px',
            },
        },
    },
    plugins: [],
}
