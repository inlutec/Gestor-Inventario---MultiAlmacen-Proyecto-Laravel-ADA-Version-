<template>
  <div class="space-y-4">
    <!-- Botones de acción -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <input v-model="filtro" type="text" placeholder="Buscar artículo..." class="border rounded px-3 py-2 w-80" />
        <button class="px-3 py-2 bg-slate-100 rounded" @click="cargarMateriales">Recargar</button>
      </div>
      <button class="px-4 py-2 bg-blue-600 text-white rounded" @click="abrirModalCrear">+ Nuevo artículo</button>
    </div>

    <!-- Tabla de artículos -->
    <div v-if="cargando" class="text-slate-500 text-sm">Cargando artículos...</div>
    <div v-else class="overflow-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="bg-slate-100 text-left">
            <th class="p-2 w-16">Foto</th>
            <th class="p-2">Referencia/Nombre</th>
            <th class="p-2">Descripción</th>
            <th class="p-2">Unidad</th>
            <th class="p-2 text-center">Stock mínimo</th>
            <th class="p-2 text-center w-24">Visible público</th>
            <th class="p-2 w-32">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in materialesFiltrados" :key="m.id" class="border-b hover:bg-slate-50">
            <td class="p-2">
              <img v-if="m.foto" :src="`/gestionmaterial/storage/${m.foto}`" class="w-12 h-12 object-cover rounded" />
              <div v-else class="w-12 h-12 bg-slate-200 rounded flex items-center justify-center text-slate-400 text-xs">Sin foto</div>
            </td>
            <td class="p-2 font-medium">{{ obtenerNombre(m) }}</td>
            <td class="p-2">{{ m.datos?.descripcion || '-' }}</td>
            <td class="p-2">{{ m.datos?.unidad || '-' }}</td>
            <td class="p-2 text-center">{{ m.datos?.stock_minimo || 0 }}</td>
            <td class="p-2 text-center">
              <span v-if="m.visible_publico" class="inline-block w-5 h-5 text-green-600">✓</span>
              <span v-else class="inline-block w-5 h-5 text-slate-400">—</span>
            </td>
            <td class="p-2">
              <div class="flex gap-1">
                <button class="px-2 py-1 border rounded text-xs" @click="abrirModalEditar(m)">Editar</button>
                <button class="px-2 py-1 border rounded text-xs text-red-600" @click="eliminar(m)">Eliminar</button>
              </div>
            </td>
          </tr>
          <tr v-if="!materialesFiltrados.length">
            <td class="p-3 text-center text-slate-500" colspan="8">No hay artículos. Crea uno nuevo.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal crear/editar -->
    <div v-if="modal.visible" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" @click.self="cerrarModal">
      <div class="bg-white rounded shadow w-[700px] max-w-[95vw] p-4 space-y-3 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">{{ modal.editar ? 'Editar artículo' : 'Nuevo artículo' }}</h3>
          <button class="text-slate-500" @click="cerrarModal">✕</button>
        </div>

        <div class="space-y-3">
          <!-- Foto del material -->
          <div class="border rounded p-3 bg-slate-50">
            <label class="block text-sm font-medium mb-2">Foto del material</label>
            <div class="flex items-start gap-4">
              <div class="flex-shrink-0">
                <img v-if="modal.fotoPreview" :src="modal.fotoPreview" class="w-32 h-32 object-cover rounded border" />
                <img v-else-if="modal.editar && modal.fotoActual" :src="`/gestionmaterial/storage/${modal.fotoActual}`" class="w-32 h-32 object-cover rounded border" />
                <div v-else class="w-32 h-32 bg-slate-200 rounded border flex items-center justify-center text-slate-400 text-sm">Sin foto</div>
              </div>
              <div class="flex-1 space-y-2">
                <input ref="fotoInput" type="file" accept="image/*" class="hidden" @change="seleccionarFoto" />
                <button class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm" @click="$refs.fotoInput.click()">
                  {{ modal.fotoPreview || modal.fotoActual ? 'Cambiar foto' : 'Subir foto' }}
                </button>
                <button v-if="modal.editar && modal.fotoActual && !modal.fotoPreview" class="px-3 py-1.5 border rounded text-sm ml-2" @click="eliminarFoto">
                  Eliminar foto
                </button>
                <p class="text-xs text-slate-500">Sube una foto del material. Se redimensionará automáticamente.</p>
              </div>
            </div>
          </div>

          <div class="grid sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium mb-1">Referencia/Nombre *</label>
              <input v-model="form.nombre" class="border rounded px-3 py-2 w-full" placeholder="Ej: Bolígrafos BIC azul" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Unidad *</label>
              <input v-model="form.unidad" class="border rounded px-3 py-2 w-full" placeholder="Ej: ud, caja, pack" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Categoría</label>
            <select v-model="form.categoria_id" class="border rounded px-3 py-2 w-full">
              <option :value="null">Sin categoría</option>
              <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                {{ cat.nombre }}
              </option>
            </select>
            <p class="text-xs text-gray-500 mt-1">Asigna el material a una categoría para navegación visual</p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Descripción</label>
            <textarea v-model="form.descripcion" class="border rounded px-3 py-2 w-full" rows="2" placeholder="Descripción detallada del artículo"></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Stock mínimo</label>
            <input v-model.number="form.stock_minimo" type="number" min="0" class="border rounded px-3 py-2 w-full" />
          </div>

          <!-- Checkbox visible al público -->
          <div class="border rounded p-3 bg-blue-50">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.visible_publico" type="checkbox" class="w-4 h-4 rounded border-gray-300" />
              <span class="text-sm font-medium">Mostrar en formulario público</span>
            </label>
            <p class="text-xs text-slate-600 mt-1 ml-6">
              Si está marcado, este material aparecerá en el formulario público de peticiones. Si no, solo será visible internamente.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
          <button class="px-4 py-2 bg-blue-600 text-white rounded" @click="guardar" :disabled="guardando">
            {{ guardando ? 'Guardando...' : 'Guardar' }}
          </button>
          <button class="px-4 py-2 border rounded" @click="cerrarModal">Cancelar</button>
          <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>
          <div v-if="exito" class="text-green-700 text-sm">{{ exito }}</div>
        </div>
      </div>
    </div>

    <!-- Modal Historial de Stock -->
    <div v-if="modalHistorial.visible" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="cerrarModalHistorial">
      <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
          <h2 class="text-2xl font-bold text-gray-800">
            📊 Historial de Stock - {{ modalHistorial.nombreMaterial }}
          </h2>
          <button @click="cerrarModalHistorial" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-4">
          <div v-if="cargandoHistorial" class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
          </div>

          <div v-else-if="errorHistorial" class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
            <p class="font-semibold">Error al cargar el historial</p>
            <p class="text-sm mt-1">{{ errorHistorial }}</p>
          </div>

          <div v-else-if="historialMovimientos.length > 0">
            <!-- Resumen de stock actual -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
              <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                  <div class="text-sm text-gray-600">Total Entradas</div>
                  <div class="text-3xl font-bold text-green-600">{{ resumenStock.totalEntradas }}</div>
                </div>
                <div>
                  <div class="text-sm text-gray-600">Total Salidas</div>
                  <div class="text-3xl font-bold text-red-600">{{ resumenStock.totalSalidas }}</div>
                </div>
                <div>
                  <div class="text-sm text-gray-600">Stock Actual</div>
                  <div class="text-3xl font-bold text-blue-600">{{ resumenStock.stockActual }}</div>
                </div>
              </div>
            </div>

            <!-- Tabla de movimientos -->
            <div class="overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead>
                  <tr class="bg-gray-100 text-left">
                    <th class="p-2">Fecha</th>
                    <th class="p-2">Documento</th>
                    <th class="p-2">Tipo</th>
                    <th class="p-2">Cantidad</th>
                    <th class="p-2">Origen</th>
                    <th class="p-2">Destino</th>
                    <th class="p-2">Estado</th>
                    <th class="p-2">Observaciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="mov in historialMovimientos" :key="mov.id" class="border-b hover:bg-gray-50">
                    <td class="p-2">{{ formatearFecha(mov.fecha_movimiento) }}</td>
                    <td class="p-2 font-medium">{{ mov.numero_documento }}</td>
                    <td class="p-2">
                      <span :class="mov.tipo === 'entrada' ? 'px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold' : 'px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-semibold'">
                        {{ mov.tipo === 'entrada' ? '⬆️ ENTRADA' : '⬇️ SALIDA' }}
                      </span>
                    </td>
                    <td class="p-2">
                      <span :class="mov.tipo === 'entrada' ? 'text-green-700 font-bold' : 'text-red-700 font-bold'">
                        {{ mov.tipo === 'entrada' ? '+' : '-' }}{{ mov.cantidad }} {{ mov.unidad }}
                      </span>
                    </td>
                    <td class="p-2 text-xs">{{ mov.origen || '-' }}</td>
                    <td class="p-2 text-xs">{{ mov.destino || '-' }}</td>
                    <td class="p-2">
                      <span :class="obtenerClaseEstado(mov.estado)" class="px-2 py-1 rounded text-xs">
                        {{ mov.estado }}
                      </span>
                    </td>
                    <td class="p-2 text-xs">{{ mov.observaciones || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-else class="text-center py-12 text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-lg">No hay movimientos registrados para este artículo</p>
          </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end">
          <button @click="cerrarModalHistorial" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-medium">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const emit = defineEmits(['updated'])

const materiales = ref([])
const categorias = ref([])
const cargando = ref(false)
const filtro = ref('')

const modal = ref({ visible: false, editar: false, id: null, fotoPreview: null, fotoActual: null, fotoFile: null })
const form = ref({ nombre: '', descripcion: '', unidad: 'ud', ubicacion: '', stock_minimo: 0, categoria_id: null, visible_publico: true })
const guardando = ref(false)
const error = ref('')
const exito = ref('')
const fotoInput = ref(null)

// Historial de stock
const modalHistorial = ref({ visible: false, entidadId: null, nombreMaterial: '' })
const historialMovimientos = ref([])
const cargandoHistorial = ref(false)
const errorHistorial = ref('')

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

const materialesFiltrados = computed(() => {
  const q = filtro.value.trim().toLowerCase()
  if (!q) return materiales.value
  return materiales.value.filter(m => {
    const nombre = obtenerNombre(m).toLowerCase()
    const desc = (m.datos?.descripcion || '').toLowerCase()
    const ubi = (m.datos?.ubicacion || '').toLowerCase()
    return nombre.includes(q) || desc.includes(q) || ubi.includes(q)
  })
})

function obtenerNombre(m) {
  const d = m.datos || {}
  return d.referencia || d.nombre || `Material ${m.id}`
}

async function cargarMateriales() {
  cargando.value = true
  try {
    const { data } = await axios.get('/entidades', { params: { tipo_entidad_id: 3 } })
    materiales.value = data.success ? data.data : []
  } catch (e) {
    console.error('Error cargando materiales', e)
  } finally {
    cargando.value = false
  }
}

async function cargarCategorias() {
  try {
    const { data } = await axios.get('/config/categorias', { params: { activo: 1 } })
    categorias.value = data.success ? data.data : []
  } catch (e) {
    console.error('Error cargando categorías', e)
  }
}

function abrirModalCrear() {
  modal.value = { visible: true, editar: false, id: null, fotoPreview: null, fotoActual: null, fotoFile: null }
  form.value = { nombre: '', descripcion: '', unidad: 'ud', ubicacion: '', stock_minimo: 0, categoria_id: null, visible_publico: true }
  error.value = ''
  exito.value = ''
}

function abrirModalEditar(m) {
  modal.value = { visible: true, editar: true, id: m.id, fotoPreview: null, fotoActual: m.foto || null, fotoFile: null }
  const d = m.datos || {}
  form.value = {
    nombre: d.referencia || d.nombre || '',
    descripcion: d.descripcion || '',
    unidad: d.unidad || 'ud',
    stock_minimo: d.stock_minimo || 0,
    categoria_id: m.categoria_id || null,
    visible_publico: m.visible_publico !== undefined ? m.visible_publico : true,
  }
  error.value = ''
  exito.value = ''
}

function cerrarModal() {
  modal.value = { visible: false, editar: false, id: null, fotoPreview: null, fotoActual: null, fotoFile: null }
}

function seleccionarFoto(e) {
  const file = e.target.files[0]
  if (!file) return
  
  if (!file.type.startsWith('image/')) {
    error.value = 'Solo se permiten imágenes'
    return
  }
  
  if (file.size > 2 * 1024 * 1024) {
    error.value = 'La imagen no puede superar 2MB'
    return
  }
  
  modal.value.fotoFile = file
  
  const reader = new FileReader()
  reader.onload = (e) => {
    modal.value.fotoPreview = e.target.result
  }
  reader.readAsDataURL(file)
}

async function eliminarFoto() {
  if (!confirm('¿Eliminar la foto del material?')) return
  
  try {
    const { data } = await axios.put(`/entidades/${modal.value.id}`, { foto: null })
    if (data.success) {
      modal.value.fotoActual = null
      modal.value.fotoPreview = null
      modal.value.fotoFile = null
      exito.value = 'Foto eliminada'
      await cargarMateriales()
    }
  } catch (e) {
    error.value = 'Error al eliminar foto: ' + (e.response?.data?.message || e.message)
  }
}

async function guardar() {
  error.value = ''
  exito.value = ''
  if (!form.value.nombre || !form.value.unidad) {
    error.value = 'Nombre y unidad son obligatorios'
    return
  }

  guardando.value = true
  try {
    const datos = {
      referencia: form.value.nombre,
      nombre: form.value.nombre,
      unidad: form.value.unidad,
    }
    if (form.value.descripcion) datos.descripcion = form.value.descripcion
    datos.stock_minimo = form.value.stock_minimo || 0

    const payload = {
      tipo_entidad_id: 3, // Pequeño material
      datos: datos,
      categoria_id: form.value.categoria_id,
      visible_publico: form.value.visible_publico,
    }

    let res
    if (modal.value.editar) {
      res = await axios.put(`/entidades/${modal.value.id}`, payload)
    } else {
      res = await axios.post('/entidades', payload)
    }

    if (res.data.success) {
      const entidadId = modal.value.editar ? modal.value.id : res.data.data.id
      
      // Subir foto si se seleccionó una nueva
      if (modal.value.fotoFile) {
        const fotoFormData = new FormData()
        fotoFormData.append('foto', modal.value.fotoFile)
        
        try {
          await axios.post(`/entidades/${entidadId}/upload-foto-material`, fotoFormData, {
            headers: { 'Content-Type': 'multipart/form-data' }
          })
        } catch (fotoError) {
          console.error('Error subiendo foto:', fotoError)
          error.value = 'Material guardado pero error al subir foto'
        }
      }
      
      exito.value = modal.value.editar ? 'Artículo actualizado' : 'Artículo creado'
      await cargarMateriales()
      emit('updated')
      setTimeout(cerrarModal, 600)
    } else {
      error.value = res.data.message || 'Error al guardar'
    }
  } catch (e) {
    const errMsg = e.response?.data?.message || e.message
    const errDetails = e.response?.data?.errors ? JSON.stringify(e.response.data.errors) : ''
    error.value = errMsg + (errDetails ? ' - ' + errDetails : '')
  } finally {
    guardando.value = false
  }
}

async function eliminar(m) {
  if (!confirm(`¿Eliminar "${obtenerNombre(m)}"?`)) return
  try {
    const { data } = await axios.delete(`/entidades/${m.id}`)
    if (data.success) {
      await cargarMateriales()
      emit('updated')
    }
  } catch (e) {
    alert('Error al eliminar: ' + (e.response?.data?.message || e.message))
  }
}

async function verHistorialStock(material) {
  modalHistorial.value.visible = true
  modalHistorial.value.entidadId = material.id
  modalHistorial.value.nombreMaterial = obtenerNombre(material)
  historialMovimientos.value = []
  cargandoHistorial.value = true
  errorHistorial.value = ''
  
  try {
    const { data } = await axios.get(`/entidades/${material.id}/historial-stock`)
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

onMounted(() => {
  cargarMateriales()
  cargarCategorias()
})
</script>
