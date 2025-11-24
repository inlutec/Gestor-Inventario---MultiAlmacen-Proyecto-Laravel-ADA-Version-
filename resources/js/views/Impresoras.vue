<template>
  <div class="space-y-6">
    <!-- Header con acciones -->
    <div class="flex items-center justify-between">
      <div>
  <h1 class="text-2xl font-bold">Impresoras</h1>
  <p class="mt-1 text-sm text-muted">Gestión de impresoras del inventario</p>
      </div>
    <div class="flex items-center space-x-3">
  <button @click="sincronizarCheckmk" :disabled="sync.loading" class="btn btn-primary flex items-center gap-2">
          <svg v-if="sync.loading" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>Sincronizar CheckMK</span>
        </button>
      </div>
    </div>

    <!-- Alerta de sincronización -->
    <div v-if="sync.success" class="alert alert-success">
      {{ sync.success }}
    </div>
    <div v-if="sync.error" class="alert alert-error">
      {{ sync.error }}
    </div>

    <!-- Filtros y búsqueda -->
  <div class="card p-4">
      <div class="flex items-center gap-4">
        <div class="flex-1">
          <input v-model="searchQuery" type="text" placeholder="Buscar impresoras..." class="input" />
        </div>
  <select v-model="filterSede" class="select">
          <option value="">Todas las sedes</option>
          <option v-for="opt in sedeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
      </div>
    </div>

    <!-- Grid de tarjetas -->
    <div>
      <!-- Impresoras del inventario -->
      <div  class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <div v-for="impresora in filteredImpresoras" :key="impresora.id" @click="openDetailModal(impresora)" class="card hover:shadow-md transition overflow-hidden cursor-pointer">
          <!-- Foto / Header -->
          <div class="relative h-48 bg-gradient-to-br from-gray-100 to-gray-200">
            <img v-if="getFotoImpresora(impresora)" :src="getFotoImpresora(impresora)" class="absolute inset-0 h-full w-full object-contain p-2" :alt="impresora.datos.marca + ' ' + impresora.datos.modelo" />
            <div v-else class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-[rgb(var(--card))] to-[rgb(var(--border))] text-gray-400">
              <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            </div>
            <div class="absolute top-2 left-2">
              <span v-if="impresora._esInventario" class="px-2 py-0.5 rounded bg-white/90 text-gray-800 text-xs font-medium shadow">{{ impresora.datos.referencia }}</span>
              <span v-else class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 text-xs font-medium shadow">CheckMK</span>
            </div>
          </div>

          <!-- Info principal -->
          <div class="p-4 space-y-2">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-sm text-muted">{{ impresora.datos.marca }}</div>
                <div class="font-semibold">{{ impresora.datos.modelo }}</div>
              </div>
              <div class="flex items-center space-x-2">
                <!-- Estado CheckMK -->
                <span v-if="impresora._esCheckmk" :class="{
                  'h-2 w-2 rounded-full inline-block': true,
                  'bg-green-500': impresora.estado === 'online',
                  'bg-yellow-500': impresora.estado === 'warning',
                  'bg-red-500': impresora.estado === 'error',
                  'bg-gray-400': impresora.estado === 'unknown'
                }"></span>
                <span class="px-2 py-1 bg-junta-green-50 text-junta-green rounded-full text-xs font-medium">{{ labelFromSedeValue(impresora.datos.sede) || '—' }}</span>
              </div>
            </div>
            <div class="text-sm text-muted">
              <span class="mr-2">{{ impresora.datos.ip || 'sin IP' }}</span>
              ·
              <span class="ml-2">{{ impresora.datos.ubicacion || 'sin ubicación' }}</span>
            </div>

            <!-- Niveles de consumibles: Tinta -->
            <div class="mt-2">
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-muted">Tinta</span>
                <span class="text-xs text-gray-400">% restantes</span>
              </div>
              <div class="grid grid-cols-4 gap-2">
                <DropletGauge v-if="impresora._esCheckmk" label="C" :percent="impresora.toner_cyan || 0" color="#06b6d4"/>
                <DropletGauge v-else label="C" :percent="level(impresora, ['cyan','cian','tinta_c','tinta_cyan'])" color="#06b6d4"/>
                
                <DropletGauge v-if="impresora._esCheckmk" label="M" :percent="impresora.toner_magenta || 0" color="#ec4899"/>
                <DropletGauge v-else label="M" :percent="level(impresora, ['magenta','m','tinta_m','tinta_magenta'])" color="#ec4899"/>
                
                <DropletGauge v-if="impresora._esCheckmk" label="Y" :percent="impresora.toner_yellow || 0" color="#eab308"/>
                <DropletGauge v-else label="Y" :percent="level(impresora, ['yellow','amarillo','y','tinta_y','tinta_yellow'])" color="#eab308"/>
                
                <DropletGauge v-if="impresora._esCheckmk" label="K" :percent="impresora.toner_black || 0" color="#111827"/>
                <DropletGauge v-else label="K" :percent="level(impresora, ['black','negro','k','tinta_k','tinta_black'])" color="#111827"/>
              </div>
            </div>

            <!-- Consumibles dinámicos desde CheckMK (excluye tintas) -->
            <div v-if="impresora._esCheckmk && otrosConsumibles(impresora).length" class="mt-3 grid grid-cols-2 gap-3">
              <BarGauge v-for="(c, idx) in otrosConsumibles(impresora)" :key="idx + '-' + (c.key || c.label)" :label="c.label" :percent="c.percent ?? 0" />
            </div>
              <div v-else-if="!impresora._esCheckmk" class="mt-3 grid grid-cols-2 gap-3">
                <BarGauge label="KIT ADF" :percent="level(impresora, ['kit_adf','adf'])"/>
                <BarGauge label="Unidad imagen" :percent="level(impresora, ['unidad_imagen','imagen','drum'])"/>
                <BarGauge label="Rodillos" :percent="level(impresora, ['rodillos','rollers'])"/>
                <BarGauge label="Fusor" :percent="level(impresora, ['fusor','fuser'])"/>
            </div>

            <!-- Acciones -->
            <div class="flex items-center justify-between pt-3">
              <div class="text-xs text-muted">Actualizado: {{ formatDate(impresora.updated_at) }}</div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="filteredImpresoras.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
  <p class="mt-2 text-sm text-muted">No se encontraron impresoras</p>
      </div>

      <!-- Impresoras de CheckMK -->
      <div v-if="false" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <div v-for="impresora in checkmkImpresoras" :key="impresora.id" class="card hover:shadow-md transition overflow-hidden">
          <!-- Header con estado -->
          <div class="relative h-36 bg-gradient-to-br from-blue-50 to-blue-100">
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <svg class="h-16 w-16 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
              </svg>
              <div class="mt-2 flex items-center space-x-2">
                <span :class="{
                  'h-3 w-3 rounded-full': true,
                  'bg-green-500': impresora.estado === 'online',
                  'bg-yellow-500': impresora.estado === 'warning',
                  'bg-red-500': impresora.estado === 'error',
                  'bg-gray-400': impresora.estado === 'unknown'
                }"></span>
                <span class="text-sm font-medium text-gray-700 capitalize">{{ impresora.estado }}</span>
              </div>
            </div>
            <div class="absolute top-2 left-2">
              <span class="px-2 py-0.5 rounded bg-white/90 text-gray-800 text-xs font-medium shadow">CheckMK</span>
            </div>
          </div>

          <!-- Info principal -->
          <div class="p-4 space-y-2">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-sm text-gray-500">{{ impresora.marca || 'Desconocido' }}</div>
                <div class="font-semibold text-gray-900">{{ impresora.modelo || impresora.hostname }}</div>
              </div>
            </div>
            <div class="text-sm text-gray-500">
              <span>{{ impresora.ip_address || 'sin IP' }}</span>
            </div>
            <div class="text-xs text-gray-400">
              <span>Host: {{ impresora.hostname }}</span>
            </div>

            <!-- Niveles de toners -->
            <div v-if="impresora.toner_cyan || impresora.toner_magenta || impresora.toner_yellow || impresora.toner_black" class="mt-2">
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-600">Toners</span>
                <span class="text-xs text-gray-400">% restantes</span>
              </div>
              <div class="grid grid-cols-4 gap-2">
                <DropletGauge v-if="impresora.toner_cyan !== null" label="C" :percent="impresora.toner_cyan" color="#06b6d4"/>
                <DropletGauge v-if="impresora.toner_magenta !== null" label="M" :percent="impresora.toner_magenta" color="#ec4899"/>
                <DropletGauge v-if="impresora.toner_yellow !== null" label="Y" :percent="impresora.toner_yellow" color="#eab308"/>
                <DropletGauge v-if="impresora.toner_black !== null" label="K" :percent="impresora.toner_black" color="#111827"/>
              </div>
            </div>

            <!-- Otros consumibles -->
            <div v-if="impresora.drum_unit || impresora.fuser" class="mt-3 grid grid-cols-2 gap-3">
              <BarGauge v-if="impresora.drum_unit !== null" label="Drum" :percent="impresora.drum_unit"/>
              <BarGauge v-if="impresora.fuser !== null" label="Fusor" :percent="impresora.fuser"/>
            </div>

            <!-- Contadores de páginas -->
            <div v-if="getPaginasInfo(impresora)" class="mt-3 p-2 bg-gray-50 rounded">
              <div class="text-xs font-medium text-gray-600 mb-1">Páginas impresas</div>
              <div v-if="getPaginasInfo(impresora).showDetailed" class="grid grid-cols-3 gap-2 text-xs">
                <div>
                  <div class="text-gray-500">Total</div>
                  <div class="font-semibold">{{ formatNumber(getPaginasInfo(impresora).total) }}</div>
                </div>
                <div>
                  <div class="text-gray-500">B/N</div>
                  <div class="font-semibold">{{ formatNumber(getPaginasInfo(impresora).bn) }}</div>
                </div>
                <div>
                  <div class="text-gray-500">Color</div>
                  <div class="font-semibold">{{ formatNumber(getPaginasInfo(impresora).color) }}</div>
                </div>
              </div>
              <div v-else class="text-xs">
                <div class="flex justify-between">
                  <div class="text-gray-500">B/N</div>
                  <div class="font-semibold">{{ formatNumber(getPaginasInfo(impresora).bn) }}</div>
                </div>
              </div>
            </div>

            <!-- Info adicional -->
            <div class="flex items-center justify-between pt-3 text-xs">
              <div class="text-gray-400">
                <span v-if="impresora.uptime_dias">Uptime: {{ impresora.uptime_dias }}d</span>
              </div>
              <div class="text-gray-400">{{ formatDate(impresora.sync_timestamp) }}</div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="showCheckmkData && checkmkImpresoras.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
        </svg>
        <p class="mt-2 text-sm text-gray-500">No hay datos sincronizados de CheckMK</p>
        <button @click="sincronizarCheckmk" class="mt-4 btn btn-primary">
          Sincronizar ahora
        </button>
      </div>
    </div>

    <!-- Modal: Crear impresora -->
    <transition name="fade">
      <div v-if="showCreateModal" class="modal-overlay">
        <div class="modal w-full max-w-2xl">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Nueva impresora</h3>
            <button @click="closeCreateModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-md transition">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div v-if="create.error" class="alert alert-error mb-4">{{ create.error }}</div>
          <div v-if="create.success" class="alert alert-success mb-4">{{ create.success }}</div>

          <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="createImpresora">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Referencia<span class="text-red-500">*</span></label>
              <input v-model="newImpresora.referencia" class="input" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Número de serie<span class="text-red-500">*</span></label>
              <input v-model="newImpresora.numero_serie" class="input" required />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Marca<span class="text-red-500">*</span></label>
              <input v-model="newImpresora.marca" class="input" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Modelo<span class="text-red-500">*</span></label>
              <input v-model="newImpresora.modelo" class="input" required />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">IP</label>
              <input v-model="newImpresora.ip" class="input" placeholder="192.168.1.50" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Sede<span class="text-red-500">*</span></label>
              <select v-model="newImpresora.sede" class="select" required @change="onSedeChange">
                <option value="">Seleccionar</option>
                <option value="Constitucion">Constitución</option>
                <option value="Cultura">Cultura</option>
                <option value="Deportes">Deportes</option>
                <option value="Igualdad">Igualdad</option>
                <option value="Biblioteca">Biblioteca</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Departamento</label>
              <select v-model="newImpresora.departamento_id" class="select" :disabled="!newImpresora.sede">
                <option value="">Seleccionar departamento</option>
                <option v-for="dept in availableDepartamentos" :key="dept.id" :value="dept.id">{{ dept.nombre }}</option>
              </select>
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Ubicación</label>
              <input v-model="newImpresora.ubicacion" class="input" placeholder="Planta 2, Oficina 204" />
            </div>

            <!-- Campos personalizados -->
            <div class="md:col-span-2">
              <h4 class="text-sm font-semibold text-gray-700 mt-2">Campos personalizados</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                <div v-for="cf in customDefs" :key="cf.id">
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ cf.label }}<span v-if="cf.required" class="text-red-500">*</span>
                  </label>
                  <template v-if="cf.type === 'select'">
                    <select v-model="customValues[cf.key]" class="select" :required="cf.required">
                      <option v-for="opt in (cf.options || [])" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                  </template>
                  <template v-else-if="cf.type === 'boolean'">
                    <input type="checkbox" v-model="customValues[cf.key]" class="h-4 w-4 align-middle">
                  </template>
                  <template v-else>
                    <input :type="cf.type === 'number' ? 'number' : (cf.type === 'date' ? 'date' : 'text')" v-model="customValues[cf.key]" class="input" :required="cf.required" />
                  </template>
                </div>
              </div>
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Foto de la impresora</label>
              <input type="file" accept="image/*" @change="onPhotoSelected" class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-junta-green file:text-white hover:file:bg-junta-green-600" />
              <p v-if="photoPreview" class="mt-2 text-xs text-gray-500">Previsualización:</p>
              <img v-if="photoPreview" :src="photoPreview" alt="Preview" class="mt-1 h-24 w-24 object-cover rounded border" />
            </div>

            <div class="md:col-span-2 flex justify-end space-x-3 pt-2">
              <button type="button" @click="closeCreateModal" class="btn btn-secondary">Cancelar</button>
              <button type="submit" :disabled="create.loading" class="btn btn-primary">
                <svg v-if="create.loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Guardar
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>

    <!-- Modal: Detalles de impresora -->
    <transition name="fade">
      <div v-if="showDetailModal && !isEditingDetail" class="modal-overlay">
        <div class="modal w-full max-w-4xl">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">
              {{ selectedImpresora?.datos?.marca }} {{ selectedImpresora?.datos?.modelo }}
            </h3>
            <button @click="closeDetailModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-md transition">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div v-if="selectedImpresora" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Columna izquierda: Info general -->
            <div class="space-y-4">
              <div>
                <span class="px-3 py-1 text-xs font-medium rounded-full" :class="selectedImpresora._esCheckmk ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'">
                  {{ selectedImpresora._esCheckmk ? 'CheckMK' : 'Inventario' }}
                </span>
              </div>

              <div class="card p-4 space-y-3">
                <h4 class="font-semibold">Información General</h4>
                <div class="grid grid-cols-2 gap-2 text-sm">
                  <div class="text-gray-500">Referencia:</div>
                  <div class="font-medium">{{ selectedImpresora.datos?.referencia || selectedImpresora.hostname }}</div>
                  
                  <div class="text-gray-500">IP:</div>
                  <div class="font-medium">{{ selectedImpresora.datos?.ip || selectedImpresora.ip_address || 'N/A' }}</div>
                  
                  <div class="text-gray-500">Sede:</div>
                  <div class="font-medium">{{ selectedImpresora.datos?.sede || 'N/A' }}</div>
                  
                  <div class="text-gray-500">Departamento:</div>
                  <div class="font-medium">{{ getDepartamentoName(selectedImpresora.departamento_id) || 'N/A' }}</div>
                  
                  <div class="text-gray-500">Ubicación:</div>
                  <div class="font-medium">{{ selectedImpresora.datos?.ubicacion || 'N/A' }}</div>
                  
                  <div v-if="selectedImpresora._esCheckmk" class="text-gray-500">Estado:</div>
                  <div v-if="selectedImpresora._esCheckmk" class="font-medium capitalize">
                    <span :class="{
                      'text-green-600': selectedImpresora.estado === 'online',
                      'text-yellow-600': selectedImpresora.estado === 'warning',
                      'text-red-600': selectedImpresora.estado === 'error'
                    }">{{ selectedImpresora.estado }}</span>
                  </div>
                </div>
              </div>

              <!-- Foto de la impresora -->
              <div v-if="getFotoImpresora(selectedImpresora)" class="card p-4">
                <h4 class="font-semibold mb-2">Fotografía</h4>
                <div class="bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg p-4">
                  <img :src="getFotoImpresora(selectedImpresora)" class="w-full h-64 object-contain rounded-lg" alt="Foto de la impresora" />
                </div>
              </div>

              <!-- Campos personalizados con valor -->
              <div v-if="detailCustomFields.length" class="card p-4">
                <h4 class="font-semibold mb-3">Campos personalizados</h4>
                <div class="grid grid-cols-2 gap-2 text-sm">
                  <template v-for="cf in detailCustomFields" :key="cf.key">
                    <div class="text-gray-500">{{ cf.label }}<span v-if="cf.required" class="text-red-500">*</span>:</div>
                    <div class="font-medium break-words">{{ cf.formatted }}</div>
                  </template>
                </div>
              </div>

              <!-- Ubicación en plano (si existe) -->
              <div v-if="planUbicacion.data?.plano" class="card p-4">
                <div class="flex items-center justify-between mb-2">
                  <h4 class="font-semibold">Ubicación en plano</h4>
                  <button @click="abrirPlanoDesdeDetalle" class="text-sm text-junta-green-600 hover:text-junta-green-700 font-medium flex items-center gap-1 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                    </svg>
                    Ver en grande
                  </button>
                </div>
                <div class="relative w-full overflow-hidden rounded-lg border-2 border-gray-200 hover:border-junta-green-400 transition cursor-pointer group" @click="abrirPlanoDesdeDetalle">
                  <img :src="storageUrlFile(planUbicacion.data.plano?.imagen)" class="w-full select-none" alt="Plano"/>
                  <!-- Marcador de ubicación -->
                  <div class="absolute" :style="{
                    left: ((planUbicacion.data.ubicacion.x/3000)*100)+'%',
                    top: ((planUbicacion.data.ubicacion.y/2000)*100)+'%',
                    transform: 'translate(-50%, -50%)'
                  }">
                    <div class="relative">
                      <div class="h-5 w-5 rounded-full bg-junta-green-500 border-2 border-white shadow-lg animate-pulse"></div>
                      <div class="absolute inset-0 h-5 w-5 rounded-full bg-junta-green-500 animate-ping opacity-75"></div>
                    </div>
                  </div>
                  <!-- Overlay al hacer hover -->
                  <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition flex items-center justify-center">
                    <div class="text-white opacity-0 group-hover:opacity-100 transition flex items-center gap-2 bg-black/50 px-4 py-2 rounded-lg backdrop-blur">
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                      </svg>
                      <span class="font-medium">Click para ampliar</span>
                    </div>
                  </div>
                </div>
                <div class="text-xs text-gray-500 mt-2 flex items-center justify-between">
                  <span>{{ planUbicacion.data.plano?.nombre }} · {{ planUbicacion.data.plano?.sede }}</span>
                  <span class="text-gray-400">({{ planUbicacion.data.ubicacion.x }}, {{ planUbicacion.data.ubicacion.y }})</span>
                </div>
              </div>
            </div>

            <!-- Columna derecha: Consumibles -->
            <div class="space-y-4">
              <!-- Niveles de tinta -->
              <div class="card p-4">
                <h4 class="font-semibold text-gray-900 mb-3">Niveles de Tinta</h4>
                <div class="grid grid-cols-4 gap-4">
                  <DropletGauge v-if="selectedImpresora._esCheckmk" label="C" :percent="selectedImpresora.toner_cyan || 0" color="#06b6d4"/>
                  <DropletGauge v-else label="C" :percent="level(selectedImpresora, ['cyan','cian','tinta_c','tinta_cyan'])" color="#06b6d4"/>
                  
                  <DropletGauge v-if="selectedImpresora._esCheckmk" label="M" :percent="selectedImpresora.toner_magenta || 0" color="#ec4899"/>
                  <DropletGauge v-else label="M" :percent="level(selectedImpresora, ['magenta','m','tinta_m','tinta_magenta'])" color="#ec4899"/>
                  
                  <DropletGauge v-if="selectedImpresora._esCheckmk" label="Y" :percent="selectedImpresora.toner_yellow || 0" color="#eab308"/>
                  <DropletGauge v-else label="Y" :percent="level(selectedImpresora, ['yellow','amarillo','y','tinta_y','tinta_yellow'])" color="#eab308"/>
                  
                  <DropletGauge v-if="selectedImpresora._esCheckmk" label="K" :percent="selectedImpresora.toner_black || 0" color="#111827"/>
                  <DropletGauge v-else label="K" :percent="level(selectedImpresora, ['black','negro','k','tinta_k','tinta_black'])" color="#111827"/>
                </div>
              </div>

              <!-- Otros consumibles dinámicos (excluye tintas) -->
              <div v-if="selectedImpresora._esCheckmk && otrosConsumibles(selectedImpresora).length" class="card p-4">
                <h4 class="font-semibold mb-3">Otros Consumibles</h4>
                <div class="space-y-2">
                  <BarGauge v-for="(c, idx) in otrosConsumibles(selectedImpresora)" :key="'modal-'+idx+'-'+(c.key||c.label)" :label="c.label" :percent="c.percent ?? 0"/>
                </div>
              </div>

              <!-- Contadores de páginas -->
              <div v-if="selectedImpresora._esCheckmk && getPaginasInfo(selectedImpresora)" class="card p-4">
                <h4 class="font-semibold mb-3">Páginas Impresas</h4>
                <div v-if="getPaginasInfo(selectedImpresora).showDetailed" class="grid grid-cols-3 gap-3 text-center">
                  <div>
                    <div class="text-2xl font-bold">{{ formatNumber(getPaginasInfo(selectedImpresora).total) }}</div>
                    <div class="text-xs text-gray-500">Total</div>
                  </div>
                  <div>
                    <div class="text-2xl font-bold">{{ formatNumber(getPaginasInfo(selectedImpresora).bn) }}</div>
                    <div class="text-xs text-gray-500">B/N</div>
                  </div>
                  <div>
                    <div class="text-2xl font-bold">{{ formatNumber(getPaginasInfo(selectedImpresora).color) }}</div>
                    <div class="text-xs text-gray-500">Color</div>
                  </div>
                </div>
                <div v-else class="text-center">
                  <div class="text-3xl font-bold">{{ formatNumber(getPaginasInfo(selectedImpresora).bn) }}</div>
                  <div class="text-sm text-gray-500">Páginas B/N</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Botones de acción -->
          <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
            <button @click="closeDetailModal" class="btn btn-secondary">Cerrar</button>
              <button v-if="selectedImpresora._esInventario" @click="deleteFromDetail" class="btn bg-red-600 text-white hover:bg-red-700">Eliminar</button>
              <button @click="editFromDetail" class="btn btn-primary">Editar</button>
              <button @click="goToPlanos" class="btn btn-secondary">Abrir en Planos</button>
          </div>
        </div>
      </div>
    </transition>

      <!-- Modal: Editar desde detalles -->
      <transition name="fade">
        <div v-if="showDetailModal && isEditingDetail" class="modal-overlay">
          <div class="modal w-full max-w-2xl">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold">
                {{ selectedImpresora._esInventario ? 'Editar Impresora' : 'Agregar al Inventario' }}
              </h3>
              <button @click="cancelEditDetail" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div v-if="create.error" class="alert alert-error mb-4">{{ create.error }}</div>
            <div v-if="create.success" class="alert alert-success mb-4">{{ create.success }}</div>

            <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="saveEditDetail">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Referencia<span class="text-red-500">*</span></label>
                <input v-model="newImpresora.referencia" class="input" required />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Número de serie<span class="text-red-500">*</span></label>
                <input v-model="newImpresora.numero_serie" class="input" required />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Marca<span class="text-red-500">*</span></label>
                <input v-model="newImpresora.marca" class="input" required />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Modelo<span class="text-red-500">*</span></label>
                <input v-model="newImpresora.modelo" class="input" required />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">IP</label>
                <input v-model="newImpresora.ip" class="input" placeholder="192.168.1.50" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sede<span class="text-red-500">*</span></label>
                <select v-model="newImpresora.sede" class="select" required @change="onSedeChange">
                  <option value="">Seleccionar</option>
                  <option v-for="opt in sedeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Departamento</label>
                <select v-model="newImpresora.departamento_id" class="select" :disabled="!newImpresora.sede">
                  <option value="">Seleccionar departamento</option>
                  <option v-for="dept in availableDepartamentos" :key="dept.id" :value="dept.id">{{ dept.nombre }}</option>
                </select>
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Ubicación</label>
                <input v-model="newImpresora.ubicacion" class="input" placeholder="Planta 2, Oficina 204" />
              </div>

              <!-- Campos personalizados -->
              <div class="md:col-span-2">
                <h4 class="text-sm font-semibold text-gray-700 mt-2">Campos personalizados</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                  <div v-for="cf in customDefs" :key="cf.id">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      {{ cf.label }}<span v-if="cf.required" class="text-red-500">*</span>
                    </label>
                    <template v-if="cf.type === 'select'">
                      <select v-model="customValues[cf.key]" class="select" :required="cf.required">
                        <option v-for="opt in (cf.options || [])" :key="opt" :value="opt">{{ opt }}</option>
                      </select>
                    </template>
                    <template v-else-if="cf.type === 'boolean'">
                      <input type="checkbox" v-model="customValues[cf.key]" class="h-4 w-4 align-middle">
                    </template>
                    <template v-else>
                      <input :type="cf.type === 'number' ? 'number' : (cf.type === 'date' ? 'date' : 'text')" v-model="customValues[cf.key]" class="input" :required="cf.required" />
                    </template>
                  </div>
                </div>
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Foto de la impresora</label>
                <input type="file" accept="image/*" @change="onPhotoSelected" class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-junta-green file:text-white hover:file:bg-junta-green-600" />
                <p v-if="photoPreview" class="mt-2 text-xs text-gray-500">Previsualización:</p>
                <img v-if="photoPreview" :src="photoPreview" alt="Preview" class="mt-1 h-24 w-24 object-cover rounded border" />
              </div>

              <div class="md:col-span-2 flex justify-end space-x-3 pt-2">
                <button type="button" @click="cancelEditDetail" class="btn btn-secondary">Cancelar</button>
                <button type="submit" :disabled="create.loading" class="btn btn-primary">
                  <svg v-if="create.loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Guardar
                </button>
              </div>
            </form>
          </div>
        </div>
      </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, h } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

