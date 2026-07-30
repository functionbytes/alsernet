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

/** Register the global API so the host site can identify the visitor. */
export function registerGlobalApi(): void {
    (window as any).helpdeskWidgetIdentify = (identity: VisitorIdentity) => {
        setVisitorIdentity(identity);
    };

    (window as any).helpdeskWidgetClearIdentity = () => {
        clearVisitorIdentity();
    };
}
