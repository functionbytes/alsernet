const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();

  await page.goto('https://system.test/login');

  // Fill form
  await page.fill('#email', 'admin@caixilhariablanco.pt');
  await page.fill('#password', 'password123');

  // Click submit and wait for navigation
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }),
    page.click('button[type="submit"]'),
  ]);

  console.log('After submit URL:', page.url());
  await page.screenshot({ path: '/tmp/login-after.png', fullPage: true });

  // Check for error messages
  const errors = await page.$$eval('.invalid-feedback, .alert-danger, [role="alert"]', els => els.map(e => e.textContent.trim()));
  console.log('Errors:', errors);

  await browser.close();
})();
