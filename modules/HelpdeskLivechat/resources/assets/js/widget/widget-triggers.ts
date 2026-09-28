/**
 * Disparadores proactivos (live commerce, fase 5).
 *
 * Las reglas del canal llegan de GET /hd/api/triggers y se evalúan aquí, en el
 * navegador, cada segundo (sin peticiones extra): tiempo en la web y en la
 * página, páginas vistas, productos vistos, producto actual, URL, idioma,
 * día y franja horaria, y valor/unidades del carrito. Como máximo un disparo
 * por página, el de mayor prioridad; nunca con el chat ya abierto.
 */

import { apiUrl, getWebsiteToken } from './api';
import { getCart, getShop, getViewedProducts } from './widget-commerce';
import { useWidgetStore } from './widget-store';
import { getDetectedLanguage } from './i18n/useLanguage';

interface TriggerCondition {
    type: string;
    op: string;
    value: string;
}

interface WidgetTriggerRule {
    id: number;
    match: 'all' | 'any';
    conditions: TriggerCondition[];
    action: 'open_chat' | 'message';
    message: string | null;
    /** Traducciones por idioma (en, fr…); si falta la del visitante, `message`. */
    messages?: Record<string, string>;
    frequency: 'once_visitor' | 'once_session' | 'every_page';
}

const FIRED_VISITOR_KEY = 'hd_triggers_fired';
const FIRED_SESSION_KEY = 'hd_triggers_fired_session';
const SITE_START_KEY = 'hd_site_start';
const PAGES_KEY = 'hd_pages_visited';
const CHECK_INTERVAL_MS = 1000;

const pageStart = Date.now();
let timer: ReturnType<typeof setInterval> | null = null;
let firedThisPage = false;

function readJson(storage: Storage | undefined, key: string): Record<string, number> {
    try {
        const raw = storage?.getItem(key);
        const parsed = raw ? JSON.parse(raw) : {};
        return parsed && typeof parsed === 'object' ? parsed : {};
    } catch {
        return {};
    }
}

function writeJson(storage: Storage | undefined, key: string, value: Record<string, number>): void {
    try {
        storage?.setItem(key, JSON.stringify(value));
    } catch {
        // best-effort (modo privado)
    }
}

/** Inicio de la sesión de navegación y páginas vistas en ella (sessionStorage). */
function trackSession(): { siteStart: number; pages: number } {
    let siteStart = Date.now();
    let pages = 1;
    try {
        const stored = Number(sessionStorage.getItem(SITE_START_KEY));
        if (stored > 0) {
            siteStart = stored;
        } else {
            sessionStorage.setItem(SITE_START_KEY, String(siteStart));
        }
        pages = Number(sessionStorage.getItem(PAGES_KEY) || '0') + 1;
        sessionStorage.setItem(PAGES_KEY, String(pages));
    } catch {
        // sin sessionStorage: cuenta solo esta página
    }
    return { siteStart, pages };
}

function numberCompare(actual: number | null, op: string, expected: number): boolean {
    if (actual === null || Number.isNaN(actual)) return false;
    return op === 'lte' ? actual <= expected : actual >= expected;
}

function urlMatches(op: string, value: string): boolean {
    const href = window.location.href.toLowerCase();
    return value.split('||').map(v => v.trim().toLowerCase()).filter(Boolean).some((v) => {
        switch (op) {
            case 'starts': return href.startsWith(v) || window.location.pathname.toLowerCase().startsWith(v);
            case 'ends': return href.endsWith(v) || window.location.pathname.toLowerCase().endsWith(v);
            case 'equals': return href === v || window.location.pathname.toLowerCase() === v;
            default: return href.includes(v);
        }
    });
}

function currentLocale(): string {
    const fromShop = getShop()?.locale ?? '';
    const lang = fromShop || document.documentElement.lang || navigator.language || '';
    return lang.slice(0, 2).toLowerCase();
}

