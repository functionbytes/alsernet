// Service Worker para cache offline-first del Helpdesk
// Cachea endpoints GET de contexto de cliente cuando el SW puede interceptarlos.
// Para interceptar /api/* el servidor debe enviar el header:
//   Service-Worker-Allowed: /
// Sin ese header el SW opera solo dentro de /modules/helpdesktickets/.
// El cache offline-first real se implementa via IndexedDB + localStorage en tickets.js.
var CACHE_NAME = 'helpdesk-v1';
var API_CACHE = 'helpdesk-api-v1';

self.addEventListener('install', function (event) {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.filter(function (key) {
                    return key !== CACHE_NAME && key !== API_CACHE;
                }).map(function (key) {
                    return caches.delete(key);
                })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

self.addEventListener('fetch', function (event) {
    // Solo GET sobre endpoints de contexto de cliente
    if (event.request.method !== 'GET') { return; }

    var url = new URL(event.request.url);
    var cacheable = /\/api\/(helpdeskErp|helpdeskprestashop)\/customers\/[^/]+\/(context|timeline)/.test(url.pathname);
    if (!cacheable) { return; }

    event.respondWith(
        fetch(event.request.clone()).then(function (response) {
            if (response && response.ok) {
                var clone = response.clone();
                caches.open(API_CACHE).then(function (cache) {
                    cache.put(event.request, clone);
                });
            }
            return response;
        }).catch(function () {
            // Red caída — servir desde cache
            return caches.open(API_CACHE).then(function (cache) {
                return cache.match(event.request).then(function (cached) {
                    if (cached) { return cached; }
                    return new Response(JSON.stringify({
                        customer: { found: false },
                        orders: [],
                        data: [],
                        meta: { offline: true, error_type: 'offline' },
                    }), {
                        status: 503,
                        headers: { 'Content-Type': 'application/json' },
                    });
                });
            });
        })
    );
});
