import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    esbuild: {
        jsx: 'automatic',
    },
    resolve: {
        alias: {
            // Use fallback for ziggy-js to handle Docker build context
            'ziggy-js': resolve(__dirname, 'resources/js/utils/ziggy-fallback.ts'),
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: undefined,
            },
        },
        target: 'es2015',
        minify: 'esbuild',
    },
    optimizeDeps: {
        include: ['react', 'react-dom'],
    },
});
