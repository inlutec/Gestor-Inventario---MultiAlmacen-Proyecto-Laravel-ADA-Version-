<template>
  <Teleport to="body">
    <Transition name="slide-up">
      <div
        v-if="showBanner && canInstall"
        class="pwa-install-banner"
        role="dialog"
        aria-labelledby="pwa-title"
        aria-describedby="pwa-description"
      >
        <div class="max-w-screen-lg mx-auto">
          <div class="flex items-start gap-4">
            <!-- Icono de la app -->
            <div class="flex-shrink-0">
              <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
              </div>
            </div>

            <!-- Contenido -->
            <div class="flex-1 min-w-0">
              <h3 id="pwa-title" class="text-base font-semibold text-white mb-1">
                Instalar aplicación
              </h3>
              <p id="pwa-description" class="text-sm text-white/90 mb-3">
                Accede más rápido y recibe notificaciones. Funciona sin conexión.
              </p>

              <!-- Botones -->
              <div class="flex gap-2">
                <button
                  @click="handleInstall"
                  class="px-4 py-2 bg-white text-emerald-700 rounded-lg font-medium text-sm hover:bg-white/90 transition-colors focus:outline-none focus:ring-2 focus:ring-white/50"
                >
                  Instalar ahora
                </button>
                <button
                  @click="handleDismiss"
                  class="px-4 py-2 bg-white/20 text-white rounded-lg font-medium text-sm hover:bg-white/30 transition-colors backdrop-blur-sm focus:outline-none focus:ring-2 focus:ring-white/50"
                >
                  Ahora no
                </button>
              </div>
            </div>

            <!-- Botón cerrar -->
            <button
              @click="handleClose"
              class="flex-shrink-0 text-white/80 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50 rounded-lg p-1"
              aria-label="Cerrar"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Indicador de estado online/offline -->
    <Transition name="fade">
      <div
        v-if="showOnlineStatus && !isOnline"
        class="online-indicator offline"
        role="status"
        aria-live="polite"
      >
        <div class="flex items-center gap-1.5">
          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <span>Sin conexión</span>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { usePWA } from '../composables/usePWA';

const { canInstall, isOnline, promptInstall } = usePWA();

const showBanner = ref(false);
const showOnlineStatus = ref(false);
const dismissed = ref(false);

// Verificar si el usuario ya descartó el banner previamente
const DISMISS_KEY = 'pwa-install-dismissed';
const DISMISS_DURATION = 7 * 24 * 60 * 60 * 1000; // 7 días

onMounted(() => {
  const dismissedData = localStorage.getItem(DISMISS_KEY);
  
  if (dismissedData) {
    const { timestamp } = JSON.parse(dismissedData);
    const now = Date.now();
    
    // Si han pasado más de 7 días, mostrar de nuevo
    if (now - timestamp < DISMISS_DURATION) {
      dismissed.value = true;
    } else {
      localStorage.removeItem(DISMISS_KEY);
    }
  }

  // Mostrar banner después de 3 segundos si es instalable
  setTimeout(() => {
    if (canInstall.value && !dismissed.value) {
      showBanner.value = true;
    }
  }, 3000);
});

// Mostrar indicador de offline inmediatamente cuando se pierde conexión
watch(isOnline, (newValue) => {
  if (!newValue) {
    showOnlineStatus.value = true;
  } else {
    // Ocultar indicador después de 2 segundos cuando se recupera conexión
    setTimeout(() => {
      showOnlineStatus.value = false;
    }, 2000);
  }
});

const handleInstall = async () => {
  const installed = await promptInstall();
  
  if (installed) {
    showBanner.value = false;
    // Limpiar el estado de descartado
    localStorage.removeItem(DISMISS_KEY);
  }
};

const handleDismiss = () => {
  showBanner.value = false;
  dismissed.value = true;
  
  // Guardar que el usuario descartó el banner
  localStorage.setItem(DISMISS_KEY, JSON.stringify({
    timestamp: Date.now(),
  }));
};

const handleClose = () => {
  showBanner.value = false;
};
</script>

<style scoped>
.slide-up-enter-active {
  animation: slideUp 0.3s ease-out;
}

.slide-up-leave-active {
  animation: slideDown 0.3s ease-in;
}

@keyframes slideUp {
  from {
    transform: translateY(100%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

@keyframes slideDown {
  from {
    transform: translateY(0);
    opacity: 1;
  }
  to {
    transform: translateY(100%);
    opacity: 0;
  }
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .pwa-install-banner {
    padding: 1rem;
  }

  .pwa-install-banner .flex {
    flex-direction: column;
    align-items: stretch;
  }

  .pwa-install-banner .flex.gap-2 {
    flex-direction: row;
  }
}
</style>
