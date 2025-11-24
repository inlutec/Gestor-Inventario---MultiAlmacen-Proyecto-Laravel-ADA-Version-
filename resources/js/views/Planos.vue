<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Planos</h1>
  <p class="mt-2 text-sm text-muted">Gestión de planos por sede, con ubicación de impresoras</p>
    </div>

    <!-- Filtro por sede y creación -->
  <div class="card p-4 flex items-end gap-4">
      <div>
        <label class="block text-sm text-gray-600 mb-1">Sede</label>
        <select v-model="filtroSede" class="select">
          <option value="">Todas</option>
          <option v-for="opt in sedeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
      </div>
      <button @click="abrirNuevoPlano" class="btn btn-primary">Nuevo plano</button>
    </div>

    <!-- Lista de planos por sede -->
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="p in planos" :key="p.id" class="card hover:shadow-md transition overflow-hidden">
        <div class="relative h-40 bg-gray-100">
          <img v-if="p.imagen" :src="storageUrl(p.imagen)" class="absolute inset-0 h-full w-full object-cover" />
          <div class="absolute top-2 left-2 px-2 py-0.5 text-xs rounded bg-white/90 text-gray-800 shadow">{{ labelFromSedeValue(p.sede) || '—' }}</div>
        </div>
        <div class="p-4">
          <div class="mb-2">
            <div class="font-semibold">{{ p.nombre }}</div>
            <div class="text-xs text-gray-500">{{ p.descripcion || '—' }}</div>
          </div>
          <div class="flex items-center gap-2">
            <button class="btn btn-primary flex-1" @click="abrirVisualizador(p)">
              <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              Ver plano
            </button>
            <button class="btn btn-secondary" @click="abrirEditor(p)" title="Editar">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
            </button>
            <button class="btn bg-red-600 text-white hover:bg-red-700" @click="eliminarPlano(p)" title="Eliminar">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: nuevo plano -->
    <transition name="fade">
      <div v-if="modalNuevo.open" class="modal-overlay">
        <div class="modal w-full max-w-xl">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Nuevo plano</h3>
            <button @click="modalNuevo.open=false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div v-if="nuevo.error" class="mb-3 px-3 py-2 rounded bg-red-50 text-red-700 text-sm border border-red-200">{{ nuevo.error }}</div>
          <div v-if="nuevo.success" class="mb-3 px-3 py-2 rounded bg-green-50 text-green-700 text-sm border border-green-200">{{ nuevo.success }}</div>
          <form class="space-y-4" @submit.prevent="crearPlano">
            <div>
              <label class="block text-sm font-medium mb-1">Nombre</label>
              <input v-model="nuevo.nombre" class="input" required />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Sede</label>
              <select v-model="nuevo.sede" class="select" required>
                <option value="">Seleccionar</option>
                <option value="Constitucion">Constitución</option>
                <option value="Cultura">Cultura</option>
                <option value="Deportes">Deportes</option>
                <option value="Igualdad">Igualdad</option>
                <option value="Biblioteca">Biblioteca</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Imagen (JPG 3000x2000)</label>
              <input type="file" accept="image/jpeg" @change="onFilePlano" required />
              <div v-if="imagenMeta.name" class="text-xs mt-1" :class="imagenMeta.valid ? 'text-green-600' : 'text-red-600'">
                {{ imagenMeta.name }} — {{ imagenMeta.type }} — {{ imagenMeta.width }}x{{ imagenMeta.height }}
                <span v-if="!imagenMeta.valid">(debe ser JPG 3000x2000)</span>
              </div>
              <p v-if="preview" class="text-xs text-gray-500 mt-1">Previsualización:</p>
              <img v-if="preview" :src="preview" class="h-32 rounded border" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Descripción</label>
              <textarea v-model="nuevo.descripcion" class="input" rows="3"></textarea>
            </div>
            <div class="flex justify-end gap-2">
              <button type="button" class="btn btn-secondary" @click="modalNuevo.open=false">Cancelar</button>
              <button type="submit" class="btn btn-primary" :disabled="nuevo.loading || !imagenMeta.valid">Guardar</button>
            </div>
          </form>
        </div>
      </div>
    </transition>

    <!-- Editor de plano -->
    <transition name="fade">
      <div v-if="editor.open" class="modal-overlay">
        <div class="modal w-full max-w-5xl">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Plano - {{ editor.plano?.nombre }}</h3>
            <div class="flex items-center gap-2">
              <div class="hidden md:flex items-center gap-1 mr-2">
                <label class="text-sm inline-flex items-center gap-1 mr-3">
                  <input type="checkbox" v-model="modoVista" class="h-4 w-4"> Modo ver
                </label>
                <button class="btn btn-secondary" @click="zoomOut" title="Alejar">-</button>
                <div class="px-2 text-sm w-16 text-center">{{ Math.round(scale*100) }}%</div>
                <button class="btn btn-secondary" @click="zoomIn" title="Acercar">+</button>
                <button class="btn btn-secondary" @click="resetView" title="Reiniciar">100%</button>
                <label class="ml-2 text-sm inline-flex items-center gap-1">
                  <input type="checkbox" v-model="panMode" class="h-4 w-4"> Mover plano
                </label>
              </div>
              <button @click="cerrarEditor" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-3">
              <div class="relative border rounded-md overflow-hidden" ref="imgWrap" @mousedown="onCanvasMouseDown">
                <div class="relative" :style="canvasStyle">
                  <img :src="storageUrl(editor.plano?.imagen)" ref="imgEl" class="w-full select-none" @load="onImageLoad" @click="onCanvasClick"/>
                  <!-- Marcadores -->
                  <div v-for="m in editor.marcadores" :key="m.hostname" class="absolute" :data-host="m.hostname" :style="markerStyle(m)" @mousedown.prevent="!modoVista && startDrag(m, $event)" @click="modoVista && abrirDetalleImpresora(m.hostname)">
                    <div class="flex flex-col items-center gap-1" :class="modoVista ? 'cursor-pointer' : 'cursor-move'">
                      <!-- Imagen de la impresora o icono por defecto -->
                      <div class="relative">
                        <div class="w-12 h-12 rounded-lg shadow-md border-2 border-white overflow-hidden bg-white flex items-center justify-center">
                          <img v-if="getImpresoraFoto(m.hostname)" 
                               :src="getImpresoraFoto(m.hostname)" 
                               class="w-full h-full object-cover"
                               :alt="displayLabel(m.hostname)"/>
                          <svg v-else class="h-7 w-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                          </svg>
                        </div>
                        <!-- Luz de estado -->
                        <div class="absolute -top-0.5 -right-0.5 h-3.5 w-3.5 rounded-full border-2 border-white shadow" 
                             :class="getEstadoColorClass(m.hostname)" 
                             :title="getEstadoTexto(m.hostname)">
                        </div>
                      </div>
                      <!-- Etiqueta con nombre -->
                      <div class="px-1.5 py-0.5 text-[10px] rounded bg-white/90 border text-gray-700 shadow-sm">{{ displayLabel(m.hostname) }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="space-y-3" v-if="!modoVista">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Añadir impresora</label>
                <select v-model="editor.hostnameSeleccionado" class="select">
                  <option value="">Seleccionar</option>
                  <option v-for="imp in impresorasFiltradas" :key="imp.hostname" :value="imp.hostname">{{ imp.datos?.referencia || imp.hostname }} - {{ imp.datos?.sede || '' }}</option>
                </select>
                <div class="flex items-center justify-between mt-1">
                  <p class="text-xs text-gray-500">Haz clic en el plano para colocarla.</p>
                  <label class="text-xs text-gray-600 inline-flex items-center gap-1">
                    <input type="checkbox" v-model="soloSede" class="h-3.5 w-3.5"> Solo sede del plano
                  </label>
                </div>
              </div>
              <div>
                <h4 class="text-sm font-semibold mb-1">Marcadores</h4>
                <div v-if="!editor.marcadores.length" class="text-xs text-gray-400">Sin impresoras ubicadas</div>
                <div v-for="m in editor.marcadores" :key="m.hostname" class="flex items-center justify-between text-sm py-1">
                  <div class="truncate" :title="m.hostname">{{ m.hostname }}</div>
                  <div class="flex items-center gap-2">
                    <button class="btn btn-secondary" @click="centrar(m)">Centrar</button>
                    <button class="btn bg-red-600 text-white hover:bg-red-700" @click="eliminarMarcador(m)">Eliminar</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Visualizador de plano en pantalla completa -->
    <transition name="fade">
      <div v-if="visualizador.open" class="fixed inset-0 z-50 bg-black/95 flex flex-col">
        <!-- Header del visualizador -->
        <div class="flex items-center justify-between px-6 py-4 bg-black/50 backdrop-blur border-b border-white/10">
          <div class="text-white">
            <h3 class="text-xl font-bold">{{ visualizador.plano?.nombre }}</h3>
            <p class="text-sm text-white/70">{{ visualizador.plano?.sede }} · {{ visualizador.marcadores?.length || 0 }} impresoras ubicadas</p>
          </div>
          <div class="flex items-center gap-3">
            <!-- Controles de zoom -->
            <div class="flex items-center gap-2 bg-white/10 rounded-lg px-3 py-2">
              <button @click="visualizadorZoomOut" class="text-white hover:text-junta-green-400 transition" title="Alejar (-)">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"/>
                </svg>
              </button>
              <span class="text-white font-mono text-sm w-16 text-center">{{ Math.round(visualizadorScale*100) }}%</span>
              <button @click="visualizadorZoomIn" class="text-white hover:text-junta-green-400 transition" title="Acercar (+)">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                </svg>
              </button>
              <button @click="visualizadorReset" class="text-white hover:text-junta-green-400 transition ml-2" title="Ajustar (0)">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
              </button>
            </div>
            <!-- Botón cerrar -->
            <button @click="cerrarVisualizador" class="p-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition" title="Cerrar (ESC)">
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Contenedor del plano con pan -->
        <div class="flex-1 overflow-hidden relative" ref="visualizadorWrap" 
             @wheel.prevent="onVisualizadorWheel"
             @mousedown="onVisualizadorPanStart"
             @mousemove="onVisualizadorPanMove"
             @mouseup="onVisualizadorPanEnd"
             @mouseleave="onVisualizadorPanEnd"
             style="cursor: grab;">
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="relative" :style="visualizadorContainerStyle" ref="visualizadorCanvas">
              <img :src="storageUrl(visualizador.plano?.imagen)" 
                   class="w-full select-none rounded-lg shadow-2xl pointer-events-none" 
                   @load="onVisualizadorImageLoad"
                   draggable="false"/>
              
              <!-- Marcadores de impresoras -->
              <div v-for="m in visualizador.marcadores" :key="m.hostname" 
                   class="absolute cursor-pointer transition-all hover:scale-110 hover:z-50" 
                   :style="visualizadorMarkerStyle(m)"
                   @click.stop="mostrarDetalleImpresora(m.hostname)"
                   @dblclick.stop="abrirDetalleImpresora(m.hostname)">
                <div class="flex flex-col items-center gap-1">
                  <!-- Imagen de la impresora o icono por defecto -->
                  <div class="relative">
                    <div class="w-16 h-16 rounded-lg shadow-lg border-2 border-white overflow-hidden bg-white flex items-center justify-center">
                      <img v-if="getImpresoraFoto(m.hostname)" 
                           :src="getImpresoraFoto(m.hostname)" 
                           class="w-full h-full object-cover"
                           :alt="displayLabel(m.hostname)"/>
                      <svg v-else class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                      </svg>
                    </div>
                    <!-- Luz de estado -->
                    <div class="absolute -top-1 -right-1 h-5 w-5 rounded-full border-2 border-white shadow-lg" 
                         :class="getEstadoColorClass(m.hostname)"
                         :title="getEstadoTexto(m.hostname)">
                    </div>
                  </div>
                  <!-- Etiqueta con nombre -->
                  <div class="px-2 py-1 bg-white/95 backdrop-blur rounded shadow-lg border border-gray-200">
                    <span class="text-xs font-semibold text-gray-900 whitespace-nowrap">{{ displayLabel(m.hostname) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer con leyenda -->
        <div class="px-6 py-3 bg-black/50 backdrop-blur border-t border-white/10 text-white/70 text-sm">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
              <span>💡 Clic: ver detalles · Doble clic: ir a impresora</span>
              <span class="hidden md:inline">🔍 Rueda del ratón: zoom · Arrastrar: mover</span>
              <span class="hidden lg:inline">⌨️ Atajos: +/- (zoom), 0 (reset), ESC (cerrar)</span>
            </div>
            <span class="text-white/50">{{ Math.round(visualizadorScale*100) }}%</span>
          </div>
        </div>

        <!-- Popup de detalles de impresora -->
        <transition name="fade">
          <div v-if="detalleImpresora.visible" 
               class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-2xl max-w-md w-full p-6 relative">
              <!-- Botón cerrar -->
              <button @click="cerrarDetalleImpresora" 
                      class="absolute top-4 right-4 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>

              <!-- Contenido -->
              <div v-if="detalleImpresora.loading" class="text-center py-8">
                <div class="animate-spin h-8 w-8 border-4 border-junta-green-500 border-t-transparent rounded-full mx-auto mb-4"></div>
                <p class="text-gray-600">Cargando detalles...</p>
              </div>

              <div v-else-if="detalleImpresora.error" class="text-center py-8">
                <p class="text-red-600">{{ detalleImpresora.error }}</p>
                <button @click="cerrarDetalleImpresora" class="mt-4 btn btn-secondary">Cerrar</button>
              </div>

              <div v-else-if="detalleImpresora.data" class="space-y-4">
                <!-- Header con foto -->
                <div class="flex items-start gap-3">
                  <div class="flex-shrink-0 h-16 w-16 rounded-lg border-2 border-gray-200 overflow-hidden bg-white flex items-center justify-center relative">
                    <img v-if="getImpresoraFoto(detalleImpresora.hostname)" 
                         :src="getImpresoraFoto(detalleImpresora.hostname)" 
                         class="w-full h-full object-cover"
                         :alt="detalleImpresora.data.display_name"/>
                    <svg v-else class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <!-- Luz de estado superpuesta -->
                    <div class="absolute top-1 right-1 h-4 w-4 rounded-full border-2 border-white shadow-lg" 
                         :class="getEstadoColorClass(detalleImpresora.hostname)">
                    </div>
                  </div>
                  <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-900">{{ detalleImpresora.data.display_name || detalleImpresora.data.hostname }}</h3>
                    <p class="text-sm text-gray-500">{{ detalleImpresora.data.hostname }}</p>
                  </div>
                </div>

                <!-- Estado -->
                <div class="flex items-center gap-2 p-3 rounded-lg" :class="getEstadoBgClass(detalleImpresora.hostname)">
                  <div class="h-3 w-3 rounded-full" :class="getEstadoColorClass(detalleImpresora.hostname)"></div>
                  <span class="text-sm font-medium" :class="getEstadoTextClass(detalleImpresora.hostname)">
                    {{ getEstadoTexto(detalleImpresora.hostname) }}
                  </span>
                </div>

                <!-- Motivos de Warning/Error -->
                <div v-if="getMotivosEstado(detalleImpresora.hostname).length > 0" class="space-y-1.5 p-3 rounded-lg bg-amber-50 border border-amber-200">
                  <h4 class="text-xs font-semibold text-amber-800 flex items-center gap-1">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Motivos de alerta:
                  </h4>
                  <ul class="space-y-1">
                    <li v-for="(motivo, idx) in getMotivosEstado(detalleImpresora.hostname)" :key="idx" class="text-xs text-amber-700">
                      {{ motivo }}
                    </li>
                  </ul>
                </div>

                <!-- Información básica -->
                <div class="space-y-2 border-t pt-3">
                  <div v-if="detalleImpresora.data.marca || detalleImpresora.data.modelo" class="flex justify-between text-sm">
                    <span class="text-gray-600">Marca:</span>
                    <span class="font-medium text-gray-900">{{ detalleImpresora.data.marca || 'N/A' }}</span>
                  </div>
                  <div v-if="detalleImpresora.data.marca || detalleImpresora.data.modelo" class="flex justify-between text-sm">
                    <span class="text-gray-600">Modelo:</span>
                    <span class="font-medium text-gray-900">{{ detalleImpresora.data.modelo || 'N/A' }}</span>
                  </div>
                  <div v-if="getImpresoraUbicacion(detalleImpresora.hostname)" class="flex justify-between text-sm">
                    <span class="text-gray-600">Ubicación:</span>
                    <span class="font-medium text-gray-900">{{ getImpresoraUbicacion(detalleImpresora.hostname) }}</span>
                  </div>
                  <div v-if="detalleImpresora.data.ip_address" class="flex justify-between text-sm">
                    <span class="text-gray-600">IP:</span>
                    <span class="font-mono text-sm text-gray-900">{{ detalleImpresora.data.ip_address }}</span>
                  </div>
                </div>

                <!-- Toners (barras CMYK) -->
                <div v-if="tieneConsumibles(detalleImpresora.data)" class="space-y-2 border-t pt-3">
                  <h4 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                    Toners / Cartuchos
                  </h4>
                  <div v-if="detalleImpresora.data.toner_cyan !== null" class="space-y-1">
                    <div class="flex justify-between text-xs">
                      <span class="text-gray-600">Cyan</span>
                      <span class="font-medium" :class="getNivelColorClass(detalleImpresora.data.toner_cyan)">{{ detalleImpresora.data.toner_cyan }}%</span>
                    </div>
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div class="h-full bg-cyan-500 transition-all" :style="{width: detalleImpresora.data.toner_cyan + '%'}"></div>
                    </div>
                  </div>
                  <div v-if="detalleImpresora.data.toner_magenta !== null" class="space-y-1">
                    <div class="flex justify-between text-xs">
                      <span class="text-gray-600">Magenta</span>
                      <span class="font-medium" :class="getNivelColorClass(detalleImpresora.data.toner_magenta)">{{ detalleImpresora.data.toner_magenta }}%</span>
                    </div>
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div class="h-full bg-pink-500 transition-all" :style="{width: detalleImpresora.data.toner_magenta + '%'}"></div>
                    </div>
                  </div>
                  <div v-if="detalleImpresora.data.toner_yellow !== null" class="space-y-1">
                    <div class="flex justify-between text-xs">
                      <span class="text-gray-600">Amarillo</span>
                      <span class="font-medium" :class="getNivelColorClass(detalleImpresora.data.toner_yellow)">{{ detalleImpresora.data.toner_yellow }}%</span>
                    </div>
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div class="h-full bg-yellow-400 transition-all" :style="{width: detalleImpresora.data.toner_yellow + '%'}"></div>
                    </div>
                  </div>
                  <div v-if="detalleImpresora.data.toner_black !== null" class="space-y-1">
                    <div class="flex justify-between text-xs">
                      <span class="text-gray-600">Negro</span>
                      <span class="font-medium" :class="getNivelColorClass(detalleImpresora.data.toner_black)">{{ detalleImpresora.data.toner_black }}%</span>
                    </div>
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div class="h-full bg-gray-800 transition-all" :style="{width: detalleImpresora.data.toner_black + '%'}"></div>
                    </div>
                  </div>
                </div>

                <!-- Otros consumibles -->
                <div v-if="detalleImpresora.data.consumibles && detalleImpresora.data.consumibles.length > 0" class="space-y-2 border-t pt-3">
                  <h4 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                    Otros Consumibles
                  </h4>
                  <div class="space-y-1.5 max-h-32 overflow-y-auto">
                    <div v-for="(c, idx) in detalleImpresora.data.consumibles.slice(0, 5)" :key="idx" class="flex justify-between items-center text-xs">
                      <span class="text-gray-600 truncate flex-1 mr-2" :title="c.label">{{ truncateText(c.label, 30) }}</span>
                      <span class="font-medium" :class="getNivelColorClass(c.percent)">{{ c.percent }}%</span>
                    </div>
                    <div v-if="detalleImpresora.data.consumibles.length > 5" class="text-xs text-gray-500 italic">
                      +{{ detalleImpresora.data.consumibles.length - 5 }} más...
                    </div>
                  </div>
                </div>

                <!-- Acciones -->
                <div class="flex gap-2 pt-4 border-t">
                  <button @click="abrirDetalleImpresora(detalleImpresora.hostname)" class="btn btn-primary flex-1">
                    Ver detalles completos
                  </button>
                  <button @click="cerrarDetalleImpresora" class="btn btn-secondary">
                    Cerrar
                  </button>
                </div>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const planos = ref([]);
const sedes = ref([]);
const sedeOptions = computed(() => {
  const arr = Array.isArray(sedes.value) ? sedes.value : [];
  return arr.map(s => ({ label: s.nombre, value: normalizeSede(s.nombre) }));
});
const filtroSede = ref('');
const modalNuevo = ref({ open: false });
const nuevo = ref({ nombre: '', sede: '', descripcion: '', loading: false, error: '', success: '' });
const filePlano = ref(null);
const preview = ref('');
const imagenMeta = ref({ name: '', type: '', width: 0, height: 0, valid: false });

const editor = ref({ open: false, plano: null, marcadores: [], hostnameSeleccionado: '' });
const modoVista = ref(false);
const impresoras = ref([]);
const imgEl = ref(null);
const natural = ref({ w: 3000, h: 2000 });
let dragging = null;
// Zoom / Pan
const scale = ref(1);
const pan = ref({ x: 0, y: 0 });
const panMode = ref(false);
const soloSede = ref(true);
const canvasStyle = computed(() => ({
  transformOrigin: 'top left',
  transform: `translate(${pan.value.x}px, ${pan.value.y}px) scale(${scale.value})`,
}));

// Visualizador en pantalla completa
const visualizador = ref({ open: false, plano: null, marcadores: [] });
const visualizadorScale = ref(1);
const visualizadorWrap = ref(null);
const visualizadorCanvas = ref(null);
const visualizadorNatural = ref({ w: 3000, h: 2000 });

const route = useRoute();
const router = useRouter();

// Convierte rutas tipo 'public/planos/abc.jpg' a '/storage/planos/abc.jpg'
const storageUrl = (rel) => {
  if (!rel) return '';
  // normaliza posibles prefijos
  const cleaned = rel.replace(/^public\//, '').replace(/^storage\//, '');
  return `/gestionmaterial/storage/${cleaned}`;
};

const labelFromSedeValue = (val) => {
  if (!val) return '';
  const opt = sedeOptions.value.find(o => o.value === val);
  return opt ? opt.label : val;
};

const cargarPlanos = async () => {
  const res = await axios.get('/planos', { params: { sede: filtroSede.value || undefined } });
  planos.value = res.data.data || [];
};

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
    .trim();
};

const cargarImpresoras = async () => {
  const res = await axios.get('/checkmk/impresoras');
  impresoras.value = (res.data.data || []).map(i => ({ ...i, datos: { ...i.datos_adicionales, referencia: (i.datos_adicionales?.referencia || i.hostname), sede: (i.datos_adicionales?.sede || '') } }));
};
const impresorasFiltradas = computed(() => {
  if (!editor.value.plano) return impresoras.value;
  if (!soloSede.value) return impresoras.value;
  const sede = editor.value.plano?.sede || '';
  return impresoras.value.filter(i => (i.datos?.sede || '') === sede);
});
const displayLabel = (hostname) => {
  const imp = impresoras.value.find(i => i.hostname === hostname);
  return (imp?.datos?.referencia) || hostname;
};

onMounted(async () => {
  await loadSedes();
  await cargarPlanos();
  await cargarImpresoras();

  // Deep-link: ?hostname=... abre editor del plano correspondiente si existe
  const host = route.query.hostname;
  if (typeof host === 'string' && host) {
    try {
      const res = await axios.get('/planos/ubicacion-impresora', { params: { hostname: host } });
      const data = res.data?.data;
      if (data?.plano?.id) {
        if (data.plano.sede) {
          filtroSede.value = data.plano.sede;
          await cargarPlanos();
        }
        const p = (planos.value || []).find(x => x.id === data.plano.id) || data.plano;
        await abrirEditor(p);
        editor.value.hostnameSeleccionado = host;
        setTimeout(() => {
          const el = document.querySelector(`[data-host="${CSS.escape(host)}"]`);
          el?.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
        }, 250);
      }
    } catch (e) {
      // sin ubicación, no hacemos nada especial
    }
  }
});

const abrirNuevoPlano = () => { modalNuevo.value.open = true; preview.value=''; filePlano.value=null; nuevo.value={ nombre:'', sede:'', descripcion:'', loading:false, error:'', success:'' }; imagenMeta.value={ name:'', type:'', width:0, height:0, valid:false } };
const onFilePlano = (e) => {
  const f = e.target.files?.[0];
  filePlano.value = f || null;
  imagenMeta.value = { name: '', type: '', width: 0, height: 0, valid: false };
  preview.value = '';
  if (!f) return;
  imagenMeta.value.name = f.name;
  imagenMeta.value.type = f.type || '';
  const r = new FileReader();
  r.onload = () => {
    const dataUrl = r.result;
    preview.value = dataUrl;
    const img = new Image();
    img.onload = () => {
      imagenMeta.value.width = img.naturalWidth || 0;
      imagenMeta.value.height = img.naturalHeight || 0;
      const isJpg = (f.type === 'image/jpeg') || /\.jpe?g$/i.test(f.name);
      imagenMeta.value.valid = isJpg && imagenMeta.value.width === 3000 && imagenMeta.value.height === 2000;
    };
    img.src = dataUrl;
  };
  r.readAsDataURL(f);
};
const crearPlano = async () => {
  nuevo.value.loading = true; nuevo.value.error = ''; nuevo.value.success = '';
  try {
    const fd = new FormData();
    fd.append('nombre', nuevo.value.nombre);
    fd.append('sede', nuevo.value.sede);
    if (nuevo.value.descripcion) fd.append('descripcion', nuevo.value.descripcion);
    fd.append('imagen', filePlano.value);
    const res = await axios.post('/planos', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
    if (res.data?.success) {
      nuevo.value.success = 'Plano creado correctamente';
      await cargarPlanos();
      setTimeout(() => { modalNuevo.value.open = false; }, 500);
    }
  } catch (e) {
    const msg = e?.response?.data?.message || 'No se pudo crear el plano';
    const errors = e?.response?.data?.errors;
    if (errors) {
      const first = Object.values(errors).flat()[0];
      nuevo.value.error = first || msg;
    } else {
      nuevo.value.error = msg;
    }
  } finally {
    nuevo.value.loading = false;
  }
};
const eliminarPlano = async (p) => { if(!confirm('¿Eliminar plano?')) return; await axios.delete(`/planos/${p.id}`); await cargarPlanos(); };

const abrirEditor = async (p) => {
  editor.value = { open: true, plano: p, marcadores: [], hostnameSeleccionado: '' };
  // reset vista
  scale.value = 1; pan.value = { x: 0, y: 0 }; panMode.value = false; soloSede.value = true;
  const res = await axios.get(`/planos/${p.id}/ubicaciones`);
  editor.value.marcadores = (res.data.data || []).map(u => ({ hostname: u.hostname, x: u.x, y: u.y }));
};
const cerrarEditor = () => { editor.value.open = false; };

// Funciones del visualizador
const visualizadorPan = ref({ x: 0, y: 0 });
const visualizadorPanning = ref(false);
const visualizadorPanStart = ref({ x: 0, y: 0 });

const visualizadorContainerStyle = computed(() => ({
  transform: `translate(${visualizadorPan.value.x}px, ${visualizadorPan.value.y}px) scale(${visualizadorScale.value})`,
  transformOrigin: 'center',
  transition: visualizadorPanning.value ? 'none' : 'transform 0.2s ease',
}));

const detalleImpresora = ref({
  visible: false,
  loading: false,
  error: '',
  hostname: '',
  data: null
});

const abrirVisualizador = async (p) => {
  visualizador.value = { open: true, plano: p, marcadores: [] };
  visualizadorScale.value = 1;
  visualizadorPan.value = { x: 0, y: 0 };
  visualizadorPanning.value = false;
  const res = await axios.get(`/planos/${p.id}/ubicaciones`);
  visualizador.value.marcadores = (res.data.data || []).map(u => ({ hostname: u.hostname, x: u.x, y: u.y }));
  
  // Cerrar con ESC, Zoom con +/-/0
  document.addEventListener('keydown', onVisualizadorKeydown);
};

const cerrarVisualizador = () => {
  visualizador.value.open = false;
  document.removeEventListener('keydown', onVisualizadorKeydown);
  cerrarDetalleImpresora();
};

const onVisualizadorKeydown = (e) => {
  if (e.key === 'Escape') {
    if (detalleImpresora.value.visible) {
      cerrarDetalleImpresora();
    } else {
      cerrarVisualizador();
    }
  }
  if (e.key === '+' || e.key === '=') visualizadorZoomIn();
  if (e.key === '-' || e.key === '_') visualizadorZoomOut();
  if (e.key === '0') visualizadorReset();
};

const onVisualizadorImageLoad = (e) => {
  const img = e.target;
  if (img) {
    visualizadorNatural.value = { w: img.naturalWidth || 3000, h: img.naturalHeight || 2000 };
  }
};

const visualizadorMarkerStyle = (m) => {
  const left = (m.x / visualizadorNatural.value.w) * 100;
  const top = (m.y / visualizadorNatural.value.h) * 100;
  return {
    left: `${left}%`,
    top: `${top}%`,
    transform: 'translate(-50%, -50%)',
  };
};

const visualizadorZoomIn = () => {
  visualizadorScale.value = Math.min(visualizadorScale.value + 0.25, 4);
};

const visualizadorZoomOut = () => {
  visualizadorScale.value = Math.max(visualizadorScale.value - 0.25, 0.5);
};

const visualizadorReset = () => {
  visualizadorScale.value = 1;
  visualizadorPan.value = { x: 0, y: 0 };
};

// Zoom con rueda del ratón
const onVisualizadorWheel = (e) => {
  const delta = -Math.sign(e.deltaY);
  const zoomSpeed = 0.1;
  const newScale = Math.max(0.5, Math.min(4, visualizadorScale.value + delta * zoomSpeed));
  visualizadorScale.value = newScale;
};

// Pan con arrastre del ratón
const onVisualizadorPanStart = (e) => {
  if (e.button !== 0) return; // Solo botón izquierdo
  visualizadorPanning.value = true;
  visualizadorPanStart.value = {
    x: e.clientX - visualizadorPan.value.x,
    y: e.clientY - visualizadorPan.value.y
  };
  e.currentTarget.style.cursor = 'grabbing';
};

const onVisualizadorPanMove = (e) => {
  if (!visualizadorPanning.value) return;
  visualizadorPan.value = {
    x: e.clientX - visualizadorPanStart.value.x,
    y: e.clientY - visualizadorPanStart.value.y
  };
};

const onVisualizadorPanEnd = (e) => {
  if (!visualizadorPanning.value) return;
  visualizadorPanning.value = false;
  e.currentTarget.style.cursor = 'grab';
};

// Popup de detalles de impresora
const mostrarDetalleImpresora = async (hostname) => {
  detalleImpresora.value = {
    visible: true,
    loading: true,
    error: '',
    hostname: hostname,
    data: null
  };

  try {
    const res = await axios.get(`/checkmk/impresoras/${hostname}`);
    detalleImpresora.value.data = res.data.data || null;
    detalleImpresora.value.loading = false;
  } catch (e) {
    detalleImpresora.value.error = e?.response?.data?.message || 'No se pudieron cargar los detalles';
    detalleImpresora.value.loading = false;
  }
};

const cerrarDetalleImpresora = () => {
  detalleImpresora.value.visible = false;
};

const tieneConsumibles = (data) => {
  if (!data) return false;
  return data.toner_cyan !== null || data.toner_magenta !== null || 
         data.toner_yellow !== null || data.toner_black !== null;
};

const formatNumber = (num) => {
  return new Intl.NumberFormat('es-ES').format(num);
};

const onImageLoad = (e) => {
  const img = imgEl.value; if (!img) return;
  natural.value = { w: img.naturalWidth || 3000, h: img.naturalHeight || 2000 };
};
const relToAbs = (clientX, clientY) => {
  const wrap = imgEl.value; if (!wrap) return { x:0, y:0 };
  const rect = wrap.getBoundingClientRect();
  const rx = (clientX - rect.left) / rect.width;
  const ry = (clientY - rect.top) / rect.height;
  return { x: Math.round(rx * natural.value.w), y: Math.round(ry * natural.value.h) };
};
const absToStyle = (x, y) => {
  const img = imgEl.value; if (!img) return { left:'0px', top:'0px' };
  const rect = img.getBoundingClientRect();
  const left = (x / natural.value.w) * rect.width;
  const top = (y / natural.value.h) * rect.height;
  return { left: `${left}px`, top: `${top}px` };
};
const markerStyle = (m) => ({ position: 'absolute', transform: 'translate(-50%, -50%)', ...absToStyle(m.x, m.y) });

const onCanvasClick = async (e) => {
  if (modoVista.value) return;
  if (!editor.value.hostnameSeleccionado) return;
  const pos = relToAbs(e.clientX, e.clientY);
  const payload = { hostname: editor.value.hostnameSeleccionado, x: pos.x, y: pos.y };
  const p = editor.value.plano;
  await axios.post(`/planos/${p.id}/ubicaciones`, payload);
  const exist = editor.value.marcadores.find(m => m.hostname === payload.hostname);
  if (exist) { exist.x = payload.x; exist.y = payload.y; }
  else editor.value.marcadores.push(payload);
};

const startDrag = (m, ev) => {
  if (modoVista.value) return;
  const move = (e) => {
    const pos = relToAbs(e.clientX, e.clientY);
    m.x = pos.x; m.y = pos.y;
  };
  const up = async (e) => {
    document.removeEventListener('mousemove', move);
    document.removeEventListener('mouseup', up);
    const p = editor.value.plano;
    await axios.post(`/planos/${p.id}/ubicaciones`, { hostname: m.hostname, x: m.x, y: m.y });
  };
  document.addEventListener('mousemove', move);
  document.addEventListener('mouseup', up);
};

const eliminarMarcador = async (m) => {
  const p = editor.value.plano; await axios.delete(`/planos/${p.id}/ubicaciones/${encodeURIComponent(m.hostname)}`);
  editor.value.marcadores = editor.value.marcadores.filter(x => x.hostname !== m.hostname);
};
const centrar = (m) => {
  const el = document.querySelector(`[data-host="${CSS.escape(m.hostname)}"]`);
  el?.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
};

// Zoom / Pan handlers
const zoomIn = () => { scale.value = Math.min(3, +(scale.value + 0.1).toFixed(2)); };
const zoomOut = () => { scale.value = Math.max(0.5, +(scale.value - 0.1).toFixed(2)); };
const resetView = () => { scale.value = 1; pan.value = { x: 0, y: 0 }; };
let panDrag = { active: false, startX: 0, startY: 0, baseX: 0, baseY: 0 };
const onCanvasMouseDown = (e) => {
  if (!panMode.value) return;
  panDrag = { active: true, startX: e.clientX, startY: e.clientY, baseX: pan.value.x, baseY: pan.value.y };
  const move = (ev) => {
    if (!panDrag.active) return;
    pan.value = { x: panDrag.baseX + (ev.clientX - panDrag.startX), y: panDrag.baseY + (ev.clientY - panDrag.startY) };
  };
  const up = () => {
    panDrag.active = false;
    document.removeEventListener('mousemove', move);
    document.removeEventListener('mouseup', up);
  };
  document.addEventListener('mousemove', move);
  document.addEventListener('mouseup', up);
};

watch(filtroSede, async () => { await cargarPlanos(); });

// Color por estado y navegación a detalles
const estadoDe = (hostname) => {
  const imp = impresoras.value.find(i => i.hostname === hostname);
  return imp?.estado || 'desconocido';
};
const markerColorClass = (hostname) => {
  const est = estadoDe(hostname);
  switch (est) {
    case 'ok':
    case 'online':
      return 'bg-green-500';
    case 'warning':
      return 'bg-yellow-500';
    case 'error':
    case 'critical':
    case 'down':
      return 'bg-red-500';
    default:
      return 'bg-gray-400';
  }
};
const abrirDetalleImpresora = (hostname) => {
  router.push({ name: 'Impresoras', query: { hostname } });
};

// Funciones auxiliares para impresoras
const getImpresoraFoto = (hostname) => {
  const imp = impresoras.value.find(i => i.hostname === hostname);
  const foto = imp?.datos_adicionales?.foto || imp?.datos?.foto;
  return foto ? storageUrl(foto) : null;
};

const getImpresoraUbicacion = (hostname) => {
  const imp = impresoras.value.find(i => i.hostname === hostname);
  return imp?.datos_adicionales?.ubicacion || imp?.datos?.ubicacion || '';
};

const getEstadoTexto = (hostname) => {
  const est = estadoDe(hostname);
  switch (est.toLowerCase()) {
    case 'ok':
    case 'online':
      return 'Online';
    case 'warning':
      return 'Advertencia';
    case 'error':
    case 'critical':
    case 'down':
      return 'Error';
    default:
      return 'Desconocido';
  }
};

const getEstadoColorClass = (hostname) => {
  const est = estadoDe(hostname);
  switch (est.toLowerCase()) {
    case 'ok':
    case 'online':
      return 'bg-green-500 animate-pulse';
    case 'warning':
      return 'bg-yellow-500 animate-pulse';
    case 'error':
    case 'critical':
    case 'down':
      return 'bg-red-500 animate-pulse';
    default:
      return 'bg-gray-400';
  }
};

const getEstadoBgClass = (hostname) => {
  const est = estadoDe(hostname);
  switch (est.toLowerCase()) {
    case 'ok':
    case 'online':
      return 'bg-green-50';
    case 'warning':
      return 'bg-yellow-50';
    case 'error':
    case 'critical':
    case 'down':
      return 'bg-red-50';
    default:
      return 'bg-gray-50';
  }
};

const getEstadoTextClass = (hostname) => {
  const est = estadoDe(hostname);
  switch (est.toLowerCase()) {
    case 'ok':
    case 'online':
      return 'text-green-700';
    case 'warning':
      return 'text-yellow-700';
    case 'error':
    case 'critical':
    case 'down':
      return 'text-red-700';
    default:
      return 'text-gray-700';
  }
};

const getNivelColorClass = (percent) => {
  if (percent >= 50) return 'text-green-600';
  if (percent >= 20) return 'text-yellow-600';
  return 'text-red-600';
};

const getMotivosEstado = (hostname) => {
  const motivos = [];
  const imp = impresoras.value.find(i => i.hostname === hostname);
  
  if (!imp) return motivos;
  
  // Obtener datos de la impresora actual
  const data = detalleImpresora.value.data;
  
  if (!data) return motivos;
  
  // Verificar toners bajos
  const toners = [
    { nombre: 'Cyan', nivel: data.toner_cyan },
    { nombre: 'Magenta', nivel: data.toner_magenta },
    { nombre: 'Amarillo', nivel: data.toner_yellow },
    { nombre: 'Negro', nivel: data.toner_black }
  ];
  
  const tonersWarning = toners.filter(t => t.nivel !== null && t.nivel >= 10 && t.nivel < 20);
  const tonersCriticos = toners.filter(t => t.nivel !== null && t.nivel < 10);
  
  if (tonersCriticos.length > 0) {
    motivos.push(`⚠️ Tóner crítico: ${tonersCriticos.map(t => `${t.nombre} (${t.nivel}%)`).join(', ')}`);
  }
  
  if (tonersWarning.length > 0) {
    motivos.push(`⚡ Tóner bajo: ${tonersWarning.map(t => `${t.nombre} (${t.nivel}%)`).join(', ')}`);
  }
  
  // Verificar otros consumibles bajos
  if (data.consumibles && data.consumibles.length > 0) {
    const consumiblesWarning = data.consumibles.filter(c => c.percent >= 10 && c.percent < 20);
    const consumiblesCriticos = data.consumibles.filter(c => c.percent < 10);
    
    if (consumiblesCriticos.length > 0) {
      consumiblesCriticos.forEach(c => {
        motivos.push(`⚠️ Consumible crítico: ${truncateText(c.label, 25)} (${c.percent}%)`);
      });
    }
    
    if (consumiblesWarning.length > 0) {
      consumiblesWarning.forEach(c => {
        motivos.push(`⚡ Consumible bajo: ${truncateText(c.label, 25)} (${c.percent}%)`);
      });
    }
  }
  
  return motivos;
};

const truncateText = (text, maxLength) => {
  if (!text) return '';
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength) + '...';
};
</script>

<!-- estilos locales eliminados para usar las clases globales del tema (app.css) -->
