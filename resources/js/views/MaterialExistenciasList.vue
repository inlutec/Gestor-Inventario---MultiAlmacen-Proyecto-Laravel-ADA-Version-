<template>
  <div class="space-y-3">
    <div class="flex flex-col md:flex-row md:items-center gap-3">
      <div class="flex items-center gap-2 flex-1">
        <input v-model="search" type="text" placeholder="Buscar material..." class="border rounded px-3 py-2 w-full md:w-80" @input="applyFilter" />
        <button class="px-3 py-2 bg-slate-100 rounded" @click="load">Recargar</button>
      </div>
      <AlmacenSelector v-model="almacenId" @change="onAlmacenChange" />
    </div>

    <div v-if="loading" class="text-slate-500 text-sm">Cargando existencias...</div>
    <div v-else>
      <div class="overflow-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="bg-slate-100 text-left">
              <th class="p-2">Material</th>
              <th class="p-2">Descripción</th>
              <th class="p-2">Ubicación</th>
              <th class="p-2 text-center">Stock</th>
              <th class="p-2 text-center">Mínimo</th>
              <th class="p-2">Unidad</th>
              <th class="p-2 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in filtered" :key="m.id" class="border-b">
              <td class="p-2 font-medium">{{ m.nombre }}</td>
              <td class="p-2">{{ m.descripcion || '-' }}</td>
              <td class="p-2">{{ m.ubicacion || '-' }}</td>
              <td class="p-2 text-center" :class="m.stock_actual <= m.stock_minimo ? 'text-red-600 font-semibold' : ''">{{ m.stock_actual }}</td>
              <td class="p-2 text-center">{{ m.stock_minimo }}</td>
              <td class="p-2">{{ m.unidad }}</td>
              <td class="p-2 text-center">
                <div class="flex gap-1 justify-center">
                  <button
                      @click="abrirModalRegularizar(m)"
                      class="px-2 py-1 text-xs bg-amber-500 text-white rounded hover:bg-amber-600 transition"
                      title="Regularizar stock por inventario"
                    >
                      📋 Regularizar
                    </button>
                  <button
                      @click="verHistorialStock(m)"
                      class="px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600 transition"
                      title="Ver historial de stock"
                    >
                      📊 Historial
                    </button>
                  <button
                      @click="abrirModalUbicacion(m)"
                      class="px-2 py-1 text-xs bg-green-500 text-white rounded hover:bg-green-600 transition"
                      title="Especificar ubicación"
                    >
                      📍 Ubicación
                    </button>
                </div>
              </td>
            </tr>
            <tr v-if="!filtered.length">
              <td class="p-3 text-center text-slate-500" colspan="7">Sin resultados</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal de Regularización de Stock -->
    <div v-if="modalRegularizar.visible" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="bg-amber-500 text-white px-6 py-4 rounded-t-lg">
          <h3 class="text-lg font-bold">📋 Regularizar Stock - {{ modalRegularizar.material?.nombre }}</h3>
        </div>
        
        <div class="p-6 space-y-4">
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="text-sm text-blue-800 font-medium mb-2">Stock Actual del Sistema</div>
            <div class="text-3xl font-bold text-blue-900">{{ modalRegularizar.stockActual }} {{ modalRegularizar.material?.unidad }}</div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Nuevo Stock Real (según inventario) <span class="text-red-500">*</span>
            </label>
            <input
              v-model.number="modalRegularizar.nuevoStock"
              type="number"
              min="0"
              step="1"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
              placeholder="Ingrese el stock real contado"
            />
            <div v-if="diferencia !== 0" class="mt-2 text-sm font-medium" :class="diferencia > 0 ? 'text-green-600' : 'text-red-600'">
              {{ diferencia > 0 ? '+' : '' }}{{ diferencia }} {{ modalRegularizar.material?.unidad }}
              ({{ diferencia > 0 ? 'Ajuste positivo' : 'Ajuste negativo' }})
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Motivo del Ajuste <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="modalRegularizar.motivo"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 resize-none"
              placeholder="Ej: Inventario anual 2025, Discrepancia detectada en recuento físico, etc."
            ></textarea>
          </div>

          <div v-if="modalRegularizar.error" class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
            {{ modalRegularizar.error }}
          </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 rounded-b-lg flex justify-end gap-2">
          <button 
            @click="cerrarModalRegularizar" 
            class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
            :disabled="modalRegularizar.guardando"
          >
            Cancelar
          </button>
          <button 
            @click="guardarRegularizacion" 
            class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="!puedeGuardar || modalRegularizar.guardando"
          >
            {{ modalRegularizar.guardando ? 'Guardando...' : 'Guardar Ajuste' }}
          </button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Modal de Historial de Stock -->
  <div v-if="modalHistorial.visible" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-hidden flex flex-col m-4">
      <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">Historial de Stock: {{ modalHistorial.nombreMaterial }}</h3>
          <button @click="cerrarModalHistorial" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
      
      <div class="flex-1 overflow-y-auto p-6">
        <div v-if="cargandoHistorial" class="text-center py-8">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p class="mt-2 text-gray-600">Cargando historial...</p>
        </div>
        
        <div v-else-if="errorHistorial" class="bg-red-50 border border-red-200 rounded-lg p-4">
          <p class="text-red-800">{{ errorHistorial }}</p>
        </div>
        
        <div v-else>
          <!-- Resumen de Stock -->
          <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <h4 class="font-semibold text-gray-900 mb-3">Resumen de Stock</h4>
            <div class="grid grid-cols-3 gap-4">
              <div>
                <p class="text-sm text-gray-600">Total Entradas</p>
                <p class="text-lg font-semibold text-green-600">{{ resumenStock.totalEntradas }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Total Salidas</p>
                <p class="text-lg font-semibold text-red-600">{{ resumenStock.totalSalidas }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Stock Actual</p>
                <p class="text-lg font-semibold text-blue-600">{{ resumenStock.stockActual }}</p>
              </div>
            </div>
          </div>
          
          <!-- Lista de Movimientos -->
          <div v-if="historialMovimientos.length > 0">
            <div class="overflow-x-auto -mx-6 px-6">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Fecha</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Tipo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Cantidad</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Usuario</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Almacén</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Estado</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[200px]">Justificación</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="movimiento in historialMovimientos" :key="movimiento.id">
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ formatearFecha(movimiento.fecha) }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">
                      <span :class="movimiento.tipo === 'entrada' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                        {{ movimiento.tipo === 'entrada' ? 'Entrada' : 'Salida' }}
                      </span>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ movimiento.cantidad }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ movimiento.usuario?.name || '-' }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ movimiento.almacen?.nombre || '-' }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">
                      <span :class="obtenerClaseEstado(movimiento.estado)"
                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                        {{ movimiento.estado.replace('_', ' ').toUpperCase() }}
                      </span>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-900">
                      <div class="min-w-[200px] max-w-md">
                        <span class="text-gray-700 break-words">{{ movimiento.justificacion || '-' }}</span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          
          <div v-else class="text-center py-8 text-gray-500">
            No hay movimientos de stock registrados para este material.
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Modal de Gestión de Ubicación -->
  <div v-if="modalUbicacion.visible" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full m-4">
      <div class="bg-green-500 text-white px-6 py-4 rounded-t-lg">
        <h3 class="text-lg font-bold">📍 Gestionar Ubicación - {{ modalUbicacion.material?.nombre }}</h3>
      </div>
      
      <div class="p-6 space-y-4">
        <div v-if="modalUbicacion.ubicacionActual" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="text-sm text-blue-800 font-medium mb-1">Ubicación Actual</div>
          <div class="text-lg font-semibold text-blue-900">{{ modalUbicacion.ubicacionActual }}</div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Nueva Ubicación <span class="text-red-500">*</span>
          </label>
          <input
            v-model="ubicacionForm"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
            placeholder="Ej: Estantería A-3, Armario 2, etc."
          />
        </div>

        <div v-if="almacenId" class="bg-gray-50 border border-gray-200 rounded-lg p-3">
          <div class="text-sm text-gray-600">
            <strong>Almacén:</strong> {{ obtenerNombreAlmacen(almacenId) }}
          </div>
          <div class="text-xs text-gray-500 mt-1">
            Esta ubicación se aplicará solo para este almacén
          </div>
        </div>

        <div v-if="errorUbicacion" class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
          {{ errorUbicacion }}
        </div>
      </div>

      <div class="px-6 py-4 bg-gray-50 rounded-b-lg flex justify-end gap-2">
        <button
          @click="cerrarModalUbicacion"
          class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
          :disabled="cargandoUbicacion"
        >
          Cancelar
        </button>
        <button
          @click="guardarUbicacion"
          class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!ubicacionForm.trim() || cargandoUbicacion"
        >
          {{ cargandoUbicacion ? 'Guardando...' : 'Guardar Ubicación' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import AlmacenSelector from '../components/AlmacenSelector.vue'

const props = defineProps({
  refreshKey: { type: Number, default: 0 }
})

const items = ref([])
const loading = ref(false)
const search = ref('')
const almacenId = ref('')
const almacenes = ref([])

const modalRegularizar = ref({
  visible: false,
  material: null,
  stockActual: 0,
  nuevoStock: 0,
  motivo: '',
  guardando: false,
  error: ''
})

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return items.value
  return items.value.filter(m =>
    (m.nombre || '').toLowerCase().includes(q) ||
    (m.descripcion || '').toLowerCase().includes(q) ||
    (m.ubicacion || '').toLowerCase().includes(q)
  )
})

