const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();
    await page.goto('https://system.test/panel/dashboard', { waitUntil: 'networkidle' });
    await page.screenshot({ path: 'dashboard-main.png', fullPage: true });
    await browser.close();
    console.log('Screenshot guardado en dashboard-main.png');
})();
