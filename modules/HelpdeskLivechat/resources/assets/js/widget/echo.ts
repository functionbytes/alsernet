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

echo.connector.pusher.connection.bind('connected', () => {
    console.log(`✅ Connected to Reverb @ ${reverbScheme}://${reverbHost}:${reverbPort}`);
});

echo.connector.pusher.connection.bind('disconnected', () => {
    console.log('❌ Disconnected from Reverb WebSocket');
});

echo.connector.pusher.connection.bind('error', (err: any) => {
    console.error('❌ Reverb WebSocket error:', err);
});
