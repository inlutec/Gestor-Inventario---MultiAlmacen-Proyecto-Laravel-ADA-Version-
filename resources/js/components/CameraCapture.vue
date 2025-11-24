<template>
  <div class="camera-capture">
    <!-- Botón para activar cámara -->
    <button 
      v-if="!cameraActive && !capturedImage"
      @click="activateCamera"
      class="btn-camera-activate"
      :disabled="loading"
    >
      <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
      {{ loading ? 'Cargando cámara...' : label || 'Hacer foto' }}
    </button>

    <!-- Vista de cámara activa -->
    <div v-if="cameraActive" class="camera-viewport">
      <video ref="videoElement" autoplay playsinline class="camera-video"></video>
      
      <!-- Controles de cámara -->
      <div class="camera-controls">
        <button @click="switchCamera" class="btn-camera-control" title="Cambiar cámara">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </button>
        
        <button @click="capturePhoto" class="btn-capture">
          <svg class="icon-large" fill="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" />
          </svg>
        </button>
        
        <button @click="closeCamera" class="btn-camera-control" title="Cerrar">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Zoom (solo si es compatible) -->
      <div v-if="supportsZoom" class="zoom-control">
        <input 
          type="range" 
          v-model.number="zoomLevel" 
          :min="zoomRange.min" 
          :max="zoomRange.max" 
          :step="zoomRange.step"
          @input="applyZoom"
        />
        <span class="zoom-label">{{ Math.round(zoomLevel * 10) / 10 }}x</span>
      </div>
    </div>

    <!-- Canvas oculto para captura -->
    <canvas ref="canvasElement" style="display: none;"></canvas>

    <!-- Imagen capturada -->
    <div v-if="capturedImage" class="captured-preview">
      <img :src="capturedImage" alt="Foto capturada" class="preview-image" />
      
      <div class="preview-controls">
        <button @click="retakePhoto" class="btn-secondary">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Repetir
        </button>
        
        <button @click="confirmPhoto" class="btn-primary">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M5 13l4 4L19 7" />
          </svg>
          Confirmar
        </button>
      </div>
    </div>

    <!-- Error -->
    <div v-if="error" class="camera-error">
      <svg class="icon-error" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
      <p>{{ error }}</p>
      <button @click="error = null" class="btn-secondary-small">Cerrar</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onUnmounted } from 'vue';

const props = defineProps({
  label: {
    type: String,
    default: 'Hacer foto'
  },
  maxWidth: {
    type: Number,
    default: 1920
  },
  maxHeight: {
    type: Number,
    default: 1080
  },
  quality: {
    type: Number,
    default: 0.9
  }
});

const emit = defineEmits(['captured', 'error']);

const videoElement = ref(null);
const canvasElement = ref(null);
const cameraActive = ref(false);
const capturedImage = ref(null);
const loading = ref(false);
const error = ref(null);
const stream = ref(null);
const currentFacingMode = ref('environment'); // 'user' (frontal) o 'environment' (trasera)
const supportsZoom = ref(false);
const zoomLevel = ref(1);
const zoomRange = ref({ min: 1, max: 3, step: 0.1 });

