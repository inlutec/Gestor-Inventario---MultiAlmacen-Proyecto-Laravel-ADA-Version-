<template>
  <div class="p-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
      <h1 class="text-2xl font-bold">Peticiones de Material</h1>
      <div class="flex flex-wrap items-center gap-3">
        <AlmacenSelector v-model="almacenId" @change="onAlmacenChange" />
        <div class="flex gap-2">
          <button @click="filtroEstado = 'pendiente'" :class="btnClass('pendiente')">
            Pendientes ({{ contadores.pendiente }})
          </button>
          <button @click="filtroEstado = 'aprobado'" :class="btnClass('aprobado')">
            Aprobadas ({{ contadores.aprobado }})
          </button>
          <button @click="filtroEstado = 'denegado'" :class="btnClass('denegado')">
            Denegadas ({{ contadores.denegado }})
          </button>
          <button @click="filtroEstado = null" :class="btnClass(null)">Todas</button>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-8">Cargando...</div>

    <div v-else-if="!peticionesFiltradas.length" class="text-center py-8 text-gray-500">
      No hay peticiones {{ filtroEstado || '' }}
    </div>

    <div v-else class="space-y-4">
      <div v-for="p in peticionesFiltradas" :key="p.id" class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start mb-4">
          <div>
            <div class="text-lg font-semibold">Petición #{{ p.numero_pedido }}</div>
            <div class="text-sm text-gray-600">{{ p.materiales.length }} {{ p.materiales.length === 1 ? 'material solicitado' : 'materiales solicitados' }}</div>
          </div>
          <span :class="estadoClass(p.estado)">{{ p.estado.toUpperCase() }}</span>
        </div>

        <!-- Lista de materiales solicitados -->
        <div class="bg-gray-50 rounded-lg p-4 mb-4">
          <div class="font-medium text-sm mb-3 text-gray-700">Materiales solicitados:</div>
          <div class="space-y-2">
            <div v-for="(mat, idx) in p.materiales" :key="mat.id" class="bg-white rounded p-3 border border-gray-200">
              <div class="flex justify-between items-start">
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <span class="text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded font-medium">#{{ idx + 1 }}</span>
                    <div class="font-semibold text-gray-900">{{ mat.referencia }}</div>
                  </div>
                  <div class="text-sm text-gray-700 mt-1">{{ mat.nombre }}</div>
                </div>
                <div class="text-right ml-4">
                  <div class="font-bold text-lg">{{ mat.cantidad_solicitada }} {{ mat.unidad }}</div>
                  <div v-if="mat.cantidad_aprobada && mat.cantidad_aprobada !== mat.cantidad_solicitada" class="text-sm text-orange-600 font-medium">
                    Aprobado: {{ mat.cantidad_aprobada }} {{ mat.unidad }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="grid md:grid-cols-3 gap-4 mb-4 text-sm border-t pt-4">
          <div>
            <span class="font-medium">Solicitante:</span> {{ p.usuario_solicitante }}<br />
            <span class="font-medium">Email:</span> {{ p.email_solicitante }}<br />
            <span v-if="p.telefono_solicitante"><span class="font-medium">Tel:</span> {{ p.telefono_solicitante }}</span>
          </div>
          <div>
            <span class="font-medium">Fecha:</span> {{ formatFecha(p.created_at) }}
          </div>
          <div v-if="p.estado !== 'pendiente'">
            <span class="font-medium">Procesado por:</span> {{ p.aprobada_por?.nombre || 'N/A' }}<br />
            <span class="font-medium">Fecha:</span> {{ formatFecha(p.fecha_aprobacion) }}
          </div>
        </div>

        <div class="border-t pt-3 mb-3">
          <div class="font-medium text-sm mb-1">Justificación:</div>
          <div class="text-sm text-gray-700">{{ p.justificacion }}</div>
        </div>

        <div v-if="p.observaciones_admin" class="bg-gray-50 rounded p-3 mb-3 text-sm">
          <div class="font-medium mb-1">Observaciones del administrador:</div>
          {{ p.observaciones_admin }}
        </div>

        <div v-if="p.comentarios_aprobacion" class="bg-blue-50 rounded p-3 mb-3 text-sm border border-blue-200">
          <div class="font-medium mb-1 text-blue-900">💬 Comentarios de aprobación:</div>
          {{ p.comentarios_aprobacion }}
        </div>

        <div v-if="p.aprobacion_parcial" class="bg-orange-50 rounded p-3 mb-3 text-sm border border-orange-200">
          <div class="font-medium text-orange-900">⚠️ Aprobación Parcial</div>
          <div class="text-orange-800">Se aprobó {{ p.cantidad_aprobada }} de {{ p.cantidad }} {{ p.unidad }} solicitados</div>
        </div>

        <!-- Acciones -->
        <div v-if="p.estado === 'pendiente'" class="flex gap-2 pt-3 border-t">
          <button @click="abrirModalAprobar(p)" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
            Aprobar
          </button>
          <button @click="abrirModalDenegar(p)" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
            Denegar
          </button>
          <button @click="verHistorial(p)" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
            📜 Historial
          </button>
        </div>

        <div v-else class="pt-3 border-t flex gap-2">
          <button @click="verHistorial(p)" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm">
            📜 Ver Historial
          </button>
          <router-link v-if="p.estado === 'aprobada' && p.movimiento_id" :to="`/historico?movimiento=${p.movimiento_id}`" class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700 text-sm">
            Ver movimiento asociado →
          </router-link>
        </div>
      </div>
    </div>

    <!-- Modal Aprobar -->
    <div v-if="modalAprobar" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="modalAprobar = null">
      <div class="bg-white rounded-lg p-6 max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-bold mb-4">Aprobar Petición</h2>
        <div class="space-y-4">
          
          <!-- Lista de materiales -->
          <div class="bg-blue-50 p-4 rounded border border-blue-200">
            <div class="font-semibold text-blue-900 mb-3">Materiales Solicitados:</div>
            <div class="space-y-3">
              <div v-for="(material, index) in modalAprobar.materiales" :key="material.detalle_id" 
                   class="bg-white p-3 rounded border">
                <div class="flex items-start justify-between gap-4">
                  <div class="flex-1">
                    <div class="font-medium text-gray-900">
                      {{ material.referencia }} - {{ material.nombre }}
                    </div>
                    <div class="text-sm text-gray-600 mt-1">
                      Solicitado: <strong>{{ material.cantidad_solicitada }} {{ material.unidad }}</strong>
                    </div>
                  </div>
                  <div class="w-32">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Aprobar:</label>
                    <input 
                      v-model.number="formAprobar.materiales[index].cantidad_aprobada" 
                      type="number" 
                      :max="material.cantidad_solicitada"
                      min="0"
                      step="0.01"
                      class="w-full border rounded px-2 py-1 text-sm" 
                    />
                    <div v-if="formAprobar.materiales[index].cantidad_aprobada < material.cantidad_solicitada" 
                         class="text-xs text-orange-600 mt-1">
                      ⚠️ Parcial
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Comentarios de aprobación</label>
            <textarea 
              v-model="formAprobar.comentarios_aprobacion" 
              class="w-full border rounded px-3 py-2" 
              rows="3"
              placeholder="Explica si hay cambios en las cantidades o cualquier otra observación..."></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Origen</label>
              <input v-model="formAprobar.origen" type="text" class="w-full border rounded px-3 py-2" placeholder="Ej: Almacén General" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Destino</label>
              <input v-model="formAprobar.destino" type="text" class="w-full border rounded px-3 py-2" placeholder="Ej: Oficina Sevilla" />
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium mb-1">Observaciones administrativas</label>
            <textarea v-model="formAprobar.observaciones_admin" class="w-full border rounded px-3 py-2" rows="2"></textarea>
          </div>
          
          <div class="flex gap-2 justify-end pt-4 border-t">
            <button @click="modalAprobar = null" class="px-4 py-2 border rounded hover:bg-gray-100">Cancelar</button>
            <button @click="aprobarPeticion" :disabled="procesando" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
              {{ procesando ? 'Procesando...' : 'Aprobar' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Denegar -->
    <div v-if="modalDenegar" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="modalDenegar = null">
      <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <h2 class="text-xl font-bold mb-4">Denegar Petición</h2>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Motivo de la denegación *</label>
            <textarea v-model="formDenegar.observaciones_admin" required class="w-full border rounded px-3 py-2" rows="4" placeholder="Explica el motivo de la denegación..."></textarea>
          </div>
          <div class="flex gap-2 justify-end">
            <button @click="modalDenegar = null" class="px-4 py-2 border rounded hover:bg-gray-100">Cancelar</button>
            <button @click="denegarPeticion" :disabled="procesando" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
              {{ procesando ? 'Procesando...' : 'Denegar' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Historial -->
    <div v-if="modalHistorial" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="modalHistorial = null">
      <div class="bg-white rounded-lg p-6 max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-bold mb-4">📜 Historial de Cambios</h2>
        
        <div v-if="loadingHistorial" class="text-center py-8">Cargando historial...</div>
        
        <div v-else-if="!historial.length" class="text-center py-8 text-gray-500">
          No hay cambios registrados para esta petición
        </div>

        <div v-else class="space-y-4">
          <div v-for="cambio in historial" :key="cambio.id" class="border rounded-lg p-4 bg-gray-50">
            <div class="flex justify-between items-start mb-2">
              <div>
                <span :class="accionClass(cambio.accion)" class="px-2 py-1 rounded text-xs font-semibold">
                  {{ accionLabel(cambio.accion) }}
                </span>
                <span class="text-sm text-gray-600 ml-2">por <strong>{{ cambio.usuario?.nombre || 'Sistema' }}</strong></span>
              </div>
              <div class="text-xs text-gray-500">{{ formatFecha(cambio.created_at) }}</div>
            </div>
            
            <div v-if="cambio.comentario" class="text-sm text-gray-700 mb-2">
              {{ cambio.comentario }}
            </div>

            <div v-if="cambio.ip_address" class="text-xs text-gray-400">
              IP: {{ cambio.ip_address }}
            </div>
          </div>
        </div>

        <div class="flex justify-end mt-6 pt-4 border-t">
          <button @click="modalHistorial = null" class="px-4 py-2 border rounded hover:bg-gray-100">Cerrar</button>
        </div>
      </div>
    </div>

    <!-- Modal Historial de Auditoría (Nuevo) -->
    <HistorialPeticionModal
      :mostrar="mostrarHistorialAuditoria"
      :entidad-id="peticionSeleccionada?.id"
      :numero-documento="peticionSeleccionada?.numero_pedido"
      tipo-entidad="Petición"
      :api-endpoint="peticionSeleccionada ? `/peticiones/${peticionSeleccionada.id}/historial-auditoria` : ''"
      @cerrar="cerrarHistorialAuditoria"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import HistorialPeticionModal from '../components/HistorialPeticionModal.vue'
import AlmacenSelector from '../components/AlmacenSelector.vue'

const peticiones = ref([])
const loading = ref(true)
const filtroEstado = ref('pendiente')
const modalAprobar = ref(null)
const modalDenegar = ref(null)
const modalHistorial = ref(null)
const mostrarHistorialAuditoria = ref(false)
const peticionSeleccionada = ref(null)
const procesando = ref(false)
const historial = ref([])
const loadingHistorial = ref(false)
const almacenId = ref('')

const formAprobar = ref({ 
  origen: '', 
  destino: '', 
  observaciones_admin: '', 
  cantidad_aprobada: 0,
  comentarios_aprobacion: '' 
})
const formDenegar = ref({ observaciones_admin: '' })

const peticionesFiltradas = computed(() => {
  if (!filtroEstado.value) return peticiones.value
  return peticiones.value.filter(p => p.estado === filtroEstado.value)
})

const contadores = computed(() => ({
  pendiente: peticiones.value.filter(p => p.estado === 'pendiente').length,
  aprobado: peticiones.value.filter(p => p.estado === 'aprobado').length,
  denegado: peticiones.value.filter(p => p.estado === 'denegado').length
}))

function btnClass(estado) {
  const base = 'px-4 py-2 rounded text-sm font-medium transition'
  if (filtroEstado.value === estado) {
    return `${base} bg-emerald-600 text-white`
  }
  return `${base} bg-gray-200 text-gray-700 hover:bg-gray-300`
}

function estadoClass(estado) {
  const base = 'px-3 py-1 rounded-full text-xs font-bold'
  if (estado === 'pendiente') return `${base} bg-yellow-100 text-yellow-800`
  if (estado === 'aprobado') return `${base} bg-green-100 text-green-800`
  if (estado === 'denegado') return `${base} bg-red-100 text-red-800`
  return base
}

function formatFecha(fecha) {
  if (!fecha) return 'N/A'
  return new Date(fecha).toLocaleString('es-ES')
}

async function cargarPeticiones() {
  loading.value = true
  try {
    const params = {}
    if (almacenId.value) {
      params.almacen_ids = [almacenId.value]
    }
    const { data } = await axios.get('/peticiones', { params })
    if (data.success) {
      peticiones.value = data.data
    }
  } catch (e) {
    console.error('Error cargando peticiones', e)
  } finally {
    loading.value = false
  }
}

function onAlmacenChange() {
  cargarPeticiones()
}

function abrirModalAprobar(p) {
  modalAprobar.value = p
  
  // Inicializar materiales con sus cantidades aprobadas por defecto = cantidad solicitada
  formAprobar.value = { 
    origen: 'Almacén General', 
    destino: p.usuario_solicitante || '', 
    observaciones_admin: '',
    comentarios_aprobacion: '',
    materiales: p.materiales.map(m => ({
      detalle_id: m.detalle_id,
      cantidad_aprobada: m.cantidad_solicitada
    }))
  }
}

function abrirModalDenegar(p) {
  modalDenegar.value = p
  formDenegar.value = { observaciones_admin: '' }
}

async function aprobarPeticion() {
  if (!formAprobar.value.origen || !formAprobar.value.destino) return
  
  procesando.value = true
  try {
    const { data } = await axios.post(`/peticiones/${modalAprobar.value.id}/aprobar`, formAprobar.value)
    if (data.success) {
      await cargarPeticiones()
      modalAprobar.value = null
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error al aprobar')
  } finally {
    procesando.value = false
  }
}

async function denegarPeticion() {
  if (!formDenegar.value.observaciones_admin) return
  
  procesando.value = true
  try {
    const { data } = await axios.post(`/peticiones/${modalDenegar.value.id}/denegar`, formDenegar.value)
    if (data.success) {
      await cargarPeticiones()
      modalDenegar.value = null
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error al denegar')
  } finally {
    procesando.value = false
  }
}

async function verHistorial(p) {
  console.log('Abriendo historial para petición - OBJETO ORIGINAL:', {
    id: p.id,
    numero_pedido: p.numero_pedido,
    movimiento_id: p.movimiento_id,
    tipo_id: typeof p.id,
    objeto_completo: JSON.parse(JSON.stringify(p)) // Clonar para evitar referencias
  })
  
  // Validar que el objeto tenga un ID válido
  if (!p || !p.id) {
    console.error('Error: La petición no tiene un ID válido', p)
    alert('Error: No se pudo obtener el ID de la petición. Por favor, recarga la página.')
    return
  }
  
  // CRÍTICO: Asegurar que estamos usando el ID del pedido, NO el movimiento_id
  // Si el ID coincide con el movimiento_id, hay un problema de mapeo
  if (p.movimiento_id && p.movimiento_id === p.id) {
    console.error('ERROR CRÍTICO: El ID del pedido coincide con el movimiento_id. Esto indica un problema de mapeo.')
    console.error('ID recibido:', p.id, 'Movimiento ID:', p.movimiento_id)
    alert('Error: Hay un problema con el ID de la petición. Por favor, recarga la página.')
    return
  }
  
  // Asegurar que el ID sea numérico y válido
  const pedidoId = Number(p.id)
  if (isNaN(pedidoId) || pedidoId <= 0) {
    console.error('Error: ID de pedido inválido', p.id)
    alert('Error: ID de pedido inválido. Por favor, recarga la página.')
    return
  }
  
  // Crear una copia del objeto con el ID correcto
  const peticionConIdCorrecto = {
    ...p,
    id: pedidoId // Asegurar que sea el ID del pedido, no el movimiento_id
  }
  
  console.log('Petición con ID corregido:', {
    id: peticionConIdCorrecto.id,
    numero_pedido: peticionConIdCorrecto.numero_pedido,
    movimiento_id: peticionConIdCorrecto.movimiento_id
  })
  
  peticionSeleccionada.value = peticionConIdCorrecto
  mostrarHistorialAuditoria.value = true
}

function cerrarHistorialAuditoria() {
  mostrarHistorialAuditoria.value = false
  peticionSeleccionada.value = null
}

function accionClass(accion) {
  const base = 'px-2 py-1 rounded text-xs font-semibold'
  if (accion === 'aprobado') return `${base} bg-green-100 text-green-800`
  if (accion === 'aprobado_parcial') return `${base} bg-orange-100 text-orange-800`
  if (accion === 'denegado') return `${base} bg-red-100 text-red-800`
  if (accion === 'creado') return `${base} bg-blue-100 text-blue-800`
  if (accion === 'modificado') return `${base} bg-yellow-100 text-yellow-800`
  return `${base} bg-gray-100 text-gray-800`
}

function accionLabel(accion) {
  const labels = {
    'creado': 'Creado',
    'modificado': 'Modificado',
    'aprobado': 'Aprobado',
    'aprobado_parcial': 'Aprobado Parcialmente',
    'denegado': 'Denegado',
    'eliminado': 'Eliminado'
  }
  return labels[accion] || accion.toUpperCase()
}

onMounted(() => {
  cargarPeticiones()
})
</script>
