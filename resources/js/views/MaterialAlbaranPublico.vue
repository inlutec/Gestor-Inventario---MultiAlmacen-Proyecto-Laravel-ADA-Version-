<template>
  <div class="max-w-4xl mx-auto p-4 space-y-4">
    <div class="space-y-1">
      <h1 class="text-2xl font-bold">Albarán de Material</h1>
      <p class="text-sm text-slate-600">Revise, firme y descargue el justificante</p>
    </div>

    <div v-if="loading" class="text-slate-500 text-sm">Cargando...</div>
    <div v-else-if="error" class="text-red-600">{{ error }}</div>

    <div v-else-if="albaran" class="space-y-4">
      <div class="bg-white shadow rounded p-4">
        <div class="flex flex-wrap items-center gap-3 justify-between">
          <div>
            <div class="text-sm text-slate-500">Documento</div>
            <div class="text-lg font-semibold">{{ albaran.numero_documento }}</div>
          </div>
          <div>
            <span :class="badgeTipo(albaran.tipo)">{{ albaran.tipo }}</span>
          </div>
        </div>
        <div class="grid md:grid-cols-3 gap-3 mt-3 text-sm">
          <div><span class="text-slate-500">Fecha: </span>{{ formatDate(albaran.fecha_movimiento) }}</div>
          <div><span class="text-slate-500">Origen: </span>{{ albaran.origen || '-' }}</div>
          <div><span class="text-slate-500">Destino: </span>{{ albaran.destino || '-' }}</div>
        </div>
      </div>

      <div class="bg-white shadow rounded p-4">
        <h3 class="font-semibold mb-2">Detalle</h3>
        <div class="overflow-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="bg-slate-100 text-left">
                <th class="p-2">Descripción</th>
                <th class="p-2 text-center">Cantidad</th>
                <th class="p-2 text-center">Unidad</th>
                <th class="p-2">Observaciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(d, i) in albaran.detalles" :key="i" class="border-b">
                <td class="p-2">{{ d.descripcion }}</td>
                <td class="p-2 text-center">{{ d.cantidad }}</td>
                <td class="p-2 text-center">{{ d.unidad }}</td>
                <td class="p-2">{{ d.observaciones || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="bg-white shadow rounded p-4">
        <h3 class="font-semibold mb-2">Firma</h3>
        <div v-if="albaran.ya_firmado" class="text-green-700">Este albarán ya ha sido firmado. Puede descargar el PDF.</div>

        <div v-else class="space-y-3">
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
              <button class="px-3 py-2 bg-blue-600 text-white rounded" @click="firmar" :disabled="signing">{{ signing ? 'Firmando...' : 'Firmar' }}</button>
            </div>
            <div v-if="firmaError" class="text-red-600 text-sm mt-1">{{ firmaError }}</div>
            <div v-if="firmaSuccess" class="text-green-700 text-sm mt-1">{{ firmaSuccess }}</div>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3 flex-wrap">
        <button class="px-3 py-2 border rounded" @click="descargarPDF" :disabled="albaran.estado !== 'firmado'">
          📄 Descargar PDF Firmado
        </button>
        <button class="px-3 py-2 border rounded bg-green-50 text-green-700 hover:bg-green-100" @click="descargarPDFSinFirmar">
          📥 Descargar PDF para Firmar con Autofirma
        </button>
        <button class="px-3 py-2 border rounded bg-blue-50 text-blue-700 hover:bg-blue-100" @click="mostrarModalSubirPDF = true">
          📤 Subir PDF Firmado
        </button>
      </div>
    </div>

    <!-- Modal para subir PDF firmado -->
    <div v-if="mostrarModalSubirPDF" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 space-y-4">
        <div class="flex justify-between items-center">
          <h3 class="text-xl font-bold text-gray-800">📤 Subir PDF Firmado</h3>
          <button @click="cerrarModalSubirPDF" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <div class="text-sm text-gray-600 space-y-2">
          <p>1. Descarga el PDF sin firmar</p>
          <p>2. Fírmalo con Autofirma en tu PC</p>
          <p>3. Sube el PDF firmado aquí</p>
        </div>

        <div class="space-y-3">
          <div>
            <label class="block text-sm font-medium mb-1">Nombre *</label>
            <input v-model="datosSubidaPDF.nombre" type="text" class="w-full border rounded px-3 py-2" required />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Apellidos *</label>
            <input v-model="datosSubidaPDF.apellidos" type="text" class="w-full border rounded px-3 py-2" required />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">DNI</label>
            <input v-model="datosSubidaPDF.dni" type="text" class="w-full border rounded px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Tipo de Firma *</label>
            <select v-model="datosSubidaPDF.tipo_firmante" class="w-full border rounded px-3 py-2" required>
              <option value="">Seleccionar...</option>
              <option value="emisor" :disabled="albaran.tiene_firma_emisor">Emisor {{ albaran.tiene_firma_emisor ? '(Ya firmado)' : '' }}</option>
              <option value="receptor" :disabled="albaran.tiene_firma_receptor">Receptor {{ albaran.tiene_firma_receptor ? '(Ya firmado)' : '' }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">PDF Firmado *</label>
            <input type="file" @change="seleccionarPDF" accept="application/pdf" class="w-full border rounded px-3 py-2" required />
          </div>
        </div>

        <div v-if="errorSubidaPDF" class="text-red-600 text-sm">{{ errorSubidaPDF }}</div>
        <div v-if="successSubidaPDF" class="text-green-700 text-sm">{{ successSubidaPDF }}</div>

        <div class="flex gap-3">
          <button 
            @click="subirPDFFirmado" 
            :disabled="subiendoPDF || !datosSubidaPDF.archivoPDF"
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
          >
            {{ subiendoPDF ? '⏳ Subiendo...' : '✓ Subir y Firmar' }}
          </button>
          <button @click="cerrarModalSubirPDF" class="px-4 py-2 border rounded hover:bg-gray-50">
            Cancelar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const token = ref(route.params.token)
const albaran = ref(null)
const loading = ref(true)
const error = ref('')

// firma
const firma = ref({ nombre: '', apellidos: '', dni: '' })
const signing = ref(false)
const firmaError = ref('')
const firmaSuccess = ref('')

// Subida de PDF firmado
const mostrarModalSubirPDF = ref(false)
const datosSubidaPDF = ref({ nombre: '', apellidos: '', dni: '', tipo_firmante: '', archivoPDF: null })
const subiendoPDF = ref(false)
const errorSubidaPDF = ref('')
const successSubidaPDF = ref('')

const canvas = ref(null)
let ctx = null
let drawing = false

function getPos(evt) {
  const rect = canvas.value.getBoundingClientRect()
  if (evt.touches && evt.touches[0]) return { x: evt.touches[0].clientX - rect.left, y: evt.touches[0].clientY - rect.top }
  return { x: evt.clientX - rect.left, y: evt.clientY - rect.top }
}
function startDraw(e) {
  drawing = true
  const p = getPos(e)
  ctx.beginPath(); ctx.moveTo(p.x, p.y)
}
function draw(e) {
  if (!drawing) return
  const p = getPos(e)
  ctx.lineTo(p.x, p.y); ctx.stroke()
}
function stopDraw() { drawing = false }
function clearCanvas() { ctx.clearRect(0,0,canvas.value.width, canvas.value.height) }

async function initCanvas() {
  await nextTick()
  const el = canvas.value
  if (!el) return
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

function formatDate(v) { return v ? new Date(v).toLocaleString() : '' }
function badgeTipo(t) { return t === 'entrada' ? 'px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-xs uppercase' : 'px-2 py-0.5 rounded-full bg-red-100 text-red-800 text-xs uppercase' }

async function load() {
  loading.value = true
  try {
    // axios has baseURL '/api' configured globally
    const { data } = await axios.get(`/albaran/${token.value}`)
    if (!data.success) throw new Error(data.message || 'No se pudo cargar el albarán')
    albaran.value = data.data
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
    await initCanvas()
  }
}

async function firmar() {
  firmaError.value = ''
  firmaSuccess.value = ''
  try {
    signing.value = true
    if (!firma.value.nombre || !firma.value.apellidos) throw new Error('Nombre y apellidos son obligatorios')
    const dataUrl = canvas.value.toDataURL('image/png')
  // axios has baseURL '/api' configured globally
  const { data } = await axios.post(`/albaran/${token.value}/firmar`, {
      nombre: firma.value.nombre,
      apellidos: firma.value.apellidos,
      dni: firma.value.dni || null,
      firma_rubrica: dataUrl,
    })
    if (data.success) {
      firmaSuccess.value = 'Albarán firmado correctamente'
      await load()
    } else {
      firmaError.value = data.message || 'No se pudo firmar'
    }
  } catch (e) {
    firmaError.value = e.response?.data?.message || e.message
  } finally {
    signing.value = false
  }
}

function descargarPDF() {
  window.open(`/gestionmaterial/api/albaran/${token.value}/pdf`, '_blank')
}

function descargarPDFSinFirmar() {
  window.open(`/gestionmaterial/api/albaran/${token.value}/pdf-sin-firmar`, '_blank')
}

function seleccionarPDF(event) {
  const file = event.target.files[0]
  if (file && file.type === 'application/pdf') {
    datosSubidaPDF.value.archivoPDF = file
    errorSubidaPDF.value = ''
  } else {
    errorSubidaPDF.value = 'Por favor, selecciona un archivo PDF válido'
    event.target.value = ''
  }
}

async function subirPDFFirmado() {
  errorSubidaPDF.value = ''
  successSubidaPDF.value = ''

  if (!datosSubidaPDF.value.nombre || !datosSubidaPDF.value.apellidos) {
    errorSubidaPDF.value = 'Por favor, completa nombre y apellidos'
    return
  }

  if (!datosSubidaPDF.value.tipo_firmante) {
    errorSubidaPDF.value = 'Por favor, selecciona el tipo de firma'
    return
  }

  if (!datosSubidaPDF.value.archivoPDF) {
    errorSubidaPDF.value = 'Por favor, selecciona un archivo PDF'
    return
  }

  subiendoPDF.value = true

  try {
    const formData = new FormData()
    formData.append('pdf_firmado', datosSubidaPDF.value.archivoPDF)
    formData.append('nombre', datosSubidaPDF.value.nombre)
    formData.append('apellidos', datosSubidaPDF.value.apellidos)
    formData.append('dni', datosSubidaPDF.value.dni || '')
    formData.append('tipo_firmante', datosSubidaPDF.value.tipo_firmante)

    const { data } = await axios.post(`/albaran/${token.value}/subir-pdf-firmado`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    if (data.success) {
      successSubidaPDF.value = '✓ PDF firmado subido correctamente'
      setTimeout(() => {
        cerrarModalSubirPDF()
        load() // Recargar datos del albarán
      }, 2000)
    } else {
      errorSubidaPDF.value = data.message || 'Error al subir el PDF'
    }
  } catch (e) {
    errorSubidaPDF.value = e.response?.data?.message || e.message || 'Error al subir el PDF'
  } finally {
    subiendoPDF.value = false
  }
}

function cerrarModalSubirPDF() {
  mostrarModalSubirPDF.value = false
  datosSubidaPDF.value = { nombre: '', apellidos: '', dni: '', tipo_firmante: '', archivoPDF: null }
  errorSubidaPDF.value = ''
  successSubidaPDF.value = ''
}

onMounted(load)
watch(() => route.params.token, newT => { token.value = newT; load() })
</script>
