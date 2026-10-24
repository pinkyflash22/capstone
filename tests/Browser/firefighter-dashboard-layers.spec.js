import { expect, test } from '@playwright/test';
import { readFileSync } from 'node:fs';

const dashboardScript = readFileSync(new URL('../../resources/js/firefighter/dashboard-shell.js', import.meta.url), 'utf8');

async function mountDashboard(page) {
    await page.route('http://dashboard.test/**', async route => {
        const path = new URL(route.request().url()).pathname;
        if (path === '/dashboard-shell.js') {
            return route.fulfill({ contentType: 'text/javascript', body: dashboardScript });
        }
        if (path !== '/') {
            return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, incidents: [], posts: [] }) });
        }
        return route.fulfill({ contentType: 'text/html', body: `
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <main data-ff-page-root="dashboard">
                <button data-ff-mobile-map-layers-button>Layers</button>
                <span data-ff-mobile-map-layers-count></span>
                <div data-ff-map-layers-panel aria-hidden="true" inert>
                    <button data-ff-map-layers-close>Close</button>
                    <input type="checkbox" data-ff-map-layer-toggle="incidents" checked>
                    <input type="checkbox" data-ff-map-layer-toggle="hydrants">
                </div>
                <div id="firefighterMobileInsightsPanel" aria-hidden="false"></div>
                <div data-ff-notifications-panel class="is-open" aria-hidden="false"></div>
                <div class="firefighter-map-stage"></div>
            </main>
            <script>
                window.firefighterDashboardConfig = { dashboardDataUrl: '/widgets', dashboardPostsUrl: '/posts' };
                window.overlayChanges = [];
                window.setPyroMapOverlayVisibility = (...args) => window.overlayChanges.push(args);
            </script>
            <script type="module" src="/dashboard-shell.js"></script>
        ` });
    });
    await page.goto('http://dashboard.test/');
}

test('shared dashboard controls open, synchronize, and close the mobile layer sheet', async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await mountDashboard(page);
    const panel = page.locator('[data-ff-map-layers-panel]');

    await expect(page.locator('[data-ff-mobile-map-layers-count]')).toHaveText('1');
    await page.locator('[data-ff-mobile-map-layers-button]').click();
    await expect(panel).toHaveAttribute('aria-hidden', 'false');
    await expect(panel).not.toHaveAttribute('inert', '');
    await expect(page.locator('[data-ff-notifications-panel]')).toHaveAttribute('aria-hidden', 'true');
    await expect(page.locator('#firefighterMobileInsightsPanel')).toHaveAttribute('aria-hidden', 'true');

    await page.locator('[data-ff-map-layer-toggle="hydrants"]').check();
    await expect(page.locator('[data-ff-mobile-map-layers-count]')).toHaveText('2');
    expect(await page.evaluate(() => window.overlayChanges)).toEqual([['hydrants', true]]);

    await page.keyboard.press('Escape');
    await expect(panel).toHaveAttribute('aria-hidden', 'true');
    await expect(panel).toHaveAttribute('inert', '');
    expect(errors).toEqual([]);
});

test('delegated controls handle map controls added after dashboard initialization', async ({ page }) => {
    await mountDashboard(page);
    await page.locator('.firefighter-map-stage').evaluate(stage => {
        stage.innerHTML = '<div class="leaflet-control-layers"><button class="leaflet-control-layers-toggle">Map layers</button></div>';
    });
    await page.locator('.leaflet-control-layers-toggle').click();
    await expect(page.locator('[data-ff-map-layers-panel]')).toHaveAttribute('aria-hidden', 'false');
    await page.locator('[data-ff-map-layers-close]').click();
    await expect(page.locator('[data-ff-map-layers-panel]')).toHaveAttribute('aria-hidden', 'true');
});
