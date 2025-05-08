import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    plugins: [react()],
    build: {
        outDir: path.resolve(__dirname, 'build'),
        rollupOptions: {
            input: {
                main: 'src/js/main.js',
                admin: 'src/js/admin.js',
                'editor-sidebar': 'src/js/editor-sidebar.jsx',
                'admin-style': 'src/css/admin.css',
                'main-style': 'src/css/main.css',
            },
            output: {
                entryFileNames: '[name].js',
                chunkFileNames: '[name].js',
                assetFileNames: '[name].css',
            },
            external: [
                '@wordpress/plugins',
                '@wordpress/edit-post',
                '@wordpress/element',
                '@wordpress/components',
                '@wordpress/data'
            ]
        },
        emptyOutDir: true
    },
    css: {
        postcss: {
            plugins: [
                require('tailwindcss'),
                require('autoprefixer'),
            ],
        },
    }
});