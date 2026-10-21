import { defineConfig } from 'vite-plus';

export default defineConfig({
    publicDir: false,
    build: {
        emptyOutDir: false,
        outDir: 'public',
        lib: {
            entry: 'resources/widget/snapshot-entry.js',
            name: 'AtreyuSnapshotRuntime',
            formats: ['iife'],
            fileName: () => 'widget-snapshot.js',
        },
    },
});
