/**
 * Contexto de compra del visitante para el latido (live commerce).
 *
 * - Productos vistos: lista local (localStorage, máx. 20) alimentada por
 *   `window.HELPDESK_WIDGET_PRODUCT`, que la tienda publica en cada ficha.
 * - Cesta en vivo: la tienda publica `window.HELPDESK_WIDGET_SHOP.context_url`,
 *   un endpoint JSON del mismo origen que devuelve la cesta de la sesión
 *   (invitado o cliente). Se lee al cargar y cada vez que termina una petición
 *   de carrito de la tienda, y se envía en el siguiente latido.
 *
 * Nunca se envía al servidor el token CSRF de la tienda (static_token).
 */

export interface WidgetShop {
    platform?: string;
    context_url?: string;
    cart_url?: string;
    checkout_url?: string;
    currency?: string | null;
    locale?: string | null;
}

export interface ViewedProduct {
    id: string;
    title?: string;
    image_url?: string;
    url?: string;
    price?: number;
    currency?: string;
    viewed_at: string;
}

export interface CartLine {
    id_product: number;
    id_product_attribute: number;
    name?: string;
    attributes?: string | null;
    reference?: string | null;
    qty: number;
    price?: number;
    total?: number;
    image_url?: string | null;
    url?: string | null;
}

export interface CartSnapshot {
    id: number;
    products_count: number;
    total: number;
    total_products?: number;
    currency?: string | null;
    customer_logged?: boolean;
    lines: CartLine[];
}

interface HostProduct {
    id?: string | number;
    title?: string;
    image_url?: string;
    url?: string;
    price?: number;
    currency?: string;
}

const VIEWED_KEY = 'livechat_widget_viewed_products';
const VIEWED_MAX = 20;
const CART_REFRESH_DEBOUNCE_MS = 400;
// Peticiones de carrito del tema Álvarez (alsernetshopping) y del carrito nativo.
const CART_REQUEST_RE = /modalitie=cart|controller=cart|\/carrito(\?|$)|\/cart(\?|$)/i;
const CART_READONLY_RE = /action=(init|count|summary|modal)/i;

/** undefined = aún no se ha leído; null = el visitante no tiene cesta. */
let cart: CartSnapshot | null | undefined;
let cartFingerprint = '';
let refreshTimer: ReturnType<typeof setTimeout> | null = null;
let watching = false;

export function getShop(): WidgetShop | null {
    const shop = (window as unknown as { HELPDESK_WIDGET_SHOP?: WidgetShop }).HELPDESK_WIDGET_SHOP;

    return shop && typeof shop === 'object' ? shop : null;
}

function hostProduct(): HostProduct | null {
    const p = (window as unknown as { HELPDESK_WIDGET_PRODUCT?: HostProduct | null }).HELPDESK_WIDGET_PRODUCT;

    return p && p.id !== undefined && p.id !== null && p.id !== '' ? p : null;
}

export function getViewedProducts(): ViewedProduct[] {
    try {
        const raw = JSON.parse(localStorage.getItem(VIEWED_KEY) ?? '[]');

        return Array.isArray(raw) ? raw.slice(0, VIEWED_MAX) : [];
    } catch {
        return [];
    }
}

/**
 * Apunta el producto de la ficha actual al principio de la lista de vistos.
 * Idempotente dentro de la misma página.
 */
export function recordViewedProduct(): void {
    const p = hostProduct();
    if (!p) {
        return;
    }

    const id = String(p.id);
    const entry: ViewedProduct = {
        id,
        title: p.title,
        image_url: p.image_url,
        url: p.url,
        price: typeof p.price === 'number' ? p.price : undefined,
        currency: p.currency,
        viewed_at: new Date().toISOString(),
    };

    const list = [entry, ...getViewedProducts().filter(v => v.id !== id)].slice(0, VIEWED_MAX);
    try {
        localStorage.setItem(VIEWED_KEY, JSON.stringify(list));
    } catch {
        // best-effort
    }
}

/** Última cesta leída (undefined si todavía no se ha podido leer). */
export function getCart(): CartSnapshot | null | undefined {
    return cart;
}

/**
 * Lee la cesta de la sesión desde la tienda. Devuelve true si cambió.
 */
export async function refreshCart(): Promise<boolean> {
    const url = getShop()?.context_url;
    if (!url) {
        return false;
    }

    try {
        const res = await fetch(url, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
            cache: 'no-store',
        });
        if (!res.ok) {
            return false;
        }
        const data = await res.json() as { cart?: CartSnapshot | null };
        const next = data?.cart ?? null;
        const fingerprint = JSON.stringify(next);
        const changed = cart === undefined || fingerprint !== cartFingerprint;
        cart = next;
        cartFingerprint = fingerprint;

        return changed;
    } catch {
        return false;
    }
}

function isCartMutation(url: string, method: string): boolean {
    if (!CART_REQUEST_RE.test(url)) {
        return false;
    }
    // Las lecturas del minicarrito no cambian nada; el resto (add/update/delete,
    // cupones, POST al carrito nativo) sí.
    return method.toUpperCase() !== 'GET' || !CART_READONLY_RE.test(url);
}

/**
 * Vigila las peticiones de carrito de la página y avisa (con debounce) cuando
 * la cesta ha cambiado. Idempotente.
 */
export function watchCart(onChange: () => void): void {
    if (watching || !getShop()?.context_url) {
        return;
    }
    watching = true;

    const schedule = () => {
        if (refreshTimer !== null) {
            clearTimeout(refreshTimer);
        }
        refreshTimer = setTimeout(async () => {
            refreshTimer = null;
            if (await refreshCart()) {
                onChange();
            }
        }, CART_REFRESH_DEBOUNCE_MS);
    };

    const origFetch = window.fetch;
    window.fetch = function (input: RequestInfo | URL, init?: RequestInit) {
        const url = typeof input === 'string' ? input : input instanceof URL ? input.href : input.url;
        const method = init?.method ?? (input instanceof Request ? input.method : 'GET');
        const promise = origFetch.call(this, input as RequestInfo, init);
        if (isCartMutation(url, method)) {
            promise.finally(schedule);
        }
        return promise;
    };

    const origOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function (this: XMLHttpRequest, method: string, url: string | URL, ...rest: unknown[]) {
        if (isCartMutation(String(url), method)) {
            this.addEventListener('loadend', schedule);
        }
        return (origOpen as (...a: unknown[]) => void).call(this, method, url, ...rest);
    } as typeof XMLHttpRequest.prototype.open;

    // Carrito nativo de PrestaShop (core.js emite updateCart tras añadir/quitar).
    const ps = (window as unknown as { prestashop?: { on?: (ev: string, cb: () => void) => void } }).prestashop;
    ps?.on?.('updateCart', schedule);

    // Otra pestaña pudo cambiar la cesta: relee al volver.
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            schedule();
        }
    });
}
