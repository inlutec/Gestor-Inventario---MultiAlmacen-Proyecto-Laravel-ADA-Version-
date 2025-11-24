<template>
  <div class="min-h-screen flex bg-[rgb(var(--bg))] text-[rgb(var(--text))]">
    <!-- Sidebar mejorado con soporte PWA -->
    <transition name="slide">
      <aside
        v-show="sidebarOpen || isDesktop"
        class="w-72 bg-gradient-to-b from-junta-green-700 to-junta-green-900 dark:from-gray-900 dark:to-gray-950 fixed h-full overflow-y-auto z-20 shadow-2xl"
        :class="{
          'hidden md:block': !sidebarOpen,
          'pwa-sidebar': isPWA
        }"
        :style="{
          paddingTop: isPWA ? 'env(safe-area-inset-top, 0)' : '0',
          paddingBottom: isPWA ? 'env(safe-area-inset-bottom, 0)' : '0'
        }"
      >
      <!-- Header del sidebar con logos -->
      <div class="p-6 border-b border-white/10">
        <div class="flex flex-col items-center text-center space-y-3">
          <img src="/gestionmaterial/images/junta-logo.png" alt="Junta de Andalucía"
               class="h-12 w-auto object-contain drop-shadow-lg brightness-0 invert"
               onerror="this.style.display='none'"/>
          <div class="text-white">
            <h1 class="text-base font-bold tracking-wide">Pequeño Material</h1>
            <p class="text-xs text-white/70 mt-1">Junta de Andalucía</p>
          </div>
        </div>
      </div>

      <!-- Navegación simplificada con las 4 secciones -->
      <nav class="p-4 space-y-1.5">
        <router-link
          v-for="item in menuItems"
          :key="item.name"
          :to="item.to"
          v-slot="{ isActive }"
          class="block"
        >
          <div
            :class="[
              'flex items-center px-4 py-3 rounded-xl transition-all duration-200 group cursor-pointer',
              isActive 
                ? 'bg-white/20 text-white shadow-lg shadow-black/20 font-semibold backdrop-blur' 
                : 'text-white/80 hover:bg-white/10 hover:text-white hover:pl-5'
            ]"
          >
            <component :is="item.icon" :class="[
              'mr-4 h-6 w-6 flex-shrink-0 transition-all duration-200',
              isActive ? 'scale-110 text-white drop-shadow-sm' : 'text-white/90 group-hover:scale-110 group-hover:text-white'
            ]" />
            <span class="text-sm">{{ item.name }}</span>
            <svg v-if="isActive" class="ml-auto h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
            </svg>
          </div>
        </router-link>
      </nav>

      <!-- Footer del sidebar con logo ADA -->
      <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-white/10">
        <div class="flex items-center justify-center gap-2 text-xs text-white/60">
          <img src="/gestionmaterial/images/ada-logo.png" alt="ADA"
               class="h-8 w-auto object-contain brightness-0 invert opacity-60"
               onerror="this.style.display='none'"/>
        </div>
      </div>
      </aside>
    </transition>

    <!-- Overlay móvil -->
    <transition name="fade">
      <div v-if="sidebarOpen && !isDesktop" class="fixed inset-0 z-10 bg-black/40" @click="sidebarOpen = false"></div>
    </transition>

    <!-- Main content -->
    <div class="flex-1" :class="isDesktop ? 'ml-72' : ''">
      <!-- Top bar mejorada con soporte PWA -->
      <header
        class="sticky top-0 z-10 bg-white/90 dark:bg-gray-900/90 backdrop-blur-md shadow-sm border-b border-gray-200 dark:border-gray-800"
        :style="{
          paddingTop: isPWA ? 'env(safe-area-inset-top, 0)' : '0'
        }"
      >
        <div class="px-4 md:px-6 py-3 flex items-center justify-between">
          <div class="flex items-center gap-4">
            <!-- Toggle sidebar (mobile) -->
            <button
              class="md:hidden p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors"
              @click="sidebarOpen = !sidebarOpen"
              aria-label="Abrir menú"
              :style="{
                minHeight: '44px',
                minWidth: '44px'
              }"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
              </svg>
            </button>
            <div>
              <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ pageTitle }}</h2>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ getBreadcrumb() }}</p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <!-- Campana de notificaciones -->
            <div class="relative">
              <button
                @click="toggleNotifications"
                class="p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition relative"
                title="Notificaciones"
                :style="{
                  minHeight: '44px',
                  minWidth: '44px'
                }"
              >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <span v-if="notificacionesNoLeidas > 0" class="absolute top-1 right-1 h-5 w-5 bg-red-500 rounded-full text-white text-xs flex items-center justify-center font-bold">
                  {{ notificacionesNoLeidas > 9 ? '9+' : notificacionesNoLeidas }}
                </span>
              </button>

              <!-- Dropdown de notificaciones -->
              <transition name="fade">
                <div v-if="mostrarNotificaciones" 
                     class="absolute right-0 mt-2 w-96 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 max-h-[500px] overflow-y-auto">
                  <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Notificaciones</h3>
                    <button 
                      v-if="notificaciones.length > 0"
                      @click="marcarTodasComoLeidas"
                      class="text-xs text-junta-green-600 hover:text-junta-green-700">
                      Marcar todas como leídas
                    </button>
                  </div>
                  
                  <div v-if="notificaciones.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <svg class="h-12 w-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <p>No hay notificaciones</p>
                  </div>
                  
                  <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="notif in notificaciones" :key="notif.id"
                         @click="marcarComoLeida(notif)"
                         class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition"
                         :class="{'bg-blue-50 dark:bg-blue-900/20': !notif.leido}">
                      <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                          <div class="h-10 w-10 rounded-full flex items-center justify-center"
                               :class="getTipoClases(notif.tipo)">
                            {{ getTipoIcono(notif.tipo) }}
                          </div>
                        </div>
                        <div class="flex-1 min-w-0">
                          <p class="font-medium text-gray-900 dark:text-white text-sm">{{ notif.titulo }}</p>
                          <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ notif.mensaje }}</p>
                          <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ formatearFecha(notif.created_at) }}</p>
                        </div>
                        <div v-if="!notif.leido" class="flex-shrink-0">
                          <div class="h-2 w-2 bg-blue-500 rounded-full"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </transition>
            </div>

            <!-- Toggle dark/light -->
            <button
              @click="toggleTheme"
              class="p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition"
              :title="`Cambiar a ${theme.current==='dark'?'claro':'oscuro'}`"
              :style="{
                minHeight: '44px',
                minWidth: '44px'
              }"
            >
              <svg v-if="theme.current==='dark'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                <path d="M21.752 15.002A9.718 9.718 0 0112 21.75a9.75 9.75 0 01-9.75-9.75c0-4.28 2.72-7.9 6.5-9.2a.75.75 0 01.97.9A8.25 8.25 0 0012 20.25c3.9 0 7.18-2.69 8.18-6.33a.75.75 0 011.57 1.082z" />
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                <path d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM6.364 4.636a.75.75 0 011.06 0l1.59 1.59a.75.75 0 11-1.06 1.06l-1.59-1.59a.75.75 0 010-1.06zM2.25 12a.75.75 0 01.75-.75h2.25a.75.75 0 010 1.5H3a.75.75 0 01-.75-.75zm15.046-5.774a.75.75 0 011.06 1.06l-1.59 1.59a.75.75 0 01-1.06-1.06l1.59-1.59zM12 18.75a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-1.5a.75.75 0 01.75-.75zm6.89-2.64a.75.75 0 10-1.06 1.06l1.59 1.59a.75.75 0 101.06-1.06l-1.59-1.59zM18.75 12a.75.75 0 01.75-.75H21a.75.75 0 010 1.5h-1.5a.75.75 0 01-.75-.75zM4.11 16.11a.75.75 0 011.06 0l1.59 1.59a.75.75 0 11-1.06 1.06l-1.59-1.59a.75.75 0 010-1.06z"/>
              </svg>
            </button>

            <!-- Divisor -->
            <div class="hidden md:block h-8 w-px bg-gray-200 dark:bg-gray-700"></div>

            <!-- Usuario -->
            <div class="flex items-center gap-3">
              <div class="hidden md:block text-right">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ authStore.user?.nombre }} {{ authStore.user?.apellido }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ authStore.user?.rol }}</p>
              </div>
              <div class="h-10 w-10 bg-gradient-to-br from-junta-green-500 to-ada-primary-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg ring-2 ring-white dark:ring-gray-900">
                {{ initials }}
              </div>
              <button
                @click="handleLogout"
                class="p-3 text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition"
                title="Cerrar sesión"
                :style="{
                  minHeight: '44px',
                  minWidth: '44px'
                }"
              >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Page content con mejoras PWA -->
      <main
        class="p-4 md:p-8 max-w-7xl mx-auto"
        :style="{
          paddingBottom: isPWA ? 'calc(2rem + env(safe-area-inset-bottom, 0))' : '2rem'
        }"
      >
        <transition name="page" mode="out-in">
          <router-view />
        </transition>
      </main>
      <!-- Footer institucional con soporte PWA -->
      <footer
        class="px-8 py-6 border-t border-[rgb(var(--border))] text-sm text-gray-600 dark:text-gray-400 flex items-center justify-between"
        :style="{
          paddingBottom: isPWA ? 'calc(1.5rem + env(safe-area-inset-bottom, 0))' : '1.5rem'
        }"
      >
        <div class="flex items-center gap-3">
          <img src="/gestionmaterial/images/junta-logo.png" alt="Junta de Andalucía" class="h-6 w-auto" onerror="this.style.display='none'"/>
          <span>Junta de Andalucía · Agencia Digital de Andalucía</span>
        </div>
        <div class="flex items-center gap-3">
          <img src="/gestionmaterial/images/ada-logo.png" alt="Agencia Digital de Andalucía" class="h-6 w-auto" onerror="this.style.display='none'"/>
          <span class="text-xs">© {{ new Date().getFullYear() }} · Pequeño Material</span>
        </div>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useThemeStore } from '../stores/theme';
