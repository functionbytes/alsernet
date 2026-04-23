const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();

  await page.goto('https://system.test/login');
  await page.screenshot({ path: '/tmp/login-page.png', fullPage: true });
  console.log('Screenshot saved to /tmp/login-page.png');

  // Print all input names
  const inputs = await page.$$eval('input', els => els.map(e => ({ type: e.type, name: e.name, id: e.id })));
  console.log('Inputs:', JSON.stringify(inputs, null, 2));

  await browser.close();
})();