async function activateCamera() {
  loading.value = true;
  error.value = null;

  try {
    // Verificar compatibilidad
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      throw new Error('Tu navegador no soporta acceso a la cámara');
    }

    // Solicitar permisos y activar cámara con mejor configuración móvil
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    const constraints = {
      video: {
        facingMode: currentFacingMode.value,
        width: isMobile ? { ideal: 1280, max: props.maxWidth } : { ideal: props.maxWidth },
        height: isMobile ? { ideal: 720, max: props.maxHeight } : { ideal: props.maxHeight },
        // Mejoras para móviles
        facingMode: { ideal: currentFacingMode.value },
        // Reducir consumo de batería en móviles
        frameRate: isMobile ? { ideal: 24, max: 30 } : { ideal: 30 },
        // Mejor calidad en buenas condiciones de luz
        advanced: [
          { torch: false },
          { whiteBalanceMode: 'continuous' },
          { exposureMode: 'continuous' }
        ]
      },
      audio: false
    };

    stream.value = await navigator.mediaDevices.getUserMedia(constraints);
    
    // Esperar a que el video esté listo
    await new Promise(resolve => setTimeout(resolve, 100));
    
    if (videoElement.value) {
      videoElement.value.srcObject = stream.value;
      cameraActive.value = true;

      // Verificar soporte de zoom
      const track = stream.value.getVideoTracks()[0];
      const capabilities = track.getCapabilities();
      
      if (capabilities.zoom) {
        supportsZoom.value = true;
        zoomRange.value = {
          min: capabilities.zoom.min || 1,
          max: capabilities.zoom.max || 3,
          step: capabilities.zoom.step || 0.1
        };
      }
    }
  } catch (err) {
    console.error('Error al acceder a la cámara:', err);
    
    if (err.name === 'NotAllowedError') {
      error.value = 'Permiso de cámara denegado. Por favor, habilita el acceso en la configuración.';
    } else if (err.name === 'NotFoundError') {
      error.value = 'No se encontró ninguna cámara en este dispositivo.';
    } else {
      error.value = err.message || 'Error al acceder a la cámara';
    }
    
    emit('error', err);
  } finally {
    loading.value = false;
  }
}

function capturePhoto() {
  if (!videoElement.value || !canvasElement.value) return;

  const video = videoElement.value;
  const canvas = canvasElement.value;

  // Configurar canvas con las dimensiones del video
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;

  // Dibujar frame actual del video
  const ctx = canvas.getContext('2d');
  
  // Mejoras de calidad para móviles
  ctx.imageSmoothingEnabled = true;
  ctx.imageSmoothingQuality = 'high';
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

  // Feedback táctil
  if ('vibrate' in navigator) {
    navigator.vibrate([50, 30, 50]);
  }

  // Convertir a imagen (JPEG con calidad configurable)
  capturedImage.value = canvas.toDataURL('image/jpeg', props.quality);

  // Cerrar cámara
  closeCamera();
}

function closeCamera() {
  if (stream.value) {
    stream.value.getTracks().forEach(track => track.stop());
    stream.value = null;
  }
  
  if (videoElement.value) {
    videoElement.value.srcObject = null;
  }
  
  cameraActive.value = false;
  supportsZoom.value = false;
  zoomLevel.value = 1;
}

async function switchCamera() {
  // Cambiar entre cámara frontal y trasera
  currentFacingMode.value = currentFacingMode.value === 'user' ? 'environment' : 'user';
  closeCamera();
  await activateCamera();
}

function applyZoom() {
  if (!stream.value || !supportsZoom.value) return;

  const track = stream.value.getVideoTracks()[0];
  track.applyConstraints({
    advanced: [{ zoom: zoomLevel.value }]
  }).catch(err => {
    console.warn('Error al aplicar zoom:', err);
  });
}

function retakePhoto() {
  capturedImage.value = null;
  activateCamera();
}

function confirmPhoto() {
  if (!capturedImage.value) return;

  // Convertir base64 a Blob para enviar al servidor
  fetch(capturedImage.value)
    .then(res => res.blob())
    .then(blob => {
      const file = new File([blob], `photo_${Date.now()}.jpg`, { type: 'image/jpeg' });
      emit('captured', {
        dataUrl: capturedImage.value,
        blob: blob,
        file: file
      });
      
      // Resetear componente
      capturedImage.value = null;
    });
}

// Limpiar al desmontar componente
onUnmounted(() => {
  closeCamera();
});
</script>

<style scoped>
.camera-capture {
  width: 100%;
  max-width: 600px;
  margin: 0 auto;
}

.btn-camera-activate {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, #006633, #009944);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(0, 102, 51, 0.3);
}

.btn-camera-activate:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 102, 51, 0.4);
}

.btn-camera-activate:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.camera-viewport {
  position: relative;
  width: 100%;
  background: #000;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.camera-video {
  width: 100%;
  display: block;
  aspect-ratio: 4/3;
  object-fit: cover;
}

