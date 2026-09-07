/**
 * Banco de pruebas del componente `.app-toggler` (modulo Theme).
 *
 * Tres modos segun el ancho (ver nav.css/main.js):
 *   >1480px      'collapse' - contrae/expande el rail en linea (data-app-sidebar)
 *   576-1480px   'push'     - el cajon empuja header+contenido, sin fondo oscuro
 *   <576px       'overlay'  - capa flotante clasica con fondo oscuro y cierre al pulsar fuera
 *
 * Uso:  node toggler-test.cjs 1920 1600 1481 1480 1300 1200 1199 1100 768
 *       node toggler-test.cjs --url=/panel/dashboard 1600 1300
 *
 * Devuelve JSON por stdout: { width, mode, checks: {nombre: {ok, ...}}, fails: [] }
 * Cada ancho corre en su propio contexto de navegador, asi que varias copias
 * del script pueden ejecutarse a la vez sin interferir.
 */
const PW = '/Users/developerts/Library/Application Support/Herd/config/nvm/versions/node/v22.22.2/lib/node_modules/@playwright/mcp/node_modules/playwright-core';
const { chromium } = require(PW);
const path = require('path');

const BASE = process.env.BASE_URL || 'http://localhost:8092';
const CHROME = process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const STATE = path.join(__dirname, 'state.json');
const OVERLAY_MAX = 1480;   // mismo breakpoint que nav.css / main.js
const OFFCANVAS_MAX = 1199; // por debajo el menu entero sale de pantalla
const PUSH_MIN = 576;       // 576-1480: el cajon empuja el contenido, no flota

const args = process.argv.slice(2);
const urlArg = args.find(a => a.startsWith('--url='));
const PATH_UNDER_TEST = urlArg ? urlArg.split('=')[1] : '/panel/mailers/templates';
const widths = args.filter(a => !a.startsWith('--')).map(Number).filter(Boolean);
if (!widths.length) { console.error('Indica al menos un ancho'); process.exit(2); }

const measure = () => {
    const q = s => document.querySelector(s);
    const box = s => { const e = q(s); if (!e) return null; const r = e.getBoundingClientRect();
        return { x: Math.round(r.x), right: Math.round(r.right), w: Math.round(r.width) }; };
    const toggler = q('.app-toggler');
    return {
        innerWidth: window.innerWidth,
        clientWidth: document.documentElement.clientWidth,
        state: document.documentElement.getAttribute('data-app-sidebar'),
        open: q('#appMenubar').classList.contains('open'),
        noSidebarOpen: q('#appMenubar').classList.contains('no-sidebar-open'),
        aria: toggler.getAttribute('aria-expanded'),
        active: toggler.classList.contains('active'),
        ariaLabel: toggler.getAttribute('aria-label'),
        backdrop: (() => { const a = getComputedStyle(toggler, '::after');
            return (a.content === 'none' || a.display === 'none') ? 'none' : a.display; })(),
        chevron: getComputedStyle(q('.app-toggler svg')).transform,
        header: box('.app-header'),
        toggler: box('.app-toggler'),
        menubar: box('.app-menubar-tabs'),
        tabContent: box('.app-tab-content'),
        wrapperMarginLeft: parseInt(getComputedStyle(q('.app-wrapper')).marginLeft, 10),
        hitOverContent: (() => {
            // A 10px del borde derecho, no al 60% del ancho: en movil angosto
            // (<=400px) el cajon abierto (rail 80px + panel ~220px = ~300px)
            // ya cubre mas del 60% del viewport, y el punto caia dentro del
            // propio cajon en vez de sobre el fondo/contenido.
            const e = document.elementFromPoint(window.innerWidth - 10, 400);
            return e ? (e.closest('.app-toggler') ? 'toggler' : e.tagName + '.' + String(e.className).split(' ')[0]) : null;
        })(),
    };
};

const sleep = ms => new Promise(r => setTimeout(r, ms));

