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
    /** Máximo pedible (stock disponible; ~1000 si la tienda permite reservar sin stock). */
    max_qty?: number;
    price?: number;
    total?: number;
    image_url?: string | null;
    url?: string | null;
}

export interface CartVoucher {
    id: number;
    code: string;
    name?: string;
    /** Importe del descuento (positivo), con impuestos. */
    amount: number;
}

export interface FreeShippingProgress {
    threshold: number;
    /** 0 = ya hay envío gratis. */
    remaining: number;
}

export interface CartSnapshot {
    id: number;
    /** Token firmado por la tienda para que el Helpdesk edite esta cesta de invitado. */
    token?: string | null;
    products_count: number;
    total: number;
    total_products?: number;
    total_discounts?: number;
    total_shipping?: number;
    currency?: string | null;
    customer_logged?: boolean;
    lines: CartLine[];
    vouchers?: CartVoucher[];
    free_shipping?: FreeShippingProgress | null;
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
                // Único punto de aviso: cualquier pestaña de "Mi cesta" abierta
                // se entera sin que quien llamó a watchCart() tenga que saberlo.
                window.dispatchEvent(new CustomEvent('helpdesk:cart-changed'));
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
    /** Cantidad absoluta de la línea; 0 = eliminarla. */
    updateQuantity?: (p: { id_product: number; id_product_attribute: number; qty: number }) => Promise<AddToCartResult | null>;
    removeLine?: (p: { id_product: number; id_product_attribute: number }) => Promise<AddToCartResult | null>;
    applyVoucher?: (code: string) => Promise<AddToCartResult | null>;
    removeVoucher?: (id: number) => Promise<AddToCartResult | null>;
}

function getShopAdapter(): ShopAdapter | undefined {
    return (window as unknown as { HELPDESK_WIDGET_SHOP_ADAPTER?: ShopAdapter }).HELPDESK_WIDGET_SHOP_ADAPTER;
}

/** La cesta cambió: relee y, si cambió de verdad, avisa a quien esté escuchando (Mi cesta, latido…). */
async function afterCartMutation(ok: boolean): Promise<void> {
    if (ok && await refreshCart()) {
        window.dispatchEvent(new CustomEvent('helpdesk:cart-changed'));
    }
}

/** ¿La tienda permite añadir al carrito desde el chat? (adaptador o carrito nativo PrestaShop) */
export function canAddToCart(): boolean {
    const adapter = getShopAdapter();
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
    const adapter = getShopAdapter();
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

    await afterCartMutation(result.ok);

    return result;
}

/**
 * Cambia la cantidad de una línea de la cesta a un valor absoluto (0 la
 * elimina). Usa el adaptador de la tienda si lo publica; si no, el carrito
 * nativo de PrestaShop. La cantidad, el stock y el precio final los decide
 * siempre la tienda: esto es solo la petición, el estado real llega con el
 * siguiente `refreshCart()`.
 */
export async function updateCartQuantity(idProduct: number, idProductAttribute: number, qty: number): Promise<AddToCartResult> {
    const adapter = getShopAdapter();
    let result: AddToCartResult | null = null;

    try {
        if (typeof adapter?.updateQuantity === 'function') {
            result = await adapter.updateQuantity({ id_product: idProduct, id_product_attribute: idProductAttribute, qty });
        }
        if (result === null) {
            result = await updateNativePrestashopCartQuantity(idProduct, idProductAttribute, qty);
        }
    } catch {
        result = { ok: false };
    }

    await afterCartMutation(result.ok);

    return result;
}

/** Quita una línea entera de la cesta. */
export async function removeCartLine(idProduct: number, idProductAttribute: number): Promise<AddToCartResult> {
    const adapter = getShopAdapter();
    let result: AddToCartResult | null = null;

    try {
        if (typeof adapter?.removeLine === 'function') {
            result = await adapter.removeLine({ id_product: idProduct, id_product_attribute: idProductAttribute });
        }
        if (result === null) {
            result = await updateNativePrestashopCartQuantity(idProduct, idProductAttribute, 0);
        }
    } catch {
        result = { ok: false };
    }

    await afterCartMutation(result.ok);

    return result;
}

