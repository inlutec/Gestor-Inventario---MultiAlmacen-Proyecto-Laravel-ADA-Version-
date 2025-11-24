<template>
  <div class="almacen-selector-mapa">
    <!-- Selector de Provincia -->
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">Filtrar por Provincia:</label>
      <select 
        v-model="provinciaSeleccionada" 
        @change="onProvinciaChange"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 bg-white text-sm"
        :disabled="loading"
      >
        <option value="">Todas las provincias</option>
        <option v-for="provincia in provinciasDisponibles" :key="provincia.id" :value="provincia.id">
          {{ provincia.nombre }}
        </option>
      </select>
    </div>

    <!-- Mapa de Andalucía con Leaflet -->
    <div class="mb-4">
      <div class="relative bg-gray-50 rounded-lg p-4 border border-gray-200">
        <div 
          id="mapa-andalucia" 
          ref="mapContainer"
          class="w-full h-96 rounded-lg overflow-hidden"
        ></div>
      </div>
    </div>

    <!-- Selector de Almacenes -->
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar Almacén:</label>
      <select 
        v-model="almacenSeleccionado" 
        @change="onAlmacenChange"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 bg-white text-sm"
        :disabled="loading"
      >
        <option value="">Seleccionar almacén...</option>
        <option v-for="almacen in almacenesFiltrados" :key="almacen.id" :value="almacen.id">
          {{ almacen.nombre }} ({{ almacen.provincia_nombre }})
        </option>
      </select>
    </div>

    <!-- Botón de limpiar -->
    <div class="flex justify-between items-center">
      <div class="text-sm text-gray-600">
        <span v-if="almacenSeleccionado">
          Almacén seleccionado: {{ getAlmacenSeleccionadoNombre() }}
        </span>
        <span v-else class="text-gray-500">
          No hay almacén seleccionado
        </span>
      </div>
      
      <button 
        v-if="almacenSeleccionado"
        @click="limpiarSeleccion"
        class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 border border-gray-300 rounded hover:bg-gray-50 transition-colors"
        title="Limpiar selección"
      >
        Limpiar
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import axios from 'axios'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// Fix para los iconos de Leaflet
delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
})

const props = defineProps({
  modelValue: { type: [String, Number], default: '' }
})

const emit = defineEmits(['update:modelValue', 'change'])

const almacenSeleccionado = ref(props.modelValue)
const provinciaSeleccionada = ref('')
const almacenes = ref([])
const provincias = ref([])
const loading = ref(false)
const mapContainer = ref(null)
const map = ref(null)
const markers = ref([])
const provinceLayers = ref({})

// Coordenadas aproximadas para los almacenes en el mapa (latitud, longitud)
const coordenadasProvincias = {
  1: { lat: 36.8381, lng: -2.4597 },  // Almería
  2: { lat: 36.5204, lng: -6.2885 },  // Cádiz
  3: { lat: 37.8881, lng: -4.7794 },  // Córdoba
  4: { lat: 37.1775, lng: -3.5985 },  // Granada
  5: { lat: 37.2658, lng: -6.9455 },  // Huelva
  6: { lat: 37.7796, lng: -3.7847 },  // Jaén
  7: { lat: 36.7202, lng: -4.4203 },  // Málaga
  8: { lat: 37.3891, lng: -5.9845 }   // Sevilla
}

// Coordenadas aproximadas para los polígonos de las provincias
const coordenadasPoligonosProvincias = {
  1: [ // Almería
    [36.8381, -2.4597], [36.9381, -2.3597], [36.9381, -2.5597], [36.7381, -2.5597], [36.7381, -2.3597]
  ],
  2: [ // Cádiz
    [36.5204, -6.2885], [36.6204, -6.1885], [36.6204, -6.3885], [36.4204, -6.3885], [36.4204, -6.1885]
  ],
  3: [ // Córdoba
    [37.8881, -4.7794], [37.9881, -4.6794], [37.9881, -4.8794], [37.7881, -4.8794], [37.7881, -4.6794]
  ],
  4: [ // Granada
    [37.1775, -3.5985], [37.2775, -3.4985], [37.2775, -3.6985], [37.0775, -3.6985], [37.0775, -3.4985]
  ],
  5: [ // Huelva
    [37.2658, -6.9455], [37.3658, -6.8455], [37.3658, -7.0455], [37.1658, -7.0455], [37.1658, -6.8455]
  ],
  6: [ // Jaén
    [37.7796, -3.7847], [37.8796, -3.6847], [37.8796, -3.8847], [37.6796, -3.8847], [37.6796, -3.6847]
  ],
  7: [ // Málaga
    [36.7202, -4.4203], [36.8202, -4.3203], [36.8202, -4.5203], [36.6202, -4.5203], [36.6202, -4.3203]
  ],
  8: [ // Sevilla
    [37.3891, -5.9845], [37.4891, -5.8845], [37.4891, -6.0845], [37.2891, -6.0845], [37.2891, -5.8845]
  ]
}

