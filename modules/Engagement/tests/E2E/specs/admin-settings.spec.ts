import { test, expect } from '@playwright/test';

const BASE_URL = process.env.E2E_BASE_URL ?? 'http://system.test';

test.describe('Engagement admin settings', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(!process.env.E2E_ADMIN_EMAIL, 'requires E2E_ADMIN_EMAIL env var');
    await page.goto(`${BASE_URL}/login`);
    await page.fill('input[name="email"]', process.env.E2E_ADMIN_EMAIL!);
    await page.fill('input[name="password"]', process.env.E2E_ADMIN_PASSWORD!);
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/panel/);
  });

  test('settings engagement index loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/panel/settings/engagement`);
    await expect(page.locator('text=Configuración de Engagement')).toBeVisible();
  });

  test('triggers page loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/panel/settings/engagement/triggers/page`);
    await expect(page.locator('text=Reglas de activación')).toBeVisible();
  });

  test('platforms page loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/panel/settings/engagement/platforms/page`);
    await expect(page.locator('text=Integraciones')).toBeVisible();
  });
});
