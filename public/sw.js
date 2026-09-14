const CACHE_NAME = 'helpdesk-v2';
// '/' se sacó de STATIC_ASSETS: el fetch de instalación sigue el 302 a /login
// cuando el usuario no está autenticado, y cachea una Response marcada como
// "redirected". Chrome rechaza servir esa Response cacheada para una
// navegación (net::ERR_FAILED) — por eso "/" fallaba siempre pero las demás
// URLs no (nunca se cacheaban). CACHE_NAME subió de versión para que
// activate() purgue la caché vieja con la entrada rota en los clientes ya
// instalados.
const STATIC_ASSETS = [];

self.addEventListener('install', event => {
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
    // La raíz redirige (302 a /login o al dashboard) según sesión — nunca debe
    // servirse desde caché, un Service Worker no puede responder una
    // navegación con una Response "redirected" cacheada (Chrome la rechaza
    // con net::ERR_FAILED). Ver nota en STATIC_ASSETS más arriba.
    if (new URL(event.request.url).pathname === '/') return;

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
