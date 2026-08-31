/**
 * Captura "antes" de /panel/settings/helpdeskemaillog y de la referencia
 * /panel/settings/backups/notifications, usando la sesion compartida.
 */
const PW = '/Users/developerts/Library/Application Support/Herd/config/nvm/versions/node/v22.22.2/lib/node_modules/@playwright/mcp/node_modules/playwright-core';
const { chromium } = require(PW);
const path = require('path');

const BASE = process.env.BASE_URL || 'http://localhost:8092';
const STATE = path.join(__dirname, 'shared-state.json');
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

const targets = [
    { url: `${BASE}/panel/settings/helpdeskemaillog`, out: path.join(__dirname, 'agent-emaillog-before-current.png') },
    { url: `${BASE}/panel/settings/backups/notifications`, out: path.join(__dirname, 'agent-emaillog-before-reference.png') },
];

(async () => {
    const browser = await chromium.launch({ headless: true, executablePath: CHROME });
    const context = await browser.newContext({ viewport: { width: 1600, height: 1000 }, storageState: STATE });
    const results = [];

    for (const t of targets) {
        const page = await context.newPage();
        const consoleErrors = [];
        page.on('console', msg => {
            if (msg.type() === 'error') consoleErrors.push(msg.text());
        });

        const response = await page.goto(t.url, { waitUntil: 'networkidle' });
        await page.screenshot({ path: t.out, fullPage: true });

        results.push({
            url: t.url,
            status: response ? response.status() : null,
            consoleErrors: consoleErrors.filter(e => !/pusher/i.test(e) && !/app key/i.test(e)),
            screenshot: t.out,
        });

        await page.close();
    }

    await browser.close();
    console.log(JSON.stringify(results, null, 2));
})().catch(e => { console.error(JSON.stringify({ ok: false, error: String(e) })); process.exit(1); });
