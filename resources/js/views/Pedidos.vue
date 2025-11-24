<template>
  <div class="pedidos-container p-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-1"><i class="fas fa-shopping-cart mr-2"></i>Gestión de Pedidos</h1>
        <p class="text-muted small mb-0">Administración de pedidos de consumibles</p>
      </div>
      <button @click="abrirFormularioCrear" class="btn btn-primary">
        <i class="fas fa-plus mr-1"></i>Nuevo Pedido
      </button>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4" v-if="estadisticas">
      <div class="col-md-3">
        <div class="card border-left-warning shadow h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pendientes</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ estadisticas.pendientes }}</div>
              </div>
              <div class="col-auto">
                <i class="fas fa-clock fa-2x text-gray-300"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-left-info shadow h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Enviados</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ estadisticas.enviados }}</div>
              </div>
              <div class="col-auto">
                <i class="fas fa-shipping-fast fa-2x text-gray-300"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-left-success shadow h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Recibidos</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ estadisticas.recibidos }}</div>
              </div>
              <div class="col-auto">
                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-left-primary shadow h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Gastado este mes</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ formatCurrency(estadisticas.total_gastado_mes) }}</div>
              </div>
              <div class="col-auto">
                <i class="fas fa-euro-sign fa-2x text-gray-300"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow mb-4">
      <div class="card-body">
        <div class="row">
          <div class="col-md-4">
            <input 
              v-model="filtros.search" 
              type="text" 
              class="form-control" 
              placeholder="Buscar pedido..."
              @input="buscar"
            >
          </div>
          <div class="col-md-3">
            <select v-model="filtros.estado" @change="cargarPedidos" class="form-control">
              <option value="">Todos los estados</option>
              <option value="pendiente">Pendiente</option>
              <option value="enviado">Enviado</option>
              <option value="recibido">Recibido</option>
              <option value="cancelado">Cancelado</option>
            </select>
          </div>
          <div class="col-md-3">
            <select v-model="filtros.proveedor" @change="cargarPedidos" class="form-control">
              <option value="">Todos los proveedores</option>
              <option v-for="prov in proveedoresUnicos" :key="prov" :value="prov">{{ prov }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <button @click="limpiarFiltros" class="btn btn-secondary btn-block">
              <i class="fas fa-times mr-1"></i>Limpiar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla de pedidos -->
    <div class="card shadow">
      <div class="card-body p-0">
        <div class="table-responsive" style="overflow-y: visible;">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th>Nº Pedido</th>
                <th>Proveedor</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Items</th>
                <th>Total</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="cargando">
                <td colspan="7" class="text-center py-4">
                  <div class="spinner-border text-primary"></div>
                </td>
              </tr>
              <tr v-else-if="pedidos.length === 0">
                <td colspan="7" class="text-center py-4 text-muted">No hay pedidos</td>
              </tr>
              <tr v-else v-for="pedido in pedidos" :key="pedido.id" style="position: relative;">
                <td><strong>{{ pedido.numero_pedido }}</strong></td>
                <td>{{ pedido.proveedor || '-' }}</td>
                <td>{{ formatDate(pedido.fecha_pedido) }}</td>
                <td><span :class="getEstadoBadge(pedido.estado)">{{ getEstadoTexto(pedido.estado) }}</span></td>
                <td><span class="badge badge-secondary">{{ pedido.detalles.length }}</span></td>
                <td><strong>{{ calcularTotal(pedido) }}</strong></td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button @click="verDetalle(pedido)" class="btn btn-info" title="Ver">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button v-if="pedido.estado === 'pendiente'" @click="editar(pedido)" class="btn btn-warning" title="Editar">
                      <i class="fas fa-edit"></i>
                    </button>
                    <div class="dropdown">
                      <button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                      </button>
                      <div class="dropdown-menu dropdown-menu-right">
                        <button v-if="pedido.estado === 'pendiente'" @click="marcarEnviado(pedido)" class="dropdown-item">
                          <i class="fas fa-shipping-fast mr-2"></i>Marcar enviado
                        </button>
                        <button v-if="['pendiente', 'enviado'].includes(pedido.estado)" @click="marcarRecibido(pedido)" class="dropdown-item">
                          <i class="fas fa-check-circle mr-2"></i>Marcar recibido
                        </button>
                        <button v-if="pedido.estado !== 'recibido' && pedido.estado !== 'cancelado'" @click="cancelarPedido(pedido)" class="dropdown-item text-warning">
                          <i class="fas fa-ban mr-2"></i>Cancelar
                        </button>
                        <div class="dropdown-divider"></div>
                        <button v-if="['pendiente', 'cancelado'].includes(pedido.estado)" @click="eliminar(pedido)" class="dropdown-item text-danger">
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
    </div>

    <!-- Modal: Crear/Editar Pedido -->
    <div v-if="modalFormulario.visible" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-shopping-cart mr-2"></i>
              {{ modalFormulario.editando ? 'Editar Pedido' : 'Nuevo Pedido' }}
            </h5>
            <button type="button" class="close" @click="cerrarFormulario">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body bg-white">
            <form @submit.prevent="guardarPedido">
              
              <!-- Información Básica del Pedido -->
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="font-weight-bold">Número de Pedido *</label>
                    <input 
                      v-model="formulario.numero_pedido" 
                      type="text" 
                      class="form-control" 
                      required
                      :disabled="modalFormulario.editando"
                      placeholder="PED-2025-0001"
                    >
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="font-weight-bold">Fecha de Pedido *</label>
                    <input 
                      v-model="formulario.fecha_pedido" 
                      type="date" 
                      class="form-control" 
                      required
                    >
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="font-weight-bold">Proveedor *</label>
                    <input 
                      v-model="formulario.proveedor"
                      type="text"
                      class="form-control"
                      list="proveedores-list"
                      placeholder="Nombre del proveedor"
                      required
                    >
                    <datalist id="proveedores-list">
                      <option v-for="prov in proveedoresDisponibles" :key="prov" :value="prov"></option>
                    </datalist>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="font-weight-bold">Contacto</label>
                    <input 
                      v-model="formulario.proveedor_contacto" 
                      type="text" 
                      class="form-control"
                      placeholder="Persona de contacto"
                    >
                  </div>
                </div>
              </div>

              <!-- Datos de Contacto del Proveedor -->
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="font-weight-bold">Email</label>
                    <input 
                      v-model="formulario.proveedor_email" 
                      type="email" 
                      class="form-control"
                      placeholder="email@proveedor.com"
                    >
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="font-weight-bold">Teléfono</label>
                    <input 
                      v-model="formulario.proveedor_telefono" 
                      type="tel" 
                      class="form-control"
                      placeholder="+34 900 123 456"
                    >
                  </div>
                </div>
              </div>

              <hr>

              <!-- Buscador y Tabla en paralelo -->
              <div class="row">
                <div class="col-md-5">
                  <!-- Buscador de Consumibles -->
                  <div class="form-group position-relative">
                    <label class="font-weight-bold">Buscar Consumibles</label>
                    <div class="input-group">
                      <input 
                        v-model="buscadorConsumibles" 
                        type="text" 
                        class="form-control" 
                        placeholder="Buscar toner, papel..."
                        @input="buscarConsumibles"
                      >
                      <div class="input-group-append">
                        <span class="input-group-text">
                          <i class="fas fa-search"></i>
                        </span>
                      </div>
                    </div>
                    
                    <!-- Resultados de Búsqueda -->
                    <div v-if="resultadosBusqueda.length > 0" class="search-results">
                      <div 
                        v-for="consumible in resultadosBusqueda" 
                        :key="consumible.id"
                        @click="agregarConsumible(consumible)"
                        class="search-item"
                      >
                        <div>
                          <strong class="d-block">{{ consumible.nombre }}</strong>
                          <small class="text-muted d-block">{{ consumible.descripcion }}</small>
                          <div class="mt-1">
                            <span class="badge badge-info badge-sm">Stock: {{ consumible.stock_actual }}</span>
                            <span v-if="consumible.precio_referencia" class="text-success font-weight-bold ml-2">
                              {{ formatCurrency(consumible.precio_referencia) }}
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Notas -->
                  <div class="form-group mt-3">
                    <label class="font-weight-bold">Notas / Observaciones</label>
                    <textarea 
                      v-model="formulario.notas" 
                      class="form-control" 
                      rows="3"
                      placeholder="Información adicional del pedido..."
                    ></textarea>
                  </div>
                </div>

                <div class="col-md-7">
                  <!-- Tabla de Consumibles -->
                  <div class="form-group">
                    <label class="font-weight-bold">Consumibles del Pedido *</label>
                    <div class="table-responsive" style="max-height: 320px;">
                      <table class="table table-bordered table-sm bg-white">
                        <thead class="thead-light">
                          <tr>
                            <th>Consumible</th>
                            <th width="80">Cant.</th>
                            <th width="100">Precio</th>
                            <th width="100">Subtotal</th>
                            <th width="50"></th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-if="formulario.detalles.length === 0">
                            <td colspan="5" class="text-center text-muted py-4">
                              <i class="fas fa-arrow-left mr-2"></i>
                              Busca y agrega consumibles
                            </td>
                          </tr>
                          <tr v-else v-for="(detalle, index) in formulario.detalles" :key="index">
                            <td>
                              <strong class="d-block">{{ detalle.nombre }}</strong>
                              <small class="text-muted">{{ detalle.descripcion }}</small>
                            </td>
                            <td>
                              <input 
                                v-model.number="detalle.cantidad" 
                                type="number" 
                                min="1" 
                                class="form-control form-control-sm"
                                @input="actualizarSubtotal(index)"
                              >
                            </td>
                            <td>
                              <div class="input-group input-group-sm">
                                <input 
                                  v-model.number="detalle.precio_unitario" 
                                  type="number" 
                                  min="0" 
                                  step="0.01" 
                                  class="form-control"
                                  @input="actualizarSubtotal(index)"
                                  placeholder="0.00"
                                >
                                <div class="input-group-append">
                                  <span class="input-group-text">€</span>
                                </div>
                              </div>
                            </td>
                            <td class="font-weight-bold text-success">
                              {{ formatCurrency(detalle.subtotal || 0) }}
                            </td>
                            <td class="text-center">
                              <button 
                                type="button" 
                                @click="eliminarDetalle(index)" 
                                class="btn btn-sm btn-danger"
                                title="Eliminar"
                              >
                                <i class="fas fa-trash"></i>
                              </button>
                            </td>
                          </tr>
                        </tbody>
                        <tfoot v-if="formulario.detalles.length > 0" class="bg-light">
                          <tr class="font-weight-bold">
                            <td colspan="3" class="text-right">TOTAL:</td>
                            <td colspan="2" class="text-success h5 mb-0">
                              {{ calcularTotalFormulario() }}
                            </td>
                          </tr>
                        </tfoot>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Mensajes de Error -->
              <div v-if="formulario.error" class="alert alert-danger mt-3">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                {{ formulario.error }}
              </div>

            </form>
          </div>
          
          <div class="modal-footer bg-light">
            <button type="button" @click="cerrarFormulario" class="btn btn-secondary">
              <i class="fas fa-times mr-1"></i>Cancelar
            </button>
            <button 
              type="submit" 
              @click="guardarPedido"
              class="btn btn-primary"
              :disabled="formulario.guardando || formulario.detalles.length === 0"
            >
              <i :class="formulario.guardando ? 'fas fa-spinner fa-spin mr-1' : 'fas fa-save mr-1'"></i>
              {{ formulario.guardando ? 'Guardando...' : 'Guardar Pedido' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODALES CONTINUARÁN EN LA SIGUIENTE PARTE -->
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'

const pedidos = ref([])
const cargando = ref(false)
const estadisticas = ref(null)
const proveedoresDisponibles = ref([])
const consumiblesDisponibles = ref([])

const filtros = reactive({
  search: '',
  estado: '',
  proveedor: ''
})

// Modal formulario (crear/editar)
const modalFormulario = reactive({
  visible: false,
  editando: false,
  pedidoId: null
})

const formulario = reactive({
  numero_pedido: '',
  proveedor: '',
  fecha_pedido: new Date().toISOString().split('T')[0],
  proveedor_contacto: '',
  proveedor_email: '',
  proveedor_telefono: '',
  notas: '',
  detalles: [],
  guardando: false,
  error: ''
})

// Buscador de consumibles
const buscadorConsumibles = ref('')
const resultadosBusqueda = ref([])
let timeoutBusqueda = null

const proveedoresUnicos = computed(() => {
  const provs = new Set()
  pedidos.value.forEach(p => {
    if (p.proveedor) provs.add(p.proveedor)
  })
  return Array.from(provs).sort()
})

async function cargarPedidos() {
  try {
    cargando.value = true
    const res = await axios.get('/pedidos', { params: filtros })
    if (res.data.success) {
      pedidos.value = res.data.data.data || res.data.data
    }
  } catch (error) {
    console.error('Error:', error)
  } finally {
    cargando.value = false
  }
}

async function cargarEstadisticas() {
  try {
    const res = await axios.get('/pedidos/estadisticas')
    if (res.data.success) {
      estadisticas.value = res.data.data
    }
  } catch (error) {
    console.error('Error:', error)
  }
}

function buscar() {
  cargarPedidos()
}

function limpiarFiltros() {
  filtros.search = ''
  filtros.estado = ''
  filtros.proveedor = ''
  cargarPedidos()
}

async function cargarDatosIniciales() {
  try {
    const res = await axios.get('/pedidos/create')
    if (res.data.success) {
      consumiblesDisponibles.value = res.data.data.consumibles
      proveedoresDisponibles.value = res.data.data.proveedores
      if (!modalFormulario.editando) {
        formulario.numero_pedido = res.data.data.numero_pedido_sugerido
      }
    }
  } catch (error) {
    console.error('Error cargando datos iniciales:', error)
  }
}

async function buscarConsumibles() {
  clearTimeout(timeoutBusqueda)
  
  if (buscadorConsumibles.value.length < 2) {
    resultadosBusqueda.value = []
    return
  }
  
  timeoutBusqueda = setTimeout(async () => {
    try {
      const res = await axios.get('/pedidos/buscar-consumibles', {
        params: { q: buscadorConsumibles.value }
      })
      
      if (res.data.success) {
        // Filtrar los que ya están agregados
        const idsAgregados = formulario.detalles.map(d => d.entidad_id)
        resultadosBusqueda.value = res.data.data.filter(c => !idsAgregados.includes(c.id))
      }
    } catch (error) {
      console.error('Error buscando consumibles:', error)
    }
  }, 300)
}

function agregarConsumible(consumible) {
  formulario.detalles.push({
    entidad_id: consumible.id,
    nombre: consumible.nombre,
    descripcion: consumible.descripcion,
    cantidad: 1,
    precio_unitario: consumible.precio_referencia || 0,
    subtotal: consumible.precio_referencia || 0
  })
  
  buscadorConsumibles.value = ''
  resultadosBusqueda.value = []
}

function eliminarDetalle(index) {
  formulario.detalles.splice(index, 1)
}

function actualizarSubtotal(index) {
  const detalle = formulario.detalles[index]
  detalle.subtotal = detalle.cantidad * (detalle.precio_unitario || 0)
}

function calcularTotalFormulario() {
  const total = formulario.detalles.reduce((sum, d) => sum + (d.subtotal || 0), 0)
  return formatCurrency(total)
}

function abrirFormularioCrear() {
  resetFormulario()
  modalFormulario.visible = true
  modalFormulario.editando = false
  cargarDatosIniciales()
}

function resetFormulario() {
  formulario.numero_pedido = ''
  formulario.proveedor = ''
  formulario.fecha_pedido = new Date().toISOString().split('T')[0]
  formulario.proveedor_contacto = ''
  formulario.proveedor_email = ''
  formulario.proveedor_telefono = ''
  formulario.notas = ''
  formulario.detalles = []
  formulario.guardando = false
  formulario.error = ''
  buscadorConsumibles.value = ''
  resultadosBusqueda.value = []
}

function cerrarFormulario() {
  modalFormulario.visible = false
  resetFormulario()
}

async function editar(pedido) {
  try {
    const res = await axios.get(`/pedidos/${pedido.id}`)
    if (res.data.success) {
      const p = res.data.data
      
      formulario.numero_pedido = p.numero_pedido
      formulario.proveedor = p.proveedor
      formulario.fecha_pedido = p.fecha_pedido
      formulario.proveedor_contacto = p.datos?.proveedor_contacto || ''
      formulario.proveedor_email = p.datos?.proveedor_email || ''
      formulario.proveedor_telefono = p.datos?.proveedor_telefono || ''
      formulario.notas = p.notas || ''
      formulario.detalles = p.detalles.map(d => ({
        entidad_id: d.entidad_id,
        nombre: d.entidad.nombre,
        descripcion: d.entidad.descripcion,
        cantidad: d.cantidad,
        precio_unitario: d.precio_unitario || 0,
        subtotal: d.cantidad * (d.precio_unitario || 0)
      }))
      
      modalFormulario.visible = true
      modalFormulario.editando = true
      modalFormulario.pedidoId = pedido.id
      
      await cargarDatosIniciales()
    }
  } catch (error) {
    console.error('Error cargando pedido:', error)
    alert('Error al cargar el pedido')
  }
}

async function guardarPedido() {
  try {
    formulario.guardando = true
    formulario.error = ''
    
    if (formulario.detalles.length === 0) {
      formulario.error = 'Debes agregar al menos un consumible al pedido'
      formulario.guardando = false
      return
    }
    
    const data = {
      numero_pedido: formulario.numero_pedido,
      proveedor: formulario.proveedor,
      fecha_pedido: formulario.fecha_pedido,
      proveedor_contacto: formulario.proveedor_contacto,
      proveedor_email: formulario.proveedor_email,
      proveedor_telefono: formulario.proveedor_telefono,
      notas: formulario.notas,
      detalles: formulario.detalles.map(d => ({
        entidad_id: d.entidad_id,
        cantidad: d.cantidad,
        precio_unitario: d.precio_unitario
      }))
    }
    
    let res
    if (modalFormulario.editando) {
      res = await axios.put(`/pedidos/${modalFormulario.pedidoId}`, data)
    } else {
      res = await axios.post('/pedidos', data)
    }
    
    if (res.data.success) {
      cerrarFormulario()
      await cargarPedidos()
      await cargarEstadisticas()
      alert(res.data.message || 'Pedido guardado correctamente')
    }
  } catch (error) {
    console.error('Error guardando pedido:', error)
    formulario.error = error.response?.data?.message || 'Error al guardar el pedido'
  } finally {
    formulario.guardando = false
  }
}

function verDetalle(pedido) {
  // TODO: Implementar modal de detalle
  alert('Ver detalle: ' + pedido.numero_pedido)
}

function marcarEnviado(pedido) {
  alert('Marcar enviado: ' + pedido.numero_pedido)
}

function marcarRecibido(pedido) {
  alert('Marcar recibido: ' + pedido.numero_pedido)
}

function cancelarPedido(pedido) {
  if (confirm('¿Cancelar pedido ' + pedido.numero_pedido + '?')) {
    // TODO: Implementar
  }
}

async function eliminar(pedido) {
  if (!confirm('¿Eliminar ' + pedido.numero_pedido + '?')) return
  
  try {
    const res = await axios.delete(`/pedidos/${pedido.id}`)
    if (res.data.success) {
      await cargarPedidos()
      await cargarEstadisticas()
      alert('Pedido eliminado')
    }
  } catch (error) {
    alert('Error al eliminar')
  }
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-ES')
}

function formatCurrency(value) {
  if (!value) return '0,00 €'
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value)
}

function getEstadoTexto(estado) {
  const map = {
    'pendiente': 'Pendiente',
    'enviado': 'Enviado',
    'recibido': 'Recibido',
    'cancelado': 'Cancelado'
  }
  return map[estado] || estado
}

function getEstadoBadge(estado) {
  const map = {
    'pendiente': 'badge badge-warning',
    'enviado': 'badge badge-info',
    'recibido': 'badge badge-success',
    'cancelado': 'badge badge-secondary'
  }
  return map[estado] || 'badge badge-secondary'
}

function calcularTotal(pedido) {
  if (!pedido.detalles) return formatCurrency(0)
  const total = pedido.detalles.reduce((sum, d) => sum + (d.cantidad * (d.precio_unitario || 0)), 0)
  return formatCurrency(total)
}

onMounted(() => {
  cargarPedidos()
  cargarEstadisticas()
})
</script>

<style scoped>
/* Estadísticas */
.border-left-warning {
  border-left: 4px solid #f6c23e !important;
}

.border-left-info {
  border-left: 4px solid #36b9cc !important;
}

.border-left-success {
  border-left: 4px solid #1cc88a !important;
}

.border-left-primary {
  border-left: 4px solid #4e73df !important;
}

/* Modal */
.modal {
  z-index: 1050;
}

.modal-dialog {
  max-width: 1400px;
  margin: 1.75rem auto;
}

.modal-dialog-scrollable {
  max-height: calc(100vh - 3.5rem);
}

.modal-dialog-scrollable .modal-content {
  max-height: calc(100vh - 3.5rem);
  overflow: hidden;
}

.modal-dialog-scrollable .modal-body {
  overflow-y: auto;
}

.modal-content {
  border: none;
  border-radius: 0.5rem;
  box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.3);
}

.modal-header {
  background-color: #4e73df;
  color: white;
  border-bottom: none;
  padding: 1rem 1.5rem;
  border-top-left-radius: 0.5rem;
  border-top-right-radius: 0.5rem;
}

.modal-header .modal-title {
  font-weight: 700;
  font-size: 1.25rem;
  color: white;
}

.modal-header .close {
  color: white;
  opacity: 0.8;
  text-shadow: none;
  font-size: 1.5rem;
}

.modal-header .close:hover {
  opacity: 1;
}

.modal-body {
  padding: 1.5rem;
  background-color: white !important;
  max-height: calc(100vh - 250px);
  overflow-y: auto;
}

.modal-footer {
  background-color: #f8f9fc;
  border-top: 1px solid #e3e6f0;
  padding: 1rem 1.5rem;
}

/* Form */
.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  font-size: 0.875rem;
  color: #5a5c69;
  margin-bottom: 0.5rem;
}