import { useRouter, useRoute } from 'vue-router';
import { usePWA } from '../composables/usePWA';
import axios from 'axios';

const authStore = useAuthStore();
const theme = useThemeStore();
const router = useRouter();
const route = useRoute();
const { isPWA } = usePWA();

const toggleTheme = () => theme.toggle();

// Notificaciones
const notificaciones = ref([]);
const mostrarNotificaciones = ref(false);
const notificacionesNoLeidas = computed(() => 
  notificaciones.value.filter(n => !n.leido).length
);
let notificationInterval = null;

async function cargarNotificaciones() {
  try {
    const response = await axios.get('/notificaciones');
    notificaciones.value = response.data;
  } catch (error) {
    console.error('Error al cargar notificaciones:', error);
  }
}

function toggleNotifications() {
  mostrarNotificaciones.value = !mostrarNotificaciones.value;
}

async function marcarComoLeida(notif) {
  if (!notif.leido) {
    try {
      await axios.post(`/notificaciones/${notif.id}/marcar-leida`);
      notif.leido = true;
    } catch (error) {
      console.error('Error al marcar como leída:', error);
    }
  }
  
  // Navegar según el tipo
  if (notif.tipo === 'peticion_nueva' || notif.tipo === 'pedido_nuevo') {
    router.push({ name: 'Solicitudes' });
  }
  
  mostrarNotificaciones.value = false;
}

