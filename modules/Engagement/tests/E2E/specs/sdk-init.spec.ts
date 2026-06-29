import { test, expect } from '@playwright/test';

const TEST_HTML_URL = process.env.E2E_TEST_PAGE ?? '/test-sdk.html';
const WEBSITE_TOKEN = process.env.E2E_WEBSITE_TOKEN ?? '';

test.describe('Engagement SDK initialization', () => {
  test('loads sdk.js without errors', async ({ page }) => {
    await page.goto(TEST_HTML_URL);
    const sdkLoaded = await page.evaluate(() => typeof (window as any).chat === 'function');
    expect(sdkLoaded).toBe(true);
  });

  test('chat.init() creates session and visitor id', async ({ page }) => {
    test.skip(!WEBSITE_TOKEN, 'requires E2E_WEBSITE_TOKEN env var');

    await page.goto(TEST_HTML_URL);
    await page.evaluate((token) => {
      (window as any).chat('init', {
        token, apiUrl: window.location.origin, consent: true, debug: true,
      });
    }, WEBSITE_TOKEN);

    await page.waitForFunction(() => !!localStorage.getItem('__hd_lc_st'), { timeout: 10_000 });
    expect(await page.evaluate(() => localStorage.getItem('__hd_lc_vid'))).toBeTruthy();
  });

  test('persists consent across reloads', async ({ page }) => {
    test.skip(!WEBSITE_TOKEN, 'requires E2E_WEBSITE_TOKEN env var');

    await page.goto(TEST_HTML_URL);
    await page.evaluate((token) => {
      (window as any).chat('init', { token, apiUrl: window.location.origin, consent: true });
    }, WEBSITE_TOKEN);

    await page.waitForFunction(() => localStorage.getItem('__hd_lc_consent') === '1');
    await page.reload();
    expect(await page.evaluate(() => localStorage.getItem('__hd_lc_consent'))).toBe('1');
  });

  test('captures UTM params on first visit', async ({ page }) => {
    await page.goto(`${TEST_HTML_URL}?utm_source=test&utm_campaign=playwright`);
    await page.waitForTimeout(500);
    const utm = await page.evaluate(() => JSON.parse(localStorage.getItem('__hd_lc_utm') ?? 'null'));
    expect(utm?.utm_source).toBe('test');
    expect(utm?.utm_campaign).toBe('playwright');
  });
});