const impresoras = ref([]);
const checkmkImpresoras = ref([]);
const sedes = ref([]);
const showCheckmkData = ref(false);
const searchQuery = ref('');
const filterSede = ref('');
const showCreateModal = ref(false);
const showDetailModal = ref(false);
const selectedImpresora = ref(null);
const isEditingDetail = ref(false);
const create = ref({ loading: false, error: '', success: '' });
const sync = ref({ loading: false, error: '', success: '' });
const newImpresora = ref({
  referencia: '',
  ip: '',
  marca: '',
  modelo: '',
  sede: '',
  numero_serie: '',
  ubicacion: '',
  departamento_id: null,
});

const selectedPhoto = ref(null);
const photoPreview = ref('');

// Campos personalizados
const customDefs = ref([]);
const customValues = ref({});
const loadCustomDefs = async () => {
  try {
    const res = await axios.get('/config/campos', { params: { entity_type: 'impresora' } });
    customDefs.value = res.data.data || [];
  } catch (e) {}
};

// Departamentos disponibles según sede seleccionada
const availableDepartamentos = computed(() => {
  if (!newImpresora.value.sede || !sedes.value.length) return [];
  const sedeSeleccionada = sedes.value.find(s => normalizeSede(s.nombre) === newImpresora.value.sede);
  return sedeSeleccionada?.departamentos || [];
});