const provinciasDisponibles = computed(() => {
  const provinciasUnicas = [...new Set(almacenes.value.map(a => a.provincia_id))]
  return provinciasUnicas.map(id => ({
    id,
    nombre: almacenes.value.find(a => a.provincia_id === id)?.provincia_nombre || ''
  })).sort((a, b) => a.nombre.localeCompare(b.nombre))
})

const almacenesFiltrados = computed(() => {
  if (!provinciaSeleccionada.value) {
    return almacenes.value
  }
  return almacenes.value.filter(almacen => almacen.provincia_id == provinciaSeleccionada.value)
})

const getAlmacenSeleccionadoNombre = () => {
  const almacen = almacenes.value.find(a => a.id == almacenSeleccionado.value)
  return almacen ? almacen.nombre : ''
}

const initializeMap = async () => {
  await nextTick()
  
  if (mapContainer.value && !map.value) {
    // Crear el mapa centrado en Andalucía
    map.value = L.map(mapContainer.value).setView([37.5, -4.5], 7)
    
    // Añadir capa de tiles de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors'
    }).addTo(map.value)
    
    // Añadir polígonos de provincias
    addProvinceLayers()
    
    // Añadir marcadores de almacenes
    updateMarkers()
  }
}

const addProvinceLayers = () => {
  if (!map.value) return
  
  // Limpiar capas existentes
  Object.values(provinceLayers.value).forEach(layer => {
    map.value.removeLayer(layer)
  })
  provinceLayers.value = {}
  
  // Añadir nuevas capas de provincias
  Object.keys(coordenadasPoligonosProvincias).forEach(provinciaId => {
    const coords = coordenadasPoligonosProvincias[provinciaId]
    const polygon = L.polygon(coords, {
      color: getProvinciaStroke(provinciaId),
      fillColor: getProvinciaColor(provinciaId),
      fillOpacity: 0.3,
      weight: 2
    })
    
    // Añadir popup con nombre de provincia
    const provinciaNombre = provinciasDisponibles.value.find(p => p.id == provinciaId)?.nombre || `Provincia ${provinciaId}`
    polygon.bindPopup(provinciaNombre)
    
    // Añadir evento de clic
    polygon.on('click', () => {
      provinciaSeleccionada.value = provinciaId
      onProvinciaChange()
    })
    
    polygon.addTo(map.value)
    provinceLayers.value[provinciaId] = polygon
  })
}

const updateMarkers = () => {
  if (!map.value) return
  
  // Limpiar marcadores existentes
  markers.value.forEach(marker => {
    map.value.removeLayer(marker)
  })
  markers.value = []
  
  // Añadir nuevos marcadores de almacenes
  almacenesFiltrados.value.forEach(almacen => {
    const coords = coordenadasProvincias[almacen.provincia_id] || { lat: 37.5, lng: -4.5 }
    
    const icon = L.divIcon({
      html: `<div class="custom-marker ${almacen.id == almacenSeleccionado.value ? 'selected' : ''}">${almacen.nombre}</div>`,
      className: 'custom-div-icon',
      iconSize: [120, 30],
      iconAnchor: [60, 15]
    })
    
    const marker = L.marker([coords.lat, coords.lng], { icon })
      .bindPopup(`<strong>${almacen.nombre}</strong><br>${almacen.provincia_nombre}`)
      .addTo(map.value)
    
    marker.on('click', () => {
      selectAlmacenDirecto(almacen)
    })
    
    markers.value.push(marker)
  })
}

