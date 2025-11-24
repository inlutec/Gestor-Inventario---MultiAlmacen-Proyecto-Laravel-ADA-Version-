<template>
  <div class="signature-pad-container">
    <div class="signature-header">
      <h3>{{ label }}</h3>
      <p v-if="required" class="text-required">* Campo obligatorio</p>
    </div>

    <div class="signature-canvas-wrapper" :class="{ 'has-signature': !isEmpty }">
      <canvas
        ref="canvas"
        @mousedown="startDrawing"
        @mousemove="draw"
        @mouseup="stopDrawing"
        @mouseleave="stopDrawing"
        @touchstart.prevent="startDrawing"
        @touchmove.prevent="draw"
        @touchend.prevent="stopDrawing"
        @touchcancel.prevent="stopDrawing"
        @pointerdown="startDrawing"
        @pointermove="draw"
        @pointerup="stopDrawing"
        @pointerleave="stopDrawing"
        class="signature-canvas"
        :style="{ touchAction: 'none' }"
      ></canvas>

      <!-- Indicador de "Firme aquí" -->
      <div v-if="isEmpty" class="signature-placeholder">
        <svg class="icon-pen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
        </svg>
        <p>{{ placeholder || 'Firme aquí con su dedo o stylus' }}</p>
      </div>
    </div>

    <div class="signature-controls">
      <button 
        @click="clear" 
        class="btn-clear"
        :disabled="isEmpty"
      >
        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
        Borrar
      </button>

      <button 
        v-if="showSaveButton"
        @click="save" 
        class="btn-save"
        :disabled="isEmpty"
      >
        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M5 13l4 4L19 7" />
        </svg>
        Guardar firma
      </button>
    </div>

    <!-- Información adicional -->
    <div v-if="showInfo" class="signature-info">
      <svg class="icon-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p>La firma será guardada de forma segura y podrá ser visualizada en el documento.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
  label: {
    type: String,
    default: 'Firma digital'
  },
  placeholder: {
    type: String,
    default: ''
  },
  width: {
    type: Number,
    default: 600
  },
  height: {
    type: Number,
    default: 300
  },
  lineWidth: {
    type: Number,
    default: 2
  },
  lineColor: {
    type: String,
    default: '#000000'
  },
  backgroundColor: {
    type: String,
    default: '#ffffff'
  },
  required: {
    type: Boolean,
    default: false
  },
  showSaveButton: {
    type: Boolean,
    default: true
  },
  showInfo: {
    type: Boolean,
    default: true
  },
  modelValue: {
    type: String,
    default: null
  }
});

const emit = defineEmits(['update:modelValue', 'save', 'clear', 'change']);

const canvas = ref(null);
const ctx = ref(null);
const isDrawing = ref(false);
const isEmpty = ref(true);
const lastX = ref(0);
const lastY = ref(0);

onMounted(() => {
  initCanvas();
  window.addEventListener('resize', handleResize);
  
  // Si hay valor inicial, cargarlo
  if (props.modelValue) {
    loadSignature(props.modelValue);
  }
});

onUnmounted(() => {
  window.addEventListener('resize', handleResize);
});

watch(() => props.modelValue, (newValue) => {
  if (newValue && isEmpty.value) {
    loadSignature(newValue);
  }
});

function initCanvas() {
  if (!canvas.value) return;

  const container = canvas.value.parentElement;
  const containerWidth = container.clientWidth;
  const dpr = window.devicePixelRatio || 1;
  
  // Configurar tamaño del canvas (responsive) con soporte para alta densidad
  const displayWidth = Math.min(props.width, containerWidth);
  const displayHeight = props.height;
  
  canvas.value.width = displayWidth * dpr;
  canvas.value.height = displayHeight * dpr;
  canvas.value.style.width = displayWidth + 'px';
  canvas.value.style.height = displayHeight + 'px';

  ctx.value = canvas.value.getContext('2d');
  ctx.value.scale(dpr, dpr);
  ctx.value.lineWidth = props.lineWidth;
  ctx.value.lineCap = 'round';
  ctx.value.lineJoin = 'round';
  ctx.value.strokeStyle = props.lineColor;

  // Fondo blanco
  ctx.value.fillStyle = props.backgroundColor;
  ctx.value.fillRect(0, 0, displayWidth, displayHeight);
}

