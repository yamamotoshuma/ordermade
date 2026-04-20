self.addEventListener('install', function(e) {
  console.log('[ServiceWorker] Install');
});

self.addEventListener('activate', function(e) {
  console.log('[ServiceWorker] Activate');
});

// Keep a no-op fetch handler so the browser treats this as an active service worker.
self.addEventListener('fetch', function(event) {});
