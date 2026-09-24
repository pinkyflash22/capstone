import { expect, test } from '@playwright/test';

const accounts = [
    {
        label: 'provincial admin',
        email: process.env.E2E_PROVINCIAL_ADMIN_EMAIL || '',
        password: process.env.E2E_PROVINCIAL_ADMIN_PASSWORD || '',
        landingPrefix: '/admin/',
        pagePath: '/admin/after-activity-reports',
        root: '.main-content > .shell',
        paginationClass: 'pagination',
        response: '<main class="main-content"><div class="shell"><div class="list"><p data-e2e-page>Provincial page 2</p></div><div class="pagination"></div></div></main>',
    },
    {
        label: 'stationadmin',
        email: process.env.E2E_STATION_ADMIN_EMAIL || '',
        password: process.env.E2E_STATION_ADMIN_PASSWORD || '',
        landingPrefix: '/station-admin/',
        pagePath: '/station-admin/reports',
        root: '.reports-shell',
        paginationClass: 'report-pagination',
        response: '<div class="reports-shell"><p data-e2e-page>Station-admin page 2</p></div>',
    },
    {
        label: 'chief',
        email: process.env.E2E_CHIEF_EMAIL || '',
        password: process.env.E2E_CHIEF_PASSWORD || '',
        landingPrefix: '/station-admin/',
        pagePath: '/station-admin/after-activity-reports',
        root: '.main-content > .shell',
        paginationClass: 'pagination',
        response: '<main class="main-content"><div class="shell"><div class="list"><p data-e2e-page>Chief page 2</p></div><div class="pagination"></div></div></main>',
    },
    {
        label: 'barangay officer',
        email: process.env.E2E_BARANGAY_EMAIL || '',
        password: process.env.E2E_BARANGAY_PASSWORD || '',
        landingPrefix: '/barangay/',
        pagePath: '/barangay/risk-map',
        root: '[data-risk-feed-root="risks"]',
        paginationClass: 'barangay-risk-pagination',
        response: '<article data-risk-feed-root="risks"><p data-e2e-page>Barangay risk page 2</p></article><article data-risk-feed-root="markers"><p>Mapped points</p></article>',
    },
];

async function signIn(page, account) {
    await page.goto('/auth', { waitUntil: 'domcontentloaded' });
    await page.locator('#email').fill(account.email);
    await page.locator('#password').fill(account.password);
    await Promise.all([
        page.waitForURL((url) => url.pathname.startsWith(account.landingPrefix), { timeout: 25_000 }),
        page.locator('#submitBtn').click(),
    ]);
}

for (const account of accounts) {
    test(`${account.label} pagination updates records without a document reload`, async ({ page }) => {
        test.skip(!account.email || !account.password, `Set the E2E credentials for ${account.label} to run this test.`);

        await signIn(page, account);
        await page.goto(account.pagePath, { waitUntil: 'load' });
        await expect(page.locator(account.root).first()).toBeVisible();

        let releaseResponse;
        const responseGate = new Promise((resolve) => { releaseResponse = resolve; });
        await page.route(`**${account.pagePath}?e2e_page=2`, async (route) => {
            await responseGate;
            await route.fulfill({ status: 200, contentType: 'text/html', body: account.response });
        });

        await page.locator(account.root).first().evaluate((root, settings) => {
            const pagination = document.createElement('nav');
            pagination.className = settings.paginationClass;
            const link = document.createElement('a');
            link.href = `${settings.pagePath}?e2e_page=2`;
            link.textContent = 'Next';
            pagination.appendChild(link);
            root.appendChild(pagination);
        }, account);

        let documentNavigations = 0;
        page.on('framenavigated', (frame) => {
            if (frame === page.mainFrame()) documentNavigations += 1;
        });

        await page.locator(`.${account.paginationClass} a`).click();
        await expect(page.locator(account.root).first()).toHaveAttribute('aria-busy', 'true');
        releaseResponse();

        await expect(page.locator('[data-e2e-page]')).toBeVisible();
        await expect.poll(() => new URL(page.url()).searchParams.get('e2e_page')).toBe('2');
        expect(documentNavigations).toBe(0);
    });
}