.camera-controls {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  justify-content: space-around;
  align-items: center;
  padding: 1.5rem;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
}

.btn-camera-control {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  color: white;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-camera-control:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: scale(1.1);
}

.btn-capture {
  width: 72px;
  height: 72px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  border: 4px solid rgba(255, 255, 255, 0.5);
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.btn-capture:hover {
  transform: scale(1.1);
}

.btn-capture:active {
  transform: scale(0.95);
}

.zoom-control {
  position: absolute;
  top: 1rem;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(10px);
  border-radius: 24px;
  color: white;
}

.zoom-control input[type="range"] {
  width: 120px;
}

.zoom-label {
  min-width: 40px;
  font-size: 0.875rem;
  font-weight: 600;
}

.captured-preview {
  width: 100%;
}

.preview-image {
  width: 100%;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.preview-controls {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
}

.btn-primary,
.btn-secondary {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.875rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-primary {
  background: #006633;
  color: white;
}

.btn-primary:hover {
  background: #009944;
  transform: translateY(-2px);
}

.btn-secondary {
  background: #6b7280;
  color: white;
}

.btn-secondary:hover {
  background: #4b5563;
  transform: translateY(-2px);
}

.camera-error {
  padding: 1.5rem;
  background: #fee;
  border: 2px solid #fcc;
  border-radius: 12px;
  text-align: center;
}

.icon-error {
  width: 48px;
  height: 48px;
  margin: 0 auto 1rem;
  color: #dc2626;
}

.camera-error p {
  margin: 0 0 1rem 0;
  color: #991b1b;
  font-weight: 500;
}

.btn-secondary-small {
  padding: 0.5rem 1rem;
  background: #6b7280;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.icon {
  width: 20px;
  height: 20px;
}

.icon-large {
  width: 32px;
  height: 32px;
}

/* Responsive mobile */
@media (max-width: 768px) {
  .camera-viewport {
    border-radius: 0;
    margin: 0 -1rem;
    width: calc(100% + 2rem);
  }
  
  .camera-video {
    aspect-ratio: 4/3;
    object-fit: cover;
  }

  .camera-controls {
    padding: 1.5rem 1rem;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.9), transparent);
  }

  .btn-capture {
    width: 72px;
    height: 72px;
    border-width: 5px;
  }

  .btn-camera-control {
    width: 48px;
    height: 48px;
  }
  
  .zoom-control {
    top: 0.75rem;
    padding: 0.375rem 0.75rem;
  }
  
  .zoom-control input[type="range"] {
    width: 100px;
  }
}

/* Optimizaciones para iPhone notch */
@supports (padding: env(safe-area-inset-top)) {
  .camera-viewport {
    margin-top: env(safe-area-inset-top);
  }
  
  .camera-controls {
    padding-bottom: calc(1.5rem + env(safe-area-inset-bottom));
  }
}

/* Mejoras para landscape en móviles */
@media (max-width: 768px) and (orientation: landscape) {
  .camera-video {
    aspect-ratio: 16/9;
  }
  
  .camera-controls {
    padding: 0.75rem 1rem;
  }
  
  .btn-capture {
    width: 56px;
    height: 56px;
  }
}

/* Optimizaciones para tablets */
@media (min-width: 769px) and (max-width: 1024px) {
  .camera-viewport {
    border-radius: 16px;
  }
  
  .btn-capture {
    width: 68px;
    height: 68px;
  }
  
  .btn-camera-control {
    width: 44px;
    height: 44px;
  }
}

/* Soporte para modo oscuro en PWA */
@media (prefers-color-scheme: dark) and (display-mode: standalone) {
  .camera-viewport {
    background: #000;
  }
  
  .btn-camera-activate {
    background: linear-gradient(135deg, #009944, #006633);
  }
}

/* Mejoras para pantallas táctiles de alta densidad */
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
  .camera-video {
    image-rendering: -webkit-optimize-contrast;
  }
}
</style>
