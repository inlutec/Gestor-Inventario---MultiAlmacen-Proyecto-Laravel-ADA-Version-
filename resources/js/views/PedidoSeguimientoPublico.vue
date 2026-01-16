<template>
  <div class="max-w-4xl mx-auto p-4 sm:p-6 space-y-4">
    <div class="space-y-1">
      <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Seguimiento de Pedido</h1>
      <p class="text-sm text-gray-600">Consulte el estado y las actualizaciones de su pedido</p>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-600">Cargando información del pedido...</p>
    </div>
    
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
      <div class="flex items-center gap-2">
        <span class="text-red-600 text-xl">⚠️</span>
        <div>
          <p class="font-semibold text-red-800">Error</p>
          <p class="text-sm text-red-700">{{ error }}</p>
        </div>
      </div>
    </div>

    <div v-else-if="pedido" class="space-y-4">
      <!-- Información del pedido -->
      <div class="bg-white shadow rounded-lg p-4 sm:p-6">
        <div class="flex flex-wrap items-center gap-3 justify-between mb-4">
          <div>
            <div class="text-sm text-gray-500">Número de Pedido</div>
            <div class="text-xl sm:text-2xl font-semibold text-gray-900">{{ pedido.numero_pedido }}</div>
          </div>
          <div>
            <span :class="estadoBadgeClass(pedido.estado)">{{ estadoLabel(pedido.estado) }}</span>
          </div>
        </div>
        
        <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm border-t pt-4">
          <div>
            <span class="text-gray-500 block mb-1">Fecha de Solicitud</span>
            <span class="font-medium">{{ formatDate(pedido.fecha_pedido) }}</span>
          </div>
          <div>
            <span class="text-gray-500 block mb-1">Solicitante</span>
            <span class="font-medium">{{ pedido.usuario_solicitante }}</span>
          </div>
          <div v-if="pedido.sede">
            <span class="text-gray-500 block mb-1">Sede</span>
            <span class="font-medium">{{ pedido.sede.nombre }}</span>
          </div>
          <div v-if="pedido.departamento">
            <span class="text-gray-500 block mb-1">Departamento</span>
            <span class="font-medium">{{ pedido.departamento.nombre }}</span>
          </div>
          <div v-if="pedido.fecha_aprobacion">
            <span class="text-gray-500 block mb-1">Fecha de Aprobación</span>
            <span class="font-medium">{{ formatDate(pedido.fecha_aprobacion) }}</span>
          </div>
        </div>

        <div v-if="pedido.observaciones" class="mt-4 pt-4 border-t">
          <span class="text-gray-500 block mb-1">Justificación</span>
          <p class="text-gray-700">{{ pedido.observaciones }}</p>
        </div>

        <div v-if="pedido.comentarios_aprobacion" class="mt-4 pt-4 border-t bg-blue-50 rounded p-3">
          <span class="text-blue-900 font-medium block mb-1">💬 Comentarios de Aprobación</span>
          <p class="text-blue-800">{{ pedido.comentarios_aprobacion }}</p>
        </div>

        <div v-if="pedido.aprobacion_parcial" class="mt-4 pt-4 border-t bg-orange-50 rounded p-3">
          <span class="text-orange-900 font-medium block mb-1">⚠️ Aprobación Parcial</span>
          <p class="text-orange-800">Algunos materiales fueron aprobados con cantidades diferentes a las solicitadas</p>
        </div>
      </div>

      <!-- Materiales solicitados -->
      <div class="bg-white shadow rounded-lg p-4 sm:p-6">
        <h3 class="font-semibold text-lg mb-4">Materiales Solicitados</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="bg-gray-100 text-left">
                <th class="p-2 sm:p-3">Material</th>
                <th class="p-2 sm:p-3 text-center">Cantidad Solicitada</th>
                <th class="p-2 sm:p-3 text-center">Cantidad Aprobada</th>
                <th class="p-2 sm:p-3 text-center">Unidad</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(material, index) in pedido.materiales" :key="index" class="border-b hover:bg-gray-50">
                <td class="p-2 sm:p-3">
                  <div class="font-medium">{{ material.referencia }}</div>
                  <div class="text-gray-600 text-xs">{{ material.nombre }}</div>
                </td>
                <td class="p-2 sm:p-3 text-center">{{ material.cantidad_solicitada }}</td>
                <td class="p-2 sm:p-3 text-center">
                  <span v-if="material.cantidad_aprobada !== null" :class="material.cantidad_aprobada === material.cantidad_solicitada ? 'text-green-600 font-medium' : 'text-orange-600 font-medium'">
                    {{ material.cantidad_aprobada }}
                  </span>
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td class="p-2 sm:p-3 text-center">{{ material.unidad }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Historial de actualizaciones -->
      <div class="bg-white shadow rounded-lg p-4 sm:p-6">
        <h3 class="font-semibold text-lg mb-4">Historial de Actualizaciones</h3>
        
        <div v-if="!pedido.historial || pedido.historial.length === 0" class="text-center py-8 text-gray-500">
          <p>No hay actualizaciones disponibles</p>
        </div>

        <div v-else class="space-y-4">
          <div v-for="(entrada, index) in pedido.historial" :key="entrada.id" class="relative">
            <!-- Timeline line -->
            <div v-if="index < pedido.historial.length - 1" class="absolute left-6 top-12 w-0.5 h-full bg-gray-200"></div>
            
            <div class="flex gap-4">
              <!-- Icon -->
              <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center text-white text-xl relative z-10" :class="obtenerColorAccion(entrada.accion)">
                {{ obtenerIconoAccion(entrada.accion) }}
              </div>

              <!-- Content Card -->
              <div class="flex-1 bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="flex justify-between items-start mb-2">
                  <div class="flex-1">
                    <h4 class="font-semibold text-gray-800">
                      {{ obtenerTituloAccion(entrada.accion) }}
                    </h4>
                    <p class="text-sm text-gray-600 mt-1">{{ entrada.descripcion }}</p>
                    
                    <!-- Mostrar comentarios de aprobación si existen -->
                    <div v-if="entrada.comentarios_aprobacion" class="mt-2 bg-blue-50 rounded p-2 border border-blue-200">
                      <p class="text-xs font-medium text-blue-900 mb-1">💬 Comentarios:</p>
                      <p class="text-sm text-blue-800">{{ entrada.comentarios_aprobacion }}</p>
                    </div>
                    
                    <!-- Mostrar cambios en materiales si existen -->
                    <div v-if="entrada.cambios_materiales && entrada.cambios_materiales.length > 0" class="mt-2 bg-orange-50 rounded p-2 border border-orange-200">
                      <p class="text-xs font-medium text-orange-900 mb-1">📦 Cambios en Materiales:</p>
                      <ul class="text-sm text-orange-800 list-disc list-inside">
                        <li v-for="(cambio, idx) in entrada.cambios_materiales" :key="idx">{{ cambio }}</li>
                      </ul>
                    </div>
                    
                    <!-- Mostrar detalles de aprobación parcial -->
                    <div v-if="entrada.aprobacion_parcial" class="mt-2 bg-yellow-50 rounded p-2 border border-yellow-200">
                      <p class="text-xs font-medium text-yellow-900">⚠️ Aprobación Parcial</p>
                    </div>
                  </div>
                  <span class="text-xs text-gray-500 bg-white px-2 py-1 rounded whitespace-nowrap ml-2">
                    {{ entrada.fecha_relativa }}
                  </span>
                </div>

                <div class="flex items-center gap-2 text-xs text-gray-600 mt-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>{{ entrada.fecha }}</span>
                  <span v-if="entrada.usuario" class="text-gray-400">•</span>
                  <span v-if="entrada.usuario">{{ entrada.usuario.nombre }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const token = ref(route.params.token)
const pedido = ref(null)
const loading = ref(true)
const error = ref('')

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function estadoBadgeClass(estado) {
  const base = 'px-3 py-1 rounded-full text-xs font-bold uppercase'
  if (estado === 'pendiente') return `${base} bg-yellow-100 text-yellow-800`
  if (estado === 'aprobado') return `${base} bg-green-100 text-green-800`
  if (estado === 'denegado') return `${base} bg-red-100 text-red-800`
  return `${base} bg-gray-100 text-gray-800`
}

function estadoLabel(estado) {
  const labels = {
    'pendiente': 'Pendiente',
    'aprobado': 'Aprobado',
    'denegado': 'Denegado'
  }
  return labels[estado] || estado
}

function obtenerIconoAccion(accion) {
  const iconos = {
    'creado': '➕',
    'modificado': '✏️',
    'aprobado': '✅',
    'rechazado': '❌',
    'comentario': '💬',
    'enviado_historico': '📦',
    'entregado': '📦'
  }
  return iconos[accion] || '📝'
}

function obtenerColorAccion(accion) {
  const colores = {
    'creado': 'bg-blue-500',
    'modificado': 'bg-yellow-500',
    'aprobado': 'bg-green-500',
    'rechazado': 'bg-red-500',
    'comentario': 'bg-purple-500',
    'enviado_historico': 'bg-indigo-500',
    'entregado': 'bg-green-600'
  }
  return colores[accion] || 'bg-gray-500'
}

function obtenerTituloAccion(accion) {
  const titulos = {
    'creado': 'Pedido Creado',
    'modificado': 'Pedido Modificado',
    'aprobado': 'Pedido Aprobado',
    'rechazado': 'Pedido Rechazado',
    'comentario': 'Comentario Añadido',
    'enviado_historico': 'Enviado al Histórico',
    'entregado': 'Material Entregado'
  }
  return titulos[accion] || 'Actualización'
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    // Usar window.axios que tiene el baseURL configurado (/gestionmaterial/api)
    const { data } = await window.axios.get(`/seguimiento-pedido/${token.value}`)
    if (!data.success) {
      throw new Error(data.message || 'No se pudo cargar el pedido')
    }
    pedido.value = data.data
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Error al cargar el pedido'
    console.error('Error cargando pedido:', e)
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(() => route.params.token, (newToken) => {
  token.value = newToken
  load()
})
</script>
