const { chromium } = require('playwright');

async function takeScreenshot(url, path, label) {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1280, height: 800 }
  });
  const page = await context.newPage();

  const consoleErrors = [];
  page.on('console', msg => {
    if (msg.type() === 'error') {
      consoleErrors.push(msg.text());
    }
  });

  page.on('pageerror', err => {
    consoleErrors.push(err.message);
  });

  await page.goto(url, { waitUntil: 'networkidle', timeout: 60000 });
  
  // Wait a bit for animations
  await page.waitForTimeout(2000);
  
  await page.screenshot({ path, fullPage: false });
  
  await browser.close();
  
  return { label, path, consoleErrors: consoleErrors.slice(0, 10) };
}

(async () => {
  const url = process.argv[2] || 'http://system.test/';
  const path = process.argv[3] || '/tmp/screenshot.png';
  const label = process.argv[4] || 'Screenshot';
  const result = await takeScreenshot(url, path, label);
  console.log(JSON.stringify(result, null, 2));
})();
