import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

(window as any).Pusher = Pusher;

// Read connection settings from server-injected WIDGET_CONFIG first
// (set by /widget/script/{token} endpoint), then env vars at build time,
// finally sane fallbacks based on current page.
const cfg: any = (window as any).HELPDESK_WIDGET_CONFIG ?? {};

const reverbKey =
    cfg.reverbKey
    ?? import.meta.env.VITE_REVERB_APP_KEY
    ?? 'local-key';

const reverbHost =
    cfg.reverbHost
    ?? import.meta.env.VITE_REVERB_HOST
    ?? window.location.hostname;

const reverbPort = Number(
    cfg.reverbPort
    ?? import.meta.env.VITE_REVERB_PORT
    ?? 8080
);

const reverbScheme =
    cfg.reverbScheme
    ?? import.meta.env.VITE_REVERB_SCHEME
    ?? (window.location.protocol === 'https:' ? 'https' : 'http');

export const echo = new Echo({
    broadcaster: 'reverb',
    key: reverbKey,
    wsHost: reverbHost,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: reverbScheme === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
});

/** Connection states emitted by pusher-js state_change events. */
export type WsConnectionState =
    | 'initialized'
    | 'connecting'
    | 'connected'
    | 'disconnected'
    | 'unavailable'
    | 'failed';

/**
 * Subscribe to WebSocket connection state changes.
 * Returns an unsubscribe function.
 */
export function onWsStateChange(
    handler: (current: WsConnectionState, previous: WsConnectionState) => void
): () => void {
    const conn = echo.connector.pusher.connection;
    const wrappedHandler = ({ current, previous }: { current: string; previous: string }) => {
        handler(current as WsConnectionState, previous as WsConnectionState);
    };
    conn.bind('state_change', wrappedHandler);
    return () => conn.unbind('state_change', wrappedHandler);
}

/** Current WebSocket connection state (read synchronously). */
export function getWsState(): WsConnectionState {
    return echo.connector.pusher.connection.state as WsConnectionState;
}

echo.connector.pusher.connection.bind('connected', () => {
    if (import.meta.env.DEV) {
        console.log(`✅ Connected to Reverb @ ${reverbScheme}://${reverbHost}:${reverbPort}`);
    }
});

echo.connector.pusher.connection.bind('disconnected', () => {
    if (import.meta.env.DEV) {
        console.log('❌ Disconnected from Reverb WebSocket');
    }
});

echo.connector.pusher.connection.bind('error', (err: any) => {
    console.error('❌ Reverb WebSocket error:', err);
});
