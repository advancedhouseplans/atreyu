import { defineConfig } from 'vite-plus';

export default defineConfig({
    publicDir: false,
    build: {
        emptyOutDir: false,
        outDir: 'public',
        lib: {
            entry: 'resources/widget/index.js',
            name: 'BugWidget',
            formats: ['iife'],
            fileName: () => 'widget.js',
        },
    },
});
