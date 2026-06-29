import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        },
    },
});

/**
 * ReverbManager — thin wrapper around Echo expected by the chat agent panel
 * (modules/Chat/public/js/chat/messaging/realtime-messaging.js).
 *
 * subscribe(type, channelName, handlers) — type is 'private' | 'public'.
 * unsubscribe(channelName) — leaves the channel and removes all listeners.
 */
const _reverbChannels = new Map();

window.ReverbManager = {
    subscribe(type, channelName, handlers = {}) {
        // Always leave a previous subscription on the same channel
        if (_reverbChannels.has(channelName)) {
            this.unsubscribe(channelName);
        }

        const channel = type === 'private'
            ? window.Echo.private(channelName)
            : window.Echo.channel(channelName);

        for (const [eventName, fn] of Object.entries(handlers)) {
            // Echo uses leading "." for events that don't follow Laravel's
            // default namespace convention (custom broadcastAs strings).
            channel.listen('.' + eventName, fn);
        }

        _reverbChannels.set(channelName, { channel, handlers });
        return channel;
    },

    unsubscribe(channelName) {
        const entry = _reverbChannels.get(channelName);
        if (!entry) return;
        try {
            window.Echo.leaveChannel(channelName);
        } catch (e) {
            console.warn('[ReverbManager] leaveChannel failed:', e);
        }
        _reverbChannels.delete(channelName);
    },

    isConnected() {
        try {
            return window.Echo.connector.pusher.connection.state === 'connected';
        } catch (e) {
            return false;
        }
    },
};

window.Echo.connector.pusher.connection.bind('connected', () => {
    console.log('[ReverbManager] ✅ Echo connected');
});
window.Echo.connector.pusher.connection.bind('error', (err) => {
    console.error('[ReverbManager] ❌ Echo error:', err);
});
