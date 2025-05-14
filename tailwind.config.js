module.exports = {
    content: [
        './src/**/*.{js,jsx,ts,tsx,php}',
        './**/*.php',
        './*.php',
    ],
    safelist: [
        { pattern: /^bg-gradient-to-/ },
        { pattern: /^(from|via|to)-/ },
        { pattern: /^justify-(start|center|end)$/ },
        { pattern: /^(from|via|to)-/ },
        'from-black/80',
        'via-black/80',
        'to-transparent',
      ],
    theme: {
        extend: {},
    },
    plugins: [
        require('@tailwindcss/typography'),
    ],
};