const diferencia = computed(() => {
  return modalRegularizar.value.nuevoStock - modalRegularizar.value.stockActual
})

const puedeGuardar = computed(() => {
  return modalRegularizar.value.nuevoStock >= 0 && 
         modalRegularizar.value.motivo.trim().length >= 10 &&
         diferencia.value !== 0
})

async function load() {
  loading.value = true
  try {
    const params = {}
    if (almacenId.value) {
      params.almacen_ids = [almacenId.value]
    }
    const { data } = await axios.get('/material-movimientos/inventario', { params })
    if (data.success) items.value = data.data
  } catch (e) {
    console.error('Error cargando inventario', e)
  } finally {
    loading.value = false
  }
}

async function cargarAlmacenes() {
  try {
    const { data } = await axios.get('/mis-almacenes')
    if (data.success) {
      almacenes.value = data.data
    }
  } catch (e) {
    console.error('Error cargando almacenes:', e)
  }
}

function onAlmacenChange() {
  load()
}

function obtenerNombreAlmacen(almacenId) {
  const almacen = almacenes.value.find(a => a.id == almacenId)
  return almacen ? almacen.nombre : 'Almacén desconocido'
}

function abrirModalRegularizar(material) {
  modalRegularizar.value = {
    visible: true,
    material: material,
    stockActual: material.stock_actual,
    nuevoStock: material.stock_actual,
    motivo: '',
    guardando: false,
    error: ''
  }
}

