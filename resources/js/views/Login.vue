<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-junta-green-600 via-junta-green-700 to-junta-green-900 py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Elementos decorativos de fondo -->
    <div class="absolute top-0 left-0 w-full h-full">
      <div class="absolute top-20 left-10 w-72 h-72 bg-junta-yellow/10 rounded-full blur-3xl"></div>
      <div class="absolute bottom-20 right-10 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
      <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-white/3 rounded-full blur-3xl"></div>
    </div>
    
    <div class="max-w-md w-full space-y-8 relative z-10">
      <!-- Header con ambos logos corporativos -->
      <div class="text-center">
        <!-- Logos corporativos -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-6 sm:gap-8 mb-8">
          <!-- Logo Junta de Andalucía -->
          <div class="flex flex-col items-center">
            <div class="bg-white rounded-xl p-4 shadow-2xl ring-2 ring-white/30 transform hover:scale-105 transition-transform duration-200">
              <img 
                src="/gestionmaterial/images/junta-logo.png" 
                alt="Junta de Andalucía" 
                class="h-16 sm:h-20 w-auto object-contain drop-shadow"
                onerror="this.style.display='none'"
              />
            </div>
            <p class="mt-3 text-sm font-semibold text-white/90">Junta de Andalucía</p>
          </div>
          
          <!-- Separador vertical (solo en desktop) -->
          <div class="hidden sm:block w-px h-20 bg-white/30"></div>
          
          <!-- Logo ADA -->
          <div class="flex flex-col items-center">
            <div class="bg-white rounded-xl p-4 shadow-2xl ring-2 ring-white/30 transform hover:scale-105 transition-transform duration-200">
              <img 
                src="/gestionmaterial/images/ada-logo.png" 
                alt="Agencia Digital de Andalucía" 
                class="h-14 sm:h-18 w-auto object-contain drop-shadow"
                onerror="this.style.display='none'"
              />
            </div>
            <p class="mt-3 text-sm font-semibold text-white/90">Agencia Digital de Andalucía</p>
          </div>
        </div>
        
        <!-- Título y subtítulo -->
        <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-2">
          Gestor de Inventario
        </h1>
        <p class="text-lg text-white/80 font-medium">
          Sistema de Gestión de Material
        </p>
      </div>

      <!-- Formulario de login -->
      <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl p-8 sm:p-10 border border-white/20">
        <form class="space-y-6" @submit.prevent="handleLogin">
          <!-- Mensaje de error -->
          <div v-if="error" class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
            <div class="flex items-center">
              <svg class="h-5 w-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
              </svg>
              <p class="text-sm font-medium text-red-800">{{ error }}</p>
            </div>
          </div>

          <!-- Campo Email -->
          <div>
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
              Correo electrónico
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                </svg>
              </div>
              <input
                id="email"
                v-model="credentials.email"
                type="email"
                required
                class="input pl-10 w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 transition"
                placeholder="usuario@juntadeandalucia.es"
              />
            </div>
          </div>

          <!-- Campo Contraseña -->
          <div>
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
              Contraseña
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
              </div>
              <input
                id="password"
                v-model="credentials.password"
                type="password"
                required
                class="input pl-10 w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 transition"
                placeholder="••••••••"
              />
            </div>
          </div>

          <!-- Botón de login -->
          <button
            type="submit"
            :disabled="loading"
            class="w-full bg-gradient-to-r from-junta-green-600 to-junta-green-700 hover:from-junta-green-700 hover:to-junta-green-800 text-white font-bold py-3 px-4 rounded-lg shadow-lg transform transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none flex items-center justify-center"
          >
            <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span v-if="!loading" class="flex items-center">
              <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
              </svg>
              Iniciar sesión
            </span>
            <span v-else>Iniciando sesión...</span>
          </button>
        </form>

        <!-- Footer del formulario -->
        <div class="mt-8 pt-6 border-t border-gray-200 text-center">
          <p class="text-xs text-gray-500 font-medium">Sistema de gestión de inventario v3.0.0</p>
          <p class="mt-2 text-xs text-gray-400">© {{ new Date().getFullYear() }} Junta de Andalucía - Agencia Digital de Andalucía</p>
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
