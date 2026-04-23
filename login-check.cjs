const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();

  await page.goto('https://system.test/login');
  await page.waitForTimeout(2000);
  console.log('URL:', page.url());
  console.log('Title:', await page.title());

  // Check for captcha elements
  const hasRecaptcha = await page.$('.g-recaptcha') !== null;
  const hasMathCaptcha = await page.$('#math-captcha') !== null;
  console.log('Has reCAPTCHA:', hasRecaptcha);
  console.log('Has math captcha:', hasMathCaptcha);

  // Check for error messages
  const errors = await page.$$eval('.invalid-feedback, .alert-danger, [role="alert"]', els => els.map(e => e.textContent.trim()));
  console.log('Errors:', errors);

  await page.screenshot({ path: '/tmp/login-check.png' });
  await browser.close();
})();
