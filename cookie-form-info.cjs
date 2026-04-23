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

  const formAction = await page.$eval('form', f => f.action);
  const formMethod = await page.$eval('form', f => f.method);
  const methodField = await page.$eval('input[name="_method"]', el => el.value).catch(() => 'no _method');
  console.log('Form action:', formAction);
  console.log('Form method:', formMethod);
  console.log('_method field:', methodField);

  await browser.close();
})();
