import { test, expect } from '@playwright/test';

const TEST_HTML_URL = process.env.E2E_TEST_PAGE ?? '/test-sdk.html';
const WEBSITE_TOKEN = process.env.E2E_WEBSITE_TOKEN ?? '';

test.describe('Engagement SDK tracking', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(!WEBSITE_TOKEN, 'requires E2E_WEBSITE_TOKEN env var');
    await page.goto(TEST_HTML_URL);
    await page.evaluate((token) => {
      (window as any).chat('init', {
        token, apiUrl: window.location.origin, consent: true, debug: true,
      });
    }, WEBSITE_TOKEN);
    await page.waitForFunction(() => !!localStorage.getItem('__hd_lc_st'), { timeout: 10_000 });
  });

  test('track page_view event', async ({ page }) => {
    const res = await page.evaluate(() => {
      return (window as any).chat('track', 'page_view', { url: '/test', title: 'Test' });
    });
    expect(res).not.toBeNull();
  });

  test('track product_view event', async ({ page }) => {
    const res = await page.evaluate(() => {
      return (window as any).chat('track', 'product_view', { sku: 'DEMO-001', price: 29.99 });
    });
    expect(res).not.toBeNull();
  });

  test('identify merges visitor data', async ({ page }) => {
    const res = await page.evaluate(() => {
      return (window as any).chat('identify', 'test@example.com', { name: 'Test User' });
    });
    expect(res).not.toBeNull();
  });
});
