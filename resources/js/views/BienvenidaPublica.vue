<template>
  <div class="min-h-screen bg-gradient-to-br from-junta-green-50 via-white to-junta-green-100">
    <!-- Header Profesional con Logos -->
    <header class="bg-white shadow-lg">
      <div class="container mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div class="flex items-center gap-4 flex-1">
            <img src="/gestionmaterial/images/junta-logo.png" alt="Junta de Andalucía"
                 class="h-16 sm:h-20 lg:h-24 object-contain"
                 onerror="this.style.display='none'"/>
            <div class="border-l-2 border-junta-green-600 pl-4">
              <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 leading-tight">
                Gestión de Material
              </h1>
              <p class="text-sm sm:text-base text-gray-600 mt-1">Junta de Andalucía</p>
            </div>
          </div>
          <img src="/gestionmaterial/images/ada-logo.png" alt="Agencia Digital de Andalucía"
               class="h-12 sm:h-14 lg:h-16 object-contain"
               onerror="this.style.display='none'"/>
        </div>
      </div>
    </header>

    <main class="container mx-auto px-4 py-8 sm:px-6 lg:px-8 max-w-6xl">
      <!-- Hero Section -->
      <section class="text-center mb-12 sm:mb-16">
        <div class="max-w-4xl mx-auto">
          <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4 sm:mb-6">
            Bienvenido al Sistema de Gestión de Material
          </h2>
          <p class="text-lg sm:text-xl text-gray-700 mb-8 sm:mb-12 leading-relaxed">
            Plataforma integral para la gestión y solicitud de material de la Junta de Andalucía
          </p>
        </div>
      </section>

      <!-- Tarjetas de Acceso Principal -->
      <section class="mb-12 sm:mb-16">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
          
          <!-- Tarjeta: Solicitar Material -->
          <div class="bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden group">
            <div class="p-6 sm:p-8">
              <div class="bg-junta-green-100 rounded-full w-16 h-16 sm:w-20 sm:h-20 flex items-center justify-center mb-6 group-hover:bg-junta-green-200 transition-colors">
                <svg class="w-8 h-8 sm:w-10 sm:h-10 text-junta-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h10c.512 0 1.024.195 1.414.586l7 7a2 2 0 002.828 0l-7-7A1.994 1.994 0 0017 3V4a1 1 0 00-1-1h-1z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 11-8 4 4 0 018 0zM7 12a4 4 0 11-8 4 4 0 01-8 0z"/>
                </svg>
              </div>
              <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-3">Solicitar Material</h3>
              <p class="text-gray-600 mb-6 text-sm sm:text-base leading-relaxed">
                Realiza solicitudes de material de forma rápida y sencilla. Accede al inventario disponible y gestiona tus peticiones.
              </p>
              <router-link 
                to="/peticion"
                class="inline-flex items-center px-6 py-3 sm:px-8 sm:py-4 bg-junta-green-600 hover:bg-junta-green-700 text-white font-semibold rounded-lg transition-colors text-sm sm:text-base group-hover:shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5-5m5 5v-10m-5 10v10"/>
                </svg>
                Acceder
              </router-link>
            </div>
          </div>

          <!-- Tarjeta: Consultar Albarán -->
          <div class="bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden group">
            <div class="p-6 sm:p-8">
              <div class="bg-blue-100 rounded-full w-16 h-16 sm:w-20 sm:h-20 flex items-center justify-center mb-6 group-hover:bg-blue-200 transition-colors">
                <svg class="w-8 h-8 sm:w-10 sm:h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707L19.586 6.707A2 2 0 0118 5v8a2 2 0 01-2 2H7a2 2 0 01-2-2V5z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707L19.586 6.707A2 2 0 0118 5v8a2 2 0 01-2 2H7a2 2 0 01-2-2V5z"/>
                </svg>
              </div>
              <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-3">Consultar Albarán</h3>
              <p class="text-gray-600 mb-6 text-sm sm:text-base leading-relaxed">
                Accede a tus albaranes digitales y consultas el estado de tus solicitudes de material.
              </p>
              <div class="text-sm text-gray-500 bg-gray-50 rounded-lg p-4 border border-gray-200">
                <p class="font-medium mb-2">📋 ¿Necesitas consultar un albarán?</p>
                <p>Introduce el token que recibiste en tu correo para acceder directamente.</p>
              </div>
            </div>
          </div>

          <!-- Tarjeta: Acceso Personal -->
          <div class="bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden group">
            <div class="p-6 sm:p-8">
              <div class="bg-purple-100 rounded-full w-16 h-16 sm:w-20 sm:h-20 flex items-center justify-center mb-6 group-hover:bg-purple-200 transition-colors">
                <svg class="w-8 h-8 sm:w-10 sm:h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l7-7m0 0l-7 7m-7 0v10"/>
                </svg>
              </div>
              <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-3">Acceso Personal</h3>
              <p class="text-gray-600 mb-6 text-sm sm:text-base leading-relaxed">
                Accede al panel de gestión completo con todas las funcionalidades administrativas.
              </p>
              <router-link 
                to="/login"
                class="inline-flex items-center px-6 py-3 sm:px-8 sm:py-4 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition-colors text-sm sm:text-base group-hover:shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4 4m0 0v-10m-4 10v10"/>
                </svg>
                Iniciar Sesión
              </router-link>
            </div>
          </div>
        </div>
      </section>

      <!-- Sección de Instrucciones -->
      <section class="mb-12 sm:mb-16">
        <div class="bg-white rounded-2xl shadow-xl p-8 sm:p-12">
          <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6 text-center">
            ¿Cómo utilizar el sistema?
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
            
            <!-- Paso 1 -->
            <div class="text-center">
              <div class="bg-junta-green-100 rounded-full w-16 h-16 sm:w-20 sm:h-20 flex items-center justify-center mx-auto mb-4">
                <span class="text-2xl sm:text-3xl font-bold text-junta-green-600">1</span>
              </div>
              <h4 class="font-semibold text-gray-900 mb-2">Solicita Material</h4>
              <p class="text-gray-600 text-sm sm:text-base">
                Selecciona los materiales que necesitas del catálogo disponible.
              </p>
            </div>

            <!-- Paso 2 -->
            <div class="text-center">
              <div class="bg-junta-green-100 rounded-full w-16 h-16 sm:w-20 sm:h-20 flex items-center justify-center mx-auto mb-4">
                <span class="text-2xl sm:text-3xl font-bold text-junta-green-600">2</span>
              </div>
              <h4 class="font-semibold text-gray-900 mb-2">Elige Almacén</h4>
              <p class="text-gray-600 text-sm sm:text-base">
                Selecciona el almacén más cercano utilizando nuestro mapa interactivo.
              </p>
            </div>

            <!-- Paso 3 -->
            <div class="text-center">
              <div class="bg-junta-green-100 rounded-full w-16 h-16 sm:w-20 sm:h-20 flex items-center justify-center mx-auto mb-4">
                <span class="text-2xl sm:text-3xl font-bold text-junta-green-600">3</span>
              </div>
              <h4 class="font-semibold text-gray-900 mb-2">Completa Formulario</h4>
              <p class="text-gray-600 text-sm sm:text-base">
                Añade tus datos y justificación para finalizar la solicitud.
              </p>
            </div>

            <!-- Paso 4 -->
            <div class="text-center">
              <div class="bg-junta-green-100 rounded-full w-16 h-16 sm:w-20 sm:h-20 flex items-center justify-center mx-auto mb-4">
                <span class="text-2xl sm:text-3xl font-bold text-junta-green-600">4</span>
              </div>
              <h4 class="font-semibold text-gray-900 mb-2">Recibe Confirmación</h4>
              <p class="text-gray-600 text-sm sm:text-base">
                Recibirás un email con la confirmación y estado de tu petición.
              </p>
            </div>
          </div>
        </div>
      </section>

      <!-- Sección de Características -->
      <section class="mb-12 sm:mb-16">
        <div class="bg-gradient-to-r from-junta-green-600 to-junta-green-700 rounded-2xl p-8 sm:p-12 text-white">
          <h3 class="text-2xl sm:text-3xl font-bold mb-8 text-center">
            Características del Sistema
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            
            <div class="flex items-start space-x-3">
              <svg class="w-6 h-6 text-junta-green-200 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <div>
                <h4 class="font-semibold mb-1">Mapa Interactivo</h4>
                <p class="text-junta-green-100 text-sm">Localización visual de todos los almacenes</p>
              </div>
            </div>

            <div class="flex items-start space-x-3">
              <svg class="w-6 h-6 text-junta-green-200 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v8a2 2 0 012 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l2 2h12l-2-2z" clip-rule="evenodd"/>
              </svg>
              <div>
                <h4 class="font-semibold mb-1">Inventario en Tiempo Real</h4>
                <p class="text-junta-green-100 text-sm">Disponibilidad actualizada de material</p>
              </div>
            </div>

            <div class="flex items-start space-x-3">
              <svg class="w-6 h-6 text-junta-green-200 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0019 4V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a2 2 0 002 2h4a2 2 0 002-2V6a2 2 0 00-2-2h-4z"/>
                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h4a2 2 0 002-2V6a2 2 0 00-2-2h-4z"/>
              </svg>
              <div>
                <h4 class="font-semibold mb-1">Notificaciones Automáticas</h4>
                <p class="text-junta-green-100 text-sm">Alertas por email del estado</p>
              </div>
            </div>

            <div class="flex items-start space-x-3">
              <svg class="w-6 h-6 text-junta-green-200 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 001-1v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
              </svg>
              <div>
                <h4 class="font-semibold mb-1">Seguridad Garantizada</h4>
                <p class="text-junta-green-100 text-sm">Acceso seguro y autenticado</p>
              </div>
            </div>

            <div class="flex items-start space-x-3">
              <svg class="w-6 h-6 text-junta-green-200 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v4h8v-4z"/>
              </svg>
              <div>
                <h4 class="font-semibold mb-1">Multiplataforma</h4>
                <p class="text-junta-green-100 text-sm">Acceso desde cualquier dispositivo</p>
              </div>
            </div>

            <div class="flex items-start space-x-3">
              <svg class="w-6 h-6 text-junta-green-200 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 00-1-1zM2 5a2 2 0 012-2v14a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2H2z" clip-rule="evenodd"/>
                <path fill-rule="evenodd" d="M15 7a1 1 0 011 1v1h1a1 1 0 011 1v1a1 1 0 01-1 1h-1a1 1 0 01-1-1V9a1 1 0 01-1-1h-1a1 1 0 01-1 1v1a1 1 0 001 1h1a1 1 0 001-1V8a1 1 0 00-1-1h-1z" clip-rule="evenodd"/>
              </svg>
              <div>
                <h4 class="font-semibold mb-1">Historial Completo</h4>
                <p class="text-junta-green-100 text-sm">Seguimiento de todas las operaciones</p>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-8 sm:py-12">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <div class="mb-4">
            <img src="/gestionmaterial/images/junta-logo.png" alt="Junta de Andalucía"
                 class="h-12 sm:h-16 object-contain mx-auto opacity-80"
                 onerror="this.style.display='none'"/>
          </div>
          <p class="text-sm sm:text-base text-gray-300 mb-2">
            © {{ new Date().getFullYear() }} Junta de Andalucía - Agencia Digital de Andalucía
          </p>
          <p class="text-xs sm:text-sm text-gray-400">
            Sistema de Gestión de Material - Todos los derechos reservados
          </p>
          <div class="mt-6 flex justify-center space-x-6">
            <a href="#" class="text-gray-400 hover:text-white transition-colors text-sm">
              Política de Privacidad
            </a>
            <a href="#" class="text-gray-400 hover:text-white transition-colors text-sm">
              Términos de Uso
            </a>
            <a href="#" class="text-gray-400 hover:text-white transition-colors text-sm">
              Ayuda
            </a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
});
</script>

<style scoped>
/* Animaciones suaves */
.group:hover {
  transform: translateY(-2px);
}

/* Gradientes personalizados */
.bg-gradient-to-br {
  background-image: linear-gradient(to bottom right, var(--tw-gradient-stops));
}

/* Sombras personalizadas */
.shadow-xl {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.shadow-2xl {
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .container {
    padding-left: 1rem;
    padding-right: 1rem;
  }
}
</style>