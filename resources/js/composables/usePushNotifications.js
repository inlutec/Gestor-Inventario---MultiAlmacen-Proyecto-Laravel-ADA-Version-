import { ref } from 'vue';

export function usePushNotifications() {
  const isSupported = ref('Notification' in window && 'serviceWorker' in navigator);
  const permission = ref(Notification?.permission || 'default');
  const subscription = ref(null);

  async function requestPermission() {
    if (!isSupported.value) {
      throw new Error('Las notificaciones no están soportadas en este navegador');
    }

    const result = await Notification.requestPermission();
    permission.value = result;
    
    if (result === 'granted') {
      await subscribe();
    }
    
    return result;
  }

  async function subscribe() {
    try {
      const registration = await navigator.serviceWorker.ready;
      
      // Obtener clave pública VAPID
      const response = await window.axios.get('/notifications/vapid-public-key');
      const { publicKey } = response.data;
      
      // Suscribirse a push
      const sub = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(publicKey)
      });
      
      subscription.value = sub;
      
      // Enviar suscripción al servidor
      await window.axios.post('/notifications/subscribe', {
        endpoint: sub.endpoint,
        keys: {
          p256dh: arrayBufferToBase64(sub.getKey('p256dh')),
          auth: arrayBufferToBase64(sub.getKey('auth'))
        },
        device_type: getMobileOperatingSystem()
      });
      
      console.log('[Push] Suscripción exitosa');
      return true;
    } catch (err) {
      console.error('[Push] Error al suscribirse:', err);
      throw err;
    }
  }

  async function unsubscribe() {
    if (!subscription.value) return;
    
    try {
      await window.axios.post('/notifications/unsubscribe', {
        endpoint: subscription.value.endpoint
      });
      
      await subscription.value.unsubscribe();
      subscription.value = null;
      console.log('[Push] Desuscripción exitosa');
    } catch (err) {
      console.error('[Push] Error al desuscribirse:', err);
      throw err;
    }
  }

  async function testNotification() {
    try {
      await window.axios.post('/notifications/test');
      console.log('[Push] Notificación de prueba enviada');
    } catch (err) {
      console.error('[Push] Error al enviar notificación de prueba:', err);
      throw err;
    }
  }

  // Helpers
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

  function arrayBufferToBase64(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
      binary += String.fromCharCode(bytes[i]);
    }
    return window.btoa(binary);
  }

  function getMobileOperatingSystem() {
    const userAgent = navigator.userAgent || navigator.vendor || window.opera;

    if (/android/i.test(userAgent)) {
      return 'android';
    }

    if (/iPad|iPhone|iPod/.test(userAgent) && !window.MSStream) {
      return 'ios';
    }

    return 'web';
  }

  return {
    isSupported,
    permission,
    subscription,
    requestPermission,
    subscribe,
    unsubscribe,
    testNotification
  };
}
