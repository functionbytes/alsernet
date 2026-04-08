import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Make Pusher available globally
(window as any).Pusher = Pusher;

// Configure Laravel Echo with Reverb
export const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'local-key',
    wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
});

// Log connection status
echo.connector.pusher.connection.bind('connected', () => {
    console.log('✅ Connected to Reverb WebSocket');
});

echo.connector.pusher.connection.bind('disconnected', () => {
    console.log('❌ Disconnected from Reverb WebSocket');
});

echo.connector.pusher.connection.bind('error', (err: any) => {
    console.error('❌ Reverb WebSocket error:', err);
});