function handleResize() {
  // Guardar firma actual si existe
  const currentSignature = isEmpty.value ? null : canvas.value.toDataURL();
  
  initCanvas();
  
  // Restaurar firma si había una
  if (currentSignature) {
    loadSignature(currentSignature);
  }
}

function getCoordinates(event) {
  const rect = canvas.value.getBoundingClientRect();
  const dpr = window.devicePixelRatio || 1;
  
  let clientX, clientY;
  
  if (event.touches && event.touches.length > 0) {
    // Touch event
    clientX = event.touches[0].clientX;
    clientY = event.touches[0].clientY;
  } else if (event.changedTouches && event.changedTouches.length > 0) {
    // Touch event (touchend)
    clientX = event.changedTouches[0].clientX;
    clientY = event.changedTouches[0].clientY;
  } else {
    // Mouse/Pointer event
    clientX = event.clientX;
    clientY = event.clientY;
  }
  
  return {
    x: (clientX - rect.left) * dpr,
    y: (clientY - rect.top) * dpr
  };
}

function startDrawing(event) {
  event.preventDefault();
  isDrawing.value = true;
  const coords = getCoordinates(event);
  lastX.value = coords.x;
  lastY.value = coords.y;

  // Iniciar trazo
  ctx.value.beginPath();
  ctx.value.moveTo(lastX.value, lastY.value);
  
  // Feedback táctil para móviles
  if ('vibrate' in navigator && window.matchMedia('(max-width: 768px)').matches) {
    navigator.vibrate(10);
  }
}

function draw(event) {
  if (!isDrawing.value) return;
  
  event.preventDefault();
  const coords = getCoordinates(event);
  
  ctx.value.lineTo(coords.x, coords.y);
  ctx.value.stroke();

  lastX.value = coords.x;
  lastY.value = coords.y;

  if (isEmpty.value) {
    isEmpty.value = false;
    emit('change', false);
  }
}

function stopDrawing(event) {
  if (!isDrawing.value) return;
  
  if (event) event.preventDefault();
  isDrawing.value = false;
  ctx.value.closePath();

  // Emitir valor actualizado
  const dataUrl = canvas.value.toDataURL('image/png');
  emit('update:modelValue', dataUrl);
}

function clear() {
  if (!ctx.value || !canvas.value) return;

  ctx.value.clearRect(0, 0, canvas.value.width, canvas.value.height);
  ctx.value.fillStyle = props.backgroundColor;
  ctx.value.fillRect(0, 0, canvas.value.width, canvas.value.height);
  
  isEmpty.value = true;
  emit('update:modelValue', null);
  emit('clear');
  emit('change', true);
}

function save() {
  if (isEmpty.value) return;

  const dataUrl = canvas.value.toDataURL('image/png');
  
  // Convertir a Blob para enviar al servidor
  fetch(dataUrl)
    .then(res => res.blob())
    .then(blob => {
      const file = new File([blob], `signature_${Date.now()}.png`, { type: 'image/png' });
      
      emit('save', {
        dataUrl: dataUrl,
        blob: blob,
        file: file
      });
      
      emit('update:modelValue', dataUrl);
    });
}

function loadSignature(dataUrl) {
  if (!dataUrl || !ctx.value || !canvas.value) return;

  const img = new Image();
  img.onload = () => {
    ctx.value.clearRect(0, 0, canvas.value.width, canvas.value.height);
    ctx.value.fillStyle = props.backgroundColor;
    ctx.value.fillRect(0, 0, canvas.value.width, canvas.value.height);
    ctx.value.drawImage(img, 0, 0);
    isEmpty.value = false;
  };
  img.src = dataUrl;
}

// Exponer métodos para uso externo
defineExpose({
  clear,
  save,
  isEmpty: () => isEmpty.value,
  getDataURL: () => canvas.value?.toDataURL('image/png'),
  getBlob: () => {
    return new Promise(resolve => {
      canvas.value.toBlob(blob => resolve(blob), 'image/png');
    });
  }
});
</script>

