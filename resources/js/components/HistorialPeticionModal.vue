<template>
  <div v-if="mostrar" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="cerrar">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-800">
          📋 Historial de Auditoría - {{ titulo || `${tipoEntidad} #${numeroDocumento}` }}
        </h2>
        <button @click="cerrar" class="text-gray-400 hover:text-gray-600 transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="flex-1 overflow-y-auto px-6 py-4">
        <!-- Loading -->
        <div v-if="cargando" class="flex justify-center items-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
          <p class="font-semibold">Error al cargar el historial</p>
          <p class="text-sm mt-1">{{ error }}</p>
        </div>

        <!-- Timeline -->
        <div v-else-if="historial.length > 0" class="space-y-6">
          <div v-for="(entrada, index) in historial" :key="entrada.id" class="relative">
            <!-- Timeline line -->
            <div v-if="index < historial.length - 1" class="absolute left-6 top-12 w-0.5 h-full bg-gray-200"></div>
            
            <div class="flex gap-4">
              <!-- Icon -->
              <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center text-white text-xl relative z-10" :class="obtenerColorAccion(entrada.accion)">
                {{ obtenerIconoAccion(entrada.accion) }}
              </div>

              <!-- Content Card -->
              <div class="flex-1 bg-gray-50 rounded-lg p-4 border border-gray-200">
                <!-- Header -->
                <div class="flex justify-between items-start mb-2">
                  <div>
                    <h3 class="font-semibold text-gray-800 text-lg">
                      {{ obtenerTituloAccion(entrada.accion) }}
                    </h3>
                    <p class="text-sm text-gray-600">{{ entrada.descripcion }}</p>
                  </div>
                  <span class="text-xs text-gray-500 bg-white px-2 py-1 rounded">
                    {{ entrada.fecha_relativa }}
                  </span>
                </div>

                <!-- User info -->
                <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  <span v-if="entrada.usuario">{{ entrada.usuario.nombre }}</span>
                  <span v-else class="italic">Sistema</span>
                  <span class="text-gray-400">•</span>
                  <span>{{ entrada.fecha }}</span>
                </div>

                <!-- Expandable details -->
                <div v-if="entrada.datos_antes || entrada.datos_despues" class="mt-3">
                  <button 
                    @click="toggleDetalles(entrada.id)" 
                    class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1"
                  >
                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': detallesExpandidos[entrada.id] }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    Ver detalles técnicos
                  </button>

                  <!-- Expanded details -->
                  <div v-if="detallesExpandidos[entrada.id]" class="mt-3 space-y-3">
                    <div v-if="entrada.datos_antes" class="bg-white p-3 rounded border border-gray-200">
                      <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Datos Anteriores</p>
                      <pre class="text-xs text-gray-700 whitespace-pre-wrap">{{ formatearJSON(entrada.datos_antes) }}</pre>
                    </div>

                    <div v-if="entrada.datos_despues" class="bg-white p-3 rounded border border-gray-200">
                      <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Datos Nuevos</p>
                      <pre class="text-xs text-gray-700 whitespace-pre-wrap">{{ formatearJSON(entrada.datos_despues) }}</pre>
                    </div>

                    <div v-if="entrada.ip_address" class="text-xs text-gray-500">
                      <span class="font-semibold">IP:</span> {{ entrada.ip_address }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty state -->
        <div v-else class="text-center py-12 text-gray-500">
          <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <p class="text-lg">No hay registros en el historial</p>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end">
        <button 
          @click="cerrar" 
          class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-medium"
        >
          Cerrar
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch } from 'vue';
import axios from 'axios';

export default {
  name: 'HistorialPeticionModal',
  props: {
    mostrar: {
      type: Boolean,
      required: true
    },
    entidadId: {
      type: Number,
      default: null
    },
    numeroDocumento: {
      type: String,
      default: ''
    },
    tipoEntidad: {
      type: String,
      default: 'Petición'
    },
    apiEndpoint: {
      type: String,
      required: true
    },
    titulo: {
      type: String,
      default: null
    }
  },
  emits: ['cerrar'],
  setup(props, { emit }) {
    const historial = ref([]);
    const cargando = ref(false);
    const error = ref(null);
    const detallesExpandidos = ref({});

    const cargarHistorial = async () => {
      if (!props.entidadId || !props.apiEndpoint) return;
      
      cargando.value = true;
      error.value = null;
      
      try {
        const response = await axios.get(props.apiEndpoint);
        historial.value = response.data.data;
      } catch (err) {
        error.value = err.response?.data?.message || 'Error al cargar el historial';
        console.error('Error al cargar historial:', err);
      } finally {
        cargando.value = false;
      }
    };

    const cerrar = () => {
      emit('cerrar');
    };

    const toggleDetalles = (id) => {
      detallesExpandidos.value[id] = !detallesExpandidos.value[id];
    };

    const obtenerIconoAccion = (accion) => {
      const iconos = {
        'creado': '➕',
        'modificado': '✏️',
        'aprobado': '✅',
        'rechazado': '❌',
        'enviado_historico': '📦',
        'comentario': '💬',
        'firmado_emisor': '✍️',
        'firmado_receptor': '✍️',
        'entregado': '📦'
      };
      return iconos[accion] || '📝';
    };

    const obtenerColorAccion = (accion) => {
      const colores = {
        'creado': 'bg-blue-500',
        'modificado': 'bg-yellow-500',
        'aprobado': 'bg-green-500',
        'rechazado': 'bg-red-500',
        'enviado_historico': 'bg-purple-500',
        'comentario': 'bg-gray-500',
        'firmado_emisor': 'bg-indigo-500',
        'firmado_receptor': 'bg-teal-500',
        'entregado': 'bg-green-600'
      };
      return colores[accion] || 'bg-gray-500';
    };

    const obtenerTituloAccion = (accion) => {
      const titulos = {
        'creado': 'Documento Creado',
        'modificado': 'Documento Modificado',
        'aprobado': 'Petición Aprobada',
        'rechazado': 'Petición Rechazada',
        'enviado_historico': 'Enviado al Histórico',
        'comentario': 'Comentario Añadido',
        'firmado_emisor': 'Firmado por Emisor',
        'firmado_receptor': 'Firmado por Receptor',
        'entregado': 'Marcado como Entregado'
      };
      return titulos[accion] || 'Acción Registrada';
    };

    const formatearJSON = (obj) => {
      return JSON.stringify(obj, null, 2);
    };

    // Watch for modal open/close
    watch(() => props.mostrar, (nuevoValor) => {
      if (nuevoValor) {
        cargarHistorial();
        detallesExpandidos.value = {};
      }
    });

    return {
      historial,
      cargando,
      error,
      detallesExpandidos,
      cerrar,
      toggleDetalles,
      obtenerIconoAccion,
      obtenerColorAccion,
      obtenerTituloAccion,
      formatearJSON
    };
  }
};
</script>
