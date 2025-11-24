<template>
  <div class="max-w-4xl mx-auto p-6">
    <div class="bg-white rounded-lg shadow-md">
      <!-- Header -->
      <div class="border-b border-gray-200 p-6">
        <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
          <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
          </svg>
          Importar Impresoras desde CSV
        </h2>
        <p class="text-gray-600 mt-2">
          Sube un archivo CSV con los datos de las impresoras para importarlas al sistema.
        </p>
      </div>

      <!-- Upload Section -->
      <div class="p-6">
        <div v-if="!archivoSeleccionado" 
             class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center hover:border-green-500 transition-colors"
             @dragover.prevent="dragging = true"
             @dragleave.prevent="dragging = false"
             @drop.prevent="handleDrop"
             :class="{ 'border-green-500 bg-green-50': dragging }">
          
          <svg class="mx-auto h-16 w-16 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          
          <div class="mt-4">
            <label class="cursor-pointer">
              <span class="text-green-600 font-semibold hover:text-green-700">Selecciona un archivo CSV</span>
              <span class="text-gray-600"> o arrástralo aquí</span>
              <input type="file" 
                     accept=".csv,.txt" 
                     class="hidden" 
                     @change="handleFileSelect" 
                     ref="fileInput">
            </label>
          </div>
          
          <p class="text-sm text-gray-500 mt-2">
            Formato: CSV con columnas D=Sede, E=Departamento, F=Referencia, G=N° Serie, I=IP, L=Marca, M=Modelo, etc.
          </p>
        </div>

        <!-- File Selected -->
        <div v-else class="bg-gray-50 rounded-lg p-6 border border-gray-200">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
              <div class="bg-green-100 p-3 rounded-lg">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <div>
                <p class="font-semibold text-gray-800">{{ archivoSeleccionado.name }}</p>
                <p class="text-sm text-gray-500">{{ formatFileSize(archivoSeleccionado.size) }}</p>
              </div>
            </div>
            <button @click="removerArchivo" 
                    class="text-red-600 hover:text-red-700 p-2 rounded-lg hover:bg-red-50 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Options -->
          <div class="mt-6 space-y-4">
            <label class="flex items-center gap-3 cursor-pointer">
              <input type="checkbox" 
                     v-model="opciones.sincronizarCheckmk" 
                     class="w-5 h-5 text-green-600 rounded focus:ring-green-500">
              <div>
                <span class="font-medium text-gray-800">Sincronizar con CheckMK</span>
                <p class="text-sm text-gray-500">Crear hosts en CheckMK con autodescubrimiento de servicios</p>
              </div>
            </label>

            <label class="flex items-center gap-3 cursor-pointer">
              <input type="checkbox" 
                     v-model="opciones.actualizarExistentes" 
                     class="w-5 h-5 text-green-600 rounded focus:ring-green-500">
              <div>
                <span class="font-medium text-gray-800">Actualizar registros existentes</span>
                <p class="text-sm text-gray-500">Si una impresora ya existe, actualizar sus datos</p>
              </div>
            </label>
          </div>

          <!-- Action Buttons -->
          <div class="mt-6 flex gap-3">
            <button @click="previsualizarCSV" 
                    :disabled="cargando"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              Previsualizar
            </button>

            <button @click="importarCSV" 
                    :disabled="cargando"
                    class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
              <svg v-if="!cargando" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
              </svg>
              <svg v-else class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ cargando ? 'Importando...' : 'Importar' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Preview Section -->
      <div v-if="previsualizacion" class="border-t border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
          Previsualización - {{ previsualizacion.total_filas }} filas en total
        </h3>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th v-for="(header, index) in previsualizacion.headers" 
                    :key="index"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ header }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(fila, rowIndex) in previsualizacion.filas_muestra" 
                  :key="rowIndex"
                  class="hover:bg-gray-50">
                <td v-for="(celda, colIndex) in fila" 
                    :key="colIndex"
                    class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                  {{ celda }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <p class="text-sm text-gray-500 mt-4">
          Mostrando las primeras 10 filas de {{ previsualizacion.total_filas }}
        </p>
      </div>

      <!-- Results Section -->
      <div v-if="resultado" class="border-t border-gray-200 p-6 bg-gray-50">
        <div class="flex items-start gap-4">
          <div class="flex-shrink-0">
            <div v-if="resultado.errores === 0" class="bg-green-100 p-3 rounded-full">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <div v-else class="bg-yellow-100 p-3 rounded-full">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
          </div>

          <div class="flex-1">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">
              Importación {{ resultado.errores === 0 ? 'Completada' : 'Completada con errores' }}
            </h3>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
              <div class="bg-white p-4 rounded-lg shadow-sm">
                <p class="text-sm text-gray-600">Total procesados</p>
                <p class="text-2xl font-bold text-gray-800">{{ resultado.total }}</p>
              </div>
              <div class="bg-white p-4 rounded-lg shadow-sm">
                <p class="text-sm text-gray-600">Importados</p>
                <p class="text-2xl font-bold text-green-600">{{ resultado.importados }}</p>
              </div>
              <div class="bg-white p-4 rounded-lg shadow-sm">
                <p class="text-sm text-gray-600">Actualizados</p>
                <p class="text-2xl font-bold text-blue-600">{{ resultado.actualizados }}</p>
              </div>
              <div class="bg-white p-4 rounded-lg shadow-sm">
                <p class="text-sm text-gray-600">Errores</p>
                <p class="text-2xl font-bold text-red-600">{{ resultado.errores }}</p>
              </div>
            </div>

            <div v-if="resultado.sincronizados_checkmk > 0" class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
              <p class="text-sm text-green-800">
                <strong>{{ resultado.sincronizados_checkmk }}</strong> impresoras sincronizadas con CheckMK
              </p>
            </div>

            <!-- Detalles con errores -->
            <div v-if="resultado.detalles.some(d => d.status === 'error')" class="mt-4">
              <details class="bg-white rounded-lg border border-gray-200">
                <summary class="cursor-pointer p-4 font-semibold text-gray-700 hover:bg-gray-50">
                  Ver errores ({{ resultado.detalles.filter(d => d.status === 'error').length }})
                </summary>
                <div class="p-4 border-t border-gray-200 max-h-96 overflow-y-auto">
                  <div v-for="detalle in resultado.detalles.filter(d => d.status === 'error')" 
                       :key="detalle.fila"
                       class="py-2 border-b border-gray-100 last:border-0">
                    <p class="text-sm">
                      <span class="font-semibold text-red-600">Fila {{ detalle.fila }}:</span>
                      <span class="text-gray-700">{{ detalle.mensaje }}</span>
                    </p>
                  </div>
                </div>
              </details>
            </div>

            <button @click="resetearFormulario" 
                    class="mt-4 bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg transition-colors">
              Importar otro archivo
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const archivoSeleccionado = ref(null);
const dragging = ref(false);
const cargando = ref(false);
const previsualizacion = ref(null);
const resultado = ref(null);
const fileInput = ref(null);

const opciones = ref({
  sincronizarCheckmk: true,
  actualizarExistentes: true,
});

const handleFileSelect = (event) => {
  const file = event.target.files[0];
  if (file) {
    archivoSeleccionado.value = file;
    previsualizacion.value = null;
    resultado.value = null;
  }
};

const handleDrop = (event) => {
  dragging.value = false;
  const file = event.dataTransfer.files[0];
  if (file && (file.type === 'text/csv' || file.name.endsWith('.csv') || file.name.endsWith('.txt'))) {
    archivoSeleccionado.value = file;
    previsualizacion.value = null;
    resultado.value = null;
  } else {
    alert('Por favor, selecciona un archivo CSV válido');
  }
};

const removerArchivo = () => {
  archivoSeleccionado.value = null;
  previsualizacion.value = null;
  resultado.value = null;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const formatFileSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
};

const previsualizarCSV = async () => {
  if (!archivoSeleccionado.value) return;

  cargando.value = true;
  resultado.value = null;

  try {
    const formData = new FormData();
    formData.append('archivo', archivoSeleccionado.value);

    const response = await axios.post('/importador/previsualizar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    if (response.data.success) {
      previsualizacion.value = response.data.data;
    } else {
      alert('Error al previsualizar: ' + response.data.message);
    }
  } catch (error) {
    console.error('Error al previsualizar CSV:', error);
    alert('Error al previsualizar el archivo: ' + (error.response?.data?.message || error.message));
  } finally {
    cargando.value = false;
  }
};

const importarCSV = async () => {
  if (!archivoSeleccionado.value) return;

  if (!confirm('¿Deseas importar este archivo? Esta acción puede tardar varios minutos dependiendo del tamaño del archivo.')) {
    return;
  }

  cargando.value = true;
  previsualizacion.value = null;
  resultado.value = null;

  try {
    const formData = new FormData();
    formData.append('archivo', archivoSeleccionado.value);
    formData.append('sincronizar_checkmk', opciones.value.sincronizarCheckmk ? '1' : '0');
    formData.append('actualizar_existentes', opciones.value.actualizarExistentes ? '1' : '0');

    const response = await axios.post('/importador/importar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      timeout: 600000, // 10 minutos de timeout para archivos grandes
    });

    if (response.data.success) {
      resultado.value = response.data.data;
    } else {
      alert('Error al importar: ' + response.data.message);
    }
  } catch (error) {
    console.error('Error al importar CSV:', error);
    alert('Error al importar el archivo: ' + (error.response?.data?.message || error.message));
  } finally {
    cargando.value = false;
  }
};

const resetearFormulario = () => {
  archivoSeleccionado.value = null;
  previsualizacion.value = null;
  resultado.value = null;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};
</script>

<style scoped>
/* Animaciones para drag & drop */
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.dragging {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
