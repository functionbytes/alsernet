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

  // Submit the correct form (index 2)
  const [response] = await Promise.all([
    page.waitForResponse(resp => {
      const req = resp.request();
      return req.method() === 'POST' && req.url().includes('/panel/settings/cookie');
    }, { timeout: 15000 }),
    page.evaluate(() => {
      const forms = document.querySelectorAll('form');
      const form = Array.from(forms).find(f => f.action.includes('/panel/settings/cookie') && f.querySelector('select[name="enabled"]'));
      if (form) form.submit();
    }),
  ]);

  console.log('Response status:', response.status());
  console.log('Response URL:', response.url());

  if (response.status() >= 500) {
    const body = await response.text();
    console.log('BODY:', body.substring(0, 3000));
  }

  await page.waitForTimeout(2000);
  console.log('Final URL:', page.url());
  await page.screenshot({ path: '/tmp/cookie-submit-final.png' });

  await browser.close();
})();