async function marcarTodasComoLeidas() {
  try {
    await axios.post('/notificaciones/marcar-todas-leidas');
    notificaciones.value.forEach(n => n.leido = true);
  } catch (error) {
    console.error('Error al marcar todas como leídas:', error);
  }
}

function getTipoIcono(tipo) {
  const iconos = {
    'peticion_nueva': '📋',
    'pedido_nuevo': '📦',
    'pedido_aprobado': '✅',
    'pedido_denegado': '❌',
    'movimiento': '🔄',
    'firma_solicitada': '✍️',
    'firma_completada': '✓',
    'info': 'ℹ️'
  };
  return iconos[tipo] || 'ℹ️';
}

function getTipoClases(tipo) {
  const clases = {
    'peticion_nueva': 'bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300',
    'pedido_nuevo': 'bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-300',
    'pedido_aprobado': 'bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-300',
    'pedido_denegado': 'bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-300',
    'movimiento': 'bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-300',
    'firma_solicitada': 'bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-300',
    'firma_completada': 'bg-teal-100 dark:bg-teal-900 text-teal-600 dark:text-teal-300',
    'info': 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
  };
  return clases[tipo] || clases.info;
}

function formatearFecha(fecha) {
  const ahora = new Date();
  const notifFecha = new Date(fecha);
  const diffMs = ahora - notifFecha;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHoras = Math.floor(diffMs / 3600000);
  
  if (diffMins < 1) return 'Ahora mismo';
  if (diffMins < 60) return `Hace ${diffMins} min`;
  if (diffHoras < 24) return `Hace ${diffHoras}h`;
  
  return notifFecha.toLocaleDateString('es-ES', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  });
}

