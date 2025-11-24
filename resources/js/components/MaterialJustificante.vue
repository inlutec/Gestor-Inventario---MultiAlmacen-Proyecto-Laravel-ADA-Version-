<template>
  <!-- Build: v1.3.2-datetime-local -->
  <div class="space-y-4">
    <div class="flex flex-wrap items-end gap-3">
      <div>
        <label class="block text-sm font-medium mb-1">Tipo</label>
        <select v-model="filtros.tipo" class="border rounded px-3 py-2 w-40" @change="load">
          <option value="">Todos</option>
          <option value="entrada">Entrada</option>
          <option value="salida">Salida</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Estado</label>
        <select v-model="filtros.estado" class="border rounded px-3 py-2 w-44" @change="load">
          <option value="">Todos</option>
          <option value="borrador">Borrador</option>
          <option value="pendiente_firma">Pendiente firma</option>
          <option value="firmado">Firmado</option>
          <option value="entregado">Entregado</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Buscar</label>
        <input v-model="filtros.buscar" @keyup.enter="load" type="text" class="border rounded px-3 py-2 w-64" placeholder="Documento, origen, destino..." />
      </div>
      <AlmacenSelector v-model="almacenId" @change="onAlmacenChange" />
      <div class="ml-auto">
        <button @click="load" class="px-3 py-2 bg-slate-100 rounded">Buscar</button>
      </div>
    </div>

    <div v-if="loading" class="text-slate-500 text-sm">Cargando movimientos...</div>

    <div v-else class="overflow-x-auto overflow-y-visible">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="bg-slate-100 text-left">
            <th class="p-2">Documento</th>
            <th class="p-2">Tipo</th>
            <th class="p-2">Fecha</th>
            <th class="p-2">Fecha Prevista Entrega</th>
            <th class="p-2">Origen</th>
            <th class="p-2">Destino</th>
            <th class="p-2">Estado</th>
            <th class="p-2 text-center">Firmas</th>
            <th class="p-2">Entrega</th>
            <th class="p-2">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in movimientos" :key="m.id" class="border-b hover:bg-slate-50" style="position: relative;">
            <td class="p-2 font-medium">{{ m.numero_documento }}</td>
            <td class="p-2">
              <span :class="badgeTipo(m.tipo)">{{ m.tipo }}</span>
            </td>
            <td class="p-2">{{ formatDate(m.fecha_movimiento) }}</td>
            <td class="p-2">
              <div v-if="m.editandoFecha === true" class="flex items-center gap-2">
                <input 
                  type="datetime-local" 
                  v-model="m.fecha_prevista_temp"
                  class="border rounded px-2 py-1 text-xs w-52"
                  placeholder="Selecciona fecha y hora"
                />
                <button 
                  @click="guardarFechaPrevista(m)" 
                  class="px-2 py-1 text-xs bg-green-500 text-white rounded hover:bg-green-600"
                  :disabled="m.guardandoFecha"
                >
                  ✓
                </button>
                <button 
                  @click="cancelarEdicionFecha(m)" 
                  class="px-2 py-1 text-xs bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
                >
                  ✕
                </button>
              </div>
              <div v-else class="flex items-center gap-2">
                <span class="text-sm" :class="m.fecha_prevista_entrega ? 'text-blue-700 font-medium' : 'text-slate-400'">
                  {{ m.fecha_prevista_entrega ? formatDateTime(m.fecha_prevista_entrega) : 'Sin fecha' }}
                </span>
                <button 
                  @click="editarFechaPrevista(m)" 
                  class="text-xs text-blue-600 hover:text-blue-800"
                  title="Editar fecha prevista"
                >
                  ✏️
                </button>
              </div>
            </td>
            <td class="p-2">{{ m.origen || '-' }}</td>
            <td class="p-2">{{ m.destino || '-' }}</td>
            <td class="p-2 uppercase font-semibold">{{ m.estado }}</td>
            <td class="p-2 text-center">
              <div class="flex flex-col gap-1 items-center">
                <div class="flex items-center gap-2">
                  <span :class="m.tiene_firma_emisor ? 'text-green-700' : 'text-slate-500'">Emisor</span>
                  <button 
                    v-if="m.tiene_firma_emisor && isAdmin" 
                    @click="anularFirma(m, 'emisor')" 
                    class="text-xs text-red-600 hover:text-red-800" 
                    title="Anular firma de emisor"
                  >
                    ✕
                  </button>
                </div>
                <div class="flex items-center gap-2">
                  <span :class="m.tiene_firma_receptor ? 'text-green-700' : 'text-slate-500'">Receptor</span>
                  <button 
                    v-if="m.tiene_firma_receptor && isAdmin" 
                    @click="anularFirma(m, 'receptor')" 
                    class="text-xs text-red-600 hover:text-red-800" 
                    title="Anular firma de receptor"
                  >
                    ✕
                  </button>
                </div>
              </div>
            </td>
            <td class="p-2">
              <div v-if="m.fecha_entrega" class="text-xs">
                <div class="text-green-700 font-semibold">✓ Entregado</div>
                <div class="text-slate-600">{{ formatDate(m.fecha_entrega) }}</div>
              </div>
              <button 
                v-else-if="m.estado === 'firmado'" 
                @click="marcarEntregado(m)" 
                class="px-2 py-1 text-xs bg-green-50 text-green-700 border border-green-300 rounded hover:bg-green-100"
                :disabled="m.marcandoEntregado"
              >
                {{ m.marcandoEntregado ? 'Marcando...' : '📦 Marcar entregado' }}
              </button>
              <span v-else class="text-slate-400 text-xs">-</span>
            </td>
            <td class="p-2">
              <div class="relative">
                <!-- Botón principal -->
                <button 
                  @click="toggleMenu(m.id, $event)"
                  class="px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 flex items-center gap-2"
                >
                  <span>Acciones</span>
                  <span class="text-xs">▼</span>
                </button>

                <!-- Menu dropdown -->
                <div 
                  v-if="menuAbierto === m.id"
                  class="absolute right-0 mt-1 bg-white border border-gray-200 rounded shadow-lg z-50 min-w-[220px]"
                  @click.stop
                >
                  <button @click="ver(m); cerrarMenu()" class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2">
                    <span>👁️</span> Ver Detalles
                  </button>
                  <button @click="verHistorial(m); cerrarMenu()" class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2">
                    <span>📋</span> Historial
                  </button>
                  <button @click="descargarPDF(m); cerrarMenu()" class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2">
                    <span>📄</span> Descargar PDF
                  </button>
                  
                  <div class="border-t border-gray-200 my-1"></div>
                  
                  <button 
                    @click="firmarEmisor(m); cerrarMenu()" 
                    :disabled="m.tiene_firma_emisor"
                    :class="m.tiene_firma_emisor ? 'opacity-50 cursor-not-allowed' : ''"
                    class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2"
                  >
                    <span>✍️</span> Firmar Emisor
                  </button>
                  <button 
                    @click="solicitarFirmaRemota(m, 'emisor'); cerrarMenu()" 
                    :disabled="m.tiene_firma_emisor"
                    :class="m.tiene_firma_emisor ? 'opacity-50 cursor-not-allowed' : ''"
                    class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2"
                  >
                    <span>📱</span> Firma Emisor Remota
                  </button>
                  <button 
                    @click="solicitarFirmaRemota(m, 'receptor'); cerrarMenu()" 
                    :disabled="m.tiene_firma_receptor"
                    :class="m.tiene_firma_receptor ? 'opacity-50 cursor-not-allowed' : ''"
                    class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2"
                  >
                    <span>📱</span> Firma Receptor Remota
                  </button>
                  
                  <div class="border-t border-gray-200 my-1"></div>
                  
                  <button @click="generarEnlace(m); cerrarMenu()" class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2">
                    <span>🔗</span> Generar Enlace Público
                  </button>
                  
                  <div v-if="isAdmin" class="border-t border-gray-200 my-1"></div>
                  
                  <button 
                    v-if="isAdmin"
                    @click="eliminarMovimiento(m); cerrarMenu()" 
                    :disabled="deletingId===m.id"
                    class="w-full text-left px-4 py-2 hover:bg-red-50 text-red-700 flex items-center gap-2"
                  >
                    <span>🗑️</span> {{ deletingId===m.id ? 'Eliminando...' : 'Eliminar' }}
                  </button>
                </div>
              </div>
              
              <!-- Enlace generado (fuera del menú) -->
              <div v-if="enlaceGenerado[m.id]" class="text-xs text-slate-600 mt-2 break-all flex items-center gap-2">
                <span>Enlace: <a class="text-blue-700 underline" :href="enlaceGenerado[m.id]" target="_blank">{{ enlaceGenerado[m.id] }}</a></span>
                <button 
                  @click="copiarAlPortapapeles(enlaceGenerado[m.id])" 
                  class="px-2 py-1 text-xs bg-blue-50 text-blue-700 border border-blue-300 rounded hover:bg-blue-100"
                  title="Copiar enlace"
                >
                  📋 Copiar
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!movimientos.length">
            <td class="p-3 text-center text-slate-500" colspan="10">Sin resultados</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal firma emisor -->
    <div v-if="modalFirma.visible" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
      <div class="bg-white rounded shadow w-[680px] max-w-[95vw] p-4 space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">Firmar documento como emisor</h3>
          <button class="text-slate-500" @click="closeModal">✕</button>
        </div>
        <div class="grid sm:grid-cols-3 gap-3">
          <div>
            <label class="block text-sm font-medium mb-1">Nombre</label>
            <input v-model="firma.nombre" class="border rounded px-3 py-2 w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Apellidos</label>
            <input v-model="firma.apellidos" class="border rounded px-3 py-2 w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">DNI (opcional)</label>
            <input v-model="firma.dni" class="border rounded px-3 py-2 w-full" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Rúbrica</label>
          <div class="border rounded p-2">
            <canvas ref="canvas" class="w-full h-40 bg-white" @mousedown="startDraw" @mouseup="stopDraw" @mouseleave="stopDraw" @mousemove="draw" @touchstart.prevent="startDraw" @touchend.prevent="stopDraw" @touchmove.prevent="draw"></canvas>
          </div>
          <div class="flex items-center gap-2 mt-2">
            <button class="px-2 py-1 border rounded" @click="clearCanvas">Limpiar</button>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <button class="px-3 py-2 bg-blue-600 text-white rounded" @click="submitFirma" :disabled="signing">{{ signing ? 'Firmando...' : 'Firmar' }}</button>
          <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>
          <div v-if="success" class="text-green-700 text-sm">{{ success }}</div>
        </div>
      </div>
    </div>

    <!-- Modal confirmar receptor -->
    <div v-if="modalConfirmarReceptor.visible" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
      <div class="bg-white rounded shadow w-[600px] max-w-[95vw] p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">¿Quién va a recoger el material?</h3>
          <button class="text-slate-500" @click="cerrarModalConfirmarReceptor">✕</button>
        </div>

        <!-- Información del pedido original -->
        <div v-if="modalConfirmarReceptor.movimiento?.pedido" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <p class="text-sm font-medium text-blue-900 mb-2">📋 Pedido registrado por:</p>
          <p class="text-sm text-blue-800">
            <strong>{{ modalConfirmarReceptor.movimiento.pedido.usuario_solicitante }}</strong>
          </p>
          <p class="text-xs text-blue-600 mt-1">
            {{ modalConfirmarReceptor.movimiento.pedido.email_solicitante }}
          </p>
          <p v-if="modalConfirmarReceptor.movimiento.pedido.telefono_solicitante" class="text-xs text-blue-600">
            {{ modalConfirmarReceptor.movimiento.pedido.telefono_solicitante }}
          </p>
        </div>

        <!-- Opciones -->
        <div class="space-y-3">
          <label class="flex items-start gap-3 p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50"
                 :class="modalConfirmarReceptor.esReceptorOriginal ? 'border-junta-green-600 bg-junta-green-50' : 'border-gray-300'">
            <input
              type="radio"
              :checked="modalConfirmarReceptor.esReceptorOriginal"
              @change="modalConfirmarReceptor.esReceptorOriginal = true"
              class="mt-1"
            />
            <div class="flex-1">
              <p class="font-medium text-gray-900">✅ La persona que hizo el pedido</p>
              <p class="text-sm text-gray-600">Firma con los datos del pedido original</p>
            </div>
          </label>

          <label class="flex items-start gap-3 p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50"
                 :class="!modalConfirmarReceptor.esReceptorOriginal ? 'border-junta-green-600 bg-junta-green-50' : 'border-gray-300'">
            <input
              type="radio"
              :checked="!modalConfirmarReceptor.esReceptorOriginal"
              @change="modalConfirmarReceptor.esReceptorOriginal = false"
              class="mt-1"
            />
            <div class="flex-1">
              <p class="font-medium text-gray-900">👤 Otra persona diferente</p>
              <p class="text-sm text-gray-600">Firma en nombre de otra persona</p>
            </div>
          </label>
        </div>

        <!-- Formulario datos receptor alternativo -->
        <div v-if="!modalConfirmarReceptor.esReceptorOriginal" class="space-y-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
          <p class="text-sm font-medium text-gray-700 mb-3">Datos de la persona que recoge:</p>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
            <input
              v-model="modalConfirmarReceptor.datosReceptor.nombre"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-transparent"
              placeholder="Nombre y apellidos"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
            <input
              v-model="modalConfirmarReceptor.datosReceptor.email"
              type="email"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-transparent"
              placeholder="email@ejemplo.com"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
            <input
              v-model="modalConfirmarReceptor.datosReceptor.telefono"
              type="tel"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-transparent"
              placeholder="600 123 456"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">DNI/NIE</label>
            <input
              v-model="modalConfirmarReceptor.datosReceptor.dni"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-transparent"
              placeholder="12345678A"
            />
          </div>
        </div>

        <div class="flex gap-3">
          <button
            @click="cerrarModalConfirmarReceptor"
            class="flex-1 px-4 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium"
          >
            Cancelar
          </button>
          <button
            @click="continuarConFirmaRemota"
            class="flex-1 px-4 py-3 bg-gradient-to-r from-junta-green-600 to-junta-green-700 text-white rounded-lg hover:from-junta-green-700 hover:to-junta-green-800 font-bold shadow-lg"
          >
            Continuar →
          </button>
        </div>
      </div>
    </div>

    <!-- Modal notificar fecha prevista -->
    <div v-if="modalNotificarFecha.visible" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-[500px] max-w-[95vw] p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">📅 Fecha de Entrega Establecida</h3>
          <button class="text-slate-500 hover:text-slate-700" @click="cerrarModalNotificar">✕</button>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <p class="text-sm text-gray-700 mb-2">Se ha establecido la fecha prevista de entrega:</p>
          <p class="text-lg font-bold text-junta-green-700">
            {{ modalNotificarFecha.fecha ? formatearFechaCompleta(modalNotificarFecha.fecha) : 'Sin fecha' }}
          </p>
        </div>

        <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4">
          <p class="text-sm text-gray-800 font-medium mb-1">¿Desea notificar al usuario?</p>
          <p class="text-xs text-gray-600">Se enviará un email al usuario informando de la fecha de recogida del material.</p>
        </div>

        <div class="flex gap-3">
          <button
            @click="confirmarFechaPrevista(false)"
            :disabled="modalNotificarFecha.enviando"
            class="flex-1 px-4 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium disabled:opacity-50 disabled:cursor-not-allowed"
          >
            No, solo guardar
          </button>
          <button
            @click="confirmarFechaPrevista(true)"
            :disabled="modalNotificarFecha.enviando"
            class="flex-1 px-4 py-3 bg-gradient-to-r from-junta-green-600 to-junta-green-700 text-white rounded-lg hover:from-junta-green-700 hover:to-junta-green-800 font-bold shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ modalNotificarFecha.enviando ? 'Enviando...' : '✉️ Sí, notificar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal detalles completos del movimiento -->
    <div v-if="modalDetalles.visible" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl w-[900px] max-w-full max-h-[90vh] overflow-y-auto">
        <!-- Header -->
        <div class="sticky top-0 bg-gradient-to-r from-junta-green-600 to-junta-green-700 text-white p-6 rounded-t-lg">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-2xl font-bold">📋 Detalles del Movimiento</h3>
              <p class="text-sm text-white/80 mt-1">{{ modalDetalles.movimiento?.numero_documento }}</p>
            </div>
            <button class="text-white hover:bg-white/20 rounded-full p-2" @click="cerrarModalDetalles">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Contenido -->
        <div v-if="modalDetalles.movimiento" class="p-6 space-y-6">
          
          <!-- Información General -->
          <section class="bg-gray-50 rounded-lg p-5">
            <h4 class="font-bold text-lg text-gray-900 mb-4 flex items-center gap-2">
              <span>📦</span> Información General
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-600">Tipo de Movimiento</p>
                <p class="font-semibold text-gray-900">
                  <span :class="modalDetalles.movimiento.tipo === 'entrada' ? 'text-green-600' : 'text-orange-600'">
                    {{ modalDetalles.movimiento.tipo === 'entrada' ? '📥 ENTRADA' : '📤 SALIDA' }}
                  </span>
                </p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Estado</p>
                <p class="font-semibold">
                  <span :class="{
                    'text-yellow-600': modalDetalles.movimiento.estado === 'borrador',
                    'text-orange-600': modalDetalles.movimiento.estado === 'pendiente_firma',
                    'text-blue-600': modalDetalles.movimiento.estado === 'firmado',
                    'text-green-600': modalDetalles.movimiento.estado === 'entregado'
                  }">
                    {{ modalDetalles.movimiento.estado?.toUpperCase() }}
                  </span>
                </p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Fecha del Movimiento</p>
                <p class="font-semibold text-gray-900">{{ formatDate(modalDetalles.movimiento.fecha_movimiento) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Número de Documento</p>
                <p class="font-semibold text-gray-900">{{ modalDetalles.movimiento.numero_documento || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Origen</p>
                <p class="font-semibold text-gray-900">{{ modalDetalles.movimiento.origen || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Destino</p>
                <p class="font-semibold text-gray-900">{{ modalDetalles.movimiento.destino || 'N/A' }}</p>
              </div>
            </div>
          </section>

          <!-- Fechas Importantes -->
          <section class="bg-blue-50 rounded-lg p-5">
            <h4 class="font-bold text-lg text-gray-900 mb-4 flex items-center gap-2">
              <span>📅</span> Fechas
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <p class="text-sm text-gray-600">Fecha Prevista Entrega</p>
                <p class="font-semibold text-blue-700">
                  {{ modalDetalles.movimiento.fecha_prevista_entrega ? formatDateTime(modalDetalles.movimiento.fecha_prevista_entrega) : '❌ Sin fecha' }}
                </p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Fecha de Entrega Real</p>
                <p class="font-semibold text-green-700">
                  {{ modalDetalles.movimiento.fecha_entrega ? formatDate(modalDetalles.movimiento.fecha_entrega) : '⏳ Pendiente' }}
                </p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Fecha de Registro</p>
                <p class="font-semibold text-gray-700">{{ formatDate(modalDetalles.movimiento.created_at) }}</p>
              </div>
            </div>
          </section>

          <!-- ORIGEN DEL MOVIMIENTO -->
          <section class="bg-gradient-to-r from-purple-100 to-pink-100 rounded-lg p-5 border-2 border-purple-300">
            <h4 class="font-bold text-xl text-gray-900 mb-4 flex items-center gap-2">
              <span>🎯</span> Origen del Movimiento
            </h4>
            <div class="bg-white rounded-lg p-4 mb-3">
              <p class="text-lg font-bold" :class="modalDetalles.movimiento.pedido ? 'text-green-700' : 'text-orange-700'">
                {{ modalDetalles.movimiento.pedido 
                  ? '✅ Creado desde PETICIÓN WEB (Solicitud de Usuario)' 
                  : '🛠️ Creado MANUALMENTE por Administrador' }}
              </p>
            </div>
          </section>

          <!-- Información COMPLETA del Pedido/Petición -->
          <section v-if="modalDetalles.movimiento.pedido" class="bg-green-50 rounded-lg p-5 border-2 border-green-300">
            <h4 class="font-bold text-xl text-gray-900 mb-4 flex items-center gap-2">
              <span>�</span> Información Completa del Pedido/Petición
            </h4>
            
            <!-- Tipo de solicitud -->
            <div class="bg-white rounded-lg p-4 mb-4 border-l-4" :class="modalDetalles.movimiento.pedido.tipo === 'peticion' ? 'border-blue-500' : 'border-purple-500'">
              <div class="flex items-center justify-between mb-3">
                <div>
                  <p class="text-sm text-gray-600">Tipo de Solicitud</p>
                  <p class="text-xl font-bold" :class="modalDetalles.movimiento.pedido.tipo === 'peticion' ? 'text-blue-700' : 'text-purple-700'">
                    {{ modalDetalles.movimiento.pedido.tipo === 'peticion' ? '🌐 PETICIÓN WEB PÚBLICA' : '📦 PEDIDO INTERNO' }}
                  </p>
                </div>
                <div class="text-right">
                  <p class="text-sm text-gray-600">Estado</p>
                  <p class="font-bold text-lg" :class="{
                    'text-yellow-600': modalDetalles.movimiento.pedido.estado === 'pendiente',
                    'text-green-600': modalDetalles.movimiento.pedido.estado === 'aprobado',
                    'text-red-600': modalDetalles.movimiento.pedido.estado === 'denegado',
                    'text-blue-600': modalDetalles.movimiento.pedido.estado === 'recibido'
                  }">
                    {{ modalDetalles.movimiento.pedido.estado?.toUpperCase() }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Datos del Solicitante -->
            <div class="bg-blue-50 rounded-lg p-4 mb-4">
              <h5 class="font-bold text-gray-900 mb-3 flex items-center gap-2">
                <span>👤</span> Datos del Solicitante
              </h5>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="bg-white rounded p-3">
                  <p class="text-xs text-gray-600 mb-1">Nombre Completo</p>
                  <p class="font-semibold text-gray-900">{{ modalDetalles.movimiento.pedido.usuario_solicitante || 'N/A' }}</p>
                </div>
                <div class="bg-white rounded p-3">
                  <p class="text-xs text-gray-600 mb-1">Email</p>
                  <p class="font-semibold">
                    <a v-if="modalDetalles.movimiento.pedido.email_solicitante" 
                       :href="'mailto:' + modalDetalles.movimiento.pedido.email_solicitante" 
                       class="text-blue-600 hover:underline">
                      {{ modalDetalles.movimiento.pedido.email_solicitante }}
                    </a>
                    <span v-else class="text-gray-400">Sin email</span>
                  </p>
                </div>
                <div class="bg-white rounded p-3">
                  <p class="text-xs text-gray-600 mb-1">Teléfono</p>
                  <p class="font-semibold">
                    <a v-if="modalDetalles.movimiento.pedido.telefono_solicitante" 
                       :href="'tel:' + modalDetalles.movimiento.pedido.telefono_solicitante" 
                       class="text-blue-600 hover:underline">
                      📞 {{ modalDetalles.movimiento.pedido.telefono_solicitante }}
                    </a>
                    <span v-else class="text-gray-400">Sin teléfono</span>
                  </p>
                </div>
                <div class="bg-white rounded p-3">
                  <p class="text-xs text-gray-600 mb-1">Número de Pedido</p>
                  <p class="font-semibold text-gray-900">{{ modalDetalles.movimiento.pedido.numero_pedido || 'N/A' }}</p>
                </div>
              </div>
            </div>

            <!-- Sede y Departamento -->
            <div v-if="modalDetalles.movimiento.pedido.sede || modalDetalles.movimiento.pedido.departamento" 
                 class="bg-yellow-50 rounded-lg p-4 mb-4">
              <h5 class="font-bold text-gray-900 mb-3 flex items-center gap-2">
                <span>🏢</span> Ubicación del Solicitante
              </h5>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div v-if="modalDetalles.movimiento.pedido.sede" class="bg-white rounded p-3">
                  <p class="text-xs text-gray-600 mb-1">Sede</p>
                  <p class="font-semibold text-gray-900">{{ modalDetalles.movimiento.pedido.sede.nombre || 'N/A' }}</p>
                </div>
                <div v-if="modalDetalles.movimiento.pedido.departamento" class="bg-white rounded p-3">
                  <p class="text-xs text-gray-600 mb-1">Departamento</p>
                  <p class="font-semibold text-gray-900">{{ modalDetalles.movimiento.pedido.departamento.nombre || 'N/A' }}</p>
                </div>
              </div>
            </div>

            <!-- Fechas del Pedido -->
            <div class="bg-indigo-50 rounded-lg p-4 mb-4">
              <h5 class="font-bold text-gray-900 mb-3 flex items-center gap-2">
                <span>📅</span> Fechas del Pedido
              </h5>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="bg-white rounded p-3">
                  <p class="text-xs text-gray-600 mb-1">Fecha de Solicitud</p>
                  <p class="font-semibold text-gray-900">{{ formatDate(modalDetalles.movimiento.pedido.created_at) }}</p>
                </div>
                <div v-if="modalDetalles.movimiento.pedido.fecha_aprobacion" class="bg-white rounded p-3">
                  <p class="text-xs text-gray-600 mb-1">Fecha de Aprobación</p>
                  <p class="font-semibold text-green-700">{{ formatDate(modalDetalles.movimiento.pedido.fecha_aprobacion) }}</p>
                </div>
                <div v-if="modalDetalles.movimiento.pedido.fecha_recepcion" class="bg-white rounded p-3">
                  <p class="text-xs text-gray-600 mb-1">Fecha de Recepción</p>
                  <p class="font-semibold text-blue-700">{{ formatDate(modalDetalles.movimiento.pedido.fecha_recepcion) }}</p>
                </div>
              </div>
            </div>

            <!-- Usuarios Involucrados -->
            <div class="bg-pink-50 rounded-lg p-4 mb-4">
              <h5 class="font-bold text-gray-900 mb-3 flex items-center gap-2">
                <span>👥</span> Usuarios Involucrados en el Proceso
              </h5>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div v-if="modalDetalles.movimiento.pedido.usuario_creador" class="bg-white rounded p-3">
                  <p class="text-xs text-gray-600 mb-1">Usuario Creador</p>
                  <p class="font-semibold text-gray-900">
                    {{ modalDetalles.movimiento.pedido.usuario_creador.nombre }} {{ modalDetalles.movimiento.pedido.usuario_creador.apellido }}
                  </p>
                  <p class="text-xs text-gray-500">{{ modalDetalles.movimiento.pedido.usuario_creador.email }}</p>
                </div>
                <div v-if="modalDetalles.movimiento.pedido.usuario_aprobador" class="bg-white rounded p-3">
                  <p class="text-xs text-gray-600 mb-1">Usuario Aprobador</p>
                  <p class="font-semibold text-gray-900">
                    {{ modalDetalles.movimiento.pedido.usuario_aprobador.nombre }} {{ modalDetalles.movimiento.pedido.usuario_aprobador.apellido }}
                  </p>
                  <p class="text-xs text-gray-500">{{ modalDetalles.movimiento.pedido.usuario_aprobador.email }}</p>
                </div>
              </div>
            </div>

            <!-- Observaciones y Comentarios del Pedido -->
            <div v-if="modalDetalles.movimiento.pedido.notas || modalDetalles.movimiento.pedido.observaciones || modalDetalles.movimiento.pedido.comentarios_aprobacion" 
                 class="bg-orange-50 rounded-lg p-4">
              <h5 class="font-bold text-gray-900 mb-3 flex items-center gap-2">
                <span>📝</span> Notas y Observaciones del Pedido
              </h5>
              <div class="space-y-3">
                <div v-if="modalDetalles.movimiento.pedido.notas" class="bg-white rounded p-3">
                  <p class="text-xs text-gray-600 mb-1 font-medium">Notas</p>
                  <p class="text-gray-800">{{ modalDetalles.movimiento.pedido.notas }}</p>
                </div>
                <div v-if="modalDetalles.movimiento.pedido.observaciones" class="bg-white rounded p-3">
                  <p class="text-xs text-gray-600 mb-1 font-medium">Observaciones</p>
                  <p class="text-gray-800">{{ modalDetalles.movimiento.pedido.observaciones }}</p>
                </div>
                <div v-if="modalDetalles.movimiento.pedido.comentarios_aprobacion" class="bg-white rounded p-3">
                  <p class="text-xs text-gray-600 mb-1 font-medium">Comentarios de Aprobación</p>
                  <p class="text-gray-800">{{ modalDetalles.movimiento.pedido.comentarios_aprobacion }}</p>
                </div>
              </div>
            </div>
          </section>

          <!-- Usuario que Registró el Movimiento -->
          <section v-if="modalDetalles.movimiento.usuario" class="bg-purple-50 rounded-lg p-5">
            <h4 class="font-bold text-lg text-gray-900 mb-4 flex items-center gap-2">
              <span>🔧</span> Usuario Admin que Registró el Movimiento
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-600">Nombre</p>
                <p class="font-semibold text-gray-900">{{ modalDetalles.movimiento.usuario.nombre }} {{ modalDetalles.movimiento.usuario.apellido }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Email</p>
                <p class="font-semibold text-gray-900">
                  <a :href="'mailto:' + modalDetalles.movimiento.usuario.email" class="text-blue-600 hover:underline">
                    {{ modalDetalles.movimiento.usuario.email }}
                  </a>
                </p>
              </div>
            </div>
          </section>

          <!-- Materiales -->
          <section class="bg-yellow-50 rounded-lg p-5">
            <h4 class="font-bold text-lg text-gray-900 mb-4 flex items-center gap-2">
              <span>📦</span> Materiales ({{ modalDetalles.movimiento.detalles?.length || 0 }})
            </h4>
            <div v-if="modalDetalles.movimiento.detalles && modalDetalles.movimiento.detalles.length > 0" class="space-y-3">
              <div v-for="(detalle, index) in modalDetalles.movimiento.detalles" :key="index" 
                   class="bg-white rounded-lg p-4 border border-yellow-200">
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <p class="font-semibold text-gray-900">{{ detalle.descripcion }}</p>
                    <p class="text-sm text-gray-600 mt-1">
                      <span class="font-medium">Cantidad:</span> {{ detalle.cantidad }} {{ detalle.unidad }}
                    </p>
                    <p v-if="detalle.observaciones" class="text-sm text-gray-600 mt-1">
                      <span class="font-medium">Observaciones:</span> {{ detalle.observaciones }}
                    </p>
                  </div>
                  <div class="text-right">
                    <span class="inline-block px-3 py-1 bg-yellow-200 text-yellow-800 rounded-full text-sm font-medium">
                      #{{ index + 1 }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <p v-else class="text-gray-500 text-center py-4">No hay materiales registrados</p>
          </section>

          <!-- Observaciones -->
          <section v-if="modalDetalles.movimiento.observaciones" class="bg-orange-50 rounded-lg p-5">
            <h4 class="font-bold text-lg text-gray-900 mb-3 flex items-center gap-2">
              <span>📝</span> Observaciones
            </h4>
            <p class="text-gray-800 whitespace-pre-wrap">{{ modalDetalles.movimiento.observaciones }}</p>
          </section>

          <!-- Información de Firmas -->
          <section v-if="modalDetalles.movimiento.firmas && modalDetalles.movimiento.firmas.length > 0" 
                   class="bg-indigo-50 rounded-lg p-5">
            <h4 class="font-bold text-lg text-gray-900 mb-4 flex items-center gap-2">
              <span>✍️</span> Firmas ({{ modalDetalles.movimiento.firmas.length }})
            </h4>
            <div class="space-y-3">
              <div v-for="(firma, index) in modalDetalles.movimiento.firmas" :key="index" 
                   class="bg-white rounded-lg p-4 border border-indigo-200">
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <p class="text-sm text-gray-600">Tipo</p>
                    <p class="font-semibold text-gray-900">{{ firma.tipo_firma === 'emisor' ? '📤 Emisor' : '📥 Receptor' }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">Método</p>
                    <p class="font-semibold text-gray-900">{{ firma.metodo_firma }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">Firmante</p>
                    <p class="font-semibold text-gray-900">{{ firma.nombre }} {{ firma.apellidos }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">DNI</p>
                    <p class="font-semibold text-gray-900">{{ firma.dni || 'N/A' }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">Email</p>
                    <p class="font-semibold text-gray-900">{{ firma.email || 'N/A' }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">Teléfono</p>
                    <p class="font-semibold text-gray-900">{{ firma.telefono || 'N/A' }}</p>
                  </div>
                  <div class="col-span-2">
                    <p class="text-sm text-gray-600">Fecha de Firma</p>
                    <p class="font-semibold text-gray-900">{{ formatDate(firma.created_at) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </section>

        </div>

        <!-- Footer con acciones -->
        <div class="sticky bottom-0 bg-gray-50 px-6 py-4 rounded-b-lg border-t border-gray-200 flex gap-3">
          <button
            @click="abrirAlbaran(modalDetalles.movimiento)"
            class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium flex items-center justify-center gap-2"
          >
            <span>📄</span> Ver Albarán
          </button>
          <button
            @click="descargarPDF(modalDetalles.movimiento)"
            class="flex-1 px-4 py-3 bg-junta-green-600 text-white rounded-lg hover:bg-junta-green-700 font-medium flex items-center justify-center gap-2"
          >
            <span>⬇️</span> Descargar PDF
          </button>
          <button
            @click="cerrarModalDetalles"
            class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 font-medium"
          >
            Cerrar
          </button>
        </div>
      </div>
    </div>

    <!-- Modal previsualización firma remota -->
    <div v-if="modalFirmaRemota.visible" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
      <div class="bg-white rounded shadow w-[600px] max-w-[95vw] p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">Firma Remota - {{ modalFirmaRemota.tipoFirma === 'receptor' ? 'Receptor' : 'Emisor' }}</h3>
          <button class="text-slate-500" @click="cerrarModalFirmaRemota">✕</button>
        </div>

        <div v-if="modalFirmaRemota.esperando" class="text-center py-8">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-junta-green-600 mb-4"></div>
          <p class="text-gray-600">Esperando firma del dispositivo móvil...</p>
          <p class="text-sm text-gray-500 mt-2">El usuario está firmando en el móvil</p>
        </div>

        <div v-else-if="modalFirmaRemota.firmaImagen" class="space-y-4">
          <div class="border-2 border-gray-300 rounded-lg p-4 bg-gray-50">
            <p class="text-sm font-medium text-gray-700 mb-2">Previsualización de la firma:</p>
            <img :src="modalFirmaRemota.firmaImagen" alt="Firma" class="w-full border-2 border-gray-300 rounded bg-white" />
          </div>

          <div class="flex items-center gap-3">
            <button
              @click="aceptarFirmaRemota"
              :disabled="signing"
              class="flex-1 px-4 py-3 bg-gradient-to-r from-junta-green-600 to-junta-green-700 text-white rounded-lg hover:from-junta-green-700 hover:to-junta-green-800 font-bold shadow-lg disabled:opacity-50"
            >
              {{ signing ? '⏳ Guardando...' : '✓ Aceptar y Guardar Firma' }}
            </button>
            <button
              @click="rechazarFirmaRemota"
              :disabled="signing"
              class="px-4 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium disabled:opacity-50"
            >
              ✗ Rechazar
            </button>
          </div>
          <div v-if="error" class="text-red-600 text-sm text-center">{{ error }}</div>
        </div>
      </div>
    </div>

    <!-- Modal Historial de Auditoría -->
    <HistorialPeticionModal
      :mostrar="mostrarHistorialAuditoria"
      :entidad-id="movimientoSeleccionado?.id"
      :numero-documento="movimientoSeleccionado?.numero_documento"
      tipo-entidad="Movimiento"
      :api-endpoint="movimientoSeleccionado ? `/material-movimientos/${movimientoSeleccionado.id}/historial-auditoria` : ''"
      @cerrar="cerrarHistorialAuditoria"
    />
  </div>
  </template>

<script setup>
import { ref, onMounted, watch, nextTick, computed } from 'vue'
import axios from 'axios'
import HistorialPeticionModal from '../components/HistorialPeticionModal.vue'
import AlmacenSelector from '../components/AlmacenSelector.vue'

const props = defineProps({
  refreshKey: { type: Number, default: 0 }
})

const filtros = ref({ tipo: '', estado: '', buscar: '' })
const movimientos = ref([])
const loading = ref(false)
const enlaceGenerado = ref({})
const deletingId = ref(null)
const menuAbierto = ref(null)
const almacenId = ref('')

// Historial de auditoría
const mostrarHistorialAuditoria = ref(false)
const movimientoSeleccionado = ref(null)

// Rol desde store de autenticación
import { useAuthStore } from '../stores/auth'
const authStore = useAuthStore()
const isAdmin = computed(() => authStore.isAdmin)

// Firma modal state
const modalFirma = ref({ visible: false, movimientoId: null })
const firma = ref({ nombre: '', apellidos: '', dni: '' })
const signing = ref(false)
const success = ref('')
const error = ref('')

// Modal de previsualización firma remota
const modalFirmaRemota = ref({
  visible: false,
  movimientoId: null,
  tipoFirma: '',
  firmaImagen: '',
  esperando: false,
  intervalId: null
})
// Build version para forzar cache bust
const BUILD_VERSION = '1.3.2-datetime-local-20251112-1400'

// Modal para confirmar quién recoge el material
const modalConfirmarReceptor = ref({
  visible: false,
  movimiento: null,
  tipoFirma: '',
  esReceptorOriginal: true,
  datosReceptor: {
    nombre: '',
    email: '',
    telefono: '',
    dni: ''
  }
})

// Modal para notificar fecha prevista
const modalNotificarFecha = ref({
  visible: false,
  movimiento: null,
  fecha: '',
  enviando: false
})

// Modal de detalles completos del movimiento
const modalDetalles = ref({
  visible: false,
  movimiento: null
})

// Canvas signature
const canvas = ref(null)
let ctx = null
let drawing = false

function getPos(evt) {
  const rect = canvas.value.getBoundingClientRect()
  if (evt.touches && evt.touches[0]) {
    return { x: evt.touches[0].clientX - rect.left, y: evt.touches[0].clientY - rect.top }
  }
  return { x: evt.clientX - rect.left, y: evt.clientY - rect.top }
}
function startDraw(e) {
  drawing = true
  const p = getPos(e)
  ctx.beginPath()
  ctx.moveTo(p.x, p.y)
}
function draw(e) {
  if (!drawing) return
  const p = getPos(e)
  ctx.lineTo(p.x, p.y)
  ctx.stroke()
}
function stopDraw() { drawing = false }
function clearCanvas() {
  ctx.clearRect(0, 0, canvas.value.width, canvas.value.height)
}
async function initCanvas() {
  await nextTick()
  const el = canvas.value
  if (!el) return
  // Set backing store size
  const ratio = window.devicePixelRatio || 1
  const cssWidth = el.clientWidth
  const cssHeight = el.clientHeight
  el.width = cssWidth * ratio
  el.height = cssHeight * ratio
  ctx = el.getContext('2d')
  ctx.scale(ratio, ratio)
  ctx.lineWidth = 2
  ctx.lineCap = 'round'
  ctx.strokeStyle = '#111827'
}

function formatDate(v) {
  if (!v) return ''
  const d = new Date(v)
  return d.toLocaleString()
}

function formatDateTime(v) {
  if (!v) return ''
  const d = new Date(v)
  const fecha = d.toLocaleDateString('es-ES', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric' 
  })
  const hora = d.toLocaleTimeString('es-ES', { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
  return `${fecha} ${hora}`
}

function formatearFechaCompleta(v) {
  if (!v) return ''
  const d = new Date(v)
  return d.toLocaleString('es-ES', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function badgeTipo(tipo) {
  return tipo === 'entrada' ? 'px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-xs uppercase' : 'px-2 py-0.5 rounded-full bg-red-100 text-red-800 text-xs uppercase'
}

// Menú dropdown
function toggleMenu(movimientoId, event) {
  event.stopPropagation()
  menuAbierto.value = menuAbierto.value === movimientoId ? null : movimientoId
}

function cerrarMenu() {
  menuAbierto.value = null
}

// Cerrar menú al hacer clic fuera
onMounted(() => {
  document.addEventListener('click', () => {
    if (menuAbierto.value !== null) {
      cerrarMenu()
    }
  })
})

// Fecha prevista de entrega
function editarFechaPrevista(movimiento) {
  movimiento.editandoFecha = true
  // Convertir fecha del backend a formato datetime-local (YYYY-MM-DDTHH:mm)
  if (movimiento.fecha_prevista_entrega) {
    const fecha = new Date(movimiento.fecha_prevista_entrega)
    // Ajustar zona horaria local
    const offset = fecha.getTimezoneOffset() * 60000
    const localDate = new Date(fecha.getTime() - offset)
    movimiento.fecha_prevista_temp = localDate.toISOString().slice(0, 16)
  } else {
    movimiento.fecha_prevista_temp = ''
  }
}

function cancelarEdicionFecha(movimiento) {
  movimiento.editandoFecha = false
  movimiento.fecha_prevista_temp = ''
}

async function guardarFechaPrevista(movimiento) {
  // Mostrar modal preguntando si quiere notificar
  modalNotificarFecha.value = {
    visible: true,
    movimiento: movimiento,
    fecha: movimiento.fecha_prevista_temp,
    enviando: false
  }
}

async function confirmarFechaPrevista(notificar) {
  const movimiento = modalNotificarFecha.value.movimiento
  const fecha = modalNotificarFecha.value.fecha
  
  try {
    modalNotificarFecha.value.enviando = true
    movimiento.guardandoFecha = true
    
    const { data } = await axios.put(`/material-movimientos/${movimiento.id}`, {
      fecha_prevista_entrega: fecha || null,
      notificar_usuario: notificar
    })
    
    if (data.success) {
      movimiento.fecha_prevista_entrega = fecha
      movimiento.editandoFecha = false
      modalNotificarFecha.value.visible = false
      
      if (notificar) {
        alert('✓ Fecha actualizada y notificación enviada')
      } else {
        alert('✓ Fecha prevista actualizada')
      }
    }
  } catch (error) {
    console.error('Error actualizando fecha prevista:', error)
    alert('Error al actualizar la fecha prevista')
  } finally {
    movimiento.guardandoFecha = false
    modalNotificarFecha.value.enviando = false
  }
}

function cerrarModalNotificar() {
  const movimiento = modalNotificarFecha.value.movimiento
  if (movimiento) {
    movimiento.editandoFecha = false
    movimiento.guardandoFecha = false
  }
  modalNotificarFecha.value.visible = false
}

async function load() {
  loading.value = true
  try {
    const params = { ...filtros.value }
    if (almacenId.value) {
      params.almacen_seleccionado = almacenId.value
    }
    // Agregar headers para evitar cache
    const { data } = await axios.get('/material-movimientos', {
      params,
      headers: {
        'Cache-Control': 'no-cache',
        'Pragma': 'no-cache',
        'Expires': '0'
      }
    })
    movimientos.value = data.success ? data.data : []
  } catch (e) {
    movimientos.value = []
  } finally {
    loading.value = false
  }
}

function onAlmacenChange() {
  load()
}

function ver(m) {
  // Mostrar modal de detalles completos
  modalDetalles.value = {
    visible: true,
    movimiento: m
  }
}

function cerrarModalDetalles() {
  modalDetalles.value.visible = false
}

function abrirAlbaran(m) {
  // Abrimos una pestaña en blanco inmediatamente para evitar bloqueadores de popups
  const win = window.open('about:blank', '_blank')
  // Si ya tiene enlace público válido, navegar directamente
  if (m.enlace_publico) {
    if (win) win.location.href = `/gestionmaterial/albaran/${m.enlace_publico}`
    else window.open(`/gestionmaterial/albaran/${m.enlace_publico}`, '_blank')
    return
  }
  // Si no lo tiene, generarlo y luego abrir en la pestaña preparada
  generarEnlace(m, { abrir: true, win })
}

async function firmarEmisor(m) {
  modalFirma.value = { visible: true, movimientoId: m.id }
  success.value = ''
  error.value = ''
  firma.value = { nombre: '', apellidos: '', dni: '' }
  await initCanvas()
}

async function submitFirma() {
  try {
    signing.value = true
    error.value = ''
    success.value = ''

    const dataUrl = canvas.value.toDataURL('image/png')
    if (!firma.value.nombre || !firma.value.apellidos) throw new Error('Nombre y apellidos son obligatorios')

    const { data } = await axios.post(`/material-movimientos/${modalFirma.value.movimientoId}/firmar-emisor`, {
      nombre: firma.value.nombre,
      apellidos: firma.value.apellidos,
      dni: firma.value.dni || null,
      firma_rubrica: dataUrl,
    })

    if (data.success) {
      success.value = 'Documento firmado'
      await load()
      setTimeout(closeModal, 800)
    } else {
      error.value = data.message || 'No se pudo firmar'
    }
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    signing.value = false
  }
}

function closeModal() {
  modalFirma.value = { visible: false, movimientoId: null }
}

async function generarEnlace(m, opts = {}) {
  try {
    const { data } = await axios.post(`/material-movimientos/${m.id}/generar-enlace`, { dias_expiracion: 30 })
    if (data.success) {
      enlaceGenerado.value[m.id] = data.data.enlace
      await load()
      if (opts.abrir) {
        if (opts.win) opts.win.location.href = data.data.enlace
        else window.open(data.data.enlace, '_blank')
      }
    }
  } catch (e) {
    console.error('Error generando enlace', e)
  }
}

async function descargarPDF(m) {
  try {
    // Usar axios para descargar el PDF con autenticación
    const response = await axios.get(`/material-movimientos/${m.id}/pdf`, {
      responseType: 'blob' // Importante para archivos binarios
    })
    
    // Crear un blob y descargarlo
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `${m.numero_documento}.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Error al descargar PDF:', error)
    alert('Error al descargar el PDF. Por favor, intenta de nuevo.')
  }
}

async function eliminarMovimiento(m) {
  if (!confirm(`¿Eliminar el movimiento ${m.numero_documento}? Esta acción no se puede deshacer.`)) return
  try {
    deletingId.value = m.id
    const { data } = await axios.delete(`/material-movimientos/${m.id}`)
    if (!data.success) throw new Error(data.message || 'Error al eliminar')
    await load()
  } catch (e) {
    alert(e.response?.data?.message || e.message || 'Error al eliminar')
  } finally {
    deletingId.value = null
  }
}

async function solicitarFirmaRemota(m, tipoFirma) {
  // Mostrar modal para confirmar quién va a recoger
  modalConfirmarReceptor.value = {
    visible: true,
    movimiento: m,
    tipoFirma: tipoFirma,
    esReceptorOriginal: true,
    datosReceptor: {
      nombre: m.pedido?.usuario_solicitante || m.destino || '',
      email: m.pedido?.email_solicitante || '',
      telefono: m.pedido?.telefono_solicitante || '',
      dni: ''
    }
  }
}

function cerrarModalConfirmarReceptor() {
  modalConfirmarReceptor.value.visible = false
}

async function continuarConFirmaRemota() {
  const m = modalConfirmarReceptor.value.movimiento
  const tipoFirma = modalConfirmarReceptor.value.tipoFirma
  const datosReceptor = modalConfirmarReceptor.value.esReceptorOriginal 
    ? modalConfirmarReceptor.value.datosReceptor
    : modalConfirmarReceptor.value.datosReceptor
  
  // Validar datos si es otra persona
  if (!modalConfirmarReceptor.value.esReceptorOriginal) {
    if (!datosReceptor.nombre || !datosReceptor.email) {
      alert('Por favor, completa al menos el nombre y email del receptor')
      return
    }
  }
  
  cerrarModalConfirmarReceptor()
  
  const sessionId = prompt(`Ingresa el código de sesión del dispositivo móvil (4 dígitos):`)
  if (!sessionId) return

  try {
    // Mostrar modal de espera
    modalFirmaRemota.value = {
      visible: true,
      movimientoId: m.id,
      tipoFirma: tipoFirma,
      firmaImagen: '',
      esperando: true,
      intervalId: null,
      sessionId: sessionId.trim(),
      datosReceptor: datosReceptor
    }

    const { data } = await axios.post(`/material-movimientos/${m.id}/solicitar-firma-remota`, {
      tipo_firma: tipoFirma,
      session_id: sessionId.trim(),
      datos_receptor: datosReceptor
    })

    if (data.success) {
      // Iniciar polling para verificar si llegó la firma
      modalFirmaRemota.value.intervalId = setInterval(async () => {
        await verificarFirmaRecibida()
      }, 2000) // Cada 2 segundos
    } else {
      alert(data.message || 'Error al enviar solicitud')
      cerrarModalFirmaRemota()
    }
  } catch (e) {
    alert(e.response?.data?.message || e.message || 'Error al solicitar firma remota')
    cerrarModalFirmaRemota()
  }
}

async function verificarFirmaRecibida() {
  try {
    const { data } = await axios.get(`/material-movimientos/${modalFirmaRemota.value.movimientoId}/verificar-firma-pendiente`, {
      params: {
        session_id: modalFirmaRemota.value.sessionId,
        tipo_firma: modalFirmaRemota.value.tipoFirma
      }
    })

    if (data.firma_recibida) {
      // Detener polling
      if (modalFirmaRemota.value.intervalId) {
        clearInterval(modalFirmaRemota.value.intervalId)
      }

      // Mostrar previsualización
      modalFirmaRemota.value.esperando = false
      modalFirmaRemota.value.firmaImagen = data.firma_base64
    }
  } catch (e) {
    console.error('Error verificando firma:', e)
  }
}

async function aceptarFirmaRemota() {
  try {
    signing.value = true
    error.value = ''

    const { data } = await axios.post(`/material-movimientos/${modalFirmaRemota.value.movimientoId}/confirmar-firma-remota`, {
      session_id: modalFirmaRemota.value.sessionId,
      tipo_firma: modalFirmaRemota.value.tipoFirma,
      datos_receptor: modalFirmaRemota.value.datosReceptor
    })

    if (data.success) {
      await load()
      cerrarModalFirmaRemota()
      alert('Firma guardada correctamente')
    } else {
      error.value = data.message || 'Error al guardar firma'
    }
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Error al guardar firma'
  } finally {
    signing.value = false
  }
}

function rechazarFirmaRemota() {
  if (confirm('¿Deseas rechazar esta firma y solicitar una nueva?')) {
    cerrarModalFirmaRemota()
  }
}

function cerrarModalFirmaRemota() {
  if (modalFirmaRemota.value.intervalId) {
    clearInterval(modalFirmaRemota.value.intervalId)
  }
  modalFirmaRemota.value = {
    visible: false,
    movimientoId: null,
    tipoFirma: '',
    firmaImagen: '',
    esperando: false,
    intervalId: null,
    sessionId: ''
  }
}

async function marcarEntregado(m) {
  if (!confirm('¿Deseas marcar este movimiento como entregado? Se registrará la fecha y hora actual.')) {
    return
  }

  try {
    // Marcar en la UI que se está procesando
    m.marcandoEntregado = true

    const { data } = await axios.post(`/material-movimientos/${m.id}/marcar-entregado`)

    if (data.success) {
      // Actualizar el movimiento en la lista
      const index = movimientos.value.findIndex(mov => mov.id === m.id)
      if (index !== -1) {
        movimientos.value[index] = {
          ...movimientos.value[index],
          estado: 'entregado',
          fecha_entrega: data.movimiento.fecha_entrega
        }
      }
      alert('✓ Movimiento marcado como entregado correctamente')
    } else {
      alert(data.message || 'Error al marcar como entregado')
    }
  } catch (e) {
    alert(e.response?.data?.message || e.message || 'Error al marcar como entregado')
  } finally {
    m.marcandoEntregado = false
  }
}

function verHistorial(m) {
  movimientoSeleccionado.value = m
  mostrarHistorialAuditoria.value = true
}

function cerrarHistorialAuditoria() {
  mostrarHistorialAuditoria.value = false
  movimientoSeleccionado.value = null
}

async function anularFirma(m, tipoFirmante) {
  const confirmacion = confirm(`¿Estás seguro de que quieres anular la firma de ${tipoFirmante}?\n\nEsto eliminará el registro de firma y el documento volverá a estado "pendiente firma".`)
  
  if (!confirmacion) return

  try {
    // Encontrar el ID de la firma
    const firma = m.firmas?.find(f => f.tipo_firmante === tipoFirmante)
    if (!firma) {
      alert('No se encontró la firma para anular')
      return
    }

    const { data } = await axios.delete(`/material-movimientos/${m.id}/firmas/${firma.id}`, {
      data: { motivo: 'Firma incorrecta o inválida - anulada por administrador' }
    })

    if (data.success) {
      alert('✓ Firma anulada correctamente')
      load() // Recargar lista de movimientos
    } else {
      alert(data.message || 'Error al anular la firma')
    }
  } catch (e) {
    alert(e.response?.data?.message || e.message || 'Error al anular la firma')
  }
}

async function copiarAlPortapapeles(texto) {
  try {
    await navigator.clipboard.writeText(texto)
    alert('✓ Enlace copiado al portapapeles')
  } catch (err) {
    // Fallback para navegadores que no soportan clipboard API
    const textarea = document.createElement('textarea')
    textarea.value = texto
    textarea.style.position = 'fixed'
    textarea.style.opacity = '0'
    document.body.appendChild(textarea)
    textarea.select()
    try {
      document.execCommand('copy')
      alert('✓ Enlace copiado al portapapeles')
    } catch (e) {
      alert('❌ No se pudo copiar el enlace. Por favor, cópialo manualmente.')
    }
    document.body.removeChild(textarea)
  }
}

onMounted(load)
watch(() => props.refreshKey, load)
</script>
