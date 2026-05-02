# E2E tests del Engagement SDK con Playwright

## Setup

```bash
cd modules/Engagement/tests/E2E
npm init -y
npm install -D @playwright/test
npx playwright install chromium
```

## Ejecutar

```bash
export E2E_BASE_URL="http://system.test"
export E2E_TEST_PAGE="/test-sdk.html"
export E2E_WEBSITE_TOKEN="tok_..."

npx playwright test
```