// Auto-suscribir si ya tiene permisos
onMounted(async () => {
  const mq = window.matchMedia('(min-width: 768px)');
  const update = () => { isDesktop.value = mq.matches; if (mq.matches) sidebarOpen.value = false; };
  mq.addEventListener?.('change', update);
  update();
  
  // Cargar notificaciones
  await cargarNotificaciones();
  
  // Actualizar cada 30 segundos
  notificationInterval = setInterval(cargarNotificaciones, 30000);
});

onUnmounted(() => {
  if (notificationInterval) {
    clearInterval(notificationInterval);
  }
});

// Solo las 4 secciones de pequeño material
const menuItems = computed(() => {
  const items = [
    {
      name: 'Dashboard',
      to: { name: 'Dashboard' },
      icon: 'IconDashboard',
    },
    {
      name: 'Referencias',
      to: { name: 'Referencias' },
      icon: 'IconCatalog',
    },
    {
      name: 'Existencias',
      to: { name: 'Existencias' },
      icon: 'IconBox',
    },
    {
      name: 'Entradas/Salidas',
      to: { name: 'Movimientos' },
      icon: 'IconArrowsExchange',
    },
    {
      name: 'Histórico',
      to: { name: 'Historico' },
      icon: 'IconHistory',
    },
    {
      name: 'Peticiones',
      to: { name: 'Peticiones' },
      icon: 'IconFileText',
    },
    {
      name: 'Solicitudes de Reposición',
      to: { name: 'SolicitudesReposicion' },
      icon: 'IconBell',
    },
  ];
  
  // Añadir configuración solo para administradores
  if (authStore.user?.rol === 'admin') {
    items.push({
      name: 'Configuración',
      to: { name: 'Configuracion' },
      icon: 'IconSettings',
    });
  }
  
  return items;
});

const pageTitle = computed(() => {
  return route.name || 'Referencias';
});

const getBreadcrumb = () => {
  const breadcrumbs = {
    'Dashboard': 'Pequeño Material / Dashboard',
    'Referencias': 'Pequeño Material / Referencias',
    'Existencias': 'Pequeño Material / Existencias',
    'Movimientos': 'Pequeño Material / Entradas y Salidas',
    'Historico': 'Pequeño Material / Histórico',
    'Peticiones': 'Pequeño Material / Peticiones',
    'SolicitudesReposicion': 'Pequeño Material / Solicitudes de Reposición',
    'Configuracion': 'Pequeño Material / Configuración',
  };
  return breadcrumbs[route.name] || 'Pequeño Material';
};

const sidebarOpen = ref(false);
const isDesktop = ref(false);

const initials = computed(() => {
  const nombre = authStore.user?.nombre || '';
  const apellido = authStore.user?.apellido || '';
  return `${nombre.charAt(0)}${apellido.charAt(0)}`.toUpperCase();
});

