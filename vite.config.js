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
                'admin-style': 'src/scss/admin.scss',
                'main-style': 'src/scss/main.scss',
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
        preprocessorOptions: {
            scss: {
                additionalData: '',
                includePaths: [
                    path.resolve(__dirname, 'src/scss')
                ]
            }
        },
        postcss: {
            plugins: [
                require('tailwindcss'),
                require('autoprefixer'),
            ],
        },
    }
});