# Offline reporting browser checks

These Playwright checks exercise the real barangay PWA UI while intercepting the
final report request, so they do not create incident records. They cover:

- one-tap reporting while the browser is offline;
- automatic synchronization after reconnecting;
- preservation of the same `client_report_id` during synchronization;
- an online browser whose incident submission fails at the network layer;
- both barangay quick reports and citizen mobile-hub fire reports;
- account-scoped queue storage and the offline-sync request header.

Install the Chromium runtime once:

```powershell
npx playwright install chromium
```

Start PyroSense against a safe browser-testing environment, then set credentials
for an existing barangay account:

```powershell
$env:E2E_BASE_URL = 'http://127.0.0.1:8000'
$env:E2E_BARANGAY_EMAIL = 'barangay@example.test'
$env:E2E_BARANGAY_PASSWORD = 'example-password'
$env:E2E_CITIZEN_EMAIL = 'citizen@example.test'
$env:E2E_CITIZEN_PASSWORD = 'example-password'
npm run test:browser
```

Each role suite skips itself when its credential pair is absent. Do not aim these
checks at production because login still uses the configured application.
