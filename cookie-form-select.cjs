const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();

  await page.goto('https://system.test/login');
  await page.fill('#email', 'admin@caixilhariablanco.pt');
  await page.fill('#password', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(3000);

  await page.goto('https://system.test/panel/settings/cookie');
  await page.waitForTimeout(2000);

  // Find the correct form (the one with action containing settings/cookie)
  const forms = await page.$$eval('form', fs => fs.map((f, i) => ({
    index: i,
    action: f.action,
    method: f.method,
    hasEnabled: f.querySelector('select[name="enabled"]') !== null,
  })));
  console.log('Forms:', JSON.stringify(forms, null, 2));

  await browser.close();
})();
