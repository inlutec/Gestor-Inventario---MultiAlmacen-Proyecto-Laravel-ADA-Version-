<template>
  <div class="signature-canvas-container">
    <div class="canvas-wrapper">
      <canvas
        ref="canvas"
        @mousedown="startDrawing"
        @mousemove="draw"
        @mouseup="stopDrawing"
        @mouseleave="stopDrawing"
        @touchstart="handleTouchStart"
        @touchmove="handleTouchMove"
        @touchend="stopDrawing"
        :width="width"
        :height="height"
        class="signature-canvas"
      ></canvas>
    </div>
    <div class="canvas-actions">
      <button @click="clear" type="button" class="btn-clear">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
        Limpiar
      </button>
      <button @click="undo" type="button" class="btn-undo" :disabled="!canUndo">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
        </svg>
        Deshacer
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';

const props = defineProps({
  width: {
    type: Number,
    default: 500
  },
  height: {
    type: Number,
    default: 200
  },
  strokeColor: {
    type: String,
    default: '#000000'
  },
  strokeWidth: {
    type: Number,
    default: 2
  },
  modelValue: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['update:modelValue', 'change']);

const canvas = ref(null);
const ctx = ref(null);
const isDrawing = ref(false);
const strokes = ref([]);
const currentStroke = ref([]);

const canUndo = computed(() => strokes.value.length > 0);

onMounted(() => {
  const canvasEl = canvas.value;
  ctx.value = canvasEl.getContext('2d');
  ctx.value.lineCap = 'round';
  ctx.value.lineJoin = 'round';
  ctx.value.strokeStyle = props.strokeColor;
  ctx.value.lineWidth = props.strokeWidth;
  
  // Fondo blanco
  ctx.value.fillStyle = '#FFFFFF';
  ctx.value.fillRect(0, 0, props.width, props.height);
  
  // Si hay un valor inicial, cargarlo
  if (props.modelValue) {
    loadSignature(props.modelValue);
  }
});

const getCoordinates = (e) => {
  const rect = canvas.value.getBoundingClientRect();
  const scaleX = canvas.value.width / rect.width;
  const scaleY = canvas.value.height / rect.height;
  
  let clientX, clientY;
  
  if (e.touches && e.touches.length > 0) {
    clientX = e.touches[0].clientX;
    clientY = e.touches[0].clientY;
  } else {
    clientX = e.clientX;
    clientY = e.clientY;
  }
  
  return {
    x: (clientX - rect.left) * scaleX,
    y: (clientY - rect.top) * scaleY
  };
};

const startDrawing = (e) => {
  e.preventDefault();
  isDrawing.value = true;
  const coords = getCoordinates(e);
  currentStroke.value = [coords];
  
  ctx.value.beginPath();
  ctx.value.moveTo(coords.x, coords.y);
};

const draw = (e) => {
  if (!isDrawing.value) return;
  e.preventDefault();
  
  const coords = getCoordinates(e);
  currentStroke.value.push(coords);
  
  ctx.value.lineTo(coords.x, coords.y);
  ctx.value.stroke();
};

const stopDrawing = (e) => {
  if (!isDrawing.value) return;
  e.preventDefault();
  
  isDrawing.value = false;
  
  if (currentStroke.value.length > 0) {
    strokes.value.push([...currentStroke.value]);
    currentStroke.value = [];
    saveSignature();
  }
};

const handleTouchStart = (e) => {
  startDrawing(e);
};

const handleTouchMove = (e) => {
  draw(e);
};

const clear = () => {
  ctx.value.fillStyle = '#FFFFFF';
  ctx.value.fillRect(0, 0, props.width, props.height);
  strokes.value = [];
  currentStroke.value = [];
  emit('update:modelValue', '');
  emit('change', '');
};

const undo = () => {
  if (strokes.value.length === 0) return;
  
  strokes.value.pop();
  redrawCanvas();
  saveSignature();
};

const redrawCanvas = () => {
  ctx.value.fillStyle = '#FFFFFF';
  ctx.value.fillRect(0, 0, props.width, props.height);
  
  ctx.value.strokeStyle = props.strokeColor;
  ctx.value.lineWidth = props.strokeWidth;
  
  strokes.value.forEach(stroke => {
    if (stroke.length === 0) return;
    
    ctx.value.beginPath();
    ctx.value.moveTo(stroke[0].x, stroke[0].y);
    
    for (let i = 1; i < stroke.length; i++) {
      ctx.value.lineTo(stroke[i].x, stroke[i].y);
    }
    
    ctx.value.stroke();
  });
};

const saveSignature = () => {
  if (strokes.value.length === 0) {
    emit('update:modelValue', '');
    emit('change', '');
    return;
  }
  
  const dataUrl = canvas.value.toDataURL('image/png');
  emit('update:modelValue', dataUrl);
  emit('change', dataUrl);
};

const loadSignature = (dataUrl) => {
  const img = new Image();
  img.onload = () => {
    ctx.value.drawImage(img, 0, 0);
  };
  img.src = dataUrl;
};

watch(() => props.strokeColor, (newColor) => {
  ctx.value.strokeStyle = newColor;
});

watch(() => props.strokeWidth, (newWidth) => {
  ctx.value.lineWidth = newWidth;
});

defineExpose({
  clear,
  undo,
  getSignature: () => canvas.value.toDataURL('image/png')
});
</script>

<style scoped>
.signature-canvas-container {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.canvas-wrapper {
  border: 2px solid #e2e8f0;
  border-radius: 0.5rem;
  background: white;
  overflow: hidden;
}

.signature-canvas {
  display: block;
  cursor: crosshair;
  touch-action: none;
}

.canvas-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

.btn-clear,
.btn-undo {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.btn-clear {
  background: #ef4444;
  color: white;
}

.btn-clear:hover {
  background: #dc2626;
}

.btn-undo {
  background: #6b7280;
  color: white;
}

.btn-undo:hover:not(:disabled) {
  background: #4b5563;
}

.btn-undo:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.w-4 {
  width: 1rem;
}

.h-4 {
  height: 1rem;
}
</style>
