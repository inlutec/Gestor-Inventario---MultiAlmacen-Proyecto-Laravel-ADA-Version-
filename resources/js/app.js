import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';
import { useThemeStore } from './stores/theme';
import axios from 'axios';

// Limpiar parámetros por defecto de axios para evitar interferencias entre componentes
axios.defaults.params = {};

// Global styles are provided by resources/css/app.css via @vite in app.blade.php.
// Avoid importing legacy styles here to prevent overrides.

const pinia = createPinia();
const app = createApp(App);

app.use(pinia);
app.use(router);

// Inicializar tema (light/dark/system) antes de montar la app
// Pasamos la instancia de pinia para evitar problemas al usar el store fuera de componentes
const themeStore = useThemeStore(pinia);
themeStore.init();

app.mount('#app');

// Registrar Service Worker para PWA con mejor manejo
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/gestionmaterial/service-worker.js', {
      scope: '/gestionmaterial/'
    })
    .then((registration) => {
      console.log('[PWA] Service Worker registrado correctamente:', registration.scope);
      
      // Verificar actualizaciones cada 30 minutos (más frecuente para mejor UX)
      setInterval(() => {
        registration.update();
      }, 30 * 60 * 1000);

      // Detectar nuevas versiones con mejor UX
      registration.addEventListener('updatefound', () => {
        const newWorker = registration.installing;
        newWorker.addEventListener('statechange', () => {
          if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
            // Hay una nueva versión disponible
            showUpdatePrompt(() => {
              newWorker.postMessage({ type: 'SKIP_WAITING' });
              window.location.reload();
            });
          }
        });
      });
      
      // Manejar mensajes del Service Worker
      navigator.serviceWorker.addEventListener('message', (event) => {
        if (event.data && event.data.type === 'SW_ACTIVATED') {
          console.log('[PWA] Service Worker activado');
          hideLoader();
        }
      });
    })
    .catch((error) => {
      console.error('[PWA] Error al registrar Service Worker:', error);
      hideLoader();
    });
  });

  // Recargar cuando se activa un nuevo service worker
  let refreshing = false;
  navigator.serviceWorker.addEventListener('controllerchange', () => {
    if (!refreshing) {
      refreshing = true;
      window.location.reload();
    }
  });
}

// Función para mostrar prompt de actualización
function showUpdatePrompt(onAccept) {
  // Crear modal de actualización
  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4';
  modal.innerHTML = `
    <div class="bg-white rounded-lg p-6 max-w-sm w-full shadow-xl">
      <div class="flex items-center mb-4">
        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-3">
          <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
        </div>
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Actualización disponible</h3>
          <p class="text-sm text-gray-600">Hay una nueva versión con mejoras y correcciones.</p>
        </div>
      </div>
      <div class="flex gap-3">
        <button onclick="this.closest('.fixed').remove()" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
          Ahora no
        </button>
        <button onclick="this.closest('.fixed').remove(); window.updateCallback()" class="flex-1 px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors">
          Actualizar
        </button>
      </div>
    </div>
  `;
  
  window.updateCallback = onAccept;
  document.body.appendChild(modal);
}

// Función para ocultar loader inicial
function hideLoader() {
  const loader = document.getElementById('initial-loader');
  if (loader) {
    loader.style.display = 'none';
  }
}

// Detectar cuando la app se instala como PWA
window.addEventListener('appinstalled', () => {
  console.log('[PWA] App instalada correctamente');
  
  // Ocultar banner de instalación si existe
  const installBanner = document.querySelector('.pwa-install-banner');
  if (installBanner) {
    installBanner.style.display = 'none';
  }
  
  // Mostrar mensaje de bienvenida
  showWelcomeMessage();
});

// Detectar cambios en la conexión con mejor manejo
window.addEventListener('online', () => {
  console.log('[PWA] Conexión restaurada');
  document.body.classList.remove('offline');
  
  // Sincronizar datos pendientes
  if ('serviceWorker' in navigator && 'sync' in ServiceWorkerRegistration.prototype) {
    navigator.serviceWorker.ready.then((registration) => {
      return registration.sync.register('sync-peticiones');
    });
  }
  
  // Ocultar indicador offline
  const offlineIndicator = document.querySelector('.online-indicator.offline');
  if (offlineIndicator) {
    offlineIndicator.remove();
  }
});

window.addEventListener('offline', () => {
  console.log('[PWA] Sin conexión - Modo offline activado');
  document.body.classList.add('offline');
  
  // Mostrar indicador offline
  showOfflineIndicator();
});

// Función para mostrar mensaje de bienvenida
function showWelcomeMessage() {
  const message = document.createElement('div');
  message.className = 'fixed top-4 right-4 z-50 bg-green-500 text-white px-4 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300';
  message.innerHTML = `
    <div class="flex items-center">
      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
      </svg>
      <span>¡App instalada correctamente!</span>
    </div>
  `;
  
  document.body.appendChild(message);
  
  // Animar entrada
  setTimeout(() => {
    message.classList.remove('translate-x-full');
  }, 100);
  
  // Remover después de 3 segundos
  setTimeout(() => {
    message.classList.add('translate-x-full');
    setTimeout(() => message.remove(), 300);
  }, 3000);
}

// Función para mostrar indicador offline
function showOfflineIndicator() {
  // Evitar duplicados
  if (document.querySelector('.offline-indicator')) return;
  
  const indicator = document.createElement('div');
  indicator.className = 'offline-indicator fixed top-4 left-1/2 transform -translate-x-1/2 z-50 bg-red-500 text-white px-4 py-2 rounded-full shadow-lg text-sm font-medium';
  indicator.innerHTML = `
    <div class="flex items-center">
      <svg class="w-4 h-4 mr-2 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
      </svg>
      <span>Sin conexión - Modo offline</span>
    </div>
  `;
  
  document.body.appendChild(indicator);
}

// Mejoras para iOS PWA
if (window.navigator.standalone) {
  document.documentElement.classList.add('ios-standalone');
}

// Detectar modo PWA
if (window.matchMedia('(display-mode: standalone)').matches) {
  document.documentElement.classList.add('pwa-standalone');
}

// Prevenir zoom en inputs para iOS
if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
  document.addEventListener('gesturestart', function(e) {
    e.preventDefault();
  });
}