const onSedeChange = () => {
  // Resetear departamento si cambia la sede
  newImpresora.value.departamento_id = null;
};

const route = useRoute();

onMounted(async () => {
  await loadSedes();
  await loadCustomDefs();
  await loadImpresoras();
  await loadCheckmkImpresoras();
  // Si venimos desde Planos con ?hostname=..., abrir modal de detalles
  const host = route.query.hostname;
  if (typeof host === 'string' && host) {
    const target = filteredImpresoras.value.find(i => i.hostname === host || i.datos?.referencia === host);
    if (target) openDetailModal(target);
  }
});

const loadSedes = async () => {
  try {
    const res = await axios.get('/sedes');
    sedes.value = res?.data?.data || [];
  } catch (e) {
    sedes.value = [];
  }
};

const normalizeSede = (nombre) => {
  if (!nombre) return '';
  return nombre
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/\s+/g, ' ')
    .trim()
    ;
};

const sedeOptions = computed(() => {
  const arr = Array.isArray(sedes.value) ? sedes.value : [];
  return arr.map(s => ({ label: s.nombre, value: normalizeSede(s.nombre) }));
});

const labelFromSedeValue = (val) => {
  if (!val) return '';
  const opt = sedeOptions.value.find(o => o.value === val);
  return opt ? opt.label : val;
};

