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

  // Capture all network responses
  let submitResponse = null;
  page.on('response', async (response) => {
    const req = response.request();
    if (req.method() === 'PATCH' && req.url().includes('/panel/settings/cookie')) {
      submitResponse = response;
      console.log('PATCH response status:', response.status());
      console.log('PATCH response URL:', response.url());
      try {
        const body = await response.text();
        if (response.status() >= 500) {
          console.log('PATCH body:', body.substring(0, 3000));
        }
      } catch (e) {
        console.log('Could not read body:', e.message);
      }
    }
  });

  // Submit
  await page.click('button:has-text("Guardar configuración")');
  await page.waitForTimeout(5000);

  console.log('After submit URL:', page.url());
  console.log('Page title:', await page.title());
  await page.screenshot({ path: '/tmp/cookie-after-submit.png' });

  await browser.close();
})();
