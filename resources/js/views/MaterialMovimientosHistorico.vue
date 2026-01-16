<template>
  <div class="movimientos-historico">
    <div class="mb-4">
      <h3 class="text-lg font-semibold mb-2">Histórico de Movimientos</h3>
      <p class="text-sm text-gray-600">Consulta de todos los movimientos de entrada y salida</p>
    </div>
    
    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Buscar
          </label>
          <input
            type="text"
            v-model="filtros.busqueda"
            @input="filtrarMovimientos"
            placeholder="Referencia, material, responsable..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Tipo
          </label>
          <select v-model="filtros.tipo" @change="cargarMovimientos" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            <option value="">Todos</option>
            <option value="entrada">Entradas</option>
            <option value="salida">Salidas</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Fecha Desde
          </label>
          <input
            type="date"
            v-model="filtros.fecha_desde"
            @change="cargarMovimientos"
            class="w-full px-3 py-2 border border-gray-300 rounded-md"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Fecha Hasta
          </label>
          <input
            type="date"
            v-model="filtros.fecha_hasta"
            @change="cargarMovimientos"
            class="w-full px-3 py-2 border border-gray-300 rounded-md"
          />
        </div>
      </div>
      
      <div class="mt-4">
        <button @click="limpiarFiltros" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
          <i class="fas fa-times mr-2"></i>Limpiar Filtros
        </button>
      </div>
    </div>

    <!-- Tabla de movimientos -->
    <div class="bg-white rounded-lg shadow p-4">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Referencia
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Material
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Tipo
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Cantidad
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Fecha
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Responsable
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Estado
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="cargando">
              <td colspan="8" class="text-center py-4">
                <div class="spinner-border text-primary"></div>
              </td>
            </tr>
            <tr v-else-if="movimientosFiltrados.length === 0">
              <td colspan="8" class="text-center py-8 text-gray-500">
                No se encontraron movimientos
              </td>
            </tr>
            <tr v-else v-for="movimiento in movimientosFiltrados" :key="movimiento.id" style="position: relative;">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ movimiento.numero_documento }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ movimiento.material_nombre }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getTipoClass(movimiento.tipo)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                  {{ movimiento.tipo === 'entrada' ? '⬆️ ENTRADA' : '⬇️ SALIDA' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <span :class="getCantidadClass(movimiento.tipo)">
                  {{ movimiento.tipo === 'entrada' ? '+' : '-' }}{{ movimiento.cantidad }} {{ movimiento.unidad }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(movimiento.fecha_movimiento) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ movimiento.responsable?.nombre || 'Sistema' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getEstadoClass(movimiento.estado)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                  {{ getEstadoTexto(movimiento.estado) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="btn-group btn-group-sm">
                  <button
                    @click="verMovimiento(movimiento)"
                    class="btn btn-info"
                    title="Ver detalles"
                  >
                    <i class="fas fa-eye"></i>
                  </button>
                  
                  <!-- Dropdown de acciones -->
                  <div class="dropdown">
                    <button 
                      class="btn btn-secondary dropdown-toggle" 
                      type="button" 
                      @click="toggleMenu(movimiento.id, $event)"
                      title="Más acciones"
                    >
                      <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div 
                      v-show="menuAbierto === movimiento.id"
                      class="dropdown-menu dropdown-menu-right"
                      style="position: absolute; right: 0; top: 100%; z-index: 1000;"
                    >
                      <button 
                        @click="imprimirMovimiento(movimiento)" 
                        class="dropdown-item"
                      >
                        <i class="fas fa-print mr-2"></i>Imprimir
                      </button>
                      <button 
                        v-if="movimiento.estado === 'pendiente'"
                        @click="aprobarMovimiento(movimiento)" 
                        class="dropdown-item"
                      >
                        <i class="fas fa-check-circle mr-2"></i>Aprobar
                      </button>
                      <button 
                        v-if="movimiento.estado === 'pendiente'"
                        @click="rechazarMovimiento(movimiento)" 
                        class="dropdown-item text-warning"
                      >
                        <i class="fas fa-times-circle mr-2"></i>Rechazar
                      </button>
                      <div class="dropdown-divider"></div>
                      <button 
                        v-if="movimiento.tipo === 'salida' && movimiento.enlace_publico"
                        @click="verAlbaranPublico(movimiento)" 
                        class="dropdown-item"
                      >
                        <i class="fas fa-external-link-alt mr-2"></i>Ver Albarán Público
                      </button>
                      <button 
                        v-if="movimiento.justificante_id"
                        @click="verJustificante(movimiento)" 
                        class="dropdown-item"
                      >
                        <i class="fas fa-file-alt mr-2"></i>Ver Justificante
                      </button>
                      <button 
                        v-if="['pendiente', 'rechazado'].includes(movimiento.estado)"
                        @click="eliminarMovimiento(movimiento)" 
                        class="dropdown-item text-danger"
                      >
                        <i class="fas fa-trash mr-2"></i>Eliminar
                      </button>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal para ver detalles del movimiento -->
    <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full" @click="closeModal">
      <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white" @click.stop>
        <div class="mt-3">
          <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
            Movimiento #{{ selectedMovimiento?.numero_documento }}
          </h3>
          <div v-if="selectedMovimiento" class="space-y-4">
            <div>
              <strong>Tipo:</strong> 
              <span :class="getTipoClass(selectedMovimiento.tipo)" class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                {{ selectedMovimiento.tipo === 'entrada' ? '⬆️ ENTRADA' : '⬇️ SALIDA' }}
              </span>
            </div>
            <div>
              <strong>Material:</strong> {{ selectedMovimiento.material_nombre }}
            </div>
            <div>
              <strong>Cantidad:</strong> 
              <span :class="getCantidadClass(selectedMovimiento.tipo)">
                {{ selectedMovimiento.tipo === 'entrada' ? '+' : '-' }}{{ selectedMovimiento.cantidad }} {{ selectedMovimiento.unidad }}
              </span>
            </div>
            <div>
              <strong>Fecha:</strong> {{ formatDate(selectedMovimiento.fecha_movimiento) }}
            </div>
            <div>
              <strong>Responsable:</strong> {{ selectedMovimiento.responsable?.nombre || 'Sistema' }}
            </div>
            <div>
              <strong>Estado:</strong> 
              <span :class="getEstadoClass(selectedMovimiento.estado)" class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                {{ getEstadoTexto(selectedMovimiento.estado) }}
              </span>
            </div>
            <div v-if="selectedMovimiento.observaciones">
              <strong>Observaciones:</strong>
              <p class="mt-1 text-gray-600">{{ selectedMovimiento.observaciones }}</p>
            </div>
            
            <!-- Comentarios del pedido asociado -->
            <div v-if="selectedMovimiento.pedido && selectedMovimiento.pedido.comentarios && selectedMovimiento.pedido.comentarios.length > 0" class="mt-4 pt-4 border-t border-gray-200">
              <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                <span>💬</span> Comentarios del Pedido #{{ selectedMovimiento.pedido.numero_pedido }}
              </h4>
              <div class="space-y-3">
                <div v-for="comentario in selectedMovimiento.pedido.comentarios" :key="comentario.id" class="bg-blue-50 rounded-lg p-3 border border-blue-200">
                  <div class="flex justify-between items-start mb-2">
                    <div class="flex-1">
                      <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ comentario.descripcion }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2 text-xs text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span v-if="comentario.usuario">{{ comentario.usuario.nombre }}</span>
                    <span v-else class="italic">Sistema</span>
                    <span class="text-gray-400">•</span>
                    <span>{{ comentario.fecha }}</span>
                    <span class="text-gray-400">•</span>
                    <span class="text-gray-500">{{ comentario.fecha_relativa }}</span>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Comentarios de aprobación del pedido -->
            <div v-if="selectedMovimiento.pedido && selectedMovimiento.pedido.comentarios_aprobacion" class="mt-4 pt-4 border-t border-gray-200">
              <h4 class="font-semibold text-gray-900 mb-2">Comentarios de Aprobación</h4>
              <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-200">
                <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ selectedMovimiento.pedido.comentarios_aprobacion }}</p>
              </div>
            </div>
          </div>
          <div class="mt-6 flex justify-end space-x-3">
            <button
              @click="closeModal"
              class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
            >
              Cerrar
            </button>
            <button
              v-if="selectedMovimiento"
              @click="imprimirMovimiento(selectedMovimiento)"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
            >
              Imprimir
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

// Estado reactivo
const movimientos = ref([])
const cargando = ref(false)
const showModal = ref(false)
const selectedMovimiento = ref(null)
const menuAbierto = ref(null)

const filtros = ref({
  busqueda: '',
  tipo: '',
  fecha_desde: '',
  fecha_hasta: ''
})

// Computed properties
const movimientosFiltrados = computed(() => {
  let filtrados = movimientos.value
  
  if (filtros.value.tipo) {
    filtrados = filtrados.filter(m => m.tipo === filtros.value.tipo)
  }
  
  if (filtros.value.busqueda) {
    const searchTerm = filtros.value.busqueda.toLowerCase()
    filtrados = filtrados.filter(movimiento =>
      movimiento.numero_documento?.toLowerCase().includes(searchTerm) ||
      movimiento.material_nombre?.toLowerCase().includes(searchTerm) ||
      movimiento.responsable?.nombre?.toLowerCase().includes(searchTerm)
    )
  }
  
  if (filtros.value.fecha_desde) {
    filtrados = filtrados.filter(m => new Date(m.fecha_movimiento) >= new Date(filtros.value.fecha_desde))
  }
  
  if (filtros.value.fecha_hasta) {
    filtrados = filtrados.filter(m => new Date(m.fecha_movimiento) <= new Date(filtros.value.fecha_hasta))
  }
  
  return filtrados
})

// Métodos
const filtrarMovimientos = () => {
  // La filtración se hace automáticamente con el computed property
}

const limpiarFiltros = () => {
  filtros.value = {
    busqueda: '',
    tipo: '',
    fecha_desde: '',
    fecha_hasta: ''
  }
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const options = { year: 'numeric', month: 'long', day: 'numeric' }
  return new Date(dateString).toLocaleDateString('es-ES', options)
}

const getTipoClass = (tipo) => {
  return tipo === 'entrada' 
    ? 'bg-green-100 text-green-800' 
    : 'bg-red-100 text-red-800'
}

const getCantidadClass = (tipo) => {
  return tipo === 'entrada' 
    ? 'text-green-700 font-bold' 
    : 'text-red-700 font-bold'
}

const getEstadoClass = (estado) => {
  const classes = {
    'pendiente': 'bg-yellow-100 text-yellow-800',
    'aprobado': 'bg-green-100 text-green-800',
    'rechazado': 'bg-red-100 text-red-800',
    'entregado': 'bg-blue-100 text-blue-800',
    'firmado': 'bg-purple-100 text-purple-800'
  }
  return classes[estado] || 'bg-gray-100 text-gray-800'
}

const getEstadoTexto = (estado) => {
  const textos = {
    'pendiente': 'Pendiente',
    'aprobado': 'Aprobado',
    'rechazado': 'Rechazado',
    'entregado': 'Entregado',
    'firmado': 'Firmado'
  }
  return textos[estado] || estado
}

const verMovimiento = (movimiento) => {
  selectedMovimiento.value = movimiento
  showModal.value = true
  menuAbierto.value = null
}

const imprimirMovimiento = (movimiento) => {
  console.log('Imprimiendo movimiento:', movimiento.numero_documento)
  window.open(`/material-movimientos/${movimiento.id}/imprimir`, '_blank')
  menuAbierto.value = null
}

const aprobarMovimiento = async (movimiento) => {
  if (confirm(`¿Aprobar movimiento ${movimiento.numero_documento}?`)) {
    try {
      const res = await axios.put(`/material-movimientos/${movimiento.id}/aprobar`)
      if (res.data.success) {
        movimiento.estado = 'aprobado'
        alert('Movimiento aprobado correctamente')
      }
    } catch (error) {
      console.error('Error aprobando movimiento:', error)
      alert('Error al aprobar el movimiento')
    }
  }
  menuAbierto.value = null
}

const rechazarMovimiento = async (movimiento) => {
  if (confirm(`¿Rechazar movimiento ${movimiento.numero_documento}?`)) {
    try {
      const res = await axios.put(`/material-movimientos/${movimiento.id}/rechazar`)
      if (res.data.success) {
        movimiento.estado = 'rechazado'
        alert('Movimiento rechazado correctamente')
      }
    } catch (error) {
      console.error('Error rechazando movimiento:', error)
      alert('Error al rechazar el movimiento')
    }
  }
  menuAbierto.value = null
}

const verAlbaranPublico = (movimiento) => {
  if (movimiento.enlace_publico) {
    window.open(movimiento.enlace_publico, '_blank')
  }
  menuAbierto.value = null
}

const verJustificante = (movimiento) => {
  if (movimiento.justificante_id) {
    window.open(`/justificantes/${movimiento.justificante_id}`, '_blank')
  }
  menuAbierto.value = null
}

const eliminarMovimiento = async (movimiento) => {
  if (confirm(`¿Eliminar movimiento ${movimiento.numero_documento}?`)) {
    try {
      const res = await axios.delete(`/material-movimientos/${movimiento.id}`)
      if (res.data.success) {
        const index = movimientos.value.findIndex(m => m.id === movimiento.id)
        if (index > -1) {
          movimientos.value.splice(index, 1)
        }
        alert('Movimiento eliminado correctamente')
      }
    } catch (error) {
      console.error('Error eliminando movimiento:', error)
      alert('Error al eliminar el movimiento')
    }
  }
  menuAbierto.value = null
}

const closeModal = () => {
  showModal.value = false
  selectedMovimiento.value = null
}

// Función toggleMenu mencionada en el archivo original
const toggleMenu = (movimientoId, event) => {
  if (event) event.stopPropagation()
  menuAbierto.value = menuAbierto.value === movimientoId ? null : movimientoId
}

// Cerrar menú al hacer clic fuera
document.addEventListener('click', () => {
  menuAbierto.value = null
})

// Método para cargar movimientos desde la API
const cargarMovimientos = async () => {
  try {
    cargando.value = true
    const params = {}
    
    if (filtros.value.tipo) params.tipo = filtros.value.tipo
    if (filtros.value.fecha_desde) params.fecha_desde = filtros.value.fecha_desde
    if (filtros.value.fecha_hasta) params.fecha_hasta = filtros.value.fecha_hasta
    
    const { data } = await axios.get('/material-movimientos', { params })
    if (data.success) {
      movimientos.value = data.data || []
    } else {
      console.error('Error en respuesta:', data)
      movimientos.value = []
    }
  } catch (error) {
    console.error('Error cargando movimientos:', error)
    movimientos.value = []
  } finally {
    cargando.value = false
  }
}

// Lifecycle hooks
onMounted(() => {
  cargarMovimientos()
})
</script>

<style scoped>
.movimientos-historico {
  max-width: 100%;
  margin: 0 auto;
}

/* Estilos para el dropdown */
.btn-group {
  position: relative;
  display: inline-flex;
  vertical-align: middle;
}

.btn {
  display: inline-block;
  font-weight: 400;
  text-align: center;
  white-space: nowrap;
  vertical-align: middle;
  user-select: none;
  border: 1px solid transparent;
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
  line-height: 1.5;
  border-radius: 0.25rem;
  transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  cursor: pointer;
}

.btn-info {
  color: #fff;
  background-color: #17a2b8;
  border-color: #17a2b8;
}

.btn-info:hover {
  color: #fff;
  background-color: #138496;
  border-color: #117a8b;
}

.btn-secondary {
  color: #fff;
  background-color: #6c757d;
  border-color: #6c757d;
}

.btn-secondary:hover {
  color: #fff;
  background-color: #5a6268;
  border-color: #545b62;
}

.btn-group-sm > .btn, .btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
  line-height: 1.5;
  border-radius: 0.2rem;
}

.dropdown {
  position: relative;
  display: inline-block;
}

.dropdown-toggle {
  background-image: none;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  z-index: 1000;
  display: none;
  float: left;
  min-width: 10rem;
  padding: 0.5rem 0;
  margin: 0.125rem 0 0;
  font-size: 0.875rem;
  color: #212529;
  text-align: left;
  list-style: none;
  background-color: #fff;
  background-clip: padding-box;
  border: 1px solid rgba(0, 0, 0, 0.15);
  border-radius: 0.25rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
}

.dropdown-menu-right {
  right: 0;
  left: auto;
}

.dropdown-menu.show {
  display: block;
}

.dropdown-item {
  display: block;
  width: 100%;
  padding: 0.25rem 1.5rem;
  clear: both;
  font-weight: 400;
  color: #212529;
  text-align: inherit;
  white-space: nowrap;
  background-color: transparent;
  border: 0;
  cursor: pointer;
}

.dropdown-item:hover, .dropdown-item:focus {
  color: #16181b;
  text-decoration: none;
  background-color: #f8f9fa;
}

.dropdown-item.active, .dropdown-item:active {
  color: #fff;
  text-decoration: none;
  background-color: #007bff;
}

.dropdown-item.text-danger {
  color: #dc3545;
}

.dropdown-item.text-danger:hover, .dropdown-item.text-danger:focus {
  color: #721c24;
  background-color: #f8d7da;
}

.dropdown-item.text-warning {
  color: #ffc107;
}

.dropdown-item.text-warning:hover, .dropdown-item.text-warning:focus {
  color: #856404;
  background-color: #fff3cd;
}

.dropdown-divider {
  height: 0;
  margin: 0.5rem 0;
  overflow: hidden;
  border-top: 1px solid #e9ecef;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

/* Spinner */
.spinner-border {
  display: inline-block;
  width: 2rem;
  height: 2rem;
  vertical-align: text-bottom;
  border: 0.25em solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: spinner-border .75s linear infinite;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
  border-width: 0.125em;
}

.text-primary {
  color: #007bff !important;
}

@keyframes spinner-border {
  to {
    transform: rotate(360deg);
  }
}
</style>