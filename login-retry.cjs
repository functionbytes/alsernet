const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();

  await page.goto('https://system.test/login');
  await page.fill('#email', 'admin@caixilhariablanco.pt');
  await page.fill('#password', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(4000);
  console.log('After login URL:', page.url());
  console.log('After login title:', await page.title());

  // Check for any visible error
  const body = await page.content();
  if (body.includes('credentials') || body.includes('incorrect') || body.includes('credenciales')) {
    console.log('Login failed - credentials error detected');
  }

  await page.screenshot({ path: '/tmp/login-retry.png' });
  await browser.close();
})();
