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
  await page.waitForTimeout(4000);
  console.log('After login URL:', page.url());

  if (page.url().includes('/login')) {
    console.log('LOGIN FAILED');
    await page.screenshot({ path: '/tmp/login-failed.png' });
    await browser.close();
    return;
  }

  // Navigate to cookie settings
  const response = await page.goto('https://system.test/panel/settings/cookie');
  console.log('Cookie settings status:', response.status());
  console.log('Cookie settings URL:', page.url());

  await page.screenshot({ path: '/tmp/cookie-after-middleware-disabled.png', fullPage: true });
  console.log('Screenshot saved');

  await browser.close();
})();