const getProvinciaColor = (provinciaId) => {
  if (provinciaSeleccionada.value && provinciaId == provinciaSeleccionada.value) {
    return '#86efac' // verde claro
  }
  
  const tieneAlmacenes = almacenes.value.some(a => a.provincia_id == provinciaId)
  return tieneAlmacenes ? '#fef3c7' : '#f3f4f6' // amarillo claro si tiene almacenes, gris si no
}

const getProvinciaStroke = (provinciaId) => {
  if (provinciaSeleccionada.value && provinciaId == provinciaSeleccionada.value) {
    return '#059669' // verde oscuro
  }
  return '#9ca3af' // gris
}

const cargarDatos = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/almacenes-disponibles')
    if (data.success) {
      almacenes.value = data.data
      // Actualizar el mapa después de cargar los datos
      if (map.value) {
        addProvinceLayers()
        updateMarkers()
      }
    }
  } catch (error) {
    console.error('Error cargando almacenes:', error)
  } finally {
    loading.value = false
  }
}

const onProvinciaChange = () => {
  // Si la provincia seleccionada no tiene almacenes, limpiar selección de almacén
  if (provinciaSeleccionada.value && almacenesFiltrados.value.length === 0) {
    almacenSeleccionado.value = ''
    onAlmacenChange()
  }
  
  // Actualizar colores de provincias y marcadores
  if (map.value) {
    addProvinceLayers()
    updateMarkers()
  }
}

const selectAlmacenDirecto = (almacen) => {
  almacenSeleccionado.value = almacen.id
  provinciaSeleccionada.value = almacen.provincia_id.toString()
  onAlmacenChange()
}

const onAlmacenChange = () => {
  emit('update:modelValue', almacenSeleccionado.value)
  emit('change', almacenSeleccionado.value)
  
  // Guardar en localStorage para persistencia
  if (almacenSeleccionado.value) {
    localStorage.setItem('almacen_seleccionado', almacenSeleccionado.value)
    localStorage.setItem('provincia_seleccionada', provinciaSeleccionada.value)
  } else {
    localStorage.removeItem('almacen_seleccionado')
  }
  
  // Añadir el parámetro a todas las peticiones axios futuras
  if (almacenSeleccionado.value && almacenSeleccionado.value !== '') {
    axios.defaults.params = {
      almacen_seleccionado: almacenSeleccionado.value
    }
  } else {
    axios.defaults.params = {}
  }
  
  // Actualizar marcadores para reflejar la selección
  if (map.value) {
    updateMarkers()
  }
}

const limpiarSeleccion = () => {
  almacenSeleccionado.value = ''
  provinciaSeleccionada.value = ''
  onAlmacenChange()
}

// Cargar datos al montar
onMounted(async () => {
  await cargarDatos()
  await initializeMap()
  
  // Recuperar selección guardada
  const guardado = localStorage.getItem('almacen_seleccionado')
  const provinciaGuardada = localStorage.getItem('provincia_seleccionada')
  
  if (guardado && guardado !== '') {
    almacenSeleccionado.value = guardado
    provinciaSeleccionada.value = provinciaGuardada || ''
    
    // Establecer el parámetro en axios para las peticiones iniciales
    axios.defaults.params = {
      almacen_seleccionado: guardado
    }
  } else {
    axios.defaults.params = {}
  }
})

// Limpiar el mapa al desmontar
onUnmounted(() => {
  if (map.value) {
    map.value.remove()
    map.value = null
  }
})

// Sincronizar con prop externa
watch(() => props.modelValue, (newValue) => {
  if (newValue !== almacenSeleccionado.value) {
    almacenSeleccionado.value = newValue
    if (map.value) {
      updateMarkers()
    }
  }
})

// Actualizar marcadores cuando cambian los almacenes filtrados
watch(almacenesFiltrados, () => {
  if (map.value) {
    updateMarkers()
  }
})
</script>

<style scoped>
.almacen-selector-mapa {
  @apply bg-white rounded-lg shadow-sm border border-gray-200 p-4;
}

/* Estilos para los marcadores personalizados */
:global(.custom-div-icon) {
  background: transparent;
  border: none;
}

:global(.custom-marker) {
  background: #059669;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: bold;
  text-align: center;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

:global(.custom-marker.selected) {
  background: #dc2626;
  border-color: #991b1b;
}
</style>