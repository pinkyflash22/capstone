import { expect, test } from '@playwright/test';

const barangayEmail = process.env.E2E_BARANGAY_EMAIL || '';
const barangayPassword = process.env.E2E_BARANGAY_PASSWORD || '';
const hasBarangayCredentials = Boolean(barangayEmail && barangayPassword);
const citizenEmail = process.env.E2E_CITIZEN_EMAIL || '';
const citizenPassword = process.env.E2E_CITIZEN_PASSWORD || '';
const hasCitizenCredentials = Boolean(citizenEmail && citizenPassword);
const reportEndpoint = '**/barangay/reports/quick';

async function signIn(page, email, password, expectedPathPrefix) {
    await page.goto('/auth', { waitUntil: 'domcontentloaded' });
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);

    await Promise.all([
        page.waitForURL((url) => url.pathname.startsWith(expectedPathPrefix), { timeout: 25_000 }),
        page.locator('#submitBtn').click(),
    ]);
}

async function signInAsBarangay(page) {
    await signIn(page, barangayEmail, barangayPassword, '/barangay/');
    await page.goto('/barangay/dashboard', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('[data-barangay-quick-report]').first()).toBeVisible();
    await clearReportQueues(page);
}

async function clearReportQueues(page) {
    await page.evaluate(() => {
        Object.keys(window.localStorage)
            .filter((key) => key.startsWith('pyrosense-pwa:report-queue:'))
            .forEach((key) => window.localStorage.removeItem(key));
    });
}

async function queuedReports(page) {
    return page.evaluate(() => ({
        keys: Object.keys(window.localStorage)
            .filter((key) => key.startsWith('pyrosense-pwa:report-queue:')),
        reports: window.pyroPwa?.getQueuedReports?.() || [],
    }));
}

test.describe('PWA emergency report reliability', () => {
    test.skip(!hasBarangayCredentials, 'Set E2E_BARANGAY_EMAIL and E2E_BARANGAY_PASSWORD to run these tests.');

    test.beforeEach(async ({ context, page, baseURL }) => {
        await context.grantPermissions(['geolocation'], { origin: new URL(baseURL).origin });
        await context.setGeolocation({ latitude: 15.8224, longitude: 120.3281, accuracy: 18 });
        await signInAsBarangay(page);
    });

    test('queues a one-tap report offline and syncs the same idempotent payload after reconnecting', async ({ context, page }) => {
        await context.setOffline(true);
        await page.locator('[data-barangay-quick-report]').first().click();

        await expect(page.locator('#barangayQuickReportStatus')).toContainText('saved offline');
        const queued = await queuedReports(page);
        expect(queued.keys).toHaveLength(1);
        expect(queued.keys[0]).toMatch(/^pyrosense-pwa:report-queue:\d+$/);
        expect(queued.keys[0]).not.toContain(':guest');
        expect(queued.reports).toHaveLength(1);

        const originalBody = JSON.parse(queued.reports[0].body);
        expect(originalBody.client_report_id).toMatch(/^[0-9a-f-]{36}$/i);
        expect(originalBody.location_accuracy).toBeUndefined();
        expect(originalBody.accuracy).toBe(18);

        let syncedRequest = null;
        await page.route(reportEndpoint, async (route) => {
            syncedRequest = {
                body: route.request().postDataJSON(),
                headers: route.request().headers(),
            };
            await route.fulfill({
                status: 201,
                contentType: 'application/json',
                body: JSON.stringify({ success: true, message: 'Report synchronized.' }),
            });
        });

        await context.setOffline(false);
        await page.evaluate(() => window.pyroPwa.syncQueuedReports());

        await expect.poll(async () => (await queuedReports(page)).reports.length).toBe(0);
        expect(syncedRequest).not.toBeNull();
        expect(syncedRequest.body.client_report_id).toBe(originalBody.client_report_id);
        expect(syncedRequest.headers['x-pyro-offline-sync']).toBe('1');
    });

    test('keeps the report when the device is online but the submission connection fails', async ({ page }) => {
        await page.route(reportEndpoint, (route) => route.abort('failed'));
        await page.locator('[data-barangay-quick-report]').first().click();

        await expect(page.locator('#barangayQuickReportStatus')).toContainText('saved offline');
        const queued = await queuedReports(page);
        expect(queued.reports).toHaveLength(1);
        expect(queued.reports[0].type).toBe('barangay-one-tap-fire-report');

        const body = JSON.parse(queued.reports[0].body);
        expect(body.client_report_id).toMatch(/^[0-9a-f-]{36}$/i);
        expect(body.lat).toBeCloseTo(15.8224, 4);
        expect(body.lng).toBeCloseTo(120.3281, 4);
    });
});

test.describe('Citizen mobile-hub report reliability', () => {
    test.skip(!hasCitizenCredentials, 'Set E2E_CITIZEN_EMAIL and E2E_CITIZEN_PASSWORD to run these tests.');

    test.beforeEach(async ({ context, page, baseURL }) => {
        await context.grantPermissions(['geolocation'], { origin: new URL(baseURL).origin });
        await context.setGeolocation({ latitude: 15.8224, longitude: 120.3281, accuracy: 18 });
        await page.route('**/api/incidents/nearest-available-responder**', (route) => route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ success: true, data: null }),
        }));
        await signIn(page, citizenEmail, citizenPassword, '/citizen/');
        await page.goto('/citizen/mobile-hub', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('#mobileHubReportButton')).toBeVisible();
        await clearReportQueues(page);
    });

    test('queues the citizen one-tap report when the incident connection drops', async ({ page }) => {
        await page.route('**/citizen/incident', (route) => route.abort('failed'));
        await page.locator('#mobileHubReportButton').click();

        await expect(page.locator('#mobileHubStatus')).toContainText('saved offline');
        const queued = await queuedReports(page);
        expect(queued.keys).toHaveLength(1);
        expect(queued.keys[0]).toMatch(/^pyrosense-pwa:report-queue:\d+$/);
        expect(queued.reports).toHaveLength(1);
        expect(queued.reports[0].type).toBe('citizen-mobile-fire-report');

        const body = JSON.parse(queued.reports[0].body);
        expect(body.client_report_id).toMatch(/^[0-9a-f-]{36}$/i);
        expect(body.location_accuracy).toBe(18);
        expect(body.lat).toBeCloseTo(15.8224, 4);
        expect(body.lng).toBeCloseTo(120.3281, 4);
    });
});