.form-control {
  border: 1px solid #d1d3e2;
  border-radius: 0.35rem;
  font-size: 0.875rem;
  padding: 0.5rem 0.75rem;
}

.form-control:focus {
  border-color: #4e73df;
  box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

/* Buscador */
.search-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  max-height: 250px;
  overflow-y: auto;
  background: white;
  border: 1px solid #d1d3e2;
  border-radius: 0.35rem;
  margin-top: 4px;
  z-index: 1060;
  box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.15);
}

.search-item {
  padding: 0.75rem 1rem;
  cursor: pointer;
  border-bottom: 1px solid #e3e6f0;
  background: white;
}

.search-item:hover {
  background-color: #f8f9fc;
}

.search-item:last-child {
  border-bottom: none;
}

/* Tabla */
.table {
  margin-bottom: 0;
  font-size: 0.875rem;
  background-color: white;
}

.table thead th {
  background-color: #f8f9fc;
  border: 1px solid #e3e6f0;
  padding: 0.75rem 0.5rem;
  font-weight: 700;
  font-size: 0.8rem;
  color: #5a5c69;
}

.table tbody td {
  padding: 0.75rem 0.5rem;
  vertical-align: middle;
  border: 1px solid #e3e6f0;
  background-color: white;
}

.table tbody tr:hover {
  background-color: #f8f9fc;
}

