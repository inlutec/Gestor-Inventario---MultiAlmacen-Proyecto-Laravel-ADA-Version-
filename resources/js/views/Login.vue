<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-junta-green-600 via-junta-green-700 to-junta-green-900 py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Elementos decorativos de fondo -->
    <div class="absolute top-0 left-0 w-full h-full">
      <div class="absolute top-20 left-10 w-72 h-72 bg-junta-yellow/10 rounded-full blur-3xl"></div>
      <div class="absolute bottom-20 right-10 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
    </div>
    
    <div class="max-w-md w-full space-y-8">
      <!-- Logo y header -->
      <div class="text-center relative z-10">
        <div class="mx-auto h-24 w-24 bg-white rounded-2xl flex items-center justify-center shadow-2xl ring-4 ring-white/20 transform hover:scale-105 transition-transform duration-200 overflow-hidden">
          <img src="/images/junta-logo.png" alt="Junta de Andalucía" class="h-16 w-auto object-contain drop-shadow"/>
        </div>
        <h2 class="mt-8 text-4xl font-extrabold text-white tracking-tight">
          Gestor de Inventario
        </h2>
        <p class="mt-3 text-base text-white/80 font-medium">
          Junta de Andalucía
        </p>
      </div>

      <!-- Formulario -->
      <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl p-10 relative z-10">
        <form class="space-y-6" @submit.prevent="handleLogin">
          <div v-if="error" class="alert alert-error">
            {{ error }}
          </div>

          <div>
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
              Correo electrónico
            </label>
            <input
              id="email"
              v-model="credentials.email"
              type="email"
              required
              class="input"
              placeholder="usuario@juntadeandalucia.es"
            />
          </div>

          <div>
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
              Contraseña
            </label>
            <input
              id="password"
              v-model="credentials.password"
              type="password"
              required
              class="input"
              placeholder="••••••••"
            />
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="w-full btn btn-primary btn-lg"
          >
            <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ loading ? 'Iniciando sesión...' : 'Iniciar sesión' }}
          </button>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-100 text-center">
          <p class="text-xs text-gray-500 font-medium">Sistema de gestión de inventario v3.0.0</p>
          <p class="mt-2 text-xs text-gray-400">© {{ new Date().getFullYear() }} Junta de Andalucía</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const credentials = ref({
  email: '',
  password: '',
});

const loading = ref(false);
const error = ref('');

const handleLogin = async () => {
  loading.value = true;
  error.value = '';

  try {
    const result = await authStore.login(credentials.value);

    if (result.success) {
      router.push({ name: 'Referencias' });
    } else {
      error.value = result.message || 'Error al iniciar sesión';
      loading.value = false;
    }
  } catch (err) {
    console.error('Error inesperado en login:', err);
    error.value = 'Error inesperado al iniciar sesión';
    loading.value = false;
  }
};
</script>
