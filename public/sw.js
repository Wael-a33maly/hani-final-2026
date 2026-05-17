const CACHE = 'aqsati-v1';
const ASSETS = [
  '/public/manifest.json',
  '/public/icons/icon-192.svg',
  '/public/icons/icon-512.svg',
];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE).then((c) => c.addAll(ASSETS))
  );
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
    )
  );
});

self.addEventListener('fetch', (e) => {
  const { request } = e;
  const url = new URL(request.url);

  if (url.pathname.startsWith('/public/')) {
    e.respondWith(
      caches.match(request).then((cached) => cached || fetch(request).then((res) => {
        const clone = res.clone();
        caches.open(CACHE).then((c) => c.put(request, clone));
        return res;
      }))
    );
    return;
  }

  e.respondWith(
    fetch(request).catch(() => caches.match(request))
  );
});
