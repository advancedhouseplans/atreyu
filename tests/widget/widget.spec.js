import { test, expect } from '@playwright/test';

test('widget submits a report', async ({ page }) => {
    let posted = null;
    await page.route('https://bugs.test/api/reports', async (route) => {
        const req = route.request();
        posted = { url: req.url(), headers: req.headers(), postData: req.postDataBuffer() };
        route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify({ report_id: 1 }) });
    });

    // Dismiss the alert() that fires after a successful submit.
    page.on('dialog', (dialog) => dialog.dismiss());

    await page.goto('/dev/widget-preview.html');
    await page.waitForFunction(() => document.getElementById('bug-widget-root'));

    const root = page.locator('#bug-widget-root');
    await root.evaluate((el) => el.shadowRoot.querySelector('button.bug-toggle').click());
    await root.evaluate((el) => {
        el.shadowRoot.querySelector('[name=title]').value = 'Preview title';
        el.shadowRoot.querySelector('[name=description]').value = 'Preview desc';
        el.shadowRoot.querySelector('.submit').click();
    });

    await expect.poll(() => posted).not.toBeNull();
    expect(posted.headers['authorization']).toMatch(/^Bearer /);
});
