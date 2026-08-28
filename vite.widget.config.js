import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        emptyOutDir: false,
        outDir: 'public',
        lib: {
            entry: 'resources/widget/index.js',
            name: 'BugWidget',
            formats: ['iife'],
            fileName: () => 'widget.js',
        },
        rollupOptions: {
            output: {
                inlineDynamicImports: true,
            },
        },
    },
});
