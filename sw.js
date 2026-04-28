self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open('ossiqn-v1').then((cache) => {
      return cache.addAll(['/', '/index.php', '/includes/header.php']);
    })
  );
});

self.addEventListener('fetch', (e) => {
  e.respondWith(
    caches.match(e.request).then((res) => {
      return res || fetch(e.request);
    })
  );
});