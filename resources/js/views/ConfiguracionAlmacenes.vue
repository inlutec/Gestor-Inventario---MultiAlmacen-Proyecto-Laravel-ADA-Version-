<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Gestión de Almacenes</h1>
      <button @click="mostrarModalUsuario = true" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
        Asignar almacenes a usuario
      </button>
    </div>

    <!-- Lista de usuarios con sus almacenes asignados -->
    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-600">Cargando usuarios...</p>
    </div>

    <div v-else class="overflow-x-auto">
      <table class="min-w-full bg-white border rounded shadow">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Usuario</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Email</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Almacenes Asignados</th>
            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="usuario in usuariosConAlmacenes" :key="usuario.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <div class="font-medium text-gray-900">{{ usuario.nombre }} {{ usuario.apellido }}</div>
            </td>
            <td class="px-4 py-3">
              <div class="text-sm text-gray-600">{{ usuario.email }}</div>
            </td>
            <td class="px-4 py-3">
              <div class="flex flex-wrap gap-2">
                <span v-for="almacen in usuario.almacenes" :key="almacen.id" 
                      class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                  {{ almacen.nombre }}
                </span>
                <span v-if="!usuario.almacenes || usuario.almacenes.length === 0" 
                      class="text-gray-400 text-sm italic">
                  Sin almacenes asignados
                </span>
              </div>
            </td>
            <td class="px-4 py-3 text-center">
              <button @click="editarUsuario(usuario)" 
                      class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">
                Editar
              </button>
            </td>
          </tr>
          <tr v-if="!usuariosConAlmacenes.length">
            <td class="px-4 py-8 text-center text-gray-500" colspan="4">
              No hay usuarios con almacenes asignados
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal para asignar/editar almacenes a usuario -->
    <div v-if="mostrarModalUsuario" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">
            {{ usuarioEditando.id ? 'Editar almacenes' : 'Asignar almacenes' }}
          </h2>
          <button @click="cerrarModalUsuario" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Selector de usuario -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Usuario</label>
          <select v-model="usuarioEditando.id" 
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  @change="cargarAlmacenesUsuario">
            <option value="">Selecciona un usuario</option>
            <option v-for="usuario in usuariosDisponibles" :key="usuario.id" :value="usuario.id">
              {{ usuario.nombre }} {{ usuario.apellido }} - {{ usuario.email }}
            </option>
          </select>
        </div>

        <!-- Lista de almacenes disponibles -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Almacenes disponibles</label>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-60 overflow-y-auto border rounded-lg p-3">
            <label v-for="almacen in almacenesDisponibles" :key="almacen.id" 
                   class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50"
                   :class="almacenesSeleccionados.includes(almacen.id) ? 'bg-blue-50 border-blue-300' : 'border-gray-200'">
              <input type="checkbox" 
                     :value="almacen.id" 
                     v-model="almacenesSeleccionados"
                     class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
              <div>
                <div class="font-medium text-gray-900">{{ almacen.nombre }}</div>
                <div class="text-sm text-gray-600">{{ almacen.provincia?.nombre }}</div>
                <div class="text-xs text-gray-500">{{ almacen.sede?.nombre }}</div>
              </div>
            </label>
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t">
          <button @click="cerrarModalUsuario" 
                  class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
            Cancelar
          </button>
          <button @click="guardarAsignacion" 
                  :disabled="!usuarioEditando.id || guardando"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
            {{ guardando ? 'Guardando...' : 'Guardar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const usuariosConAlmacenes = ref([])
const usuariosDisponibles = ref([])
const almacenesDisponibles = ref([])
const loading = ref(true)
const mostrarModalUsuario = ref(false)
const guardando = ref(false)

const usuarioEditando = ref({
  id: '',
  nombre: '',
  apellido: '',
  email: ''
})

const almacenesSeleccionados = ref([])

const cargarUsuariosConAlmacenes = async () => {
  try {
    const { data } = await axios.get('/usuarios-con-almacenes')
    usuariosConAlmacenes.value = data.success ? data.data : []
  } catch (e) {
    console.error('Error cargando usuarios con almacenes:', e)
    usuariosConAlmacenes.value = []
  }
}

const cargarUsuariosDisponibles = async () => {
  try {
    const { data } = await axios.get('/usuarios')
    usuariosDisponibles.value = data.success ? data.data : []
  } catch (e) {
    console.error('Error cargando usuarios disponibles:', e)
    usuariosDisponibles.value = []
  }
}

const cargarAlmacenesDisponibles = async () => {
  try {
    const { data } = await axios.get('/almacenes-disponibles')
    almacenesDisponibles.value = data.success ? data.data : []
  } catch (e) {
    console.error('Error cargando almacenes disponibles:', e)
    almacenesDisponibles.value = []
  }
}

const cargarAlmacenesUsuario = async () => {
  if (!usuarioEditando.value.id) {
    almacenesSeleccionados.value = []
    return
  }

  try {
    const { data } = await axios.get(`/usuarios/${usuarioEditando.value.id}/almacenes`)
    if (data.success) {
      almacenesSeleccionados.value = data.data.map(a => a.id)
    }
  } catch (e) {
    console.error('Error cargando almacenes del usuario:', e)
    almacenesSeleccionados.value = []
  }
}

const editarUsuario = (usuario) => {
  usuarioEditando.value = { ...usuario }
  mostrarModalUsuario.value = true
  cargarAlmacenesUsuario()
}

const cerrarModalUsuario = () => {
  mostrarModalUsuario.value = false
  usuarioEditando.value = {
    id: '',
    nombre: '',
    apellido: '',
    email: ''
  }
  almacenesSeleccionados.value = []
}

const guardarAsignacion = async () => {
  if (!usuarioEditando.value.id) return

  guardando.value = true
  try {
    const { data } = await axios.post(`/usuarios/${usuarioEditando.value.id}/almacenes`, {
      almacen_ids: almacenesSeleccionados.value
    })

    if (data.success) {
      await cargarUsuariosConAlmacenes()
      cerrarModalUsuario()
      alert('✓ Almacenes asignados correctamente')
    } else {
      alert(data.message || 'Error al asignar almacenes')
    }
  } catch (e) {
    console.error('Error guardando asignación:', e)
    alert(e.response?.data?.message || 'Error al asignar almacenes')
  } finally {
    guardando.value = false
  }
}

onMounted(async () => {
  loading.value = true
  await Promise.all([
    cargarUsuariosConAlmacenes(),
    cargarUsuariosDisponibles(),
    cargarAlmacenesDisponibles()
  ])
  loading.value = false
})
</script>