// Service Worker para PWA - Gestor de Material Junta de Andalucía
// Build: 2025-11-14T10:15:00 - FORCE REFRESH
const CACHE_NAME = 'gestion-material-v1.4.0';
const RUNTIME_CACHE = 'gestion-material-runtime-v1.4';
const IMAGE_CACHE = 'gestion-material-images-v1.1';
const API_CACHE = 'gestion-material-api-v1.4';
const FONT_CACHE = 'gestion-material-fonts-v1.1';
const BASE_URL = '/gestionmaterial';

// Recursos estáticos que se cachean durante la instalación
const STATIC_ASSETS = [
  `${BASE_URL}/`,
  `${BASE_URL}/manifest.json`,
  `${BASE_URL}/images/logo-junta.svg`,
  `${BASE_URL}/images/logo-ada.svg`,
  `${BASE_URL}/images/icons/icon-192x192.png`,
  `${BASE_URL}/images/icons/icon-512x512.png`,
  `${BASE_URL}/images/icons/icon-96x96.png`,
];

// Patrones para caché dinámico
const CACHE_PATTERNS = {
  fonts: /\.(woff2?|ttf|eot)$/,
  images: /\.(png|jpe?g|svg|gif|webp|ico)$/,
  scripts: /\.js$/,
  styles: /\.css$/,
  api: /^.*\/api\//
};

// Instalación del Service Worker
self.addEventListener('install', (event) => {
  console.log('[SW] Installing service worker v1.3.2 (Build: 2025-11-12T13:40)...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[SW] Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => {
        console.log('[SW] Static assets cached successfully');
        return self.skipWaiting();
      })
      .catch((error) => {
        console.error('[SW] Failed to cache static assets:', error);
      })
  );
});

// Activación del Service Worker
self.addEventListener('activate', (event) => {
  console.log('[SW] Activating service worker v1.3.2...');
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => {
            if (cacheName !== CACHE_NAME &&
                cacheName !== RUNTIME_CACHE &&
                cacheName !== IMAGE_CACHE &&
                cacheName !== API_CACHE &&
                cacheName !== FONT_CACHE) {
              console.log('[SW] Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => {
        console.log('[SW] Service worker activated');
        return self.clients.claim();
      })
      .then(() => {
        // Notificar a todos los clientes que el SW está activo
        return self.clients.matchAll().then(clients => {
          clients.forEach(client => {
            client.postMessage({ type: 'SW_ACTIVATED' });
          });
        });
      })
  );
});

