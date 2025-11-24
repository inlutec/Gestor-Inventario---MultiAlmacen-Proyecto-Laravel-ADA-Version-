<template>
  <div class="min-h-screen bg-gradient-to-br from-junta-green-700 to-junta-green-900 flex items-center justify-center p-4">
    <div class="max-w-md w-full">
      <!-- Estado: Esperando -->
      <div v-if="estado === 'esperando'" class="bg-white rounded-2xl shadow-2xl p-8 text-center">
        <div class="mb-6">
          <div class="inline-block p-4 bg-junta-green-100 rounded-full mb-4">
            <svg class="h-16 w-16 text-junta-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
          </div>
          <h1 class="text-2xl font-bold text-gray-900 mb-2">Firma Móvil</h1>
          <p class="text-gray-600">Esperando solicitud de firma...</p>
        </div>

        <div class="flex items-center justify-center space-x-2">
          <div class="w-3 h-3 bg-junta-green-600 rounded-full animate-bounce" style="animation-delay: 0s"></div>
          <div class="w-3 h-3 bg-junta-green-600 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
          <div class="w-3 h-3 bg-junta-green-600 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
        </div>

        <div class="mt-6 text-sm text-gray-500">
          <p>Esta página está lista para recibir solicitudes de firma</p>
          <p class="mt-1">ID de sesión: <span class="font-mono font-bold">{{ sessionId }}</span></p>
        </div>
      </div>

      <!-- Estado: Firmando -->
      <div v-else-if="estado === 'firmando'" class="bg-white rounded-2xl shadow-2xl overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-junta-green-600 to-junta-green-700 px-6 py-4">
          <h2 class="text-xl font-bold text-white">{{ tipoFirma === 'receptor' ? 'Firma de Receptor' : 'Firma de Emisor' }}</h2>
          <p class="text-junta-green-100 text-sm">Movimiento #{{ movimientoData?.numero_albaran }}</p>
        </div>

        <!-- Datos del movimiento -->
        <div class="p-6 space-y-4">
          <div class="bg-gray-50 rounded-lg p-4">
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div>
                <span class="font-medium text-gray-600">Tipo:</span>
                <span class="ml-2 font-semibold">{{ movimientoData?.tipo }}</span>
              </div>
              <div>
                <span class="font-medium text-gray-600">Fecha:</span>
                <span class="ml-2">{{ formatFecha(movimientoData?.fecha) }}</span>
              </div>
              <div class="col-span-2">
                <span class="font-medium text-gray-600">Origen:</span>
                <span class="ml-2">{{ movimientoData?.origen }}</span>
              </div>
              <div class="col-span-2">
                <span class="font-medium text-gray-600">Destino:</span>
                <span class="ml-2">{{ movimientoData?.destino }}</span>
              </div>
            </div>
          </div>

          <!-- Canvas de firma -->
          <div class="space-y-2">
            <label class="block text-sm font-semibold text-gray-700">
              Firma aquí con tu dedo o stylus:
            </label>
            <div class="border-4 border-gray-300 rounded-lg overflow-hidden bg-white relative">
              <canvas 
                ref="canvasRef"
                @touchstart="startDrawing"
                @touchmove="draw"
                @touchend="stopDrawing"
                @mousedown="startDrawing"
                @mousemove="draw"
                @mouseup="stopDrawing"
                @mouseleave="stopDrawing"
                class="w-full touch-none"
                :width="canvasWidth"
                :height="canvasHeight"
              ></canvas>
              <div v-if="!hasSignature" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <p class="text-gray-400 text-center">Toque aquí para firmar</p>
              </div>
            </div>
          </div>

          <!-- Botones -->
          <div class="flex gap-3">
            <button
              @click="limpiarFirma"
              type="button"
              class="flex-1 px-4 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium"
            >
              🗑️ Limpiar
            </button>
            <button
              @click="enviarFirma"
              :disabled="!hasSignature || enviando"
              class="flex-1 px-4 py-3 bg-gradient-to-r from-junta-green-600 to-junta-green-700 text-white rounded-lg hover:from-junta-green-700 hover:to-junta-green-800 disabled:from-gray-400 disabled:to-gray-400 font-bold shadow-lg"
            >
              {{ enviando ? '⏳ Enviando...' : '✓ Confirmar Firma' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Estado: Éxito -->
      <div v-else-if="estado === 'exito'" class="bg-white rounded-2xl shadow-2xl p-8 text-center">
        <div class="mb-6">
          <div class="inline-block p-4 bg-green-100 rounded-full mb-4">
            <svg class="h-16 w-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <h2 class="text-2xl font-bold text-gray-900 mb-2">¡Firma enviada!</h2>
          <p class="text-gray-600">La firma se ha registrado correctamente</p>
        </div>
        <button
          @click="volverAEspera"
          class="px-6 py-3 bg-junta-green-600 text-white rounded-lg hover:bg-junta-green-700 font-medium"
        >
          Volver a espera
        </button>
      </div>

      <!-- Estado: Error -->
      <div v-else-if="estado === 'error'" class="bg-white rounded-2xl shadow-2xl p-8 text-center">
        <div class="mb-6">
          <div class="inline-block p-4 bg-red-100 rounded-full mb-4">
            <svg class="h-16 w-16 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <h2 class="text-2xl font-bold text-gray-900 mb-2">Error</h2>
          <p class="text-gray-600 whitespace-pre-wrap break-words">{{ mensajeError }}</p>
        </div>
        <button
          @click="volverAEspera"
          class="px-6 py-3 bg-junta-green-600 text-white rounded-lg hover:bg-junta-green-700 font-medium"
        >
          Reintentar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';

const estado = ref('esperando'); // esperando, firmando, exito, error
const sessionId = ref('');
const movimientoData = ref(null);
const tipoFirma = ref(''); // receptor, emisor
const canvasRef = ref(null);
const isDrawing = ref(false);
const hasSignature = ref(false);
const enviando = ref(false);
const mensajeError = ref('');
const eventSource = ref(null);

const canvasWidth = 400;
const canvasHeight = 300;

let ctx = null;

// Inicializar canvas cuando se muestra
watch(estado, async (newEstado) => {
  if (newEstado === 'firmando') {
    await nextTick();
    if (canvasRef.value) {
      ctx = canvasRef.value.getContext('2d');
      ctx.strokeStyle = '#000';
      ctx.lineWidth = 3;
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      limpiarFirma();
    }
  }
});

onMounted(() => {
  // Generar ID de sesión único (4 dígitos)
  sessionId.value = String(Math.floor(1000 + Math.random() * 9000));
  
  // Conectar a SSE para recibir solicitudes de firma
  conectarSSE();
});

onUnmounted(() => {
  if (eventSource.value) {
    eventSource.value.close();
  }
});

function conectarSSE() {
  const url = `/gestionmaterial/api/firma-movil/stream?session=${sessionId.value}`;
  eventSource.value = new EventSource(url);

  eventSource.value.onmessage = (event) => {
    const data = JSON.parse(event.data);
    console.log('Datos recibidos:', data);

    if (data.tipo === 'solicitud_firma') {
      movimientoData.value = data.movimiento;
      tipoFirma.value = data.tipo_firma;
      estado.value = 'firmando';
      limpiarFirma();
    }
  };

  eventSource.value.onerror = (error) => {
    console.error('Error en SSE:', error);
    // Reconectar después de 3 segundos
    setTimeout(() => {
      if (estado.value === 'esperando') {
        conectarSSE();
      }
    }, 3000);
  };
}

function startDrawing(e) {
  isDrawing.value = true;
  hasSignature.value = true;
  const { x, y } = getCoordinates(e);
  ctx.beginPath();
  ctx.moveTo(x, y);
}

function draw(e) {
  if (!isDrawing.value) return;
  e.preventDefault();
  
  const { x, y } = getCoordinates(e);
  ctx.lineTo(x, y);
  ctx.stroke();
}

function stopDrawing() {
  isDrawing.value = false;
}

function getCoordinates(e) {
  const canvas = canvasRef.value;
  const rect = canvas.getBoundingClientRect();
  
  let clientX, clientY;
  if (e.touches && e.touches.length > 0) {
    clientX = e.touches[0].clientX;
    clientY = e.touches[0].clientY;
  } else {
    clientX = e.clientX;
    clientY = e.clientY;
  }
  
  const scaleX = canvas.width / rect.width;
  const scaleY = canvas.height / rect.height;
  
  return {
    x: (clientX - rect.left) * scaleX,
    y: (clientY - rect.top) * scaleY
  };
}

function limpiarFirma() {
  if (ctx && canvasRef.value) {
    ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);
    hasSignature.value = false;
  }
}

async function enviarFirma() {
  if (!hasSignature.value) return;

  enviando.value = true;

  try {
    const firmaBase64 = canvasRef.value.toDataURL('image/png');
    
    console.log('Enviando firma para movimiento:', movimientoData.value.id);
    
    const response = await window.axios.post(`/material-movimientos/${movimientoData.value.id}/firmar-remoto`, {
      tipo_firma: tipoFirma.value,
      firma: firmaBase64,
      session_id: sessionId.value
    });

    console.log('Respuesta del servidor:', response.data);

    if (response.data.success) {
      estado.value = 'exito';
      setTimeout(() => {
        volverAEspera();
      }, 3000);
    } else {
      throw new Error(response.data.message || 'Error al enviar firma');
    }
  } catch (error) {
    console.error('Error completo al enviar firma:', error);
    console.error('Error response:', error.response);
    
    let errorMsg = 'Error desconocido al enviar la firma';
    
    if (error.response) {
      // Error de respuesta del servidor
      errorMsg = error.response.data?.message || error.response.statusText || `Error ${error.response.status}`;
      
      if (error.response.data?.errors) {
        errorMsg += '\n\nDetalles:\n' + Object.values(error.response.data.errors).flat().join('\n');
      }
    } else if (error.request) {
      // Error de red
      errorMsg = 'Error de conexión. Verifica tu conexión a internet.';
    } else {
      // Otro tipo de error
      errorMsg = error.message;
    }
    
    mensajeError.value = errorMsg;
    estado.value = 'error';
  } finally {
    enviando.value = false;
  }
}

function volverAEspera() {
  estado.value = 'esperando';
  movimientoData.value = null;
  tipoFirma.value = '';
  limpiarFirma();
}

function formatFecha(fecha) {
  if (!fecha) return '';
  return new Date(fecha).toLocaleString('es-ES');
}
</script>

<style scoped>
canvas {
  cursor: crosshair;
  display: block;
  background: white;
}

@keyframes bounce {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

.animate-bounce {
  animation: bounce 1s infinite;
}
</style>
