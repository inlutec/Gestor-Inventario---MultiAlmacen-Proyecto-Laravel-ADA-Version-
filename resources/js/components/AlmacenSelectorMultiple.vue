<template>
  <div class="space-y-3">
    <label class="block text-sm font-medium text-gray-700">Almacenes asignados:</label>
    
    <div v-if="loading" class="text-sm text-gray-500">Cargando almacenes...</div>
    
    <div v-else class="space-y-2 max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-3">
      <!-- Opción "Todos los almacenes" -->
      <div class="flex items-center">
        <input 
          type="checkbox" 
          id="todos-almacenes"
          v-model="todosSeleccionados"
          @change="onTodosChange"
          class="h-4 w-4 text-junta-green-600 focus:ring-junta-green-500 border-gray-300 rounded"
        />
        <label for="todos-almacenes" class="ml-2 text-sm text-gray-700">
          Todos los almacenes
        </label>
      </div>
      
      <!-- Lista de almacenes agrupados por provincia -->
      <div v-for="provincia in almacenesPorProvincia" :key="provincia.id" class="mt-3">
        <div class="flex items-center mb-2">
          <input 
            type="checkbox" 
            :id="`provincia-${provincia.id}`"
            v-model="provinciasSeleccionadas[provincia.id]"
            @change="onProvinciaChange(provincia.id)"
            class="h-4 w-4 text-junta-green-600 focus:ring-junta-green-500 border-gray-300 rounded"
          />
          <label :for="`provincia-${provincia.id}`" class="ml-2 text-sm font-medium text-gray-900">
            {{ provincia.nombre }}
          </label>
        </div>
        
        <div class="ml-6 space-y-1">
          <div v-for="almacen in provincia.almacenes" :key="almacen.id" class="flex items-center">
            <input 
              type="checkbox" 
              :id="`almacen-${almacen.id}`"
              :value="almacen.id"
              v-model="almacenesSeleccionados"
              class="h-4 w-4 text-junta-green-600 focus:ring-junta-green-500 border-gray-300 rounded"
            />
            <label :for="`almacen-${almacen.id}`" class="ml-2 text-sm text-gray-700">
              {{ almacen.nombre }}
            </label>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Resumen de selección -->
    <div v-if="almacenesSeleccionados.length > 0" class="text-sm text-gray-600">
      Seleccionados: {{ almacenesSeleccionados.length }} almacén(es)
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: { type: Array, default: () => [] }
})

const emit = defineEmits(['update:modelValue'])

const almacenesSeleccionados = ref([...props.modelValue])
const almacenes = ref([])
const loading = ref(false)
const provinciasSeleccionadas = ref({})

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

const todosSeleccionados = computed({
  get() {
    return almacenesSeleccionados.value.length === almacenes.value.length && almacenes.value.length > 0
  },
  set(value) {
    if (value) {
      // Seleccionar todos los almacenes
      almacenesSeleccionados.value = almacenes.value.map(a => a.id)
      actualizarProvinciasSeleccionadas()
    } else {
      // Deseleccionar todos
      almacenesSeleccionados.value = []
      provinciasSeleccionadas.value = {}
    }
    emitChange()
  }
})

const cargarAlmacenes = async () => {
  loading.value = true
  try {
    // Para la configuración de usuarios, usar los almacenes disponibles (todos)
    // Para otros usos, podría usar /mis-almacenes para filtrar por permisos
    const endpoint = '/almacenes-disponibles'
    const { data } = await axios.get(endpoint)
    if (data.success) {
      almacenes.value = data.data
      actualizarProvinciasSeleccionadas()
    }
  } catch (error) {
    console.error('Error cargando almacenes:', error)
  } finally {
    loading.value = false
  }
}

const onTodosChange = () => {
  // Esta función se maneja con el computed setter de todosSeleccionados
}

const onProvinciaChange = (provinciaId) => {
  const provincia = almacenesPorProvincia.value.find(p => p.id === provinciaId)
  if (provincia) {
    if (provinciasSeleccionadas.value[provinciaId]) {
      // Añadir todos los almacenes de esta provincia
      provincia.almacenes.forEach(almacen => {
        if (!almacenesSeleccionados.value.includes(almacen.id)) {
          almacenesSeleccionados.value.push(almacen.id)
        }
      })
    } else {
      // Quitar todos los almacenes de esta provincia
      provincia.almacenes.forEach(almacen => {
        const index = almacenesSeleccionados.value.indexOf(almacen.id)
        if (index > -1) {
          almacenesSeleccionados.value.splice(index, 1)
        }
      })
    }
    emitChange()
  }
}

const actualizarProvinciasSeleccionadas = () => {
  const nuevoEstado = {}
  
  almacenesPorProvincia.value.forEach(provincia => {
    const todosAlmacenProvincia = provincia.almacenes.every(almacen => 
      almacenesSeleccionados.value.includes(almacen.id)
    )
    const algunAlmacenProvincia = provincia.almacenes.some(almacen => 
      almacenesSeleccionados.value.includes(almacen.id)
    )
    
    if (todosAlmacenProvincia) {
      nuevoEstado[provincia.id] = true
    } else if (algunAlmacenProvincia) {
      // Estado indeterminado (algunos seleccionados)
      nuevoEstado[provincia.id] = false
    } else {
      nuevoEstado[provincia.id] = false
    }
  })
  
  provinciasSeleccionadas.value = nuevoEstado
}

const emitChange = () => {
  emit('update:modelValue', [...almacenesSeleccionados.value])
}

// Cargar almacenes al montar
onMounted(() => {
  cargarAlmacenes()
})

// Sincronizar con prop externa
watch(() => props.modelValue, (newValue) => {
  if (JSON.stringify(newValue) !== JSON.stringify(almacenesSeleccionados.value)) {
    almacenesSeleccionados.value = [...newValue]
    actualizarProvinciasSeleccionadas()
  }
})

// Actualizar provincias cuando cambian los almacenes seleccionados
watch(almacenesSeleccionados, () => {
  actualizarProvinciasSeleccionadas()
}, { deep: true })
</script>