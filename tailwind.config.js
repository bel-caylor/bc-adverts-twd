module.exports = {
    content: [
        './src/**/*.{js,jsx,ts,tsx,php}',
        './**/*.php',
        './*.php',
    ],
    safelist: [
        { pattern: /^bg-gradient-to-/ },
        { pattern: /^(from|via)-black\/(20|40|60|80)$/ },
        { pattern: /^justify-(start|center|end)$/ },
      ],
    theme: {
        extend: {},
    },
    plugins: [
        require('@tailwindcss/typography'),
    ],
};

