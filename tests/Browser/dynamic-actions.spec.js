import { expect, test } from '@playwright/test';
import { fileURLToPath } from 'node:url';

const dynamicActionsPath = fileURLToPath(new URL('../../resources/js/dynamic-actions.js', import.meta.url));

async function mountCommunityRequestForm(page, extraAttributes = '') {
    await page.setContent(`
        <meta name="csrf-token" content="test-token">
        <form method="POST" action="http://pyro.test/community-activity-requests" ${extraAttributes}>
            <input name="title" value="Community drill">
            <button type="submit"><span>Save request</span></button>
        </form>
        <script>
            window.testNotifications = [];
            window.PyroNotify = { notify: (message, type) => window.testNotifications.push({ message, type }) };
        </script>
    `);
    await page.addScriptTag({ path: dynamicActionsPath, type: 'module' });
}

test.describe('shared dynamic form reliability', () => {
    test('ignores a repeated submit while the first request is pending', async ({ page }) => {
        let requests = 0;
        await page.route('**/community-activity-requests', async (route) => {
            requests += 1;
            await new Promise((resolve) => setTimeout(resolve, 150));
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({ success: true, message: 'Saved once.' }),
            });
        });
        await mountCommunityRequestForm(page);

        await page.locator('form').evaluate((form) => {
            form.dispatchEvent(new SubmitEvent('submit', { bubbles: true, cancelable: true }));
            form.dispatchEvent(new SubmitEvent('submit', { bubbles: true, cancelable: true }));
        });

        await expect.poll(() => requests).toBe(1);
        await expect.poll(() => page.evaluate(() => window.testNotifications.length)).toBe(1);
        await expect(page.locator('button[type="submit"]')).toBeEnabled();
    });

    test('shows the first validation message and restores the submit control', async ({ page }) => {
        await page.route('**/community-activity-requests', (route) => route.fulfill({
            status: 422,
            contentType: 'application/json',
            body: JSON.stringify({
                message: 'The submitted data is invalid.',
                errors: { title: ['Please provide a valid activity title.'] },
            }),
        }));
        await mountCommunityRequestForm(page);

        await page.locator('button[type="submit"]').click();

        await expect.poll(() => page.evaluate(() => window.testNotifications.at(-1))).toEqual({
            message: 'Please provide a valid activity title.',
            type: 'error',
        });
        await expect(page.locator('button[type="submit"]')).toBeEnabled();
        await expect(page.locator('button[type="submit"]')).toContainText('Save request');
    });

    test('leaves an offline-capable submission to the PWA fallback', async ({ page }) => {
        await mountCommunityRequestForm(page, 'data-offline-submit');
        await page.evaluate(() => Object.defineProperty(window.navigator, 'onLine', {
            configurable: true,
            value: false,
        }));

        const wasNotCancelled = await page.locator('form').evaluate((form) => form.dispatchEvent(
            new SubmitEvent('submit', { bubbles: true, cancelable: true })
        ));

        expect(wasNotCancelled).toBe(true);
        await expect(page.locator('button[type="submit"]')).toBeEnabled();
        expect(await page.evaluate(() => window.testNotifications)).toEqual([]);
    });
});
