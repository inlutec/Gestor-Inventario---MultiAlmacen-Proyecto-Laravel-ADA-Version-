// Service Worker para desregistrar la versión anterior
console.log('[SW Firma] Desregistrando service worker...');

self.addEventListener('install', (event) => {
  console.log('[SW Firma] Instalando SW de limpieza...');
  event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
  console.log('[SW Firma] Activando SW de limpieza...');
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        // Eliminar todos los caches de firma-movil
        return Promise.all(
          cacheNames.map((cacheName) => {
            if (cacheName.startsWith('firma-movil-')) {
              console.log('[SW Firma] Eliminando cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => {
        console.log('[SW Firma] Caches eliminados');
        return self.clients.claim();
      })
      .then(() => {
        // Desregistrar este service worker después de limpiar
        return self.registration.unregister();
      })
      .then(() => {
        console.log('[SW Firma] Service worker desregistrado');
      })
  );
});
