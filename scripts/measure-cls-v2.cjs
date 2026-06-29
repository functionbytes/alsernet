const { chromium } = require('playwright');

async function measureCLS(url) {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const page = await context.newPage();

  // Navigate first, then query performance entries
  await page.goto(url, { waitUntil: 'domcontentloaded' });
  
  // Wait for fonts, images, etc.
  await page.waitForTimeout(2000);
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(1000);

  const result = await page.evaluate(() => {
    let cls = 0;
    const shifts = [];
    const entries = performance.getEntriesByType('layout-shift');
    entries.forEach(entry => {
      if (!entry.hadRecentInput) {
        cls += entry.value;
        shifts.push({
          value: entry.value,
          startTime: entry.startTime,
          sources: (entry.sources || []).map(s => {
            const n = s.node;
            return n ? {
              tag: n.nodeName,
              class: n.className,
              id: n.id,
            } : null;
          }).filter(Boolean),
        });
      }
    });
    return { cls, shifts };
  });

  // Check computed styles for elements that might cause shifts
  const computedInfo = await page.evaluate(() => {
    const info = [];
    document.querySelectorAll('img').forEach(img => {
      const rect = img.getBoundingClientRect();
      const style = window.getComputedStyle(img);
      if (!img.width || !img.height) {
        info.push({
          src: img.src.split('/').pop(),
          naturalWidth: img.naturalWidth,
          naturalHeight: img.naturalHeight,
          clientWidth: rect.width,
          clientHeight: rect.height,
          hasWidthAttr: img.hasAttribute('width'),
          hasHeightAttr: img.hasAttribute('height'),
          cssWidth: style.width,
          cssHeight: style.height,
        });
      }
    });
    return info;
  });

  await browser.close();
  return { ...result, computedInfo };
}

(async () => {
  const result = await measureCLS('http://system.test/');
  console.log(JSON.stringify(result, null, 2));
})();
