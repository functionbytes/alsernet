const CACHE_NAME = 'helpdesk-v1';
const STATIC_ASSETS = ['/'];

self.addEventListener('install', event => {
    // addAll() rejects the whole batch if any single request fails (e.g. when
    // the user is logged out and '/' returns a 302). Use Promise.allSettled
    // with put() so a single failure doesn't abort install.
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache =>
            Promise.allSettled(
                STATIC_ASSETS.map(url =>
                    fetch(url, { credentials: 'same-origin' })
                        .then(res => res.ok ? cache.put(url, res) : null)
                        .catch(() => null)
                )
            )
        )
    );
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') return;
    if (event.request.url.includes('/panel/') || event.request.url.includes('/api/')) return;

    event.respondWith(
        caches.match(event.request).then(cached => cached || fetch(event.request))
    );
});

self.addEventListener('push', event => {
    const data = event.data?.json() || {};
    const title = data.title || 'Helpdesk';
    const options = {
        body: data.body || '',
        icon: '/favicon.png',
        badge: '/favicon.png',
        data: { url: data.url || '/' },
        requireInteraction: data.requireInteraction || false,
        tag: data.tag,
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil(clients.openWindow(event.notification.data.url || '/'));
});