const handleLogout = async () => {
  await authStore.logout();
  router.push({ name: 'Login' });
};

// Iconos SVG profesionales y grandes para el sidebar
const IconDashboard = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>`
};

const IconCatalog = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h3M6.75 3.5a.75.75 0 01.75-.75h6a.75.75 0 01.75.75v16.5a.75.75 0 01-.75.75h-6a.75.75 0 01-.75-.75V3.5zM8.25 9.75h3m-3 3h3m3-6h3a.75.75 0 01.75.75v16.5a.75.75 0 01-.75.75h-3m-6-3h3m-3 3h3" /></svg>`
};

const IconBox = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>`
};

const IconArrowsExchange = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>`
};

const IconHistory = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
};

const IconSettings = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.142 4.242a1.5 1.5 0 011.716 0c.376.295.888.416 1.384.342l.134-.02a1.5 1.5 0 001.299-1.28l.019-.137a1.5 1.5 0 011.119-1.259l.137-.019c.564-.078 1.04-.425 1.28-1.119l.02-.134c.074-.496.323-.947.694-1.268a1.5 1.5 0 00.124-2.008l-.095-.108a1.5 1.5 0 01-.124-1.69l.095-.108a1.5 1.5 0 00-.124-2.008 1.5 1.5 0 01-.694-1.268l-.02-.134A1.5 1.5 0 0013.99 1.2l-.137-.019a1.5 1.5 0 01-1.119-1.259l-.019-.137a1.5 1.5 0 00-1.299-1.28l-.134-.02a1.5 1.5 0 01-1.384.342 1.5 1.5 0 00-1.716 0c-.376.295-.888.416-1.384.342l-.134-.02a1.5 1.5 0 00-1.299 1.28l-.019.137a1.5 1.5 0 01-1.119 1.259l-.137.019c-.564.078-1.04.425-1.28 1.119l-.02.134a1.5 1.5 0 01-.694 1.268 1.5 1.5 0 00-.124 2.008l.095.108a1.5 1.5 0 01.124 1.69l-.095.108a1.5 1.5 0 00.124 2.008c.371.321.62.772.694 1.268l.02.134a1.5 1.5 0 001.299 1.28l.134.02a1.5 1.5 0 011.384-.342zM12 12a3 3 0 100-6 3 3 0 000 6z" /></svg>`
};

const IconFileText = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>`
};

const IconBell = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>`
};
</script>

<style scoped>
.slide-enter-active, .slide-leave-active {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-enter-from, .slide-leave-to {
  transform: translateX(-100%);
}
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
.page-enter-active, .page-leave-active {
  transition: opacity 0.2s ease;
}
.page-enter-from, .page-leave-to {
  opacity: 0;
}

/* Mejoras para PWA standalone */
.pwa-sidebar {
  padding-top: env(safe-area-inset-top, 0);
  padding-bottom: env(safe-area-inset-bottom, 0);
}

/* Responsive mejorado */
@media (max-width: 768px) {
  .pwa-sidebar {
    width: 100%;
    max-width: 280px;
  }
}

/* Optimizaciones para tablets */
@media (min-width: 769px) and (max-width: 1024px) {
  aside {
    width: 64px;
  }
  
  aside .text-sm {
    display: none;
  }
  
  aside .flex.gap-3 {
    justify-content: center;
  }
}

/* Soporte para modo oscuro en PWA */
@media (prefers-color-scheme: dark) and (display-mode: standalone) {
  header {
    background-color: rgba(17, 24, 39, 0.95);
    backdrop-filter: blur(12px);
  }
}

/* Mejoras para notch en iPhone */
@supports (padding: env(safe-area-inset-top)) {
  header {
    padding-top: env(safe-area-inset-top, 0);
  }
}

/* Animaciones suaves para móviles */
@media (prefers-reduced-motion: no-preference) {
  .slide-enter-active, .slide-leave-active {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
}
</style>
