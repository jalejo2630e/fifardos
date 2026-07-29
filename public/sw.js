// FIFARDOS service worker.
//
// IMPORTANTE (seguridad): NUNCA cachear respuestas autenticadas/dinámicas
// (HTML de navegación ni XHR de Inertia), porque incluyen datos del usuario
// (auth.user) y, cacheadas por URL, se filtrarían a otro usuario que abra la
// misma ruta. Solo cacheamos assets estáticos inmutables (build hasheado,
// íconos y fuentes). Todo lo demás va directo a la red, sin caché.
const CACHE = 'fifardos-v4';
const PRECACHE = ['/manifest.json', '/icon-192.png', '/icon-512.png', '/favicon.ico'];

// Extensiones de assets estáticos seguros de cachear (no específicos de usuario).
const STATIC_RE = /\.(?:js|css|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|otf|mp4|webm)$/i;

self.addEventListener('install', (e) => {
    self.skipWaiting();
    e.waitUntil(caches.open(CACHE).then((c) => c.addAll(PRECACHE).catch(() => {})));
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys()
            // Purga cachés viejos (incluye v3, que cacheaba páginas autenticadas).
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (e) => {
    const req = e.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);
    const sameOrigin = url.origin === self.location.origin;

    // Inertia (XHR con X-Inertia) y peticiones que esperan JSON → SIEMPRE red, sin caché.
    if (req.headers.get('X-Inertia') || (req.headers.get('Accept') || '').includes('application/json')) {
        return; // deja que el navegador lo maneje por red
    }

    // Navegaciones HTML → SIEMPRE red, sin caché (contienen auth.user embebido).
    if (req.mode === 'navigate') {
        return;
    }

    // Assets estáticos inmutables → cache-first (build hasheado, íconos, fuentes).
    const isStatic = sameOrigin && (url.pathname.startsWith('/build/') || STATIC_RE.test(url.pathname));
    if (isStatic) {
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
        return;
    }

    // Cualquier otra cosa → red directa, sin caché.
});
