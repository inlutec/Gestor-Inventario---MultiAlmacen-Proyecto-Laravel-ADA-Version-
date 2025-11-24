<template>
  <div class="space-y-6">
    <!-- Selector de provincias y almacenes -->
    <div class="bg-white rounded-xl shadow-md p-6">
      <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-junta-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 111.57 0L9.586 7.757a8 8 0 111.415 0l4.242 4.243a8 8 0 010 11.414zm-.493 3.314l4.242-4.242a6 6 0 10-8.486 0L8.486 13.9a6 6 0 108.486 0l4.242 4.242a6 6 0 010 8.486z"/>
        </svg>
        Seleccionar almacén
      </h3>
      
      <div class="grid md:grid-cols-2 gap-4">
        <!-- Selector de provincia -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">
            Provincia <span class="text-red-500">*</span>
          </label>
          <select
            v-model="provinciaSeleccionadaId"
            @change="cargarAlmacenesProvincia"
            class="w-full px-4 py-3 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 transition-colors bg-white"
          >
            <option value="">Selecciona una provincia</option>
            <option v-for="provincia in provincias" :key="provincia.provincia_id" :value="provincia.provincia_id">
              {{ provincia.provincia }} ({{ provincia.almacenes.length }} almacenes)
            </option>
          </select>
        </div>

        <!-- Selector de almacén -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">
            Almacén <span class="text-red-500">*</span>
          </label>
          <select
            v-model="almacenSeleccionado"
            :disabled="!provinciaSeleccionadaId || almacenesProvincia.length === 0"
            class="w-full px-4 py-3 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 transition-colors bg-white disabled:bg-gray-100 disabled:cursor-not-allowed"
          >
            <option value="">Selecciona un almacén</option>
            <option v-for="almacen in almacenesProvincia" :key="almacen.id" :value="almacen.id">
              {{ almacen.nombre }} - {{ almacen.sede }}
            </option>
          </select>
        </div>
      </div>

      <!-- Información del almacén seleccionado -->
      <div v-if="almacenSeleccionadoInfo" class="mt-4 p-4 bg-junta-green-50 border border-junta-green-200 rounded-lg">
        <h4 class="font-semibold text-junta-green-800 mb-2">Almacén seleccionado:</h4>
        <div class="text-sm text-junta-green-700">
          <p><strong>{{ almacenSeleccionadoInfo.nombre }}</strong></p>
          <p>Sede: {{ almacenSeleccionadoInfo.sede }}</p>
          <p>Provincia: {{ almacenSeleccionadoInfo.provincia }}</p>
        </div>
      </div>
    </div>

    <!-- Mapa interactivo -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
      <div class="bg-gradient-to-r from-junta-green-600 to-junta-green-700 px-6 py-4">
        <h3 class="text-lg font-bold text-white flex items-center">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013.354 16l-1.391-2.318A2 2 0 015 12V5a2 2 0 00-2-2H5a2 2 0 00-2 2v7a2 2 0 001.391 1.682L4.646 16a1 1 0 00.708.276l5.447 2.724z"/>
          </svg>
          Mapa de almacenes en Andalucía
        </h3>
      </div>
      
      <div class="p-6">
        <div class="flex items-center justify-between mb-4">
          <span class="text-sm text-gray-600">
            Haz clic en cualquier provincia para ver los almacenes disponibles
          </span>
          <button
            @click="toggleMapa"
            class="text-sm text-junta-green-600 hover:text-junta-green-700 font-medium flex items-center gap-1"
          >
            <svg v-if="mostrarMapa" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 0v4m0 0h4m4-4v4m0 0h4M4 0v4m0 0h4"/>
            </svg>
            {{ mostrarMapa ? 'Ocultar mapa' : 'Mostrar mapa' }}
          </button>
        </div>

        <!-- Contenedor del mapa -->
        <div v-show="mostrarMapa" class="relative">
          <div id="mapa-almacenes" class="w-full h-96 rounded-lg border-2 border-gray-200"></div>
          
          <!-- Loading -->
          <div v-if="cargandoMapa" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center rounded-lg">
            <div class="text-center">
              <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-junta-green-600"></div>
              <p class="mt-2 text-sm text-gray-600">Cargando mapa...</p>
            </div>
          </div>
        </div>

        <!-- Leyenda del mapa -->
        <div v-show="mostrarMapa" class="mt-4 flex flex-wrap gap-4 text-sm">
          <div class="flex items-center gap-2">
            <div class="w-4 h-4 bg-blue-500 rounded-full"></div>
            <span class="text-gray-600">Provincia seleccionada</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-4 h-4 bg-junta-green-600 rounded-full"></div>
            <span class="text-gray-600">Almacenes disponibles</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Props
