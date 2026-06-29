const { chromium } = require('playwright');

async function measureCLS(url) {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();

  const client = await page.context().newCDPSession(page);
  await client.send('Performance.enable');

  await page.goto(url, { waitUntil: 'networkidle', timeout: 60000 });
  await page.waitForTimeout(3000);

  // Get layout shift entries via CDP
  const metrics = await client.send('Performance.getMetrics');
  console.log('Performance Metrics:', JSON.stringify(metrics.metrics, null, 2));

  // Try to get LayoutShift via tracing
  await client.send('Tracing.end');

  await browser.close();
}

(async () => {
  await measureCLS('http://system.test/');
})();