async function runWidth(browser, width) {
    const errors = [];
    const context = await browser.newContext({ storageState: STATE, viewport: { width, height: 900 } });
    const page = await context.newPage();
    const KNOWN_NOISE = [/Pusher/i, /Echo/i, /favicon/i];
    const isNoise = t => KNOWN_NOISE.some(re => re.test(t));
    const noise = [];
    const push = t => (isNoise(t) ? noise : errors).push(t);
    page.on('console', m => { if (m.type() === 'error') push(m.text()); });
    page.on('pageerror', e => push('pageerror: ' + String(e)));

    await page.goto(BASE + PATH_UNDER_TEST, { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('.app-toggler');
    await sleep(500);

    const initial = await page.evaluate(measure);
    await page.click('.app-toggler');
    await sleep(500);
    const opened = await page.evaluate(measure);
    if (width < PUSH_MIN) {
        // <576px: modo capa flotante clasico y ya documentado -el propio
        // boton queda tapado por el cajon abierto por diseño (sin hueco para
        // apartar el header sin dejar el buscador/notificaciones inutilizables
        // en un movil tan angosto)-, asi que aqui se cierra pulsando el fondo,
        // no con un segundo clic en el boton (que colgaria el test, no el
        // producto: Esc y el fondo si funcionan, y se verifican mas abajo).
        // A 10px del borde derecho: en movil muy angosto (320-400px) un punto
        // como 0.9*ancho todavia cae DENTRO del cajon abierto (rail 80px +
        // panel ~220px = ~300px), que intercepta el clic en vez del fondo.
        await page.mouse.click(width - 10, 400);
    } else {
        await page.click('.app-toggler');
    }
    await sleep(500);
    const closed = await page.evaluate(measure);

    // Esc aplica en cualquier modo <=1480 (capa superpuesta o empuje)
    let escClosed = null;
    if (width <= OVERLAY_MAX) {
        await page.click('.app-toggler');
        await sleep(450);
        await page.keyboard.press('Escape');
        await sleep(450);
        escClosed = await page.evaluate(measure);
    }

    // Solo en modo empuje (576-1480): clic sobre el contenido NO debe cerrar
    // (el contenido esta visible/usable, no tapado; cerrar solo por hacer
    // clic dentro de un formulario abierto seria sorprendente).
    let stillOpenAfterContentClick = null;
    if (width >= PUSH_MIN && width <= OVERLAY_MAX) {
        await page.click('.app-toggler');
        await sleep(450);
        await page.mouse.click(Math.round(width * 0.85), 400);
        await sleep(400);
        stillOpenAfterContentClick = await page.evaluate(measure);
        // lo dejamos cerrado para no ensuciar la siguiente medicion
        await page.click('.app-toggler');
        await sleep(400);
    }

    await context.close();
    // `noise` se reporta aparte: son errores del entorno, no del componente.

    const mode = width > OVERLAY_MAX ? 'collapse' : (width >= PUSH_MIN ? 'push' : 'overlay');
    const scrollbar = initial.innerWidth - initial.clientWidth;
    const checks = {};
    const fail = (name, ok, detail) => { checks[name] = { ok, ...detail }; };

    // 1. El header arranca exactamente donde arranca el contenido (sin doble offset)
    fail('header_alineado_con_contenido',
        initial.header.x === initial.wrapperMarginLeft,
        { headerX: initial.header.x, wrapperMarginLeft: initial.wrapperMarginLeft });

    // 2. El boton pegado al borde izquierdo del header (margen de 10px del CSS)
    fail('toggler_pegado_a_la_izquierda',
        initial.toggler.x - initial.header.x === 10,
        { togglerX: initial.toggler.x, headerX: initial.header.x, offset: initial.toggler.x - initial.header.x });

    // 3. El boton no queda tapado por el rail de iconos
    const railRight = width > OFFCANVAS_MAX ? initial.menubar.right : 0;
    fail('toggler_no_solapa_el_rail',
        initial.toggler.x >= railRight,
        { togglerX: initial.toggler.x, railRight });

    // 4. El header llega hasta el borde derecho (sin hueco muerto)
    fail('header_hasta_el_borde_derecho',
        Math.abs(initial.header.right - (width - scrollbar)) <= 1,
        { headerRight: initial.header.right, esperado: width - scrollbar, scrollbar });

    // 5. El clic produce un cambio visible
    let abre, cierra;
    if (mode === 'collapse') {
        abre = Math.abs(opened.menubar.w - initial.menubar.w) > 100;
        cierra = closed.menubar.w === initial.menubar.w;
    } else {
        abre = initial.tabContent.x < 0 && opened.tabContent.x >= 0;
        cierra = closed.tabContent.x < 0;
    }
    fail('el_clic_abre', abre, {
        modo: mode,
        antes: mode === 'collapse' ? initial.menubar.w : initial.tabContent.x,
        despues: mode === 'collapse' ? opened.menubar.w : opened.tabContent.x,
    });
    fail('el_clic_cierra', cierra, {
        despues: mode === 'collapse' ? closed.menubar.w : closed.tabContent.x,
    });

    // 6. aria-expanded refleja el estado real
    fail('aria_expanded_sincronizado',
        initial.aria !== opened.aria && closed.aria === initial.aria,
        { inicial: initial.aria, abierto: opened.aria, cerrado: closed.aria });

    // 7. Los chevrons giran cuando el panel esta cerrado
    const rotado = s => s.chevron !== 'none' && s.chevron !== '';
    fail('chevron_gira_al_cerrar',
        (initial.aria === 'false') === rotado(initial) && (opened.aria === 'false') === rotado(opened),
        { inicialAria: initial.aria, inicialTransform: initial.chevron, abiertoAria: opened.aria, abiertoTransform: opened.chevron });

    // 8. Backdrop / cierre al pulsar fuera / empuje del contenido, segun el modo
    if (mode === 'overlay') {
        // <576px: capa flotante clasica, con fondo oscuro y cierre al pulsar fuera.
        fail('backdrop_visible_al_abrir',
            opened.backdrop === 'block' && initial.backdrop === 'none' && closed.backdrop === 'none',
            { inicial: initial.backdrop, abierto: opened.backdrop, cerrado: closed.backdrop });
        fail('pulsar_fuera_cierra',
            opened.hitOverContent === 'toggler',
            { elementoSobreElContenido: opened.hitOverContent });
        fail('escape_cierra',
            escClosed && escClosed.open === false,
            { open: escClosed && escClosed.open });
    } else if (mode === 'push') {
        // 576-1480px: sin fondo oscuro, el contenido se empuja en vez de taparse.
        fail('sin_backdrop_en_modo_empuje',
            initial.backdrop === 'none' && opened.backdrop === 'none',
            { inicial: initial.backdrop, abierto: opened.backdrop });
        fail('header_y_contenido_se_empujan_juntos',
            opened.header.x === opened.wrapperMarginLeft && opened.header.x > initial.header.x,
            { headerX: opened.header.x, wrapperMarginLeft: opened.wrapperMarginLeft, headerXInicial: initial.header.x });
        fail('clic_en_contenido_no_cierra',
            stillOpenAfterContentClick && stillOpenAfterContentClick.open === true,
            { open: stillOpenAfterContentClick && stillOpenAfterContentClick.open });
        fail('escape_cierra',
            escClosed && escClosed.open === false,
            { open: escClosed && escClosed.open });
    } else {
        fail('sin_backdrop_en_escritorio',
            initial.backdrop === 'none' && opened.backdrop === 'none',
            { inicial: initial.backdrop, abierto: opened.backdrop });
    }

    // 9. Sin errores de consola
    fail('sin_errores_de_consola', errors.length === 0, { errores: errors.slice(0, 5), ruidoPreexistente: noise.slice(0, 3) });

    const fails = Object.entries(checks).filter(([, v]) => !v.ok).map(([k]) => k);
    return { width, mode, url: PATH_UNDER_TEST, noSidebarOpen: initial.noSidebarOpen, checks, fails, ok: fails.length === 0 };
}

(async () => {
    const browser = await chromium.launch({ headless: true, executablePath: CHROME });
    const results = [];
    for (const w of widths) results.push(await runWidth(browser, w));
    await browser.close();
    const failed = results.filter(r => !r.ok);
    console.log(JSON.stringify({ total: results.length, ok: results.length - failed.length, ko: failed.length, results }, null, 2));
    process.exit(failed.length ? 1 : 0);
})().catch(e => { console.error(String(e && e.stack || e)); process.exit(2); });
