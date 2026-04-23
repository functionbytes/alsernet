const { chromium } = require('playwright');

async function measure(url, label) {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1280, height: 800 }
  });
  const page = await context.newPage();

  const start = Date.now();
  const response = await page.goto(url, { waitUntil: 'networkidle', timeout: 60000 });
  const loadTime = Date.now() - start;

  // Get resource sizes
  const resources = await page.evaluate(() => {
    return performance.getEntriesByType('resource').map(r => ({
      name: r.name.split('/').pop().split('?')[0],
      initiatorType: r.initiatorType,
      transferSize: r.transferSize,
      duration: r.duration,
    }));
  });

  const totalTransfer = resources.reduce((s, r) => s + r.transferSize, 0);
  const cssTransfer = resources.filter(r => r.name.endsWith('.css')).reduce((s, r) => s + r.transferSize, 0);
  const jsTransfer = resources.filter(r => r.name.endsWith('.js')).reduce((s, r) => s + r.transferSize, 0);
  const imgTransfer = resources.filter(r => /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(r.name)).reduce((s, r) => s + r.transferSize, 0);

  // Get LCP if available
  const lcpData = await page.evaluate(() => {
    const entries = performance.getEntriesByType('largest-contentful-paint');
    return entries.length > 0 ? entries[entries.length - 1].startTime : null;
  });

  // Get FCP
  const fcpData = await page.evaluate(() => {
    const entries = performance.getEntriesByType('paint');
    const fcp = entries.find(e => e.name === 'first-contentful-paint');
    return fcp ? fcp.startTime : null;
  });

  // Count images with decoding=async
  const imgCount = await page.evaluate(() => document.querySelectorAll('img').length);
  const imgAsyncCount = await page.evaluate(() => document.querySelectorAll('img[decoding="async"]').length);
  const hasCriticalCss = await page.evaluate(() => !!document.getElementById('critical-css'));
  const hasPreloadLinks = await page.evaluate(() => document.querySelectorAll('link[rel="preload"]').length);

  await browser.close();

  return {
    label,
    url,
    loadTime,
    lcp: lcpData,
    fcp: fcpData,
    totalTransfer,
    cssTransfer,
    jsTransfer,
    imgTransfer,
    resourceCount: resources.length,
    imgCount,
    imgAsyncCount,
    hasCriticalCss,
    hasPreloadLinks,
    topResources: resources.sort((a, b) => b.transferSize - a.transferSize).slice(0, 15),
  };
}

(async () => {
  const url = process.argv[2] || 'http://system.test/';
  const label = process.argv[3] || 'Test';
  const result = await measure(url, label);
  console.log(JSON.stringify(result, null, 2));
})();
