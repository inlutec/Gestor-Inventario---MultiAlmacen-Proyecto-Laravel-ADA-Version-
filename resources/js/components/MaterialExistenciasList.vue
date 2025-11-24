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
                <button 
                  @click="abrirModalRegularizar(m)" 
                  class="px-3 py-1 text-xs bg-amber-500 text-white rounded hover:bg-amber-600 transition"
                  title="Regularizar stock por inventario"
                >
                  📋 Regularizar
                </button>
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
      params.almacen_seleccionado = almacenId.value
    }
    // Añadir timestamp para evitar caché del navegador
    const { data } = await axios.get('/material-movimientos/inventario', {
      params,
      headers: {
        'Cache-Control': 'no-cache',
        'Pragma': 'no-cache'
      }
    })
    if (data.success) {
      items.value = data.data
      // Forzar reactividad de Vue
      items.value = [...items.value]
    }
  } catch (e) {
    console.error('Error cargando inventario', e)
  } finally {
    loading.value = false
  }
}

function onAlmacenChange() {
  // Limpiar datos anteriores para evitar mezcla
  items.value = []
  // Pequeña demora para asegurar que el parámetro se actualice
  setTimeout(() => {
    load()
  }, 100)
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

onMounted(load)
watch(() => props.refreshKey, load)
</script>