<style scoped>
.signature-pad-container {
  width: 100%;
  max-width: 600px;
  margin: 0 auto;
}

.signature-header {
  margin-bottom: 1rem;
}

.signature-header h3 {
  margin: 0 0 0.25rem 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
}

.text-required {
  margin: 0;
  font-size: 0.875rem;
  color: #dc2626;
}

.signature-canvas-wrapper {
  position: relative;
  border: 2px solid #d1d5db;
  border-radius: 12px;
  background: #ffffff;
  overflow: hidden;
  transition: all 0.3s ease;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.signature-canvas-wrapper.has-signature {
  border-color: #006633;
  box-shadow: 0 0 0 3px rgba(0, 102, 51, 0.1);
}

.signature-canvas {
  display: block;
  width: 100%;
  touch-action: none;
  cursor: crosshair;
}

.signature-placeholder {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  color: #9ca3af;
  pointer-events: none;
  user-select: none;
}

.icon-pen {
  width: 48px;
  height: 48px;
  margin: 0 auto 0.5rem;
  opacity: 0.5;
}

.signature-placeholder p {
  margin: 0;
  font-size: 0.875rem;
  font-weight: 500;
}

.signature-controls {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
}

.btn-clear,
.btn-save {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-clear {
  background: #f3f4f6;
  color: #374151;
}

.btn-clear:hover:not(:disabled) {
  background: #e5e7eb;
  transform: translateY(-2px);
}

.btn-save {
  background: #006633;
  color: white;
}

.btn-save:hover:not(:disabled) {
  background: #009944;
  transform: translateY(-2px);
}

.btn-clear:disabled,
.btn-save:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.signature-info {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  margin-top: 1rem;
  padding: 1rem;
  background: #f0f9ff;
  border-left: 4px solid #006633;
  border-radius: 8px;
}

.icon-info {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
  color: #006633;
}

.signature-info p {
  margin: 0;
  font-size: 0.875rem;
  color: #1e40af;
  line-height: 1.5;
}

.icon {
  width: 20px;
  height: 20px;
}

/* Responsive mobile */
@media (max-width: 768px) {
  .signature-canvas-wrapper {
    touch-action: none;
    border-width: 3px;
  }

  .signature-controls {
    flex-direction: column;
    gap: 0.75rem;
  }

  .btn-clear,
  .btn-save {
    width: 100%;
    padding: 1rem;
    font-size: 1rem;
    min-height: 48px;
  }
  
  .signature-placeholder {
    font-size: 1rem;
  }
  
  .icon-pen {
    width: 56px;
    height: 56px;
  }
}

/* Optimizaciones para tablets */
@media (min-width: 769px) and (max-width: 1024px) {
  .signature-canvas-wrapper {
    border-width: 2px;
  }
  
  .btn-clear,
  .btn-save {
    padding: 0.875rem;
  }
}

/* Soporte para modo oscuro en PWA */
@media (prefers-color-scheme: dark) and (display-mode: standalone) {
  .signature-canvas-wrapper {
    border-color: #4b5563;
    background: #1f2937;
  }
  
  .signature-canvas-wrapper.has-signature {
    border-color: #009944;
    box-shadow: 0 0 0 3px rgba(0, 153, 68, 0.2);
  }
}

/* Mejoras para pantallas táctiles de alta densidad */
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
  .signature-canvas {
    image-rendering: -webkit-optimize-contrast;
    image-rendering: crisp-edges;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .signature-header h3 {
    color: #f9fafb;
  }

  .signature-canvas-wrapper {
    border-color: #374151;
    background: #1f2937;
  }

  .signature-canvas-wrapper.has-signature {
    border-color: #009944;
  }

  .btn-clear {
    background: #374151;
    color: #f9fafb;
  }

  .btn-clear:hover:not(:disabled) {
    background: #4b5563;
  }

  .signature-info {
    background: #1f2937;
    border-left-color: #009944;
  }

  .signature-info p {
    color: #93c5fd;
  }
}
</style>