const getDepartamentoName = (departamentoId) => {
  if (!departamentoId) return '';
  for (const sede of sedes.value) {
    const dept = sede.departamentos?.find(d => d.id === departamentoId);
    if (dept) return dept.nombre;
  }
  return '';
};

const loadImpresoras = async () => {
  try {
    const response = await axios.get('/entidades', {
      params: { tipo_entidad_id: 1 } // ID del tipo impresora
    });
    if (response.data.success) {
      impresoras.value = response.data.data;
    }
  } catch (error) {
    console.error('Error al cargar impresoras:', error);
  }
};

const loadCheckmkImpresoras = async () => {
  try {
    const response = await axios.get('/checkmk/impresoras');
    console.log('Respuesta CheckMK API:', response.data); // DEBUG
    if (response.data.success) {
      checkmkImpresoras.value = response.data.data;
      console.log('CheckMK impresoras cargadas:', checkmkImpresoras.value.length); // DEBUG
    }
  } catch (error) {
    console.error('Error al cargar impresoras de CheckMK:', error);
  }
};

const sincronizarCheckmk = async () => {
  sync.value = { loading: true, error: '', success: '' };
  try {
    const response = await axios.post('/checkmk/sincronizar');
    if (response.data.success) {
      sync.value.success = response.data.message;
      await loadCheckmkImpresoras();
      setTimeout(() => { sync.value.success = ''; }, 5000);
    }
  } catch (error) {
    const apiMsg = error?.response?.data?.message;
    sync.value.error = apiMsg || 'Error al sincronizar con CheckMK';
    setTimeout(() => { sync.value.error = ''; }, 5000);
  } finally {
    sync.value.loading = false;
  }
};

