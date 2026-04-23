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

  const buttons = await page.$$eval('button', els => els.map(e => ({
    text: e.textContent.trim(),
    type: e.type,
    visible: e.offsetParent !== null,
    class: e.className
  })));
  console.log('Buttons:', JSON.stringify(buttons, null, 2));

  await browser.close();
})();
