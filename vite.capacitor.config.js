import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    root: 'capacitor',
    base: './',
    plugins: [vue()],
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        rollupOptions: {
            input: path.resolve(__dirname, 'capacitor/index.html'),
        },
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
});