const toggleViewCheckmk = () => {
  showCheckmkData.value = !showCheckmkData.value;
};

const filteredImpresoras = computed(() => {
  // SOLO CheckMK como fuente única, con posibilidad de sobrescribir con datos_adicionales
  const lista = checkmkImpresoras.value.map(imp => {
    const extra = imp.datos_adicionales || {};
    const foto = extra.foto ? [extra.foto] : [];
    return {
      ...imp,
      _origen: 'checkmk',
      _esCheckmk: true,
      datos: {
        marca: extra.marca || imp.marca || 'Desconocido',
        modelo: extra.modelo || imp.modelo || imp.hostname,
        ip: extra.ip || imp.ip_address,
        referencia: extra.referencia || imp.hostname,
        sede: extra.sede || '',
        ubicacion: extra.ubicacion || '',
        numero_serie: extra.numero_serie || imp.numero_serie || '',
        foto: extra.foto || null, // Agregar la foto aquí también
      },
      fotos: foto,
    };
  });

  return lista.filter(impresora => {
    const matchesSearch = !searchQuery.value ||
      JSON.stringify(impresora).toLowerCase().includes(searchQuery.value.toLowerCase());

    const matchesSede = !filterSede.value || 
      (impresora.datos?.sede && impresora.datos.sede === filterSede.value);

    return matchesSearch && matchesSede;
  });
});