function cerrarModalRegularizar() {
  modalRegularizar.value = {
    visible: false,
    material: null,
    stockActual: 0,
    nuevoStock: 0,
    motivo: '',
    guardando: false,
    error: ''
  }
}

async function guardarRegularizacion() {
  if (!puedeGuardar.value) return

  modalRegularizar.value.guardando = true
  modalRegularizar.value.error = ''

  try {
    const { data } = await axios.post(`/entidades/${modalRegularizar.value.material.id}/regularizar-stock`, {
      stock_anterior: modalRegularizar.value.stockActual,
      stock_nuevo: modalRegularizar.value.nuevoStock,
      motivo: modalRegularizar.value.motivo
    })

    if (data.success) {
      alert('✓ Stock regularizado correctamente')
      cerrarModalRegularizar()
      await load() // Recargar inventario
    }
  } catch (error) {
    console.error('Error regularizando stock:', error)
    modalRegularizar.value.error = error.response?.data?.message || 'Error al guardar la regularización'
  } finally {
    modalRegularizar.value.guardando = false
  }
}

function applyFilter() {
  // computed handles it; stub to allow @input
}

// Historial de stock
const modalHistorial = ref({ visible: false, entidadId: null, nombreMaterial: '' })
const historialMovimientos = ref([])
const cargandoHistorial = ref(false)
const errorHistorial = ref('')

// Gestión de ubicación
const modalUbicacion = ref({ visible: false, material: null, ubicacionActual: '' })
const ubicacionForm = ref('')
const cargandoUbicacion = ref(false)
const errorUbicacion = ref('')

