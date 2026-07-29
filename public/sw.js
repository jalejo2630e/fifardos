const CACHE = 'fifardos-v1';
const ASSETS = [
    '/',
    '/manifest.json',
    '/build/assets/app-DpNRPhzq.css',
    '/build/assets/app-CFIU-nDu.js',
    '/build/assets/ChatBot-COChJV3l.js',
    '/build/assets/ChatBot-BvWIeDMA.css',
    '/build/assets/Dashboard-CsDPFieh.js',
    '/build/assets/Dashboard-h3Kav66u.css',
];

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE).then((c) => c.addAll(ASSETS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (e) => {
    if (e.request.method !== 'GET') return;
    e.respondWith(
        caches.match(e.request).then((cached) => {
            const fetchPromise = fetch(e.request).then((res) => {
                if (res.ok && res.type === 'basic') {
                    const clone = res.clone();
                    caches.open(CACHE).then((c) => c.put(e.request, clone));
                }
                return res;
            }).catch(() => cached);
            return cached || fetchPromise;
        })
    );
});