// Estrategias de caché
const cacheFirst = async (request, cacheName) => {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(request);
  if (cached) {
    return cached;
  }
  try {
    const response = await fetch(request);
    if (response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    console.error('[SW] Fetch failed:', error);
    throw error;
  }
};

const networkFirst = async (request, cacheName) => {
  const cache = await caches.open(cacheName);
  try {
    const response = await fetch(request);
    if (response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    console.log('[SW] Network failed, trying cache...');
    const cached = await cache.match(request);
    if (cached) {
      return cached;
    }
    throw error;
  }
};

const staleWhileRevalidate = async (request, cacheName) => {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(request);
  
  const fetchPromise = fetch(request).then((response) => {
    if (response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  }).catch((error) => {
    console.warn('[SW] Network request failed, serving from cache:', error);
    return cached;
  });

  return cached || fetchPromise;
};

const networkFirstWithTimeout = async (request, cacheName, timeout = 5000) => {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(request);
  
  try {
    const timeoutPromise = new Promise((_, reject) => {
      setTimeout(() => reject(new Error('Network timeout')), timeout);
    });
    
    const networkPromise = fetch(request);
    const response = await Promise.race([networkPromise, timeoutPromise]);
    
    if (response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    console.log('[SW] Network failed or timeout, trying cache...');
    if (cached) {
      return cached;
    }
    throw error;
  }
};

const networkFirstWithFallback = async (request, cacheName) => {
  const cache = await caches.open(cacheName);
  
  try {
    const response = await fetch(request);
    if (response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    console.log('[SW] Network failed, trying cache...');
    const cached = await cache.match(request);
    if (cached) {
      return cached;
    }
    
    // Fallback a página offline
    const offlineResponse = await cache.match(`${BASE_URL}/`);
    if (offlineResponse) {
      return offlineResponse;
    }
    
    throw error;
  }
};

// Interceptar las peticiones
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorar peticiones que no sean GET o peticiones a otros dominios (excepto CDN)
  if (request.method !== 'GET') {
    return;
  }

  // Solo interceptar peticiones del mismo origen o CDNs permitidos
  if (!url.origin.includes(self.location.origin) &&
      !url.origin.includes('fonts.googleapis.com') &&
      !url.origin.includes('fonts.gstatic.com')) {
    return;
  }

  // Estrategia para fuentes: Cache First (con caché dedicado)
  if (CACHE_PATTERNS.fonts.test(url.pathname)) {
    event.respondWith(cacheFirst(request, FONT_CACHE));
    return;
  }

  // Estrategia para imágenes: Cache First
  if (request.destination === 'image' || CACHE_PATTERNS.images.test(url.pathname)) {
    event.respondWith(cacheFirst(request, IMAGE_CACHE));
    return;
  }

  // Estrategia para API: Network First con timeout
  if (url.pathname.startsWith(`${BASE_URL}/api/`) || url.pathname.startsWith('/gestionmaterial/api/')) {
    event.respondWith(networkFirstWithTimeout(request, API_CACHE, 5000));
    return;
  }

  // Estrategia para assets estáticos: Stale While Revalidate
  if (url.pathname.startsWith(`${BASE_URL}/build/`) ||
      url.pathname.startsWith(`${BASE_URL}/images/`) ||
      CACHE_PATTERNS.styles.test(url.pathname) ||
      CACHE_PATTERNS.scripts.test(url.pathname)) {
    event.respondWith(staleWhileRevalidate(request, RUNTIME_CACHE));
    return;
  }

  // Estrategia para páginas HTML: Network First con fallback
  if (request.destination === 'document') {
    event.respondWith(networkFirstWithFallback(request, CACHE_NAME));
    return;
  }

  // Estrategia por defecto: Network First
  event.respondWith(networkFirst(request, RUNTIME_CACHE));
});

// Manejo de notificaciones push
self.addEventListener('push', (event) => {
  console.log('[SW] Push received:', event);
  
  let data = {
    title: 'Gestión de Material',
    body: 'Nueva notificación',
    icon: `${BASE_URL}/images/icons/icon-192x192.png`,
    badge: `${BASE_URL}/images/icons/icon-96x96.png`,
    tag: 'default',
    requireInteraction: true,
  };

  if (event.data) {
    try {
      data = { ...data, ...event.data.json() };
    } catch (e) {
      data.body = event.data.text();
    }
  }

  const options = {
    body: data.body,
    icon: data.icon,
    badge: data.badge,
    tag: data.tag,
    requireInteraction: data.requireInteraction,
    data: data.url ? { url: data.url } : {},
    actions: data.actions || [],
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

// Manejo de clicks en notificaciones
self.addEventListener('notificationclick', (event) => {
  console.log('[SW] Notification clicked:', event);
  
  event.notification.close();

  const urlToOpen = event.notification.data?.url || `${BASE_URL}/`;

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then((clientList) => {
        // Buscar si ya hay una ventana abierta con la app
        for (const client of clientList) {
          if (client.url.includes(BASE_URL) && 'focus' in client) {
            return client.focus().then(() => {
              if (urlToOpen !== `${BASE_URL}/`) {
                client.navigate(urlToOpen);
              }
            });
          }
        }
        // Si no hay ninguna ventana abierta, abrir una nueva
        if (clients.openWindow) {
          return clients.openWindow(urlToOpen);
        }
      })
  );
});

// Sincronización en segundo plano
self.addEventListener('sync', (event) => {
  console.log('[SW] Background sync:', event.tag);
  
  if (event.tag === 'sync-peticiones') {
    event.waitUntil(syncPeticiones());
  }
});

async function syncPeticiones() {
  try {
    // Sincronizar peticiones pendientes offline
    console.log('[SW] Syncing peticiones data...');
    const db = await openIndexedDB();
    const tx = db.transaction('peticiones-pendientes', 'readonly');
    const store = tx.objectStore('peticiones-pendientes');
    const peticiones = await getAllFromStore(store);
    
    for (let peticion of peticiones) {
      try {
        const response = await fetch(`${BASE_URL}/api/peticiones`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(peticion.data)
        });
        
        if (response.ok) {
          // Eliminar petición sincronizada
          const txDelete = db.transaction('peticiones-pendientes', 'readwrite');
          await txDelete.objectStore('peticiones-pendientes').delete(peticion.id);
          console.log('[SW] Petición sincronizada:', peticion.id);
        }
      } catch (err) {
        console.error('[SW] Error sincronizando petición individual:', err);
      }
    }
  } catch (error) {
    console.error('[SW] Sync failed:', error);
    throw error;
  }
}

function openIndexedDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('gestion-material-db', 1);
    
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
    
    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains('peticiones-pendientes')) {
        db.createObjectStore('peticiones-pendientes', { keyPath: 'id', autoIncrement: true });
      }
    };
  });
}

function getAllFromStore(store) {
  return new Promise((resolve, reject) => {
    const request = store.getAll();
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

// Manejo de mensajes desde la aplicación
self.addEventListener('message', (event) => {
  console.log('[SW] Message received:', event.data);
  
  if (event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  
  if (event.data.type === 'CLEAR_CACHE') {
    event.waitUntil(
      caches.keys().then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => caches.delete(cacheName))
        );
      })
    );
  }

  if (event.data.type === 'CACHE_URLS') {
    event.waitUntil(
      caches.open(RUNTIME_CACHE).then((cache) => {
        return cache.addAll(event.data.urls);
      })
    );
  }
});

console.log('[SW] Service Worker loaded');
