/**
 * Visitor identity manager for the helpdesk widget.
 *
 * The host site (e.g. PrestaShop) can call window.helpdeskWidgetIdentify()
 * whenever the visitor logs in, registers, or updates their profile.
 * The data is persisted in localStorage and sent with every subsequent
 * message so the backend can update the customer record and the agent
 * sees the real name, email, cart contents, orders, etc.
 */

export interface VisitorIdentity {
    email?: string;
    name?: string;
    phone?: string;
    userId?: string | number;
    customer_id?: string | number;
    platform?: string;
    cart?: Record<string, unknown>;
    orders?: Array<Record<string, unknown>>;
    [key: string]: unknown;
}

const STORAGE_KEY = 'livechat_visitor_identity';

export function getVisitorIdentity(): VisitorIdentity | null {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

export function setVisitorIdentity(identity: VisitorIdentity): void {
    try {
        const previousRaw = localStorage.getItem(STORAGE_KEY);
        const previous = previousRaw ? JSON.parse(previousRaw) : null;
        const previousId = previous?.customer_id ?? previous?.userId ?? null;
        const newId = identity.customer_id ?? identity.userId ?? null;

        // If the customer identity changed, discard any stale conversation
        // from localStorage so the backend can look up (or create) the
        // correct conversation for the new customer.
        if (newId != null && String(newId) !== String(previousId)) {
            localStorage.removeItem('livechat_conversation_id');
        }

        localStorage.setItem(STORAGE_KEY, JSON.stringify(identity));

        // Also update the legacy keys so ConversationScreen picks them up
        // immediately without waiting for a re-mount.
        if (identity.email) {
            localStorage.setItem('livechat_customer_email', identity.email);
        }
        if (identity.name) {
            localStorage.setItem('livechat_customer_name', identity.name);
        }
        const id = identity.customer_id ?? identity.userId;
        if (id != null) {
            localStorage.setItem('livechat_customer_id', String(id));
        }

        // Notify any open widget instances that the identity changed.
        window.dispatchEvent(new CustomEvent('helpdesk:identity:changed', {
            detail: identity,
        }));
    } catch {
        // localStorage may be unavailable in private mode.
    }
}

export function clearVisitorIdentity(): void {
    localStorage.removeItem(STORAGE_KEY);
}

export function getCustomAttributes(): Record<string, unknown> | null {
    const identity = getVisitorIdentity();
    if (!identity) return null;

    const { email, name, phone, userId, ...extras } = identity;
    return Object.keys(extras).length > 0 ? extras : null;
}

export interface HostCustomer {
    email?: unknown;
    name?: unknown;
    customer_id?: unknown;
    platform?: unknown;
    identifier?: unknown;
    identifier_hash?: unknown;
}

const HOST_SOURCE = 'host';

/** Prueba de identidad de la última lectura de la tienda (solo en memoria). */
let hostProof: { identifier: string; identifier_hash: string } | null = null;

/**
 * Prueba de identidad que firma la tienda (HMAC del email con el hmac_token
 * del canal). El servidor solo da por verificado al cliente si cuadra.
 */
export function getHostIdentityProof(): { identifier: string; identifier_hash: string } | null {
    return hostProof;
}

/**
 * Aplica el cliente logueado que devuelve la tienda en su endpoint de contexto
 * (no en el HTML, que pasa por la caché de página). null = invitado: si la
 * identidad guardada venía de la tienda se borra — en un ordenador compartido
 * el siguiente visitante no hereda el chat.
 */
export function applyHostIdentity(c: HostCustomer | null): void {
    const current = getVisitorIdentity();

    if (c === null) {
        hostProof = null;
        if (current?.source === HOST_SOURCE) {
            clearVisitorIdentity();
            ['livechat_customer_email', 'livechat_customer_name', 'livechat_customer_id'].forEach(k => {
                try { localStorage.removeItem(k); } catch { /* best-effort */ }
            });
        }
        return;
    }
    if (typeof c.email !== 'string' || c.email === '') {
        return;
    }

    hostProof = typeof c.identifier === 'string' && typeof c.identifier_hash === 'string'
        ? { identifier: c.identifier, identifier_hash: c.identifier_hash }
        : null;

    if (current?.email === c.email && String(current?.customer_id ?? '') === String(c.customer_id ?? '')) {
        return;
    }

    setVisitorIdentity({
        email: c.email,
        name: typeof c.name === 'string' ? c.name : undefined,
        customer_id: typeof c.customer_id === 'number' || typeof c.customer_id === 'string' ? c.customer_id : undefined,
        platform: typeof c.platform === 'string' ? c.platform : undefined,
        source: HOST_SOURCE,
    });
}

/** Register the global API so the host site can identify the visitor. */
export function registerGlobalApi(): void {
    (window as any).helpdeskWidgetIdentify = (identity: VisitorIdentity) => {
        setVisitorIdentity(identity);
    };

    (window as any).helpdeskWidgetClearIdentity = () => {
        clearVisitorIdentity();
    };
}
