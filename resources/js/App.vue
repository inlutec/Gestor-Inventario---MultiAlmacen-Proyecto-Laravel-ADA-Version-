<template>
  <div id="app" class="min-h-screen">
    <router-view />
    
    <!-- PWA Install Prompt -->
    <PWAInstallPrompt />
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from './stores/auth';
import { useRouter, useRoute } from 'vue-router';
import PWAInstallPrompt from './components/PWAInstallPrompt.vue';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

onMounted(async () => {
  // Solo verificar autenticación si NO es una ruta pública
  if (authStore.isAuthenticated && !route.meta.public) {
    await authStore.checkAuth();
  }
});
</script>
