<template>
  <div class="flex items-center gap-2">
    <label class="text-sm font-medium text-gray-700">Almacén:</label>
    <select 
      v-model="almacenSeleccionado" 
      @change="onAlmacenChange"
      class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 bg-white text-sm"
      :disabled="loading"
    >
      <option value="">Todos mis almacenes</option>
      <optgroup v-for="provincia in almacenesPorProvincia" :key="provincia.id" :label="provincia.nombre">
        <option v-for="almacen in provincia.almacenes" :key="almacen.id" :value="almacen.id">
          {{ almacen.nombre }}
        </option>
      </optgroup>
    </select>
    <button 
      v-if="almacenSeleccionado"
      @click="limpiarSeleccion"
      class="px-2 py-1 text-xs text-gray-500 hover:text-gray-700 border border-gray-300 rounded hover:bg-gray-50"
      title="Limpiar selección"
    >
      ✕
    </button>
  </div>
</template>

<script setup>
// Updated: Using /mis-almacenes to show only warehouses assigned to current user
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' }
})

const emit = defineEmits(['update:modelValue', 'change'])

const almacenSeleccionado = ref(props.modelValue)
const almacenes = ref([])
const loading = ref(false)

const almacenesPorProvincia = computed(() => {
  const grouped = {}
  
  almacenes.value.forEach(almacen => {
    if (!grouped[almacen.provincia_id]) {
      grouped[almacen.provincia_id] = {
        id: almacen.provincia_id,
        nombre: almacen.provincia_nombre,
        almacenes: []
      }
    }
    grouped[almacen.provincia_id].almacenes.push(almacen)
  })
  
  return Object.values(grouped).sort((a, b) => a.nombre.localeCompare(b.nombre))
})

const cargarAlmacenes = async () => {
  loading.value = true
  try {
    // Usar /mis-almacenes para mostrar solo los almacenes asignados al usuario
    const { data } = await axios.get('/mis-almacenes')
    if (data.success) {
      almacenes.value = data.data
    }
  } catch (error) {
    console.error('Error cargando almacenes:', error)
  } finally {
    loading.value = false
  }
}

const onAlmacenChange = () => {
  emit('update:modelValue', almacenSeleccionado.value)
  emit('change', almacenSeleccionado.value)
  
  // Guardar en localStorage para persistencia
  if (almacenSeleccionado.value) {
    localStorage.setItem('almacen_seleccionado', almacenSeleccionado.value)
  } else {
    localStorage.removeItem('almacen_seleccionado')
  }
  
  // NO usar axios.defaults.params para evitar interferencias entre componentes
  // Cada vista manejará sus propios parámetros
}

const limpiarSeleccion = () => {
  almacenSeleccionado.value = ''
  onAlmacenChange()
}

// Cargar almacenes al montar
onMounted(() => {
  cargarAlmacenes()
  
  // Recuperar selección guardada
  const guardado = localStorage.getItem('almacen_seleccionado')
  if (guardado && guardado !== '') {
    almacenSeleccionado.value = guardado
  }
})

// Sincronizar con prop externa
watch(() => props.modelValue, (newValue) => {
  if (newValue !== almacenSeleccionado.value) {
    almacenSeleccionado.value = newValue
  }
})
</script>