const editingId = ref(null);

const openDetailModal = (impresora) => {
  selectedImpresora.value = impresora;
    // cargar ubicación en plano si existe
    loadPlanUbicacionForSelected();
  showDetailModal.value = true;
};

const closeDetailModal = () => {
  showDetailModal.value = false;
  selectedImpresora.value = null;
  isEditingDetail.value = false;
    planUbicacion.value = { loading: false, data: null, error: '' };
};

const editFromDetail = () => {
  if (selectedImpresora.value._esCheckmk) {
    const d = selectedImpresora.value.datos || {};
    newImpresora.value = {
      referencia: d.referencia || selectedImpresora.value.hostname || '',
      ip: d.ip || selectedImpresora.value.ip_address || '',
      marca: d.marca || selectedImpresora.value.marca || '',
      modelo: d.modelo || selectedImpresora.value.modelo || '',
      sede: d.sede || '',
      numero_serie: d.numero_serie || selectedImpresora.value.numero_serie || '',
      ubicacion: d.ubicacion || '',
      departamento_id: selectedImpresora.value.departamento_id || null,
    };
    // Cargar valores personalizados si existen en datos_adicionales
    const extra = selectedImpresora.value.datos_adicionales || {};
    const cf = extra.custom_fields;
    // Normalizar: siempre objeto plano, nunca array (evita JSON.stringify de arrays ignorando claves string)
    customValues.value = (cf && typeof cf === 'object' && !Array.isArray(cf)) ? { ...cf } : {};
    editingId.value = null; // No inventario
  } else {
    // Inventario existente (legado)
    newImpresora.value = {
      referencia: selectedImpresora.value.datos.referencia || '',
      ip: selectedImpresora.value.datos.ip || '',
      marca: selectedImpresora.value.datos.marca || '',
      modelo: selectedImpresora.value.datos.modelo || '',
      sede: selectedImpresora.value.datos.sede || '',
      numero_serie: selectedImpresora.value.datos.numero_serie || '',
      ubicacion: selectedImpresora.value.datos.ubicacion || ''
    };
    customValues.value = {}; // legacy path
    editingId.value = selectedImpresora.value.id;
  }
  isEditingDetail.value = true;
};

const cancelEditDetail = () => {
  isEditingDetail.value = false;
  create.value = { loading: false, error: '', success: '' };
  newImpresora.value = { referencia: '', ip: '', marca: '', modelo: '', sede: '', numero_serie: '', ubicacion: '', departamento_id: null };
  selectedPhoto.value = null;
  photoPreview.value = '';
  customValues.value = {};
};

const saveEditDetail = async () => {
  create.value.loading = true;
  create.value.error = '';
  try {
    if (selectedImpresora.value?._esCheckmk) {
      // Actualizar datos adicionales de CheckMK (sin crear en inventario)
      const payload = {
        referencia: newImpresora.value.referencia,
        ip: newImpresora.value.ip,
        marca: newImpresora.value.marca,
        modelo: newImpresora.value.modelo,
        sede: newImpresora.value.sede,
        numero_serie: newImpresora.value.numero_serie,
        ubicacion: newImpresora.value.ubicacion,
        departamento_id: newImpresora.value.departamento_id,
        custom_fields: (customValues.value && typeof customValues.value === 'object' && !Array.isArray(customValues.value)) ? customValues.value : {},
      };
      const hostname = selectedImpresora.value.hostname || selectedImpresora.value.datos?.referencia;
      const response = await axios.patch(`/checkmk/impresoras/${encodeURIComponent(hostname)}`, payload);
      const data = response.data;
      if (data.success) {
        if (selectedPhoto.value) {
          const fd = new FormData();
          fd.append('photo', selectedPhoto.value);
          await axios.post(`/checkmk/impresoras/${encodeURIComponent(hostname)}/upload-photo`, fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
          });
        }
        create.value.success = 'Impresora actualizada correctamente';
        await loadCheckmkImpresoras();
        
        // Actualizar la impresora seleccionada con los nuevos datos
        const updatedImpresora = filteredImpresoras.value.find(imp => 
          (imp.hostname || imp.datos?.referencia) === hostname
        );
        if (updatedImpresora) {
          selectedImpresora.value = updatedImpresora;
        }
        
        setTimeout(() => {
          cancelEditDetail();
          // No cerrar el modal para que vea los cambios
          // closeDetailModal();
        }, 800);
      }
    } else if (editingId.value) {
      // Soporte legado para inventario existente (no crear nuevas)
      const payload = { tipo_entidad_id: 1, datos: { ...newImpresora.value }, custom_fields: customValues.value };
      const response = await axios.put(`/entidades/${editingId.value}`, payload);
      const data = response.data;
      if (data.success) {
        if (selectedPhoto.value) {
          const fd = new FormData();
          fd.append('photo', selectedPhoto.value);
          await axios.post(`/entidades/${editingId.value}/upload-photo`, fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
          });
        }
        create.value.success = 'Impresora actualizada correctamente';
        await loadImpresoras();
        setTimeout(() => {
          cancelEditDetail();
          closeDetailModal();
        }, 800);
      }
    }
  } catch (e) {
    const apiMsg = e?.response?.data?.message;
    const apiErrors = e?.response?.data?.errors;
    if (apiErrors) {
      const first = Object.values(apiErrors).flat()[0];
      create.value.error = first || apiMsg || 'No se pudo guardar la impresora';
    } else {
      create.value.error = apiMsg || 'No se pudo guardar la impresora';
    }
  } finally {
    create.value.loading = false;
  }
};

