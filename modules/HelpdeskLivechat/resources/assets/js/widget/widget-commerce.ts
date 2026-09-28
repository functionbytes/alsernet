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

import { applyHostIdentity, HostCustomer } from './widget-identity';

export interface WidgetShop {
    platform?: string;
    context_url?: string;
    /** Ficha de producto para el panel "Productos" (?id=). */
    product_url?: string;
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
    /** Token firmado por la tienda para que el Helpdesk edite esta cesta de invitado. */
    token?: string | null;
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
        const data = await res.json() as { cart?: CartSnapshot | null; customer?: HostCustomer | null };
        // Cliente logueado (o null = invitado) de la sesión de la tienda.
        if (data && 'customer' in data) {
            applyHostIdentity(data.customer ?? null);
        }
        const next = data?.cart ?? null;
        // El token de la cesta de invitado se renueva en cada lectura: no
        // cuenta como cambio de cesta.
        const fingerprint = JSON.stringify(next ? { ...next, token: undefined } : null);
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

export interface AddToCartResult {
    ok: boolean;
    message?: string | null;
}

interface ShopAdapter {
    addToCart?: (p: { id_product: number; id_product_attribute: number; qty: number }) => Promise<AddToCartResult | null>;
    refreshCart?: () => Promise<void> | void;
}

/** ¿La tienda permite añadir al carrito desde el chat? (adaptador o carrito nativo PrestaShop) */
export function canAddToCart(): boolean {
    const adapter = (window as unknown as { HELPDESK_WIDGET_SHOP_ADAPTER?: ShopAdapter }).HELPDESK_WIDGET_SHOP_ADAPTER;
    if (typeof adapter?.addToCart === 'function') {
        return true;
    }
    const ps = (window as unknown as { prestashop?: { static_token?: string } }).prestashop;

    return Boolean(ps?.static_token && getShop()?.cart_url);
}

/**
 * Añade un producto al carrito de la sesión del visitante (invitado o
 * cliente), en el mismo origen y con sus cookies: primero con el adaptador
 * que publica la tienda (su propio carrito/minicarrito); si no hay, con el
 * carrito nativo de PrestaShop (POST /carrito + static_token, respuesta JSON).
 * Precio, stock e impuestos los decide siempre la tienda.
 */
export async function addToCart(idProduct: number, idProductAttribute = 0, qty = 1): Promise<AddToCartResult> {
    const adapter = (window as unknown as { HELPDESK_WIDGET_SHOP_ADAPTER?: ShopAdapter }).HELPDESK_WIDGET_SHOP_ADAPTER;
    let result: AddToCartResult | null = null;

    try {
        if (typeof adapter?.addToCart === 'function') {
            result = await adapter.addToCart({ id_product: idProduct, id_product_attribute: idProductAttribute, qty });
        }
        if (result === null) {
            result = await addToNativePrestashopCart(idProduct, idProductAttribute, qty);
        }
    } catch {
        result = { ok: false };
    }

    // La cesta cambió: el latido la lleva al agente (watchCart también lo
    // detecta por la URL, esto cubre adaptadores con otras rutas).
    if (result.ok && await refreshCart()) {
        window.dispatchEvent(new CustomEvent('helpdesk:cart-changed'));
    }

    return result;
}

async function addToNativePrestashopCart(idProduct: number, idProductAttribute: number, qty: number): Promise<AddToCartResult> {
    const ps = (window as unknown as { prestashop?: { static_token?: string; emit?: (ev: string, data: unknown) => void } }).prestashop;
    const cartUrl = getShop()?.cart_url;
    if (!ps?.static_token || !cartUrl) {
        return { ok: false };
    }

    const url = new URL(cartUrl, window.location.href);
    url.searchParams.delete('action');
    const body = new URLSearchParams({
        token: ps.static_token,
        id_product: String(idProduct),
        id_product_attribute: String(idProductAttribute),
        qty: String(Math.max(1, qty)),
        add: '1',
        action: 'update',
        ajax: '1',
    });

    const res = await fetch(url.toString(), {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body,
    });
    const json = await res.json().catch(() => null) as { success?: boolean; hasError?: boolean; errors?: string[] } | null;
    const ok = res.ok && !!json && json.hasError !== true && json.success !== false;
    if (ok) {
        // Minicarrito del tema clásico de PrestaShop (core.js escucha updateCart).
        ps.emit?.('updateCart', {
            reason: { idProduct, idProductAttribute, linkAction: 'add-to-cart' },
            resp: json,
        });
    }

    return { ok, message: json?.errors?.[0] ?? null };
}

/**
 * La cesta cambió desde el servidor (el agente o el bot añadieron un producto
 * vía la tienda): relee la cesta, avisa al latido y refresca el minicarrito.
 */
export async function onServerCartChange(): Promise<void> {
    if (await refreshCart()) {
        window.dispatchEvent(new CustomEvent('helpdesk:cart-changed'));
    }
    const adapter = (window as unknown as { HELPDESK_WIDGET_SHOP_ADAPTER?: ShopAdapter }).HELPDESK_WIDGET_SHOP_ADAPTER;
    try {
        if (typeof adapter?.refreshCart === 'function') {
            await adapter.refreshCart();
            return;
        }
        const ps = (window as unknown as { prestashop?: { emit?: (ev: string, data: unknown) => void } }).prestashop;
        ps?.emit?.('updateCart', { reason: { linkAction: 'refresh' }, resp: {} });
    } catch {
        // best-effort: la cesta ya está bien en la tienda
    }
}
