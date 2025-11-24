import { ref, computed, onMounted } from 'vue';

/**
 * Composable para gestionar funcionalidades PWA
 * - Detección de instalación
 * - Prompt de instalación
 * - Soporte para notificaciones
 * - Estado online/offline
 */
export function usePWA() {
  const deferredPrompt = ref(null);
  const isInstallable = ref(false);
  const isInstalled = ref(false);
  const isOnline = ref(navigator.onLine);
  const registration = ref(null);
  const notificationPermission = ref(Notification.permission);

  // Verificar si la app ya está instalada
  const checkIfInstalled = () => {
    // Detectar si se ejecuta en modo standalone (instalada)
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
      || window.navigator.standalone
      || document.referrer.includes('android-app://');
    
    isInstalled.value = isStandalone;
    return isStandalone;
  };

  // Capturar el evento beforeinstallprompt
  const handleBeforeInstallPrompt = (e) => {
    e.preventDefault();
    deferredPrompt.value = e;
    isInstallable.value = true;
    console.log('✨ La aplicación se puede instalar');
  };

  // Mostrar el prompt de instalación
  const promptInstall = async () => {
    if (!deferredPrompt.value) {
      console.warn('El prompt de instalación no está disponible');
      return false;
    }

    deferredPrompt.value.prompt();
    const { outcome } = await deferredPrompt.value.userChoice;
    
    console.log(`Usuario ${outcome === 'accepted' ? 'aceptó' : 'rechazó'} la instalación`);
    
    if (outcome === 'accepted') {
      isInstallable.value = false;
      isInstalled.value = true;
    }
    
    deferredPrompt.value = null;
    return outcome === 'accepted';
  };

  // Manejar el evento de instalación completada
  const handleAppInstalled = () => {
    console.log('✅ PWA instalada exitosamente');
    isInstallable.value = false;
    isInstalled.value = true;
    deferredPrompt.value = null;
  };

  // Solicitar permiso para notificaciones
  const requestNotificationPermission = async () => {
    if (!('Notification' in window)) {
      console.warn('Este navegador no soporta notificaciones');
      return 'denied';
    }

    if (Notification.permission === 'granted') {
      notificationPermission.value = 'granted';
      return 'granted';
    }

    if (Notification.permission !== 'denied') {
      const permission = await Notification.requestPermission();
      notificationPermission.value = permission;
      return permission;
    }

    return Notification.permission;
  };

  // Enviar una notificación de prueba
  const sendNotification = (title, options = {}) => {
    if (Notification.permission !== 'granted') {
      console.warn('No hay permiso para enviar notificaciones');
      return null;
    }

    const defaultOptions = {
      icon: '/images/icons/icon-192x192.png',
      badge: '/images/icons/icon-96x96.png',
      vibrate: [200, 100, 200],
      tag: 'inventario-junta',
      requireInteraction: false,
      ...options,
    };

    if (registration.value && registration.value.showNotification) {
      return registration.value.showNotification(title, defaultOptions);
    } else {
      return new Notification(title, defaultOptions);
    }
  };

  // Suscribirse a notificaciones push
  const subscribeToPush = async () => {
    if (!registration.value) {
      console.warn('Service Worker no registrado');
      return null;
    }

    try {
      const permission = await requestNotificationPermission();
      if (permission !== 'granted') {
        throw new Error('Permiso de notificaciones denegado');
      }

      // Aquí deberías usar tu clave VAPID pública
      // const vapidPublicKey = 'TU_CLAVE_VAPID_PUBLICA';
      // const subscription = await registration.value.pushManager.subscribe({
      //   userVisibleOnly: true,
      //   applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
      // });

      console.log('✅ Suscripción a push notifications lista');
      // return subscription;
      return null;
    } catch (error) {
      console.error('❌ Error al suscribirse a push:', error);
      return null;
    }
  };

  // Manejar cambios en el estado de conexión
  const handleOnline = () => {
    isOnline.value = true;
    console.log('🌐 Conexión restaurada');
  };

  const handleOffline = () => {
    isOnline.value = false;
    console.log('📡 Sin conexión - modo offline');
  };

  // Obtener el Service Worker registration
  const getServiceWorkerRegistration = async () => {
    if ('serviceWorker' in navigator) {
      try {
        registration.value = await navigator.serviceWorker.ready;
        console.log('✅ Service Worker listo');
        return registration.value;
      } catch (error) {
        console.error('❌ Error al obtener Service Worker:', error);
        return null;
      }
    }
    return null;
  };

  // Actualizar la aplicación
  const updateApp = async () => {
    if (!registration.value) {
      console.warn('Service Worker no disponible');
      return false;
    }

    try {
      await registration.value.update();
      console.log('🔄 Buscando actualizaciones...');
      return true;
    } catch (error) {
      console.error('❌ Error al actualizar:', error);
      return false;
    }
  };

  // Limpiar caché
  const clearCache = async () => {
    if ('caches' in window) {
      const cacheNames = await caches.keys();
      await Promise.all(cacheNames.map(name => caches.delete(name)));
      console.log('🗑️ Caché limpiada');
      return true;
    }
    return false;
  };

  // Estado computed
  const canInstall = computed(() => isInstallable.value && !isInstalled.value);
  const canNotify = computed(() => notificationPermission.value === 'granted');
  const isPWA = computed(() => isInstalled.value);

  // Inicialización
  onMounted(async () => {
    checkIfInstalled();
    await getServiceWorkerRegistration();

    // Event listeners
    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
    window.addEventListener('appinstalled', handleAppInstalled);
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    // Cleanup
    return () => {
      window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
      window.removeEventListener('appinstalled', handleAppInstalled);
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  });

  return {
    // Estado
    isInstallable,
    isInstalled,
    isOnline,
    canInstall,
    canNotify,
    isPWA,
    notificationPermission,
    registration,

    // Métodos
    promptInstall,
    requestNotificationPermission,
    sendNotification,
    subscribeToPush,
    updateApp,
    clearCache,
    checkIfInstalled,
  };
}

// Utilidad para convertir clave VAPID
function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding)
    .replace(/\-/g, '+')
    .replace(/_/g, '/');

  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}
