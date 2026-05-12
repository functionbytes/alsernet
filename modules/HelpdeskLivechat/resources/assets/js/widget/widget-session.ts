/**
 * Visitor session token manager.
 *
 * The widget generates a stable per-browser session token (UUID-ish) that is
 * persisted in localStorage and sent on every heartbeat + conversation create.
 * The backend uses this token to:
 *   - Track visitor pages, IP, browser, OS (anonymized).
 *   - Show the agent which pages the visitor saw before/during the chat.
 */

import { apiUrl } from './api';

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
 * Send a heartbeat to the backend with current URL + page title.
 * Backend creates/updates the WidgetSession and appends a WidgetPageView
 * if the URL changed or the cooldown (5s server-side) elapsed.
 *
 * Uses sendBeacon for URL changes to fire instantly even on page unload —
 * critical for MPAs (PrestaShop, etc.) where the user navigates before
 * fetch can complete.
 */
export async function sendHeartbeat(): Promise<void> {
    const token = getSessionToken();
    const url = window.location.href;
    const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    const payload = {
        session_token: token,
        url,
        title: document.title,
    };

    lastSentUrl = url;

    if (typeof navigator.sendBeacon === 'function') {
        try {
            const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
            const ok = navigator.sendBeacon(apiUrl('/hd/api/session/heartbeat'), blob);
            if (ok) {
                return;
            }
        } catch {
            // fall through to fetch
        }
    }

    try {
        await fetch(apiUrl('/hd/api/session/heartbeat'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
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
    sendHeartbeat();
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
