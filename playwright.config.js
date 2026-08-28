import { defineConfig } from '@playwright/test';
export default defineConfig({
    testDir: './tests/widget',
    use: { baseURL: 'http://127.0.0.1:8000' },
    webServer: {
        command: 'php artisan serve --port=8000',
        url: 'http://127.0.0.1:8000/dev/widget-preview.html',
        reuseExistingServer: !process.env.CI,
        timeout: 30_000,
    },
});
