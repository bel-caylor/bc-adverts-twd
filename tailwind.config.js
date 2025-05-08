module.exports = {
    content: [
        './src/**/*.{js,jsx,ts,tsx,php}',
        './**/*.php',
        './*.php',
    ],
    theme: {
        extend: {},
    },
    plugins: [
        require('@tailwindcss/typography'),
    ],
};

