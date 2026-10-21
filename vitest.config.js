import { defineConfig } from 'vite-plus';

export default defineConfig({
    test: {
        environment: 'jsdom',
        include: ['resources/widget/**/*.test.js', 'packages/obsidian/resources/js/**/*.test.js'],
    },
});
