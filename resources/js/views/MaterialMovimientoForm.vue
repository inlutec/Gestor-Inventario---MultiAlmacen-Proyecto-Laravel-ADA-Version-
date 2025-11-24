<template>
  <div class="space-y-6">
    <!-- Selector de Almacén -->
    <div class="bg-white rounded-lg shadow p-4">
      <AlmacenSelector v-model="almacenId" @change="onAlmacenChange" />
    </div>

    <!-- Información General -->
    <div class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Información General
      </h3>
      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Movimiento</label>
          <select v-model="form.tipo" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            <option value="salida">Salida</option>
            <option value="entrada">Entrada</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Fecha</label>
          <input v-model="form.fecha_movimiento" type="datetime-local" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Justificante</label>
          <select v-model="form.justificante_id" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            <option :value="null">Seleccionar motivo...</option>
            <option v-for="j in justificantesActivos" :key="j.id" :value="j.id" :title="j.descripcion">
              {{ j.nombre }}
            </option>
          </select>
          <p v-if="justificanteSeleccionado?.descripcion" class="text-xs text-gray-600 mt-2 p-2 bg-gray-50 rounded border border-gray-200">
            {{ justificanteSeleccionado.descripcion }}
          </p>
        </div>
      </div>
    </div>

    <!-- Origen y Destino -->
    <div class="grid md:grid-cols-2 gap-6">
      <!-- Origen -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Origen
        </h3>
        <div class="flex gap-3 mb-4 p-2 bg-gray-50 rounded-lg">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" value="manual" v-model="origenModo" class="text-blue-600 focus:ring-blue-500">
            <span class="text-sm font-medium text-gray-700">Manual</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" value="catalogo" v-model="origenModo" class="text-blue-600 focus:ring-blue-500">
            <span class="text-sm font-medium text-gray-700">Sede/Departamento</span>
          </label>
        </div>
        <div v-if="origenModo==='manual'">
          <input v-model="form.origen" type="text" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" placeholder="Ej: Almacén A" />
        </div>
        <div v-else class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Provincia</label>
            <select v-model="origenProvinciaId" @change="onOrigenProvinciaChange" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
              <option :value="null">Seleccionar provincia...</option>
              <option v-for="p in provincias" :key="p.id" :value="p.id">{{ p.nombre }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Sede</label>
            <select v-model="origenSedeId" @change="syncOrigenTexto" :disabled="!origenProvinciaId" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition disabled:bg-gray-100 disabled:cursor-not-allowed">
              <option :value="null">Seleccionar sede...</option>
              <option v-for="s in sedesOrigen" :key="s.id" :value="s.id">{{ s.nombre }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Departamento <span class="text-gray-500 font-normal">(Opcional)</span></label>
            <select v-model="origenDepartamentoId" @change="syncOrigenTexto" :disabled="!origenSedeId" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition disabled:bg-gray-100 disabled:cursor-not-allowed">
              <option :value="null">Seleccionar departamento...</option>
              <option v-for="d in departamentosOrigen" :key="d.id" :value="d.id">{{ d.nombre }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Destino -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
          </svg>
          Destino
        </h3>
        <div class="flex gap-3 mb-4 p-2 bg-gray-50 rounded-lg">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" value="manual" v-model="destinoModo" class="text-blue-600 focus:ring-blue-500">
            <span class="text-sm font-medium text-gray-700">Manual</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" value="catalogo" v-model="destinoModo" class="text-blue-600 focus:ring-blue-500">
            <span class="text-sm font-medium text-gray-700">Sede/Departamento</span>
          </label>
        </div>
        <div v-if="destinoModo==='manual'">
          <input v-model="form.destino" type="text" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" placeholder="Ej: Oficina B" />
        </div>
        <div v-else class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Provincia</label>
            <select v-model="destinoProvinciaId" @change="onDestinoProvinciaChange" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
              <option :value="null">Seleccionar provincia...</option>
              <option v-for="p in provincias" :key="p.id" :value="p.id">{{ p.nombre }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Sede</label>
            <select v-model="destinoSedeId" @change="syncDestinoTexto" :disabled="!destinoProvinciaId" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition disabled:bg-gray-100 disabled:cursor-not-allowed">
              <option :value="null">Seleccionar sede...</option>
              <option v-for="s in sedesDestino" :key="s.id" :value="s.id">{{ s.nombre }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Departamento <span class="text-gray-500 font-normal">(Opcional)</span></label>
            <select v-model="destinoDepartamentoId" @change="syncDestinoTexto" :disabled="!destinoSedeId" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition disabled:bg-gray-100 disabled:cursor-not-allowed">
              <option :value="null">Seleccionar departamento...</option>
              <option v-for="d in departamentosDestino" :key="d.id" :value="d.id">{{ d.nombre }}</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Observaciones -->
    <div class="bg-white rounded-lg shadow p-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
      <textarea v-model="form.observaciones" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition resize-none" placeholder="Añadir observaciones adicionales..."></textarea>
    </div>

    <!-- Detalle del Movimiento -->
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
          <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
          Detalle del Movimiento
        </h3>
        <span class="text-sm text-gray-500">{{ form.detalles.length }} {{ form.detalles.length === 1 ? 'línea' : 'líneas' }}</span>
      </div>

      <div class="space-y-4">
        <div v-for="(d,idx) in form.detalles" :key="idx" class="p-5 border-2 border-gray-200 rounded-lg bg-gradient-to-br from-gray-50 to-white hover:border-blue-300 transition-all">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
              <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">Línea {{ idx + 1 }}</span>
            </div>
            <button type="button" @click="removeLinea(idx)" class="px-3 py-1.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-sm font-medium flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
              Eliminar
            </button>
          </div>
          
          <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Buscador de Material -->
            <div class="lg:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">Material</label>
              <div class="relative">
                <input 
                  v-model="d.busqueda" 
                  @input="onBusquedaInput(d)" 
                  @focus="onBusquedaFocus(d)" 
                  @blur="onBusquedaBlur(d)" 
                  type="text" 
                  class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pr-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" 
                  placeholder="Buscar material..." 
                />
                <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                  </svg>
                </span>
                <ul v-if="d.mostrarOpciones && materialesFiltrados(d.busqueda).length"
                  class="absolute left-0 top-full w-full z-[9999] bg-white border-2 border-blue-400 rounded-lg shadow-2xl max-h-80 overflow-y-auto mt-1"
                  style="box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                  <li 
                    v-for="m in materialesFiltrados(d.busqueda)" 
                    :key="m.id" 
                    @mousedown="seleccionarMaterial(d, m)" 
                    class="px-4 py-3 hover:bg-blue-50 cursor-pointer border-b last:border-b-0 text-sm font-medium text-gray-800 transition-colors"
                  >
                    {{ labelMaterial(m) }}
                  </li>
                </ul>
              </div>
            </div>
            
            <!-- Cantidad -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad</label>
              <input v-model.number="d.cantidad" type="number" min="1" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-right focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" />
            </div>
            
            <!-- Unidad -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Unidad</label>
              <input v-model="d.unidad" type="text" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" />
            </div>
          </div>

          <!-- Descripción y Observaciones -->
          <div class="grid md:grid-cols-2 gap-4 mt-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
              <input v-model="d.descripcion" type="text" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
              <input v-model="d.observaciones" type="text" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" />
            </div>
          </div>
        </div>
        
        <button type="button" @click="addLinea" class="w-full px-4 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-medium flex items-center justify-center gap-2 shadow-md hover:shadow-lg">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Añadir línea
        </button>
      </div>
    </div>

    <!-- Botones de Acción -->
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center justify-between">
        <div class="flex-1">
          <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ error }}
          </div>
          <div v-if="success" class="p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ success }}
          </div>
        </div>
        <button type="submit" :disabled="submitting" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
          <svg v-if="!submitting" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 8 4 12zm8 0a8 8 0 100-16v4a8 8 0 018 8z"></path>
          </svg>
          {{ submitting ? 'Guardando...' : 'Guardar movimiento' }}
        </button>
      </div>
    </div>

    <!-- Mostrar enlace público si se generó -->
    <div v-if="enlacePublico" class="bg-blue-50 border-2 border-blue-200 rounded-lg p-6 shadow-lg">
      <div class="flex items-start gap-3 mb-4">
        <div class="flex-shrink-0">
          <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <div class="flex-1">
          <h4 class="font-semibold text-blue-900 mb-1">Movimiento creado correctamente</h4>
          <p class="text-sm text-blue-800 mb-3">Comparte este enlace para que el responsable pueda firmar:</p>
          <div class="flex items-center gap-2">
            <input 
              :value="enlacePublico" 
              readonly 
              class="flex-1 px-4 py-2.5 border border-blue-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <button 
              type="button" 
              @click="copyEnlace" 
              class="px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium transition-colors flex items-center gap-2 whitespace-nowrap"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
              </svg>
              {{ copiado ? 'Copiado!' : 'Copiar' }}
            </button>
          </div>
          <p v-if="copiado" class="text-sm text-green-700 mt-2 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Enlace copiado al portapapeles
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import axios from 'axios'
import AlmacenSelector from '../components/AlmacenSelector.vue'

// Filtra materiales por texto de búsqueda
function materialesFiltrados(busqueda) {
  const txt = (busqueda || '').toLowerCase().trim();
  let resultados = materiales.value;
  
  if (txt) {
    resultados = materiales.value.filter(m => {
      const d = m.datos || {};
      return (
        (d.referencia && d.referencia.toLowerCase().includes(txt)) ||
        (d.nombre && d.nombre.toLowerCase().includes(txt)) ||
        (String(m.id).includes(txt))
      );
    });
  }
  
  // Limitar a 10 resultados
  return resultados.slice(0, 10);
}

const emit = defineEmits(['created'])

const materiales = ref([])
const sedes = ref([])
const provincias = ref([])
const departamentosPorSede = ref({})
const justificantes = ref([])
const almacenId = ref('')

const origenModo = ref('manual')
const destinoModo = ref('manual')
const origenProvinciaId = ref(null)
const origenSedeId = ref(null)
const origenDepartamentoId = ref(null)
const destinoProvinciaId = ref(null)
const destinoSedeId = ref(null)
const destinoDepartamentoId = ref(null)
const submitting = ref(false)
const error = ref('')
const success = ref('')
const enlacePublico = ref('')
const copiado = ref(false)

function nuevaLinea() {
  return { entidad_id: null, descripcion: '', cantidad: 1, unidad: 'ud', observaciones: '', busqueda: '', mostrarOpciones: false }
}

const form = ref({
  tipo: 'salida',
  fecha_movimiento: new Date().toISOString().slice(0,16),
  justificante_id: null,
  origen: '',
  destino: '',
  observaciones: '',
  detalles: [ nuevaLinea() ]
})

// Computed para justificantes activos según tipo
const justificantesActivos = computed(() => {
  return justificantes.value.filter(j => 
    j.tipo === form.value.tipo && j.activo
  );
});

// Computed para obtener justificante seleccionado
const justificanteSeleccionado = computed(() => {
  if (!form.value.justificante_id) return null;
  return justificantes.value.find(j => j.id === form.value.justificante_id);
});

// Watch para resetear justificante_id cuando cambia el tipo
watch(() => form.value.tipo, () => {
  form.value.justificante_id = null;
});

async function loadJustificantes() {
  try {
    const { data } = await axios.get('/config/justificantes');
    justificantes.value = data.data || [];
  } catch (e) {
    console.error('Error cargando justificantes:', e);
  }
}

function labelMaterial(m) {
  const d = m.datos || {}
  return d.referencia || d.nombre || `#${m.id}`
}

function onEntidadChange(det) {
  const m = materiales.value.find(x => x.id === det.entidad_id)
  if (!m) return
  const d = m.datos || {}
  if (!det.descripcion) det.descripcion = d.referencia || d.nombre || ''
  if (!det.unidad) det.unidad = d.unidad || 'ud'
}

function seleccionarMaterial(det, material) {
  det.entidad_id = material.id
  det.busqueda = labelMaterial(material)
  det.mostrarOpciones = false
  onEntidadChange(det)
}

function onBusquedaInput(det) {
  det.busqueda = det.busqueda?.trim() || ''
  det.mostrarOpciones = true
  if (!det.busqueda) {
    det.entidad_id = null
  }
}

function onBusquedaFocus(det) {
  det.mostrarOpciones = true
}

function onBusquedaBlur(det) {
  // Delay para permitir click en opción
  setTimeout(() => {
    det.mostrarOpciones = false
  }, 200)
}

function addLinea() {
  form.value.detalles.push(nuevaLinea())
}

function removeLinea(idx) {
  form.value.detalles.splice(idx, 1)
  if (!form.value.detalles.length) addLinea()
}

function copyEnlace() {
  navigator.clipboard.writeText(enlacePublico.value).then(() => {
    copiado.value = true
    setTimeout(() => {
      copiado.value = false
    }, 3000)
  }).catch(err => {
    console.error('Error al copiar:', err)
  })
}

async function loadMateriales() {
  try {
    const params = { tipo_entidad_id: 3 }
    if (almacenId.value) {
      params.almacen_ids = [almacenId.value]
    }
    const { data } = await axios.get('/entidades', { params })
    if (data.success) materiales.value = data.data
  } catch (e) {
    console.error('Error cargando materiales', e)
  }
}

function onAlmacenChange() {
  loadMateriales()
}

async function loadProvincias() {
  try {
    const { data } = await axios.get('/config/provincias')
    if (data.success) {
      provincias.value = data.data.filter(p => p.activo !== false) || []
    }
  } catch (e) {
    console.error('Error cargando provincias', e)
  }
}

async function loadSedes() {
  try {
    const { data } = await axios.get('/config/sedes')
    if (data.success) {
      sedes.value = data.data.map(s => ({ 
        id: s.id, 
        nombre: s.nombre, 
        provincia_id: s.provincia_id,
        departamentos: s.departamentos || [] 
      }))
      departamentosPorSede.value = Object.fromEntries(
        sedes.value.map(s => [s.id, s.departamentos])
      )
    }
  } catch (e) {
    console.error('Error cargando sedes', e)
  }
}

const sedesOrigen = computed(() => {
  if (!origenProvinciaId.value) return []
  return sedes.value.filter(s => s.provincia_id === origenProvinciaId.value)
})

const sedesDestino = computed(() => {
  if (!destinoProvinciaId.value) return []
  return sedes.value.filter(s => s.provincia_id === destinoProvinciaId.value)
})

const departamentosOrigen = computed(() => departamentosPorSede.value[origenSedeId.value] || [])
const departamentosDestino = computed(() => departamentosPorSede.value[destinoSedeId.value] || [])

function onOrigenProvinciaChange() {
  origenSedeId.value = null
  origenDepartamentoId.value = null
  syncOrigenTexto()
}

function onDestinoProvinciaChange() {
  destinoSedeId.value = null
  destinoDepartamentoId.value = null
  syncDestinoTexto()
}

function syncOrigenTexto() {
  const provincia = provincias.value.find(p => p.id === origenProvinciaId.value)
  const sede = sedesOrigen.value.find(s => s.id === origenSedeId.value)
  const dep = departamentosOrigen.value.find(d => d.id === origenDepartamentoId.value)
  const partes = [provincia?.nombre, sede?.nombre, dep?.nombre].filter(Boolean)
  form.value.origen = partes.join(' - ')
}

function syncDestinoTexto() {
  const provincia = provincias.value.find(p => p.id === destinoProvinciaId.value)
  const sede = sedesDestino.value.find(s => s.id === destinoSedeId.value)
  const dep = departamentosDestino.value.find(d => d.id === destinoDepartamentoId.value)
  const partes = [provincia?.nombre, sede?.nombre, dep?.nombre].filter(Boolean)
  form.value.destino = partes.join(' - ')
}

async function onSubmit() {
  submitting.value = true
  error.value = ''
  success.value = ''
  enlacePublico.value = ''
  copiado.value = false
  
  try {
    const payload = JSON.parse(JSON.stringify(form.value))
    // Limpieza: asegurar enteros y mínimos
    payload.detalles = payload.detalles.filter(d => d.entidad_id && d.descripcion && d.cantidad > 0)
    if (!payload.detalles.length) throw new Error('Debe añadir al menos una línea válida')

    // Añadir IDs de sede/departamento según modo seleccionado
    payload.origen_sede_id = origenModo.value === 'catalogo' ? origenSedeId.value : null
    payload.origen_departamento_id = origenModo.value === 'catalogo' ? origenDepartamentoId.value : null
    payload.destino_sede_id = destinoModo.value === 'catalogo' ? destinoSedeId.value : null
    payload.destino_departamento_id = destinoModo.value === 'catalogo' ? destinoDepartamentoId.value : null

    // Reglas: ENTRADA exige destino sede y departamento
    if (payload.tipo === 'entrada') {
      if (!payload.destino_sede_id || !payload.destino_departamento_id) {
        throw new Error('Para ENTRADA debe seleccionar Sede y Departamento de destino')
      }
    }

    const { data } = await axios.post('/material-movimientos', payload)
    if (data.success) {
      success.value = 'Movimiento creado correctamente'
      
      // Si es una entrada y se generó enlace público, mostrarlo
      if (payload.tipo === 'entrada' && data.enlace_publico) {
        enlacePublico.value = data.enlace_publico
      }
      
      emit('created')
      
      // Reset form
      form.value = {
        tipo: 'salida',
        fecha_movimiento: new Date().toISOString().slice(0,16),
        justificante_id: null,
        origen: '',
        destino: '',
        observaciones: '',
        detalles: [ nuevaLinea() ]
      }
      origenProvinciaId.value = null
      origenSedeId.value = null
      origenDepartamentoId.value = null
      destinoProvinciaId.value = null
      destinoSedeId.value = null
      destinoDepartamentoId.value = null
    } else {
      error.value = data.message || 'No se pudo crear el movimiento'
    }
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Error inesperado'
    if (e.response?.data?.errors) {
      const errDetails = Object.entries(e.response.data.errors).map(([k,v]) => `${k}: ${v.join(', ')}`).join(' | ')
      error.value += ' - ' + errDetails
    }
    console.error('Error al crear movimiento:', e.response?.data || e)
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadMateriales(), loadProvincias(), loadSedes(), loadJustificantes()])
})
</script>
