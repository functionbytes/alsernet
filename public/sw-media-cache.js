/**
 * Media Cache Service Worker
 * Strategy: Cache-First for media images with stale-while-revalidate.
 */

const CACHE_NAME = 'media-cache-v1';
const MEDIA_ORIGIN = self.location.origin;

// Patterns that identify media files
const MEDIA_PATTERNS = [
  /\/media\/files\//,
  /\/media\//,
  /\.(avif|webp|jpg|jpeg|png|gif|svg|mp4|webm)(\?.*)?$/i,
];

function isMediaRequest(url) {
  return MEDIA_PATTERNS.some((pattern) => pattern.test(url));
}

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
  const { request } = event;

  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);

  if (!isMediaRequest(url.pathname + url.search)) {
    return;
  }

  event.respondWith(
    caches.match(request).then((cached) => {
      if (cached) {
        // Stale-while-revalidate: return cached version immediately,
        // then update cache in background
        event.waitUntil(
          fetch(request)
            .then((response) => {
              if (response.ok) {
                const clone = response.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
              }
            })
            .catch(() => {})
        );
        return cached;
      }

      return fetch(request).then((response) => {
        if (!response.ok || !response.body) {
          return response;
        }

        const clone = response.clone();
        event.waitUntil(
          caches.open(CACHE_NAME).then((cache) => cache.put(request, clone))
        );

        return response;
      });
    })
  );
});
