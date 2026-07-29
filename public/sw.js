// FIFARDOS service worker — network-first para contenido, cache-first para íconos.
// Bump CACHE en cada cambio de estrategia para forzar purga del caché viejo.
const CACHE = 'fifardos-v3';
const PRECACHE = ['/manifest.json', '/icon-192.png', '/icon-512.png', '/favicon.ico'];

self.addEventListener('install', (e) => {
    self.skipWaiting();
    e.waitUntil(
        caches.open(CACHE).then((c) => c.addAll(PRECACHE).catch(() => {}))
    );
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (e) => {
    const req = e.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);
    const isNavigation = req.mode === 'navigate';
    const isBuild = url.pathname.startsWith('/build/');

    // Network-first: navegaciones (HTML) y assets compilados → siempre lo más nuevo.
    if (isNavigation || isBuild) {
        e.respondWith(
            fetch(req)
                .then((res) => {
                    if (res && res.ok && res.type === 'basic') {
                        const clone = res.clone();
                        caches.open(CACHE).then((c) => c.put(req, clone));
                    }
                    return res;
                })
                .catch(() => caches.match(req).then((c) => c || caches.match('/')))
        );
        return;
    }

    // Cache-first: íconos y estáticos estables.
    e.respondWith(
        caches.match(req).then((cached) =>
            cached ||
            fetch(req).then((res) => {
                if (res && res.ok && res.type === 'basic') {
                    const clone = res.clone();
                    caches.open(CACHE).then((c) => c.put(req, clone));
                }
                return res;
            }).catch(() => cached)
        )
    );
});
