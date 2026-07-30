/**
 * Build an absolute API URL for the widget.
 *
 * When the widget is embedded on a third-party site (e.g. PrestaShop),
 * window.location.origin is the *host* site, not the Laravel backend.
 * All fetch() calls must therefore be rooted at the backend baseUrl
 * injected via window.HELPDESK_WIDGET_CONFIG.
 */
export function apiUrl(path: string): string {
    const base =
        (window as any).HELPDESK_WIDGET_CONFIG?.baseUrl
        ?? (window as any).HELPDESK_WIDGET_CONFIG?.apiUrl
        ?? '';

    const cleanBase = base.replace(/\/$/, '');
    const cleanPath = path.replace(/^\//, '');

    return cleanBase ? `${cleanBase}/${cleanPath}` : `/${cleanPath}`;
}

/**
 * Per-conversation secret (the conversation's `widget_pubsub_token`).
 *
 * Handed to the visitor only when the conversation is created/reused and
 * persisted in localStorage. Every conversation REST call must send it back
 * via the `X-Conversation-Token` header so the backend can authorize access
 * to that specific conversation — instead of trusting a guessable, sequential
 * customer_id (which was an IDOR). See WidgetConversationController.
 */
const CONVERSATION_TOKEN_KEY = 'livechat_pubsub_token';

export function getConversationToken(): string {
    try {
        return localStorage.getItem(CONVERSATION_TOKEN_KEY) ?? '';
    } catch {
        return '';
    }
}

export function setConversationToken(token: string): void {
    try {
        if (token) {
            localStorage.setItem(CONVERSATION_TOKEN_KEY, token);
        }
    } catch {
        /* localStorage may be unavailable in private mode */
    }
}

export function clearConversationToken(): void {
    try {
        localStorage.removeItem(CONVERSATION_TOKEN_KEY);
    } catch {
        /* localStorage may be unavailable in private mode */
    }
}

/**
 * Auth header for per-conversation REST calls. Returns an empty object when no
 * token is stored yet (e.g. before the first conversation is created), so it is
 * always safe to spread into a fetch() headers object.
 */
export function conversationAuthHeaders(): Record<string, string> {
    const token = getConversationToken();

    return token ? { 'X-Conversation-Token': token } : {};
}
