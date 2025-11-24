<template>
  <div class="space-y-6">
    <div class="card">
      <h2 class="text-xl font-semibold mb-4">⚙️ Configuración PWA</h2>
      
      <!-- Estado de Instalación -->
      <div class="mb-6 p-4 rounded-lg" :class="isPWA ? 'bg-green-50 dark:bg-green-900/20' : 'bg-blue-50 dark:bg-blue-900/20'">
        <div class="flex items-center gap-3">
          <svg v-if="isPWA" class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
          <svg v-else class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
          </svg>
          <div>
            <p class="font-medium" :class="isPWA ? 'text-green-900 dark:text-green-100' : 'text-blue-900 dark:text-blue-100'">
              {{ isPWA ? 'Aplicación instalada' : 'Aplicación web' }}
            </p>
            <p class="text-sm" :class="isPWA ? 'text-green-700 dark:text-green-300' : 'text-blue-700 dark:text-blue-300'">
              {{ isPWA ? 'Estás usando la versión instalada' : 'Puedes instalar esta app para acceso rápido' }}
            </p>
          </div>
        </div>
        
        <button
          v-if="canInstall"
          @click="handleInstall"
          class="mt-3 btn btn-primary w-full sm:w-auto"
        >
          📱 Instalar aplicación
        </button>
      </div>

      <!-- Estado de Conexión -->
      <div class="mb-6 p-4 rounded-lg border" :class="isOnline ? 'border-green-200 bg-green-50 dark:bg-green-900/20' : 'border-red-200 bg-red-50 dark:bg-red-900/20'">
        <div class="flex items-center gap-2">
          <div class="w-3 h-3 rounded-full" :class="isOnline ? 'bg-green-500' : 'bg-red-500 animate-pulse'"></div>
          <span class="font-medium" :class="isOnline ? 'text-green-900 dark:text-green-100' : 'text-red-900 dark:text-red-100'">
            {{ isOnline ? '🌐 Conectado' : '📡 Sin conexión' }}
          </span>
        </div>
      </div>

      <!-- Notificaciones -->
      <div class="space-y-4">
        <h3 class="text-lg font-semibold">🔔 Notificaciones</h3>
        
        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <p class="font-medium text-gray-900 dark:text-gray-100">
                Notificaciones push
              </p>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Recibe alertas sobre stock bajo, pedidos y actualizaciones
              </p>
            </div>
            
            <div class="ml-4">
              <span
                v-if="notificationPermission === 'granted'"
                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
              >
                ✓ Activadas
              </span>
              <span
                v-else-if="notificationPermission === 'denied'"
                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800"
              >
                ✗ Bloqueadas
              </span>
              <button
                v-else
                @click="handleEnableNotifications"
                class="btn btn-sm btn-primary"
              >
                Activar
              </button>
            </div>
          </div>

          <!-- Botón de prueba -->
          <div v-if="canNotify" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <button
              @click="handleTestNotification"
              :disabled="sendingTest"
              class="btn btn-sm btn-secondary"
            >
              {{ sendingTest ? 'Enviando...' : '📬 Enviar notificación de prueba' }}
            </button>
          </div>

          <!-- Ayuda si están bloqueadas -->
          <div v-if="notificationPermission === 'denied'" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Las notificaciones están bloqueadas. Para habilitarlas:
            </p>
            <ul class="mt-2 text-sm text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
              <li>Click en el icono 🔒 en la barra de direcciones</li>
              <li>Busca "Notificaciones" y cámbialo a "Permitir"</li>
              <li>Recarga la página</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Información de Service Worker -->
      <div class="mt-6 space-y-4">
        <h3 class="text-lg font-semibold">🔧 Información técnica</h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">Service Worker</p>
            <p class="text-sm font-medium mt-1">
              {{ registration ? '✅ Activo' : '❌ Inactivo' }}
            </p>
          </div>
          
          <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">Modo de visualización</p>
            <p class="text-sm font-medium mt-1">
              {{ isPWA ? '🖥️ Standalone' : '🌐 Navegador' }}
            </p>
          </div>
        </div>

        <!-- Botones de mantenimiento -->
        <div class="flex flex-wrap gap-2 mt-4">
          <button
            @click="handleUpdate"
            :disabled="updating"
            class="btn btn-sm btn-secondary"
          >
            {{ updating ? 'Actualizando...' : '🔄 Buscar actualizaciones' }}
          </button>
          
          <button
            @click="handleClearCache"
            :disabled="clearing"
            class="btn btn-sm btn-secondary"
          >
            {{ clearing ? 'Limpiando...' : '🗑️ Limpiar caché' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { usePWA } from '../composables/usePWA';

const {
  canInstall,
  isPWA,
  isOnline,
  canNotify,
  notificationPermission,
  registration,
  promptInstall,
  requestNotificationPermission,
  sendNotification,
  updateApp,
  clearCache,
} = usePWA();

const sendingTest = ref(false);
const updating = ref(false);
const clearing = ref(false);

const handleInstall = async () => {
  const installed = await promptInstall();
  if (installed) {
    alert('¡Aplicación instalada correctamente! 🎉');
  }
};

const handleEnableNotifications = async () => {
  const permission = await requestNotificationPermission();
  
  if (permission === 'granted') {
    alert('✅ Notificaciones activadas correctamente');
  } else if (permission === 'denied') {
    alert('❌ Has bloqueado las notificaciones. Revisa los permisos del navegador.');
  }
};

const handleTestNotification = async () => {
  sendingTest.value = true;
  
  try {
    await sendNotification('🎉 ¡Notificación de prueba!', {
      body: 'Las notificaciones funcionan correctamente en tu dispositivo.',
      requireInteraction: false,
    });
  } catch (error) {
    console.error('Error al enviar notificación:', error);
    alert('Error al enviar notificación. Revisa la consola.');
  } finally {
    sendingTest.value = false;
  }
};

const handleUpdate = async () => {
  updating.value = true;
  
  try {
    await updateApp();
    alert('✅ Se ha buscado actualizaciones. Si hay alguna, se instalará en segundo plano.');
  } catch (error) {
    console.error('Error al actualizar:', error);
    alert('Error al buscar actualizaciones.');
  } finally {
    updating.value = false;
  }
};

const handleClearCache = async () => {
  if (!confirm('¿Estás seguro de que quieres limpiar la caché? Tendrás que descargar todos los recursos de nuevo.')) {
    return;
  }
  
  clearing.value = true;
  
  try {
    await clearCache();
    alert('✅ Caché limpiada. Recarga la página para descargar recursos frescos.');
  } catch (error) {
    console.error('Error al limpiar caché:', error);
    alert('Error al limpiar caché.');
  } finally {
    clearing.value = false;
  }
};
</script>
