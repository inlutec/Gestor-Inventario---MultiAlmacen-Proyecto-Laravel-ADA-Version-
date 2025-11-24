<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header Principal -->
    <div class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-10 w-10 bg-gradient-to-br from-junta-green-500 to-junta-green-600 rounded-lg flex items-center justify-center">
                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <h1 class="text-2xl font-bold text-gray-900">Panel de Control</h1>
              <p class="text-sm text-gray-500">Gestión de Inventario de Materiales</p>
            </div>
          </div>
          
          <!-- Selector de Almacenes -->
          <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
              <label class="text-sm font-medium text-gray-700">Almacén:</label>
              <select 
                v-model="almacenSeleccionado" 
                @change="cargarDatosDashboard"
                class="block w-48 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 sm:text-sm"
              >
                <option value="">Todos los almacenes</option>
                <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">
                  {{ almacen.nombre }}
                </option>
              </select>
            </div>
            <div class="text-sm text-gray-500">
              <span class="inline-flex items-center">
                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ formatoFecha(new Date()) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="text-center">
        <div class="animate-spin h-16 w-16 border-4 border-junta-green-500 border-t-transparent rounded-full mx-auto mb-4"></div>
        <p class="text-lg text-gray-600 font-medium">Cargando estadísticas del inventario...</p>
        <p class="text-sm text-gray-500 mt-2">Por favor, espere un momento</p>
      </div>
    </div>

    <!-- Dashboard Content -->
    <div v-else class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- KPIs Principales -->
      <div class="grid gap-6 mb-8 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total Materiales -->
        <div class="bg-white overflow-hidden shadow-lg rounded-xl border-l-4 border-blue-500 hover:shadow-xl transition-shadow duration-300">
          <div class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="h-14 w-14 rounded-full bg-blue-100 flex items-center justify-center">
                  <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                  </svg>
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Total Materiales</dt>
                  <dd class="text-3xl font-bold text-gray-900">{{ stats.total_materiales || 0 }}</dd>
                  <div class="mt-2 flex items-center text-sm">
                    <span class="text-green-600 font-medium">{{ stats.total_entradas_mes || 0 }} entradas</span>
                    <span class="mx-2 text-gray-300">|</span>
                    <span class="text-amber-600 font-medium">{{ stats.total_salidas_mes || 0 }} salidas</span>
                  </div>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Movimientos Pendientes -->
        <div class="bg-white overflow-hidden shadow-lg rounded-xl border-l-4 border-purple-500 hover:shadow-xl transition-shadow duration-300">
          <div class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="h-14 w-14 rounded-full bg-purple-100 flex items-center justify-center">
                  <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                  </svg>
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Pendientes de Firma</dt>
                  <dd class="text-3xl font-bold text-gray-900">{{ stats.movimientos_pendientes_firma?.length || 0 }}</dd>
                  <div class="mt-2 flex items-center text-sm">
                    <span class="text-orange-600 font-medium">{{ movimientosPorEstado.pendiente || 0 }} pendientes</span>
                    <span class="mx-2 text-gray-300">|</span>
                    <span class="text-blue-600 font-medium">{{ movimientosPorEstado.firmado || 0 }} firmados</span>
                  </div>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Stock Bajo -->
        <div class="bg-white overflow-hidden shadow-lg rounded-xl border-l-4 border-amber-500 hover:shadow-xl transition-shadow duration-300">
          <div class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="h-14 w-14 rounded-full bg-amber-100 flex items-center justify-center">
                  <svg class="h-8 w-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                  </svg>
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Alertas de Stock</dt>
                  <dd class="text-3xl font-bold text-gray-900">{{ stockBajo.length || 0 }}</dd>
                  <div class="mt-2">
                    <span v-if="stockBajo.length > 0" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                      {{ stockBajo.length }} materiales necesitan atención
                    </span>
                    <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                      Todos los niveles OK
                    </span>
                  </div>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Peticiones -->
        <div class="bg-white overflow-hidden shadow-lg rounded-xl border-l-4 border-green-500 hover:shadow-xl transition-shadow duration-300">
          <div class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="h-14 w-14 rounded-full bg-green-100 flex items-center justify-center">
                  <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Peticiones</dt>
                  <dd class="text-3xl font-bold text-gray-900">{{ stats.total_peticiones_mes || 0 }}</dd>
                  <div class="mt-2 flex items-center text-sm">
                    <span class="text-green-600 font-medium">{{ stats.total_peticiones_mes }} este mes</span>
                    <span v-if="stats.total_solicitudes_pendientes > 0" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                      {{ stats.total_solicitudes_pendientes }} pendientes
                    </span>
                  </div>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Alertas Críticas -->
      <div v-if="hayAlertasCriticas" class="mb-8">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <!-- Alerta Stock Crítico -->
          <div v-if="stockCritico.length > 0" class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-md">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Stock Crítico</h3>
                <p class="text-sm text-red-700">{{ stockCritico.length }} materiales sin stock</p>
              </div>
            </div>
          </div>

          <!-- Alerta Movimientos Urgentes -->
          <div v-if="movimientosUrgentes.length > 0" class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded-lg shadow-md">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-orange-800">Movimientos Urgentes</h3>
                <p class="text-sm text-orange-700">{{ movimientosUrgentes.length }} pendientes más de 48h</p>
              </div>
            </div>
          </div>

          <!-- Alerta Solicitudes Pendientes -->
          <div v-if="stats.total_solicitudes_pendientes > 0" class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-lg shadow-md">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Solicitudes Pendientes</h3>
                <p class="text-sm text-yellow-700">{{ stats.total_solicitudes_pendientes }} solicitudes de reposición</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráficos y Estadísticas -->
      <div class="grid gap-6 mb-8 lg:grid-cols-2">
        <!-- Gráfico de Movimientos -->
        <div class="bg-white shadow-lg rounded-xl">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
              <svg class="h-5 w-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
              </svg>
              Movimientos Mensuales
            </h3>
          </div>
          <div class="p-6">
            <div class="space-y-6">
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Entradas</span>
                    <span class="text-2xl font-bold text-green-600">{{ stats.total_entradas_mes || 0 }}</span>
                  </div>
                  <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-green-400 to-green-600 rounded-full" 
                         :style="{width: Math.min((stats.total_entradas_mes || 0) / Math.max((stats.total_entradas_mes || 0) + (stats.total_salidas_mes || 0), 1) * 100, 100) + '%'}"></div>
                  </div>
                </div>
              </div>
              
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Salidas</span>
                    <span class="text-2xl font-bold text-amber-600">{{ stats.total_salidas_mes || 0 }}</span>
                  </div>
                  <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-amber-400 to-amber-600 rounded-full" 
                         :style="{width: Math.min((stats.total_salidas_mes || 0) / Math.max((stats.total_entradas_mes || 0) + (stats.total_salidas_mes || 0), 1) * 100, 100) + '%'}"></div>
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Peticiones</span>
                    <span class="text-2xl font-bold text-blue-600">{{ stats.total_peticiones_mes || 0 }}</span>
                  </div>
                  <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-blue-400 to-blue-600 rounded-full" 
                         :style="{width: Math.min((stats.total_peticiones_mes || 0) / Math.max(Math.max(stats.total_entradas_mes || 0, stats.total_salidas_mes || 0, stats.total_peticiones_mes || 0), 1) * 100, 100) + '%'}"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Estado General del Sistema -->
        <div class="bg-white shadow-lg rounded-xl">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
              <svg class="h-5 w-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Estado General del Sistema
            </h3>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-2 gap-4">
              <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg">
                <div class="text-2xl font-bold text-blue-800">{{ stats.total_materiales || 0 }}</div>
                <div class="text-sm text-blue-600 font-medium">Materiales Totales</div>
              </div>
              <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-lg">
                <div class="text-2xl font-bold text-purple-800">{{ stats.movimientos_pendientes_firma?.length || 0 }}</div>
                <div class="text-sm text-purple-600 font-medium">Movimientos Pendientes</div>
              </div>
              <div class="bg-gradient-to-br from-amber-50 to-amber-100 p-4 rounded-lg">
                <div class="text-2xl font-bold text-amber-800">{{ stockBajo.length || 0 }}</div>
                <div class="text-sm text-amber-600 font-medium">Stock Bajo</div>
              </div>
              <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg">
                <div class="text-2xl font-bold text-green-800">{{ stats.total_peticiones_mes || 0 }}</div>
                <div class="text-sm text-green-600 font-medium">Peticiones Mes</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tablas de Información Detallada -->
      <div class="grid gap-6 mb-8 lg:grid-cols-2">
        <!-- Movimientos Recientes -->
        <div class="bg-white shadow-lg rounded-xl">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
              <svg class="h-5 w-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
              </svg>
              Movimientos Recientes
            </h3>
          </div>
          <div class="overflow-hidden">
            <div v-if="movimientosRecientes.length === 0" class="p-8 text-center text-gray-500">
              <svg class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2-2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 00-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
              </svg>
              <p class="text-sm">No hay movimientos recientes</p>
            </div>
            <div v-else class="divide-y divide-gray-200 max-h-80 overflow-y-auto">
              <div v-for="movimiento in movimientosRecientes" :key="movimiento.id" class="p-4 hover:bg-gray-50 transition cursor-pointer">
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <div class="flex items-center justify-between">
                      <p class="text-sm font-semibold text-gray-900">{{ movimiento.numero_documento || 'Sin documento' }}</p>
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                            :class="estadoMovimientoClass(movimiento.estado)">
                        {{ movimiento.estado }}
                      </span>
                    </div>
                    <p class="text-xs text-gray-600 mt-1">{{ movimiento.tipo }}</p>
                    <div class="flex items-center gap-2 mt-2">
                      <span class="text-xs text-gray-500">{{ formatoFechaCorta(movimiento.fecha_movimiento) }}</span>
                      <span v-if="movimiento.destino" class="text-xs text-gray-400">• {{ movimiento.destino }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Actividad Reciente -->
        <div class="bg-white shadow-lg rounded-xl">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
              <svg class="h-5 w-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Actividad Reciente
            </h3>
          </div>
          <div class="overflow-hidden">
            <div v-if="actividadReciente.length === 0" class="p-8 text-center text-gray-500">
              <svg class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
              <p class="text-sm">No hay actividad reciente</p>
            </div>
            <div v-else class="divide-y divide-gray-200 max-h-80 overflow-y-auto">
              <div v-for="actividad in actividadReciente" :key="actividad.id" class="p-4 hover:bg-gray-50 transition">
                <div class="flex items-start gap-3">
                  <div class="h-10 w-10 rounded-full bg-gradient-to-br from-junta-green-400 to-junta-green-600 flex items-center justify-center flex-shrink-0">
                    <span class="text-white font-bold text-sm">{{ inicial(actividad.usuario?.nombre) }}</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-900">
                      <span class="font-semibold">{{ actividad.usuario?.nombre || 'Sistema' }}</span>
                      <span class="text-gray-600"> {{ actividad.accion }}</span>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">{{ formatoFechaHora(actividad.created_at) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Materiales Más Solicitados -->
      <div v-if="materialesMasSolicitados.length > 0" class="bg-white shadow-lg rounded-xl">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900 flex items-center">
            <svg class="h-5 w-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
            Materiales Más Solicitados (último mes)
          </h3>
        </div>
        <div class="p-6">
          <div class="space-y-4">
            <div v-for="(material, idx) in materialesMasSolicitados" :key="idx" class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
              <div class="h-12 w-12 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center flex-shrink-0 shadow-md">
                <span class="text-white font-bold text-lg">{{ idx + 1 }}</span>
              </div>
              <div class="flex-1">
                <div class="flex items-center justify-between mb-2">
                  <div>
                    <span class="text-sm font-semibold text-gray-900">{{ material.referencia }}</span>
                    <span v-if="material.descripcion" class="text-xs text-gray-500 ml-2">- {{ material.descripcion }}</span>
                  </div>
                  <span class="text-lg font-bold text-amber-600">{{ material.total_solicitudes }}</span>
                </div>
                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                  <div class="h-full bg-gradient-to-r from-amber-400 to-amber-600 rounded-full transition-all duration-500" 
                       :style="{width: (material.total_solicitudes / maxTopMateriales * 100) + '%'}"></div>
                </div>
                <div class="mt-1 text-xs text-gray-500">{{ Math.round(material.total_solicitudes / maxTopMateriales * 100) }}% del total</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Resumen de Stock por Categoría -->
      <div v-if="stockPorCategoria.length > 0" class="bg-white shadow-lg rounded-xl mt-6">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900 flex items-center">
            <svg class="h-5 w-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            Resumen de Stock por Categoría
          </h3>
        </div>
        <div class="p-6">
          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="categoria in stockPorCategoria" :key="categoria.id" class="bg-gray-50 p-4 rounded-lg hover:bg-gray-100 transition">
              <div class="flex items-center justify-between mb-2">
                <h4 class="text-sm font-semibold text-gray-900">{{ categoria.nombre }}</h4>
                <span class="text-lg font-bold text-gray-700">{{ categoria.total_materiales }}</span>
              </div>
              <div class="space-y-2">
                <div class="flex justify-between text-xs">
                  <span class="text-gray-600">Stock normal:</span>
                  <span class="font-medium text-green-600">{{ categoria.stock_normal || 0 }}</span>
                </div>
                <div class="flex justify-between text-xs">
                  <span class="text-gray-600">Stock bajo:</span>
                  <span class="font-medium text-amber-600">{{ categoria.stock_bajo || 0 }}</span>
                </div>
                <div class="flex justify-between text-xs">
                  <span class="text-gray-600">Sin stock:</span>
                  <span class="font-medium text-red-600">{{ categoria.stock_critico || 0 }}</span>
                </div>
              </div>
              <div class="mt-3 h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full flex">
                  <div class="bg-green-500" :style="{width: (categoria.stock_normal || 0) / categoria.total_materiales * 100 + '%'}"></div>
                  <div class="bg-amber-500" :style="{width: (categoria.stock_bajo || 0) / categoria.total_materiales * 100 + '%'}"></div>
                  <div class="bg-red-500" :style="{width: (categoria.stock_critico || 0) / categoria.total_materiales * 100 + '%'}"></div>
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
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const loading = ref(true);
const stats = ref({});
const actividadReciente = ref([]);
const movimientosRecientes = ref([]);
const stockBajo = ref([]);
const stockCritico = ref([]);
const movimientosUrgentes = ref([]);
const materialesMasSolicitados = ref([]);
const stockPorCategoria = ref([]);
const almacenes = ref([]);
const almacenSeleccionado = ref('');

const hayAlertasCriticas = computed(() => {
  return stockCritico.value.length > 0 || 
         movimientosUrgentes.value.length > 0 || 
         (stats.value.total_solicitudes_pendientes && stats.value.total_solicitudes_pendientes > 0);
});

const movimientosPorEstado = computed(() => {
  const movimientos = stats.value.movimientos_pendientes_firma || [];
  return {
    pendiente: movimientos.filter(m => m.estado === 'pendiente').length,
    pendiente_firma: movimientos.filter(m => m.estado === 'pendiente_firma').length,
    firmado: movimientos.filter(m => m.estado === 'firmado').length,
    entregado: movimientos.filter(m => m.estado === 'entregado').length,
  };
});

const maxTopMateriales = computed(() => {
  if (materialesMasSolicitados.value.length === 0) return 1;
  return Math.max(...materialesMasSolicitados.value.map(m => m.total_solicitudes));
});

const cargarDatosDashboard = async () => {
  try {
    loading.value = true;
    
    const params = almacenSeleccionado.value ? { almacen_ids: [almacenSeleccionado.value] } : {};
    const res = await axios.get('/dashboard/stats', { params });
    
    if (res.data && res.data.success) {
      const data = res.data.data;
      stats.value = data.stats || {};
      actividadReciente.value = data.actividad_reciente || [];
      movimientosRecientes.value = data.movimientos_recientes || [];
      stockBajo.value = data.stock_bajo || [];
      stockCritico.value = data.stock_critico || [];
      movimientosUrgentes.value = data.movimientos_urgentes || [];
      materialesMasSolicitados.value = data.materiales_mas_solicitados || [];
      stockPorCategoria.value = data.stock_por_categoria || [];
    }
  } catch (e) {
    console.error('Error cargando dashboard', e);
  } finally {
    loading.value = false;
  }
};

const cargarAlmacenes = async () => {
  try {
    const res = await axios.get('/mis-almacenes');
    if (res.data && res.data.success) {
      almacenes.value = res.data.data || [];
    }
  } catch (e) {
    console.error('❌ Error cargando almacenes', e);
  }
};

onMounted(async () => {
  await cargarAlmacenes();
  await cargarDatosDashboard();
});

const formatoFecha = (fecha) => {
  if (!fecha) return '';
  const d = new Date(fecha);
  return d.toLocaleDateString('es-ES', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatoFechaCorta = (fecha) => {
  if (!fecha) return '';
  const d = new Date(fecha);
  return d.toLocaleDateString('es-ES', { 
    day: '2-digit', 
    month: 'short', 
    year: 'numeric' 
  });
};

const formatoFechaHora = (fecha) => {
  if (!fecha) return '';
  const d = new Date(fecha);
  const ahora = new Date();
  const diff = Math.floor((ahora - d) / 1000);
  
  if (diff < 60) return 'Hace un momento';
  if (diff < 3600) return `Hace ${Math.floor(diff / 60)} min`;
  if (diff < 86400) return `Hace ${Math.floor(diff / 3600)} h`;
  if (diff < 604800) return `Hace ${Math.floor(diff / 86400)} días`;
  
  return d.toLocaleDateString('es-ES', { 
    day: 'numeric', 
    month: 'short' 
  });
};

const estadoMovimientoClass = (estado) => {
  const clases = {
    'pendiente': 'bg-yellow-100 text-yellow-800',
    'pendiente_firma': 'bg-orange-100 text-orange-800',
    'firmado': 'bg-blue-100 text-blue-800',
    'entregado': 'bg-green-100 text-green-800',
    'cancelado': 'bg-red-100 text-red-800',
  };
  return clases[estado] || 'bg-gray-100 text-gray-800';
};

const inicial = (nombre) => {
  if (!nombre) return '?';
  return nombre.charAt(0).toUpperCase();
};
</script>

<style scoped>
/* Animaciones personalizadas */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.card {
  animation: slideIn 0.5s ease-out;
}

/* Gradientes personalizados */
.bg-junta-green-500 {
  background-color: #006633;
}

.bg-junta-green-600 {
  background-color: #004d26;
}

.from-junta-green-400 {
  --tw-gradient-from: #008040;
}

.to-junta-green-600 {
  --tw-gradient-to: #004d26;
}

/* Transiciones suaves */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}
</style>