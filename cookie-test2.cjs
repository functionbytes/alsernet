const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();

  // Login
  await page.goto('https://system.test/login');
  await page.fill('#email', 'admin@caixilhariablanco.pt');
  await page.fill('#password', 'password123');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }),
    page.click('button[type="submit"]'),
  ]);
  console.log('After login URL:', page.url());

  // Navigate to cookie settings
  const response = await page.goto('https://system.test/panel/settings/cookie');
  console.log('Cookie settings status:', response.status());
  console.log('Cookie settings URL:', page.url());

  if (response.status() >= 500) {
    const body = await page.content();
    console.log('BODY:', body.substring(0, 3000));
  }

  await page.screenshot({ path: '/tmp/cookie-settings-authed.png', fullPage: true });
  console.log('Screenshot saved to /tmp/cookie-settings-authed.png');

  await browser.close();
})();
