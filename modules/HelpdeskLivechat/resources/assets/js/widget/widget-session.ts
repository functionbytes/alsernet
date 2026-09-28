/**
 * Visitor session token manager.
 *
 * The widget generates a stable per-browser session token (UUID-ish) that is
 * persisted in localStorage and sent on every heartbeat + conversation create.
 * The backend uses this token to:
 *   - Track visitor pages, IP, browser, OS (anonymized).
 *   - Show the agent which pages the visitor saw before/during the chat.
 */

import { apiUrl, getWebsiteToken } from './api';
import { getCart, getViewedProducts, recordViewedProduct, refreshCart, watchCart } from './widget-commerce';

const STORAGE_KEY = 'livechat_widget_session_token';
const DEFAULT_HEARTBEAT_INTERVAL_MS = 5_000; // 5s — matches server cooldown
let heartbeatTimer: ReturnType<typeof setInterval> | null = null;
let lastSentUrl = '';

function resolveHeartbeatIntervalMs(): number {
    const cfg = (window as unknown as { HELPDESK_WIDGET_CONFIG?: { tracking?: { heartbeatIntervalMs?: number } } }).HELPDESK_WIDGET_CONFIG;
    const raw = cfg?.tracking?.heartbeatIntervalMs;
    if (typeof raw === 'number' && raw >= 5_000 && raw <= 600_000) {
        return raw;
    }
    return DEFAULT_HEARTBEAT_INTERVAL_MS;
}

/**
 * Producto que el visitante está viendo, para la covisualización (el agente lo
 * ve en el panel antes de abrir el chat). La página anfitriona lo publica en
 * `window.HELPDESK_WIDGET_PRODUCT` en las fichas de producto (p. ej. desde el
 * hook de PrestaShop/Shopify). Ausente en páginas que no son de producto.
 */
interface WidgetProduct {
    id: string | number;
    id_product_attribute?: number;
    title?: string;
    image_url?: string;
    url?: string;
    price?: number;
    currency?: string;
}

function resolveCurrentProduct(): WidgetProduct | null {
    const product = (window as unknown as { HELPDESK_WIDGET_PRODUCT?: WidgetProduct }).HELPDESK_WIDGET_PRODUCT;
    if (!product || product.id === undefined || product.id === null || product.id === '') {
        return null;
    }
    return {
        id: String(product.id),
        id_product_attribute: typeof product.id_product_attribute === 'number' ? product.id_product_attribute : undefined,
        title: product.title,
        image_url: product.image_url,
        url: product.url,
        price: typeof product.price === 'number' ? product.price : undefined,
        currency: product.currency,
    };
}

export function getSessionToken(): string {
    let token = '';
    try {
        token = localStorage.getItem(STORAGE_KEY) ?? '';
    } catch {
        // localStorage unavailable (private mode) — fall through to generate a fresh token
    }

    if (!token) {
        token = generateToken();
        try {
            localStorage.setItem(STORAGE_KEY, token);
        } catch {
            // best-effort
        }
    }

    return token;
}

function generateToken(): string {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }
    if (typeof crypto !== 'undefined' && crypto.getRandomValues) {
        const bytes = new Uint8Array(16);
        crypto.getRandomValues(bytes);
        return 'wgs-' + Array.from(bytes, b => b.toString(16).padStart(2, '0')).join('');
    }
    // Last resort for very old browsers without crypto support
    return 'wgs-' + Math.random().toString(36).slice(2) + Date.now().toString(36);
}

/**
 * Send a heartbeat to the backend with current URL + page title, the product
 * being viewed, the recently viewed products and the live cart.
 * Backend creates/updates the WidgetSession and appends a WidgetPageView
 * if the URL changed or the cooldown (5s server-side) elapsed.
 *
 * fetch con keepalive (no sendBeacon): sobrevive a la descarga de la página
 * en MPAs (PrestaShop) y, a diferencia de sendBeacon, puede llevar la cabecera
 * X-Website-Token que exige HeartbeatRequest. El token va también en el body.
 */
export async function sendHeartbeat(): Promise<void> {
    const token = getSessionToken();
    const url = window.location.href;
    const websiteToken = getWebsiteToken();
    const product = resolveCurrentProduct();
    const cart = getCart();
    const viewed = getViewedProducts();
    const payload = {
        session_token: token,
        website_token: websiteToken,
        url,
        title: document.title,
        ...(product ? { product } : {}),
        // undefined = cesta aún no leída: no se envía para no borrar la guardada.
        ...(cart !== undefined ? { cart } : {}),
        ...(viewed.length ? { viewed_products: viewed } : {}),
    };

    lastSentUrl = url;

    try {
        await fetch(apiUrl('/hd/api/session/heartbeat'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(websiteToken ? { 'X-Website-Token': websiteToken } : {}),
            },
            body: JSON.stringify(payload),
            keepalive: true,
        });
    } catch {
        // Silent — heartbeat is best-effort, no UX impact
    }
}

function sendIfUrlChanged(): void {
    if (window.location.href !== lastSentUrl) {
        sendHeartbeat();
    }
}

/**
 * Start periodic heartbeats. Idempotent — calling again is a no-op.
 *
 * Hooks the History API (pushState/replaceState) so SPAs that navigate
 * without firing popstate (React Router, Vue Router, etc.) still register
 * page views immediately.
 */
export function startHeartbeat(): void {
    if (heartbeatTimer !== null) {
        return;
    }
    recordViewedProduct();
    // Primer latido con la cesta ya leída (si la tienda la expone); si tarda
    // más de 1,5 s, sale sin ella.
    let firstSent = false;
    const initialCart = refreshCart();
    Promise.race([initialCart, new Promise(r => setTimeout(r, 1500))]).finally(() => {
        firstSent = true;
        sendHeartbeat();
    });
    // Si la cesta llegó después del primer latido, se envía ya y no en el
    // siguiente intervalo.
    initialCart.then(changed => {
        if (changed && firstSent) {
            sendHeartbeat();
        }
    });
    watchCart(() => { sendHeartbeat(); });
    // Añadido desde el chat (widget-commerce ya releyó la cesta).
    window.addEventListener('helpdesk:cart-changed', () => { sendHeartbeat(); });
    heartbeatTimer = setInterval(sendHeartbeat, resolveHeartbeatIntervalMs());

    window.addEventListener('popstate', sendIfUrlChanged);
    window.addEventListener('hashchange', sendIfUrlChanged);
    window.addEventListener('pageshow', sendIfUrlChanged);

    // Fire immediately when the tab regains focus — reduces lag in same-browser
    // testing and on mobile where tabs are frequently backgrounded.
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            sendHeartbeat();
        }
    });

    const origPush = history.pushState.bind(history);
    const origReplace = history.replaceState.bind(history);
    history.pushState = function (...args) {
        origPush(...args);
        setTimeout(sendIfUrlChanged, 0);
    };
    history.replaceState = function (...args) {
        origReplace(...args);
        setTimeout(sendIfUrlChanged, 0);
    };
}

export function stopHeartbeat(): void {
    if (heartbeatTimer !== null) {
        clearInterval(heartbeatTimer);
        heartbeatTimer = null;
    }
}
