const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();

  // Login
  await page.goto('https://system.test/login');
  await page.fill('#email', 'admin@caixilhariablanco.pt');
  await page.fill('#password', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(3000);
  console.log('After login URL:', page.url());

  // Navigate to cookie settings
  await page.goto('https://system.test/panel/settings/cookie');
  await page.waitForTimeout(2000);

  // Intercept the PATCH request response
  const [response] = await Promise.all([
    page.waitForResponse(resp => resp.request().method() === 'PATCH' && resp.url().includes('/panel/settings/cookie'), { timeout: 10000 }),
    page.evaluate(() => document.querySelector('form[action*="settings/cookie"]').submit()),
  ]);

  console.log('PATCH status:', response.status());
  console.log('PATCH URL:', response.url());

  if (response.status() >= 500) {
    const body = await response.text();
    console.log('PATCH body:', body.substring(0, 3000));
  }

  await page.waitForTimeout(2000);
  console.log('Final URL:', page.url());
  await page.screenshot({ path: '/tmp/cookie-submit-result.png' });

  await browser.close();
})();