const resumenStock = computed(() => {
  const totalEntradas = historialMovimientos.value
    .filter(m => m.tipo === 'entrada' && ['firmado', 'entregado'].includes(m.estado))
    .reduce((sum, m) => sum + parseFloat(m.cantidad || 0), 0)
  
  const totalSalidas = historialMovimientos.value
    .filter(m => m.tipo === 'salida' && ['firmado', 'entregado'].includes(m.estado))
    .reduce((sum, m) => sum + parseFloat(m.cantidad || 0), 0)
  
  return {
    totalEntradas,
    totalSalidas,
    stockActual: totalEntradas - totalSalidas
  }
})

async function verHistorialStock(material) {
  modalHistorial.value.visible = true
  modalHistorial.value.entidadId = material.id
  modalHistorial.value.nombreMaterial = material.nombre
  historialMovimientos.value = []
  cargandoHistorial.value = true
  errorHistorial.value = ''
  
  try {
    const params = {}
    if (almacenId.value) {
      params.almacen_ids = [almacenId.value]
    }
    const { data } = await axios.get(`/entidades/${material.id}/historial-stock`, { params })
    if (data.success) {
      historialMovimientos.value = data.data
    } else {
      errorHistorial.value = data.message || 'Error al cargar el historial'
    }
  } catch (e) {
    errorHistorial.value = e.response?.data?.message || e.message || 'Error al cargar el historial'
  } finally {
    cargandoHistorial.value = false
  }
}

function cerrarModalHistorial() {
  modalHistorial.value.visible = false
  modalHistorial.value.entidadId = null
  modalHistorial.value.nombreMaterial = ''
  historialMovimientos.value = []
  errorHistorial.value = ''
}

function formatearFecha(fecha) {
  if (!fecha) return '-'
  const d = new Date(fecha)
  return d.toLocaleString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function obtenerClaseEstado(estado) {
  const clases = {
    'borrador': 'bg-gray-100 text-gray-800',
    'pendiente_firma': 'bg-yellow-100 text-yellow-800',
    'firmado': 'bg-green-100 text-green-800',
    'entregado': 'bg-blue-100 text-blue-800'
  }
  return clases[estado] || 'bg-gray-100 text-gray-800'
}

// Funciones para gestión de ubicación
function abrirModalUbicacion(material) {
  modalUbicacion.value = {
    visible: true,
    material: material,
    ubicacionActual: material.ubicacion || ''
  }
  ubicacionForm.value = material.ubicacion || ''
  errorUbicacion.value = ''
}

function cerrarModalUbicacion() {
  modalUbicacion.value = {
    visible: false,
    material: null,
    ubicacionActual: ''
  }
  ubicacionForm.value = ''
  errorUbicacion.value = ''
}

async function guardarUbicacion() {
  if (!ubicacionForm.value.trim()) {
    errorUbicacion.value = 'La ubicación no puede estar vacía'
    return
  }

  cargandoUbicacion.value = true
  errorUbicacion.value = ''

  try {
    const params = {}
    if (almacenId.value) {
      params.almacen_ids = [almacenId.value]
    }

    const { data } = await axios.patch(`/entidades/${modalUbicacion.value.material.id}/ubicacion`, {
      ubicacion: ubicacionForm.value.trim(),
      almacen_ids: almacenId.value ? [almacenId.value] : null
    }, { params })

    if (data.success) {
      // Actualizar la ubicación en la lista local
      const index = items.value.findIndex(item => item.id === modalUbicacion.value.material.id)
      if (index !== -1) {
        items.value[index].ubicacion = ubicacionForm.value.trim()
      }
      
      alert('✓ Ubicación actualizada correctamente')
      cerrarModalUbicacion()
    } else {
      errorUbicacion.value = data.message || 'Error al actualizar la ubicación'
    }
  } catch (error) {
    console.error('Error actualizando ubicación:', error)
    errorUbicacion.value = error.response?.data?.message || 'Error al actualizar la ubicación'
  } finally {
    cargandoUbicacion.value = false
  }
}

onMounted(() => {
  load()
  cargarAlmacenes()
})
watch(() => props.refreshKey, load)
</script>