const emit = defineEmits(['almacen-seleccionado']);

// Estado reactivo
const provincias = ref([]);
const provinciaSeleccionadaId = ref('');
const almacenesProvincia = ref([]);
const almacenSeleccionado = ref('');
const almacenSeleccionadoInfo = ref(null);
const mostrarMapa = ref(false);
const cargandoMapa = ref(false);
let mapa = null;
let marcadores = [];

// Cargar provincias con almacenes
const cargarProvincias = async () => {
  try {
    const response = await fetch('/api/almacenes-por-provincia');
    const data = await response.json();
    if (data.success) {
      provincias.value = data.data;
    }
  } catch (error) {
    console.error('Error al cargar provincias:', error);
  }
};

// Cargar almacenes de una provincia específica
const cargarAlmacenesProvincia = () => {
  const provincia = provincias.value.find(p => p.provincia_id === provinciaSeleccionadaId.value);
  if (provincia) {
    almacenesProvincia.value = provincia.almacenes;
    almacenSeleccionado.value = '';
    almacenSeleccionadoInfo.value = null;
    
    // Actualizar mapa si está visible
    if (mostrarMapa.value && mapa) {
      actualizarMapa();
    }
  } else {
    almacenesProvincia.value = [];
  }
};

// Inicializar mapa
const inicializarMapa = async () => {
  if (!mostrarMapa.value) return;
  
  cargandoMapa.value = true;
  
  await nextTick();
  
  // Destruir mapa existente
  if (mapa) {
    mapa.remove();
    mapa = null;
  }
  
  // Crear nuevo mapa centrado en Andalucía
  mapa = L.map('mapa-almacenes').setView([37.5, -4.5], 7);
  
  // Añadir capa de tiles
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 18
  }).addTo(mapa);
  
  // Esperar a que el mapa esté listo
  setTimeout(() => {
    actualizarMapa();
    cargandoMapa.value = false;
  }, 500);
};

