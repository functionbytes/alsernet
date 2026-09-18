/**
 * Guarda una sesion autenticada en .ui-test/state.json para que todas las
 * corridas de prueba la reutilicen (el login va con rate limit por email+IP,
 * asi que no conviene repetirlo en cada agente).
 */
const PW = '/Users/developerts/Library/Application Support/Herd/config/nvm/versions/node/v22.22.2/lib/node_modules/@playwright/mcp/node_modules/playwright-core';
const { chromium } = require(PW);
const path = require('path');

const BASE = process.env.BASE_URL || 'http://localhost:8092';
const EMAIL = process.env.UI_EMAIL || 'uitest-theme@alsernet.test';
const PASSWORD = process.env.UI_PASSWORD || 'UiTest#2026';
const STATE = path.join(__dirname, 'state.json');
const CHROME = process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

(async () => {
    const browser = await chromium.launch({ headless: true, executablePath: CHROME });
    const context = await browser.newContext({ viewport: { width: 1600, height: 900 } });
    const page = await context.newPage();

    await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
    await page.fill('input[type="email"], input[name="email"]', EMAIL);
    await page.fill('input[type="password"], input[name="password"]', PASSWORD);
    await Promise.all([
        page.waitForURL(/\/panel/, { timeout: 20000 }),
        page.click('button[type="submit"]'),
    ]);

    await context.storageState({ path: STATE });
    console.log(JSON.stringify({ ok: true, url: page.url(), state: STATE }));
    await browser.close();
})().catch(e => { console.error(JSON.stringify({ ok: false, error: String(e) })); process.exit(1); });