.table tfoot td {
  background-color: #f8f9fc;
  padding: 0.75rem 0.5rem;
  border: 1px solid #e3e6f0;
}

/* Botones */
.btn {
  border-radius: 0.35rem;
  font-weight: 600;
}

.btn-primary {
  background-color: #4e73df;
  border-color: #4e73df;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2e59d9;
  border-color: #2653d4;
}

.btn-secondary {
  background-color: #858796;
  border-color: #858796;
}

.btn-secondary:hover {
  background-color: #717384;
}

.btn-danger {
  background-color: #e74a3b;
  border-color: #e74a3b;
}

.btn-danger:hover {
  background-color: #e02d1b;
}

/* Scrollbar */
.modal-body::-webkit-scrollbar,
.search-results::-webkit-scrollbar,
.table-responsive::-webkit-scrollbar {
  width: 8px;
}

.modal-body::-webkit-scrollbar-track,
.search-results::-webkit-scrollbar-track,
.table-responsive::-webkit-scrollbar-track {
  background: #f8f9fc;
}

.modal-body::-webkit-scrollbar-thumb,
.search-results::-webkit-scrollbar-thumb,
.table-responsive::-webkit-scrollbar-thumb {
  background: #d1d3e2;
  border-radius: 10px;
}

.modal-body::-webkit-scrollbar-thumb:hover,
.search-results::-webkit-scrollbar-thumb:hover,
.table-responsive::-webkit-scrollbar-thumb:hover {
  background: #b7b9cc;
}
</style>