const deleteFromDetail = async () => {
  if (!confirm('¿Está seguro de eliminar esta impresora?')) return;
  
  try {
    await axios.delete(`/entidades/${selectedImpresora.value.id}`);
    await loadImpresoras();
    closeDetailModal();
  } catch (error) {
    console.error('Error al eliminar impresora:', error);
    alert('Error al eliminar la impresora');
  }
};

const deleteImpresora = async (id) => {
  if (!confirm('¿Está seguro de eliminar esta impresora?')) return;
  
  try {
    await axios.delete(`/entidades/${id}`);
    await loadImpresoras();
  } catch (error) {
    console.error('Error al eliminar impresora:', error);
  }
};

const closeCreateModal = () => {
  showCreateModal.value = false;
  create.value = { loading: false, error: '', success: '' };
  newImpresora.value = { referencia: '', ip: '', marca: '', modelo: '', sede: '', numero_serie: '', ubicacion: '' };
  selectedPhoto.value = null;
  photoPreview.value = '';
  editingId.value = null;
  customValues.value = {};
};

const createImpresora = async () => {
  create.value.loading = true;
  create.value.error = '';
  try {
    const payload = {
      tipo_entidad_id: 1,
      datos: { ...newImpresora.value },
      custom_fields: customValues.value,
    };
    
    let data;
    if (editingId.value) {
      // Actualizar impresora existente
      const response = await axios.put(`/entidades/${editingId.value}`, payload);
      data = response.data;
    } else {
      // Crear nueva impresora
      const response = await axios.post('/entidades', payload);
      data = response.data;
    }
    
    if (data.success) {
      const entityId = editingId.value || data.data.id;
      // Subir foto si se seleccionó
      if (selectedPhoto.value) {
        const fd = new FormData();
        fd.append('photo', selectedPhoto.value);
        await axios.post(`/entidades/${entityId}/upload-photo`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      }
      create.value.success = editingId.value ? 'Impresora actualizada correctamente' : 'Impresora creada correctamente';
      await loadImpresoras();
      await loadCheckmkImpresoras();
      setTimeout(() => closeCreateModal(), 400);
    }
  } catch (e) {
    const apiMsg = e?.response?.data?.message;
    const apiErrors = e?.response?.data?.errors;
    if (apiErrors) {
      const first = Object.values(apiErrors).flat()[0];
      create.value.error = first || apiMsg || 'No se pudo crear la impresora';
    } else {
      create.value.error = apiMsg || 'No se pudo crear la impresora';
    }
  } finally {
    create.value.loading = false;
  }
};

const onPhotoSelected = (e) => {
  const file = e.target.files?.[0];
  if (!file) { selectedPhoto.value = null; photoPreview.value = ''; return; }
  selectedPhoto.value = file;
  const reader = new FileReader();
  reader.onload = () => { photoPreview.value = reader.result; };
  reader.readAsDataURL(file);
};

// Helpers visuales y de datos
const clamp = (n) => Math.max(0, Math.min(100, Number.isFinite(+n) ? +n : 0));
const level = (imp, keys) => {
  const d = imp?.datos || {};
  for (const k of keys) {
    if (d[k] !== undefined && d[k] !== null) return clamp(d[k]);
  }
  return 0;
};
const firstPhoto = (imp) => (Array.isArray(imp?.fotos) && imp.fotos.length ? imp.fotos[0] : null);
const photoUrl = (rel) => rel ? `/storage/${rel.replace(/^\/?storage\//,'')}` : '';
const getFotoImpresora = (imp) => {
  if (!imp) return null;
  // Si es de inventario y tiene fotos
  if (imp._esInventario && firstPhoto(imp)) {
    return photoUrl(firstPhoto(imp));
  }
  // Si es de CheckMK y tiene foto en datos_adicionales
  if (imp._esCheckmk && imp.datos?.foto) {
    return storageUrlFile(imp.datos.foto);
  }
  // También intentar desde datos_adicionales directamente
  if (imp._esCheckmk && imp.datos_adicionales?.foto) {
    return storageUrlFile(imp.datos_adicionales.foto);
  }
  return null;
};
const storageUrlFile = (rel) => {
  if (!rel) return '';
  const cleaned = rel.replace(/^public\//,'').replace(/^storage\//,'');
  return `/gestionmaterial/storage/${cleaned}`;
};
const formatDate = (iso) => iso ? new Date(iso).toLocaleDateString() : '';
const formatNumber = (num) => num ? num.toLocaleString() : '0';

// Función para obtener la información de páginas impresas
const getPaginasInfo = (impresora) => {
  const bn = impresora.paginas_bn;
  const color = impresora.paginas_color;
  const total = impresora.paginas_total;
  
  // Caso 1: Tenemos datos de B/N y Color
  if ((bn !== null && bn !== undefined) && (color !== null && color !== undefined)) {
    const calculatedTotal = bn + color;
    return {
      hasBN: true,
      hasColor: true,
      hasTotal: true,
      bn: bn,
      color: color,
      total: total || calculatedTotal, // Usar el total de la API o calcularlo
      showDetailed: true
    };
  }
  
  // Caso 2: Solo tenemos total (asumir que son páginas B/N)
  if (total !== null && total !== undefined) {
    return {
      hasBN: true,
      hasColor: false,
      hasTotal: false,
      bn: total,
      color: 0,
      total: total,
      showDetailed: false
    };
  }
  
  // Caso 3: Solo tenemos B/N
  if (bn !== null && bn !== undefined) {
    return {
      hasBN: true,
      hasColor: false,
      hasTotal: false,
      bn: bn,
      color: 0,
      total: bn,
      showDetailed: false
    };
  }
  
  // Caso 4: Solo tenemos Color (raro pero posible)
  if (color !== null && color !== undefined) {
    return {
      hasBN: false,
      hasColor: true,
      hasTotal: false,
      bn: 0,
      color: color,
      total: color,
      showDetailed: false
    };
  }
  
  // Caso 5: No hay datos
  return null;
};

// Formateo de valores de campos personalizados
const formatCustomValue = (def, value) => {
  if (value === null || value === undefined) return '';
  if (def?.type === 'boolean') return value ? 'Sí' : 'No';
  if (Array.isArray(value)) return value.join(', ');
  if (typeof value === 'object') return JSON.stringify(value);
  return String(value);
};

// Campos personalizados a mostrar en el modal de detalle
const detailCustomFields = computed(() => {
  const imp = selectedImpresora.value;
  if (!imp) return [];
  const defs = Array.isArray(customDefs.value) ? customDefs.value : [];
  const rawVals = imp?.datos_adicionales?.custom_fields;
  const vals = (rawVals && typeof rawVals === 'object' && !Array.isArray(rawVals)) ? rawVals : {};
  const items = defs.map(def => {
    const hasKey = Object.prototype.hasOwnProperty.call(vals, def.key);
    const raw = hasKey ? vals[def.key] : null;
    const formatted = formatCustomValue(def, raw);
    const hasValue = (() => {
      if (!hasKey) return false;
      if (def.type === 'boolean') return typeof raw === 'boolean';
      if (raw === null || raw === undefined) return false;
      if (typeof raw === 'string') return raw.trim().length > 0;
      if (Array.isArray(raw)) return raw.length > 0;
      return true; // number / object
    })();
    return { id: def.id, key: def.key, label: def.label, required: !!def.required, formatted, hasValue };
  }).filter(it => it.hasValue);

  // Requeridos primero, luego alfabético
  items.sort((a, b) => (Number(b.required) - Number(a.required)) || a.label.localeCompare(b.label));
  return items;
});

// Filtrado de consumibles: excluir únicamente consumibles de categoría 'toner'/'ink'.
// Mantener elementos como "Waste Toner Bottle" (categoría 'waste') aunque contengan la palabra 'toner'.
const otrosConsumibles = (imp) => {
  const arr = Array.isArray(imp?.consumibles) ? imp.consumibles : [];
  return arr.filter(c => {
    const cat = (c?.category || '').toLowerCase();
    if (cat === 'toner' || cat === 'ink') return false;
    return true;
  });
};

// Componentes inline para gauges
const DropletGauge = {
  props: { label: String, percent: Number, color: String },
  setup(props) {
    const pct = Math.max(0, Math.min(100, props.percent || 0));
    return () => h('div', { class: 'flex flex-col items-center' }, [
      h('div', { class: 'relative h-10 w-8' }, [
        h('svg', { viewBox: '0 0 24 24', class: 'absolute inset-0 h-full w-full text-gray-300' }, [
          h('path', { fill: 'currentColor', d: 'M12 2C12 2 5 9 5 13.5C5 17.09 7.91 20 11.5 20H12.5C16.09 20 19 17.09 19 13.5C19 9 12 2 12 2Z' })
        ]),
        h('div', { 
          class: 'absolute bottom-0 left-0 right-0 overflow-hidden rounded-b-[10px]',
          style: 'height: 100%'
        }, [
          h('div', { 
            class: 'absolute bottom-0 left-0 right-0',
            style: `height: ${pct}%; background: ${props.color}`
          })
        ])
      ]),
      h('div', { class: 'mt-1 text-xs font-medium text-gray-700' }, `${pct}%`),
      h('div', { class: 'text-[10px] text-gray-400' }, props.label)
    ]);
  }
};

const BarGauge = {
  props: { label: String, percent: Number },
  setup(props) {
    const pct = Math.max(0, Math.min(100, props.percent || 0));
    return () => h('div', {}, [
      h('div', { class: 'flex items-center justify-between text-xs mb-1' }, [
        h('span', { class: 'text-gray-600' }, props.label),
        h('span', { class: 'text-gray-500' }, `${pct}%`)
      ]),
      h('div', { class: 'h-2 w-full rounded bg-gray-200 overflow-hidden' }, [
        h('div', { class: 'h-full bg-junta-green', style: `width: ${pct}%` })
      ])
    ]);
  }
};

// Ubicación en plano (solo visual)
const planUbicacion = ref({ loading: false, data: null, error: '' });
const router = useRouter();
const loadPlanUbicacionForSelected = async () => {
  const host = selectedImpresora.value?.hostname || selectedImpresora.value?.datos?.referencia;
  if (!host) { planUbicacion.value = { loading: false, data: null, error: '' }; return; }
  planUbicacion.value = { loading: true, data: null, error: '' };
  try {
    const res = await axios.get('/planos/ubicacion-impresora', { params: { hostname: host } });
    planUbicacion.value.data = res.data?.data || null;
  } catch (e) {
    planUbicacion.value.error = e?.response?.data?.message || 'No se pudo cargar la ubicación';
  } finally {
    planUbicacion.value.loading = false;
  }
};

const abrirPlanoDesdeDetalle = () => {
  const host = selectedImpresora.value?.hostname || selectedImpresora.value?.datos?.referencia;
  if (host) {
    router.push({ path: '/planos', query: { hostname: host } });
  }
};

const goToPlanos = () => {
  const host = selectedImpresora.value?.hostname || selectedImpresora.value?.datos?.referencia;
  if (!host) return;
  router.push({ name: 'Planos', query: { hostname: host } });
};
</script>
