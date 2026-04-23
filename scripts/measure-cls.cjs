const { chromium } = require('playwright');

async function measureCLS(url) {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });

  // Inject CLS observer before navigation
  await page.addInitScript(() => {
    window.__clsValue = 0;
    new PerformanceObserver((list) => {
      for (const entry of list.getEntries()) {
        if (!entry.hadRecentInput) {
          window.__clsValue += entry.value;
        }
      }
    }).observe({ type: 'layout-shift', buffered: true });
  });

  await page.goto(url, { waitUntil: 'networkidle', timeout: 60000 });
  await page.waitForTimeout(3000);

  const cls = await page.evaluate(() => window.__clsValue);
  
  const shifts = await page.evaluate(() => {
    return performance.getEntriesByType('layout-shift').map(e => ({
      value: e.value,
      hadRecentInput: e.hadRecentInput,
      sources: (e.sources || []).map(s => {
        const n = s.node;
        return n ? {
          tag: n.nodeName,
          class: n.className,
          id: n.id,
          rect: n.getBoundingClientRect ? { top: n.getBoundingClientRect().top, left: n.getBoundingClientRect().left, width: n.getBoundingClientRect().width, height: n.getBoundingClientRect().height } : null,
        } : null;
      }).filter(Boolean),
    }));
  });

  const cookieBanner = await page.evaluate(() => {
    const el = document.querySelector('.cookie-consent, #cookie-banner, .cookie-banner, [class*="cookie"]');
    return el ? { tag: el.tagName, className: el.className, id: el.id } : null;
  });

  const fontsLoaded = await page.evaluate(() => {
    return performance.getEntriesByType('layout-shift')
      .filter(e => !e.hadRecentInput)
      .map(e => ({
        value: e.value,
        sources: (e.sources || []).map(s => s.node?.nodeName).filter(Boolean),
      }));
  });

  await browser.close();

  return { cls, shifts, cookieBanner, fontsLoaded };
}

(async () => {
  const result = await measureCLS('http://system.test/');
  console.log(JSON.stringify(result, null, 2));
})();