/** Aplica un código de descuento a la cesta. */
export async function applyCartVoucher(code: string): Promise<AddToCartResult> {
    const adapter = getShopAdapter();
    let result: AddToCartResult | null = null;

    try {
        if (typeof adapter?.applyVoucher === 'function') {
            result = await adapter.applyVoucher(code);
        }
    } catch {
        result = { ok: false };
    }
    result ??= { ok: false, message: null };

    await afterCartMutation(result.ok);

    return result;
}

/** Quita un código de descuento ya aplicado. */
export async function removeCartVoucher(id: number): Promise<AddToCartResult> {
    const adapter = getShopAdapter();
    let result: AddToCartResult | null = null;

    try {
        if (typeof adapter?.removeVoucher === 'function') {
            result = await adapter.removeVoucher(id);
        }
    } catch {
        result = { ok: false };
    }
    result ??= { ok: false, message: null };

    await afterCartMutation(result.ok);

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

/** Carrito nativo de PrestaShop: cantidad absoluta (0 = eliminar), best-effort para temas sin adaptador. */
async function updateNativePrestashopCartQuantity(idProduct: number, idProductAttribute: number, qty: number): Promise<AddToCartResult> {
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
        action: 'update',
        ajax: '1',
    });
    if (qty <= 0) {
        body.set('delete', '1');
    } else {
        body.set('qty', String(qty));
    }

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
        ps.emit?.('updateCart', {
            reason: { idProduct, idProductAttribute, linkAction: qty <= 0 ? 'remove-from-cart' : 'update-quantity' },
            resp: json,
        });
    }

    return { ok, message: json?.errors?.[0] ?? null };
}

/**
 * Combinaciones (talla, color…) de un producto para elegirlas sin salir del
 * widget: la ficha (`widgetproduct.php`) las incluye cuando el producto
 * `has_combinations`. Se piden solo al abrir el selector y se cachean en
 * memoria por producto (misma pestaña).
 */
export interface VariantGroupValue {
    value: string;
    color_hex?: string | null;
}

export interface VariantGroup {
    name: string;
    type: 'select' | 'radio' | 'color' | string;
    values: VariantGroupValue[];
}

export interface VariantOption {
    id_product_attribute: number;
    label: string;
    /** Grupo → valor elegido, p. ej. { Talla: '42', Color: 'Marrón' }. */
    groups: Record<string, string>;
    available: boolean;
    low_stock: boolean;
    price: number;
    price_original: number | null;
    image: string | null;
    default: boolean;
}

export interface ProductVariants {
    groups: VariantGroup[];
    options: VariantOption[];
}

const variantsCache = new Map<string, Promise<ProductVariants | null>>();

/** Combinaciones del producto (cacheadas); null si no tiene o no se pudieron cargar. */
export function getProductVariants(idProduct: string | number): Promise<ProductVariants | null> {
    const key = String(idProduct);
    let cached = variantsCache.get(key);
    if (!cached) {
        cached = fetchProductVariants(key);
        variantsCache.set(key, cached);
    }

    return cached;
}

async function fetchProductVariants(idProduct: string): Promise<ProductVariants | null> {
    const productUrl = getShop()?.product_url;
    if (!productUrl) {
        return null;
    }

    try {
        const url = new URL(productUrl, window.location.href);
        url.searchParams.set('id', idProduct);
        const res = await fetch(url.toString(), { credentials: 'same-origin', headers: { Accept: 'application/json' }, cache: 'no-store' });
        if (!res.ok) {
            return null;
        }
        const json = await res.json() as { product?: { groups?: VariantGroup[]; options?: VariantOption[] } | null };
        const groups = json?.product?.groups ?? [];
        const options = json?.product?.options ?? [];

        return groups.length && options.length ? { groups, options } : null;
    } catch {
        return null;
    }
}

/** La combinación cuya selección de grupos coincide exactamente, o null si falta elegir alguno. */
export function findVariantOption(variants: ProductVariants, selection: Record<string, string>): VariantOption | null {
    if (Object.keys(selection).length !== variants.groups.length) {
        return null;
    }

    return variants.options.find(o => variants.groups.every(g => o.groups[g.name] === selection[g.name])) ?? null;
}

/**
 * La cesta cambió desde el servidor (el agente o el bot añadieron un producto
 * vía la tienda): relee la cesta, avisa al latido y refresca el minicarrito.
 */
export async function onServerCartChange(): Promise<void> {
    if (await refreshCart()) {
        window.dispatchEvent(new CustomEvent('helpdesk:cart-changed'));
    }
    const adapter = getShopAdapter();
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
