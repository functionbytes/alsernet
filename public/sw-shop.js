// Service Worker para tienda Caixilharia Blanco
// Estrategia: cache-first para assets, network-first para HTML

const CACHE_VERSION = 'shop-v1';
const STATIC_CACHE = `static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `dynamic-${CACHE_VERSION}`;

const STATIC_ASSETS = [
    '/tienda',
    '/manifest.json',
    '/modules/ecommerce/images/product-placeholder.svg',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll(STATIC_ASSETS).catch(() => {}))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => key.startsWith('shop-') && key !== STATIC_CACHE && key !== DYNAMIC_CACHE)
                    .map((key) => caches.delete(key))
            )
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET') return;
    if (url.origin !== location.origin) return;
    if (url.pathname.startsWith('/panel') || url.pathname.startsWith('/horizon') || url.pathname.startsWith('/telescope')) return;

    const acceptsHTML = request.headers.get('accept')?.includes('text/html');

    if (acceptsHTML) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const copy = response.clone();
                    caches.open(DYNAMIC_CACHE).then((cache) => cache.put(request, copy));
                    return response;
                })
                .catch(() => caches.match(request).then((cached) => cached || caches.match('/tienda')))
        );
        return;
    }

    event.respondWith(
        caches.match(request).then((cached) => {
            return (
                cached ||
                fetch(request).then((response) => {
                    if (response && response.status === 200) {
                        const copy = response.clone();
                        caches.open(DYNAMIC_CACHE).then((cache) => cache.put(request, copy));
                    }
                    return response;
                })
            );
        })
    );
});

self.addEventListener('push', (event) => {
    if (!event.data) return;
    let data = {};
    try { data = event.data.json(); } catch (e) { data = { title: 'Notificación', body: event.data.text() }; }
    event.waitUntil(self.registration.showNotification(data.title || 'Notificación', {
        body: data.body || '',
        icon: data.icon || '/android-chrome-192x192.png',
        badge: '/favicon-32x32.png',
        data: { url: data.url || '/tienda' },
        vibrate: [200, 100, 200],
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(clients.openWindow(event.notification.data.url));
});