function evaluate(condition: TriggerCondition, session: { siteStart: number; pages: number }): boolean {
    const value = String(condition.value ?? '').trim();
    const num = Number(value);
    const now = new Date();

    switch (condition.type) {
        case 'site_time':
            return numberCompare((Date.now() - session.siteStart) / 1000, condition.op, num);
        case 'page_time':
            return numberCompare((Date.now() - pageStart) / 1000, condition.op, num);
        case 'pages_visited':
            return numberCompare(session.pages, condition.op, num);
        case 'products_viewed':
            return numberCompare(getViewedProducts().length, condition.op, num);
        case 'product_viewed':
            return getViewedProducts().some(p => String(p.id) === value);
        case 'current_product': {
            const p = (window as unknown as { HELPDESK_WIDGET_PRODUCT?: { id?: string | number } | null }).HELPDESK_WIDGET_PRODUCT;
            return !!p && String(p.id) === value;
        }
        case 'url':
            return urlMatches(condition.op, value);
        case 'locale':
            return currentLocale() === value.toLowerCase();
        case 'weekday': {
            const day = now.getDay() === 0 ? 7 : now.getDay();
            return value.split(',').map(Number).includes(day);
        }
        case 'hour_range': {
            const [from, to] = value.split('-').map(Number);
            const hour = now.getHours();
            return hour >= from && hour < to;
        }
        case 'cart_value': {
            const cart = getCart();
            return numberCompare(cart ? Number(cart.total_products ?? cart.total ?? 0) : cart === null ? 0 : null, condition.op, num);
        }
        case 'cart_items': {
            const cart = getCart();
            return numberCompare(cart ? Number(cart.products_count ?? 0) : cart === null ? 0 : null, condition.op, num);
        }
        default:
            return false;
    }
}

function alreadyFired(rule: WidgetTriggerRule): boolean {
    if (rule.frequency === 'once_visitor') {
        return !!readJson(localStorage, FIRED_VISITOR_KEY)[rule.id];
    }
    if (rule.frequency === 'once_session') {
        return !!readJson(sessionStorage, FIRED_SESSION_KEY)[rule.id];
    }
    return false;
}

function markFired(rule: WidgetTriggerRule): void {
    const key = rule.frequency === 'once_visitor' ? FIRED_VISITOR_KEY : rule.frequency === 'once_session' ? FIRED_SESSION_KEY : null;
    if (!key) return;
    const storage = rule.frequency === 'once_visitor' ? localStorage : sessionStorage;
    const fired = readJson(storage, key);
    fired[rule.id] = Math.floor(Date.now() / 1000);
    writeJson(storage, key, fired);
}

function fire(rule: WidgetTriggerRule): void {
    firedThisPage = true;
    markFired(rule);
    const store = useWidgetStore.getState();
    const text = (rule.messages && rule.messages[getDetectedLanguage()]) || rule.message;
    if (rule.action === 'message' && text) {
        store.pushBotMessage(text);
    }
    store.requestRoute('/conversation');
    store.setOpen(true);
}

async function loadRules(): Promise<WidgetTriggerRule[]> {
    const token = getWebsiteToken();
    if (!token) return [];
    try {
        const res = await fetch(apiUrl(`/hd/api/triggers?website_token=${encodeURIComponent(token)}`), {
            headers: { Accept: 'application/json' },
        });
        if (!res.ok) return [];
        const json = await res.json() as { data?: WidgetTriggerRule[] };
        return Array.isArray(json?.data) ? json.data : [];
    } catch {
        return [];
    }
}

/** Arranca la evaluación de disparadores del canal. Idempotente. */
export async function startTriggers(): Promise<void> {
    if (timer !== null) return;
    const session = trackSession();
    const rules = await loadRules();
    if (rules.length === 0) return;

    timer = setInterval(() => {
        if (firedThisPage) {
            if (timer !== null) clearInterval(timer);
            timer = null;
            return;
        }
        // Con el chat abierto el visitante ya está atendido.
        if (useWidgetStore.getState().isOpen) return;

        const rule = rules.find((r) => {
            if (alreadyFired(r) || !Array.isArray(r.conditions) || r.conditions.length === 0) return false;
            const results = r.conditions.map(c => evaluate(c, session));
            return r.match === 'any' ? results.some(Boolean) : results.every(Boolean);
        });
        if (rule) fire(rule);
    }, CHECK_INTERVAL_MS);
}
