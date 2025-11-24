<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">Consumibles</h1>
        <p class="text-sm text-muted">Gestión de stock de consumibles para impresoras</p>
      </div>
      <button @click="abrirModalNuevo" class="btn btn-primary">
        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo Consumible
      </button>
    </div>

    <!-- Filtros -->
    <div class="card p-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Buscar</label>
          <input v-model="searchQuery" type="text" placeholder="Buscar consumible..." class="input w-full"/>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Tipo</label>
          <select v-model="filterTipo" class="input w-full">
            <option value="">Todos los tipos</option>
            <option value="toner">Tóner</option>
            <option value="cartucho">Cartucho</option>
            <option value="kit_adf">Kit ADF</option>
            <option value="kit_mantenimiento">Kit de Mantenimiento</option>
            <option value="unidad_imagen">Unidad de Imagen</option>
            <option value="rodillo">Rodillo</option>
            <option value="botella_residual">Botella de Tóner Residual</option>
            <option value="kit_limpieza">Kit de limpieza de cabezal</option>
            <option value="otro">Otro Consumible</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Stock</label>
          <select v-model="filterStock" class="input w-full">
            <option value="">Todos</option>
            <option value="bajo">Stock bajo</option>
            <option value="sin_stock">Sin stock</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Tabla de consumibles -->
    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Referencia</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marca</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modelo</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Color</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock Actual</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock Mínimo</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="consumible in consumiblesFiltrados" :key="consumible.id" class="hover:bg-gray-50 cursor-pointer" @click="abrirDetalles(consumible)">
              <td class="px-4 py-3">
                <div class="font-medium text-gray-900">{{ consumible.referencia }}</div>
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ consumible.marca || '—' }}</td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ consumible.modelo || '—' }}</td>
              <td class="px-4 py-3">
                <span class="text-sm text-gray-600">{{ formatTipo(consumible.tipo) }}</span>
              </td>
              <td class="px-4 py-3">
                <div v-if="consumible.color" class="flex items-center gap-2">
                  <div class="h-5 w-5 rounded-full border-2 border-gray-300" :style="{ backgroundColor: getColorHex(consumible.color) }"></div>
                  <span class="text-sm">{{ formatColor(consumible.color) }}</span>
                </div>
                <span v-else class="text-sm text-gray-400">—</span>
              </td>
              <td class="px-4 py-3">
                <span class="font-medium" :class="stockClass(consumible)">{{ consumible.stock_actual }}</span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ consumible.stock_minimo }}</td>
              <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs rounded-full font-medium" :class="estadoClass(consumible)">
                  {{ estadoText(consumible) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <button @click.stop="abrirEditar(consumible)" class="p-1.5 text-gray-400 hover:text-junta-green-600 hover:bg-gray-100 rounded transition" title="Editar">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                  </button>
                  <button @click.stop="eliminarConsumible(consumible)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition" title="Eliminar">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div v-if="consumiblesFiltrados.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
        </svg>
        <p class="mt-2 text-sm text-muted">No se encontraron consumibles</p>
      </div>
    </div>

    <!-- Modal: Nuevo/Editar Consumible -->
    <transition name="fade">
      <div v-if="modal.open" class="modal-overlay">
        <div class="modal w-full max-w-2xl">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">{{ modal.editando ? 'Editar' : 'Nuevo' }} Consumible</h3>
            <button @click="cerrarModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-md transition">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div v-if="modal.error" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded">
            {{ modal.error }}
          </div>

          <div v-if="modal.success" class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded">
            {{ modal.success }}
          </div>

          <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Referencia <span class="text-red-500">*</span></label>
                <input v-model="form.referencia" type="text" class="input w-full" placeholder="Ej: HP 305A CE410A"/>
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Tipo de Consumible <span class="text-red-500">*</span></label>
                <select v-model="form.tipo" class="input w-full">
                  <option value="">Seleccionar tipo</option>
                  <option value="toner">Tóner</option>
                  <option value="cartucho">Cartucho</option>
                  <option value="kit_adf">Kit ADF</option>
                  <option value="kit_mantenimiento">Kit de Mantenimiento</option>
                  <option value="unidad_imagen">Unidad de Imagen</option>
                  <option value="rodillo">Rodillo</option>
                  <option value="botella_residual">Botella de Tóner Residual</option>
                  <option value="kit_limpieza">Kit de limpieza de cabezal</option>
                  <option value="otro">Otro Consumible</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Marca <span class="text-red-500">*</span></label>
                <input v-model="form.marca" type="text" class="input w-full" placeholder="Ej: HP, Canon, Brother"/>
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Modelo <span class="text-red-500">*</span></label>
                <input v-model="form.modelo" type="text" class="input w-full" placeholder="Ej: LaserJet 305A"/>
              </div>
            </div>

            <!-- Color (solo si es tóner o cartucho) -->
            <div v-if="muestraColor" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Color</label>
                <select v-model="form.color" class="input w-full">
                  <option value="">Sin color específico</option>
                  <option value="negro">Negro</option>
                  <option value="magenta">Magenta</option>
                  <option value="cian">Cian</option>
                  <option value="amarillo">Amarillo</option>
                </select>
              </div>
              <div v-if="form.color" class="flex items-end">
                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg">
                  <div class="h-8 w-8 rounded-full border-2 border-gray-300" :style="{ backgroundColor: getColorHex(form.color) }"></div>
                  <span class="text-sm font-medium">{{ formatColor(form.color) }}</span>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Ubicación</label>
              <input v-model="form.ubicacion" type="text" class="input w-full" placeholder="Ej: Almacén A - Estante 3"/>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Stock Actual <span class="text-red-500">*</span></label>
                <input v-model.number="form.stock_actual" type="number" min="0" class="input w-full" placeholder="0"/>
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Stock Mínimo <span class="text-red-500">*</span></label>
                <input v-model.number="form.stock_minimo" type="number" min="0" class="input w-full" placeholder="0"/>
              </div>
            </div>

            <!-- Campos personalizados -->
            <div v-if="customFields.length" class="border-t pt-4">
              <h4 class="text-sm font-semibold mb-3">Campos Personalizados</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="field in customFields" :key="field.id">
                  <label class="block text-sm font-medium mb-1">
                    {{ field.label }}
                    <span v-if="field.required" class="text-red-500">*</span>
                  </label>
                  
                  <!-- Text -->
                  <input v-if="field.type === 'text'" v-model="form.custom_fields[field.key]" type="text" class="input w-full"/>
                  
                  <!-- Number -->
                  <input v-else-if="field.type === 'number'" v-model.number="form.custom_fields[field.key]" type="number" class="input w-full"/>
                  
                  <!-- Boolean -->
                  <select v-else-if="field.type === 'boolean'" v-model="form.custom_fields[field.key]" class="input w-full">
                    <option :value="null">—</option>
                    <option :value="true">Sí</option>
                    <option :value="false">No</option>
                  </select>
                  
                  <!-- Select -->
                  <select v-else-if="field.type === 'select'" v-model="form.custom_fields[field.key]" class="input w-full">
                    <option value="">Seleccionar</option>
                    <option v-for="opt in field.options" :key="opt" :value="opt">{{ opt }}</option>
                  </select>
                  
                  <!-- Date -->
                  <input v-else-if="field.type === 'date'" v-model="form.custom_fields[field.key]" type="date" class="input w-full"/>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 mt-6">
            <button @click="cerrarModal" class="btn btn-secondary" :disabled="modal.loading">Cancelar</button>
            <button @click="guardarConsumible" class="btn btn-primary" :disabled="modal.loading">
              <span v-if="modal.loading" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Guardando...
              </span>
              <span v-else>{{ modal.editando ? 'Actualizar' : 'Crear' }} Consumible</span>
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Modal: Detalles -->
    <transition name="fade">
      <div v-if="modalDetalles.open" class="modal-overlay">
        <div class="modal w-full max-w-3xl">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Detalles del Consumible</h3>
            <button @click="cerrarDetalles" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-md transition">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div v-if="consumibleSeleccionado" class="space-y-4">
            <!-- Información principal -->
            <div class="card p-4">
              <div class="flex items-start justify-between mb-4">
                <div>
                  <h4 class="text-xl font-bold">{{ consumibleSeleccionado.referencia }}</h4>
                  <p class="text-sm text-gray-500 mt-1">{{ formatTipo(consumibleSeleccionado.tipo) }}</p>
                  <div class="flex items-center gap-3 mt-2 text-sm text-gray-600">
                    <span v-if="consumibleSeleccionado.marca"><strong>Marca:</strong> {{ consumibleSeleccionado.marca }}</span>
                    <span v-if="consumibleSeleccionado.modelo"><strong>Modelo:</strong> {{ consumibleSeleccionado.modelo }}</span>
                  </div>
                </div>
                <span class="px-3 py-1 text-sm rounded-full font-medium" :class="estadoClass(consumibleSeleccionado)">
                  {{ estadoText(consumibleSeleccionado) }}
                </span>
              </div>

              <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div v-if="consumibleSeleccionado.color">
                  <div class="text-xs text-gray-500 mb-1">Color</div>
                  <div class="flex items-center gap-2">
                    <div class="h-6 w-6 rounded-full border-2 border-gray-300" :style="{ backgroundColor: getColorHex(consumibleSeleccionado.color) }"></div>
                    <span class="font-medium">{{ formatColor(consumibleSeleccionado.color) }}</span>
                  </div>
                </div>
                
                <div>
                  <div class="text-xs text-gray-500 mb-1">Ubicación</div>
                  <div class="font-medium">{{ consumibleSeleccionado.ubicacion || '—' }}</div>
                </div>

                <div>
                  <div class="text-xs text-gray-500 mb-1">Stock Actual</div>
                  <div class="text-2xl font-bold" :class="stockClass(consumibleSeleccionado)">
                    {{ consumibleSeleccionado.stock_actual }}
                  </div>
                </div>

                <div>
                  <div class="text-xs text-gray-500 mb-1">Stock Mínimo</div>
                  <div class="text-lg font-medium text-gray-700">{{ consumibleSeleccionado.stock_minimo }}</div>
                </div>
              </div>
            </div>

            <!-- Campos personalizados -->
            <div v-if="detallesCustomFields.length" class="card p-4">
              <h4 class="font-semibold mb-3">Información Adicional</h4>
              <div class="grid grid-cols-2 gap-3 text-sm">
                <template v-for="cf in detallesCustomFields" :key="cf.key">
                  <div class="text-gray-500">{{ cf.label }}:</div>
                  <div class="font-medium">{{ cf.formatted }}</div>
                </template>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 mt-6">
            <button @click="cerrarDetalles" class="btn btn-secondary">Cerrar</button>
            <button @click="abrirEditar(consumibleSeleccionado); cerrarDetalles();" class="btn btn-primary">Editar</button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const consumibles = ref([]);
const searchQuery = ref('');
const filterTipo = ref('');
const filterStock = ref('');

const modal = ref({
  open: false,
  editando: false,
  loading: false,
  error: '',
  success: ''
});

const modalDetalles = ref({
  open: false
});

const consumibleSeleccionado = ref(null);

const form = ref({
  referencia: '',
  tipo: '',
  color: '',
  ubicacion: '',
  stock_actual: 0,
  stock_minimo: 0,
  custom_fields: {}
});

const customFields = ref([]);

const muestraColor = computed(() => {
  return form.value.tipo === 'toner' || form.value.tipo === 'cartucho';
});

const consumiblesFiltrados = computed(() => {
  return consumibles.value.filter(c => {
    const matchSearch = !searchQuery.value || 
      c.referencia.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (c.marca && c.marca.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
      (c.modelo && c.modelo.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
      (c.ubicacion && c.ubicacion.toLowerCase().includes(searchQuery.value.toLowerCase()));
    
    const matchTipo = !filterTipo.value || c.tipo === filterTipo.value;
    
    const matchStock = !filterStock.value ||
      (filterStock.value === 'bajo' && c.stock_actual <= c.stock_minimo && c.stock_actual > 0) ||
      (filterStock.value === 'sin_stock' && c.stock_actual === 0);
    
    return matchSearch && matchTipo && matchStock;
  });
});

const detallesCustomFields = computed(() => {
  if (!consumibleSeleccionado.value) return [];
  const defs = customFields.value;
  const vals = consumibleSeleccionado.value.custom_fields || {};
  
  return defs.map(def => {
    const hasKey = Object.prototype.hasOwnProperty.call(vals, def.key);
    const raw = hasKey ? vals[def.key] : null;
    const formatted = formatCustomValue(def, raw);
    const hasValue = (() => {
      if (!hasKey) return false;
      if (def.type === 'boolean') return typeof raw === 'boolean';
      if (raw === null || raw === undefined) return false;
      if (typeof raw === 'string') return raw.trim().length > 0;
      if (Array.isArray(raw)) return raw.length > 0;
      return true;
    })();
    return { key: def.key, label: def.label, formatted, hasValue };
  }).filter(it => it.hasValue);
});

const cargarConsumibles = async () => {
  try {
    const res = await axios.get('/consumibles');
    consumibles.value = res.data.data || [];
  } catch (e) {
    console.error('Error al cargar consumibles:', e);
  }
};

const cargarCustomFields = async () => {
  try {
    const res = await axios.get('/custom-fields', { params: { tipo_entidad: 'consumible' } });
    customFields.value = res.data.data || [];
  } catch (e) {
    console.error('Error al cargar campos personalizados:', e);
  }
};

const abrirModalNuevo = () => {
  form.value = {
    referencia: '',
    tipo: '',
    marca: '',
    modelo: '',
    color: '',
    ubicacion: '',
    stock_actual: 0,
    stock_minimo: 0,
    custom_fields: {}
  };
  modal.value = {
    open: true,
    editando: false,
    loading: false,
    error: '',
    success: ''
  };
};

const abrirEditar = (consumible) => {
  form.value = {
    id: consumible.id,
    referencia: consumible.referencia,
    tipo: consumible.tipo,
    marca: consumible.marca || '',
    modelo: consumible.modelo || '',
    color: consumible.color || '',
    ubicacion: consumible.ubicacion || '',
    stock_actual: consumible.stock_actual,
    stock_minimo: consumible.stock_minimo,
    custom_fields: consumible.custom_fields || {}
  };
  modal.value = {
    open: true,
    editando: true,
    loading: false,
    error: '',
    success: ''
  };
};

const cerrarModal = () => {
  modal.value.open = false;
};

const guardarConsumible = async () => {
  modal.value.error = '';
  modal.value.success = '';
  
  if (!form.value.referencia || !form.value.tipo || !form.value.marca || !form.value.modelo) {
    modal.value.error = 'Por favor complete los campos obligatorios (Referencia, Tipo, Marca y Modelo)';
    return;
  }

  modal.value.loading = true;
  
  try {
    const payload = {
      referencia: form.value.referencia,
      tipo: form.value.tipo,
      marca: form.value.marca,
      modelo: form.value.modelo,
      color: form.value.color || null,
      ubicacion: form.value.ubicacion,
      stock_actual: form.value.stock_actual,
      stock_minimo: form.value.stock_minimo,
      custom_fields: form.value.custom_fields
    };

    if (modal.value.editando) {
      await axios.put(`/consumibles/${form.value.id}`, payload);
      modal.value.success = 'Consumible actualizado correctamente';
    } else {
      await axios.post('/consumibles', payload);
      modal.value.success = 'Consumible creado correctamente';
    }

    await cargarConsumibles();
    setTimeout(() => cerrarModal(), 800);
  } catch (e) {
    modal.value.error = e?.response?.data?.message || 'Error al guardar el consumible';
  } finally {
    modal.value.loading = false;
  }
};

const eliminarConsumible = async (consumible) => {
  if (!confirm(`¿Está seguro de eliminar el consumible "${consumible.referencia}"?`)) return;
  
  try {
    await axios.delete(`/consumibles/${consumible.id}`);
    await cargarConsumibles();
  } catch (e) {
    alert('Error al eliminar el consumible');
  }
};

const abrirDetalles = (consumible) => {
  consumibleSeleccionado.value = consumible;
  modalDetalles.value.open = true;
};

const cerrarDetalles = () => {
  modalDetalles.value.open = false;
  consumibleSeleccionado.value = null;
};

const formatTipo = (tipo) => {
  const tipos = {
    'toner': 'Tóner',
    'cartucho': 'Cartucho',
    'kit_adf': 'Kit ADF',
    'kit_mantenimiento': 'Kit de Mantenimiento',
    'unidad_imagen': 'Unidad de Imagen',
    'rodillo': 'Rodillo',
    'botella_residual': 'Botella de Tóner Residual',
    'kit_limpieza': 'Kit de limpieza de cabezal',
    'otro': 'Otro Consumible'
  };
  return tipos[tipo] || tipo;
};

const formatColor = (color) => {
  const colores = {
    'negro': 'Negro',
    'magenta': 'Magenta',
    'cian': 'Cian',
    'amarillo': 'Amarillo'
  };
  return colores[color] || color;
};

const getColorHex = (color) => {
  const colores = {
    'negro': '#000000',
    'magenta': '#EC4899',
    'cian': '#06B6D4',
    'amarillo': '#EAB308'
  };
  return colores[color] || '#9CA3AF';
};

const stockClass = (consumible) => {
  if (consumible.stock_actual === 0) return 'text-red-600';
  if (consumible.stock_actual <= consumible.stock_minimo) return 'text-yellow-600';
  return 'text-green-600';
};

const estadoClass = (consumible) => {
  if (consumible.stock_actual === 0) return 'bg-red-100 text-red-800';
  if (consumible.stock_actual <= consumible.stock_minimo) return 'bg-yellow-100 text-yellow-800';
  return 'bg-green-100 text-green-800';
};

const estadoText = (consumible) => {
  if (consumible.stock_actual === 0) return 'Sin stock';
  if (consumible.stock_actual <= consumible.stock_minimo) return 'Stock bajo';
  return 'Disponible';
};

const formatCustomValue = (def, value) => {
  if (value === null || value === undefined) return '—';
  if (def?.type === 'boolean') return value ? 'Sí' : 'No';
  if (Array.isArray(value)) return value.join(', ');
  if (typeof value === 'object') return JSON.stringify(value);
  return String(value);
};

onMounted(() => {
  cargarConsumibles();
  cargarCustomFields();
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