// Actualizar marcadores del mapa
const actualizarMapa = () => {
  if (!mapa) return;
  
  // Limpiar marcadores existentes
  marcadores.forEach(marcador => {
    mapa.removeLayer(marcador);
  });
  marcadores = [];
  
  // Añadir marcadores de provincias
  provincias.value.forEach(provincia => {
    const color = provincia.provincia_id === provinciaSeleccionadaId.value ? '#3b82f6' : '#10b981';
    
    // Marcador de provincia
    const marcadorProvincia = L.circleMarker([provincia.lat, provincia.lng], {
      radius: 20000,
      fillColor: color,
      color: provincia.provincia_id === provinciaSeleccionadaId.value ? '#1e40af' : '#047857',
      weight: 2,
      opacity: 0.8,
      fillOpacity: 0.4
    }).addTo(mapa);
    
    // Popup con información de la provincia
    const popupContent = `
      <div class="p-2">
        <h4 class="font-bold text-lg mb-2">${provincia.provincia}</h4>
        <p class="text-sm text-gray-600 mb-2">${provincia.almacenes.length} almacenes disponibles</p>
        <button 
          onclick="window.seleccionarProvinciaDesdeMapa(${provincia.provincia_id})"
          class="bg-junta-green-600 text-white px-3 py-1 rounded text-sm hover:bg-junta-green-700 transition-colors"
        >
          Seleccionar provincia
        </button>
      </div>
    `;
    
    marcadorProvincia.bindPopup(popupContent);
    marcadores.push(marcadorProvincia);
    
    // Añadir marcadores de almacenes si la provincia está seleccionada
    if (provincia.provincia_id === provinciaSeleccionadaId.value) {
      provincia.almacenes.forEach(almacen => {
        const marcadorAlmacen = L.marker([provincia.lat, provincia.lng], {
          icon: L.divIcon({
            html: `
              <div class="bg-junta-green-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold shadow-lg">
                📦
              </div>
            `,
            className: 'almacen-marker',
            iconSize: [24, 24]
          })
        }).addTo(mapa);
        
        const almacenPopup = `
          <div class="p-2">
            <h5 class="font-bold mb-1">${almacen.nombre}</h5>
            <p class="text-sm text-gray-600">Sede: ${almacen.sede}</p>
            <button 
              onclick="window.seleccionarAlmacenDesdeMapa(${almacen.id})"
              class="bg-junta-green-600 text-white px-3 py-1 rounded text-sm hover:bg-junta-green-700 transition-colors mt-2"
            >
              Seleccionar este almacén
            </button>
          </div>
        `;
        
        marcadorAlmacen.bindPopup(almacenPopup);
        marcadores.push(marcadorAlmacen);
      });
    }
  });
  
  // Ajustar vista del mapa si hay provincia seleccionada
  if (provinciaSeleccionadaId.value) {
    const provincia = provincias.value.find(p => p.provincia_id === provinciaSeleccionadaId.value);
    if (provincia) {
      mapa.setView([provincia.lat, provincia.lng], 9);
    }
  }
};

// Toggle del mapa
const toggleMapa = () => {
  mostrarMapa.value = !mostrarMapa.value;
  if (mostrarMapa.value) {
    setTimeout(() => {
      inicializarMapa();
    }, 100);
  }
};

// Seleccionar provincia desde el mapa (función global)
window.seleccionarProvinciaDesdeMapa = (provinciaId) => {
  provinciaSeleccionadaId.value = provinciaId;
  cargarAlmacenesProvincia();
};

// Seleccionar almacén desde el mapa (función global)
window.seleccionarAlmacenDesdeMapa = (almacenId) => {
  almacenSeleccionado.value = almacenId;
  const almacen = almacenesProvincia.value.find(a => a.id === almacenId);
  if (almacen) {
    almacenSeleccionadoInfo.value = almacen;
    emit('almacen-seleccionado', almacen);
  }
};

// Watch para emitir cambios en el almacén seleccionado
const emitirCambioAlmacen = () => {
  if (almacenSeleccionado.value) {
    const almacen = almacenesProvincia.value.find(a => a.id === almacenSeleccionado.value);
    if (almacen) {
      almacenSeleccionadoInfo.value = almacen;
      emit('almacen-seleccionado', almacen);
    }
  } else {
    almacenSeleccionadoInfo.value = null;
    emit('almacen-seleccionado', null);
  }
};

// Watch para cambios en el almacén seleccionado
const unwatchAlmacen = watch(almacenSeleccionado, emitirCambioAlmacen);

onMounted(() => {
  cargarProvincias();
});

// Cleanup
onUnmounted(() => {
  if (unwatchAlmacen) {
    unwatchAlmacen();
  }
  if (mapa) {
    mapa.remove();
  }
  // Limpiar funciones globales
  delete window.seleccionarProvinciaDesdeMapa;
  delete window.seleccionarAlmacenDesdeMapa;
});
</script>

<style scoped>
#mapa-almacenes {
  min-height: 400px;
  background: #f3f4f6;
}

:deep(.almacen-marker) {
  background: transparent !important;
  border: none !important;
}

:deep(.leaflet-popup-content-wrapper) {
  border-radius: 8px;
}

:deep(.leaflet-popup-content) {
  margin: 0;
  min-width: 200px;
}

:deep(.leaflet-popup-tip) {
  background: #10b981;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>