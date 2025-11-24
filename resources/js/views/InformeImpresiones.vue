<template>
  <div class="min-h-screen bg-[rgb(var(--bg))] p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Encabezado -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-[rgb(var(--text))]">Informe de Impresiones</h1>
        <p class="text-sm text-[rgb(var(--muted))] mt-1">Análisis detallado del uso de impresoras</p>
      </div>

      <!-- Filtros -->
      <div class="card p-5 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Rango de fechas -->
          <div>
            <label class="block text-sm font-medium text-[rgb(var(--text))] mb-1">Fecha Inicio</label>
            <input type="date" v-model="filtros.fechaInicio" @change="cargarDatos" class="input" />
          </div>
          <div>
            <label class="block text-sm font-medium text-[rgb(var(--text))] mb-1">Fecha Fin</label>
            <input type="date" v-model="filtros.fechaFin" @change="cargarDatos" class="input" />
          </div>

          <!-- Hostname -->
          <div>
            <label class="block text-sm font-medium text-[rgb(var(--text))] mb-1">Impresora (hostname)</label>
            <input type="text" v-model="filtros.hostname" @change="cargarDatos" placeholder="Filtrar por IP/hostname" class="input" />
          </div>

          <!-- Agrupación -->
          <div>
            <label class="block text-sm font-medium text-[rgb(var(--text))] mb-1">Agrupar por</label>
            <select v-model="filtros.agrupacion" @change="cargarDatos" class="select">
              <option value="dia">Día</option>
              <option value="semana">Semana</option>
              <option value="mes">Mes</option>
            </select>
          </div>

          <!-- Botón actualizar -->
          <div class="flex items-end">
            <button @click="cargarDatos" :disabled="cargando" class="btn btn-primary w-full">
              <svg v-if="cargando" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ cargando ? 'Cargando...' : 'Actualizar' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Tarjetas de resumen -->
      <div v-if="!cargando && datos.resumen" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="card p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs text-[rgb(var(--muted))] font-medium">Total Impresiones</p>
              <p class="text-2xl font-bold text-[rgb(var(--text))] mt-1">{{ formatNumber(datos.resumen.total_impresiones) }}</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </div>
          </div>
        </div>

        <div class="card p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs text-[rgb(var(--muted))] font-medium">Promedio Diario</p>
              <p class="text-2xl font-bold text-[rgb(var(--text))] mt-1">{{ formatNumber(datos.resumen.promedio_diario) }}</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
              </svg>
            </div>
          </div>
        </div>

        <div class="card p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs text-[rgb(var(--muted))] font-medium">Impresiones B/N</p>
              <p class="text-2xl font-bold text-[rgb(var(--text))] mt-1">{{ formatNumber(datos.resumen.total_bn) }}</p>
              <p class="text-xs text-[rgb(var(--muted))] mt-1">{{ datos.resumen.porcentaje_bn }}% del total</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
              <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
              </svg>
            </div>
          </div>
        </div>

        <div class="card p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs text-[rgb(var(--muted))] font-medium">Impresiones Color</p>
              <p class="text-2xl font-bold text-[rgb(var(--text))] mt-1">{{ formatNumber(datos.resumen.total_color) }}</p>
              <p class="text-xs text-[rgb(var(--muted))] mt-1">{{ datos.resumen.porcentaje_color }}% del total</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-cyan-400 via-magenta-400 to-yellow-400 flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráficos -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Gráfico de tendencia temporal -->
        <div class="card p-5">
          <h3 class="text-base font-semibold text-[rgb(var(--text))] mb-4">Tendencia de Impresiones</h3>
          <div style="height: 250px;">
            <canvas ref="chartTemporal"></canvas>
          </div>
        </div>

        <!-- Gráfico de distribución Color vs B/N -->
        <div class="card p-5">
          <h3 class="text-base font-semibold text-[rgb(var(--text))] mb-4">Distribución Color vs B/N</h3>
          <div class="flex justify-center" style="height: 250px;">
            <canvas ref="chartDistribucion"></canvas>
          </div>
        </div>
      </div>

      <!-- Gráficos de distribución por modelo y marca -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Por modelo -->
        <div class="card p-5">
          <h3 class="text-base font-semibold text-[rgb(var(--text))] mb-4">Impresiones por Modelo</h3>
          <div style="height: 250px;">
            <canvas ref="chartModelos"></canvas>
          </div>
        </div>

        <!-- Por marca -->
        <div class="card p-5">
          <h3 class="text-base font-semibold text-[rgb(var(--text))] mb-4">Impresiones por Marca</h3>
          <div style="height: 250px;">
            <canvas ref="chartMarcas"></canvas>
          </div>
        </div>
      </div>

      <!-- Tabla de detalles -->
      <div class="card p-5">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-base font-semibold text-[rgb(var(--text))]">Detalle por Período</h3>
          <button @click="exportarDatos" class="btn btn-secondary btn-sm">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Exportar CSV
          </button>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Período</th>
                <th class="text-right">Total</th>
                <th class="text-right">B/N</th>
                <th class="text-right">Color</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in datos.serie_temporal" :key="item.fecha">
                <td class="font-medium">{{ item.etiqueta }}</td>
                <td class="text-right">{{ formatNumber(item.total) }}</td>
                <td class="text-right">{{ formatNumber(item.bn) }}</td>
                <td class="text-right">{{ formatNumber(item.color) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { Chart, registerables } from 'chart.js';
import axios from 'axios';

Chart.register(...registerables);

// Refs
const cargando = ref(false);
const filtros = ref({
  fechaInicio: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  fechaFin: new Date().toISOString().split('T')[0],
  hostname: '',
  agrupacion: 'dia',
});

const datos = ref({
  serie_temporal: [],
  resumen: null,
  por_modelo: [],
  por_marca: [],
  top_impresoras: [],
});

const chartTemporal = ref(null);
const chartDistribucion = ref(null);
const chartModelos = ref(null);
const chartMarcas = ref(null);

let chartTemporalInstance = null;
let chartDistribucionInstance = null;
let chartModelosInstance = null;
let chartMarcasInstance = null;

// Métodos
const cargarDatos = async () => {
  cargando.value = true;
  try {
    const params = {
      fecha_inicio: filtros.value.fechaInicio,
      fecha_fin: filtros.value.fechaFin,
      agrupacion: filtros.value.agrupacion,
    };
    
    if (filtros.value.hostname) params.hostname = filtros.value.hostname;

    console.log('Llamando a API con params:', params);
    const response = await axios.get('/informes/impresiones/estadisticas', { params });
    console.log('Respuesta completa:', response);
    console.log('response.data:', response.data);
    
    if (response.data && response.data.success) {
      datos.value = response.data.data;
      console.log('Datos cargados:', datos.value);
      await nextTick();
      renderizarGraficos();
    } else {
      console.error('Error: response.data.success no es true');
      console.error('response.data completo:', response.data);
    }
  } catch (error) {
    console.error('Error al cargar estadísticas:', error);
    if (error.response) {
      console.error('Respuesta del servidor:', error.response.data);
      console.error('Estado:', error.response.status);
    }
  } finally {
    cargando.value = false;
  }
};

const renderizarGraficos = () => {
  renderizarGraficoTemporal();
  renderizarGraficoDistribucion();
  renderizarGraficoModelos();
  renderizarGraficoMarcas();
};

const renderizarGraficoTemporal = () => {
  if (chartTemporalInstance) {
    chartTemporalInstance.destroy();
  }

  const ctx = chartTemporal.value?.getContext('2d');
  if (!ctx) return;

  chartTemporalInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: datos.value.serie_temporal.map(d => d.etiqueta),
      datasets: [
        {
          label: 'Total',
          data: datos.value.serie_temporal.map(d => d.total),
          borderColor: 'rgb(59, 130, 246)',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          tension: 0.4,
          fill: true,
        },
        {
          label: 'B/N',
          data: datos.value.serie_temporal.map(d => d.bn),
          borderColor: 'rgb(107, 114, 128)',
          backgroundColor: 'rgba(107, 114, 128, 0.1)',
          tension: 0.4,
        },
        {
          label: 'Color',
          data: datos.value.serie_temporal.map(d => d.color),
          borderColor: 'rgb(236, 72, 153)',
          backgroundColor: 'rgba(236, 72, 153, 0.1)',
          tension: 0.4,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
        },
      },
      scales: {
        y: {
          beginAtZero: true,
        },
      },
    },
  });
};

const renderizarGraficoDistribucion = () => {
  if (chartDistribucionInstance) {
    chartDistribucionInstance.destroy();
  }

  const ctx = chartDistribucion.value?.getContext('2d');
  if (!ctx || !datos.value.resumen) return;

  chartDistribucionInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['B/N', 'Color'],
      datasets: [{
        data: [datos.value.resumen.total_bn, datos.value.resumen.total_color],
        backgroundColor: ['rgb(107, 114, 128)', 'rgb(236, 72, 153)'],
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
        },
      },
    },
  });
};

const renderizarGraficoModelos = () => {
  if (chartModelosInstance) {
    chartModelosInstance.destroy();
  }

  const ctx = chartModelos.value?.getContext('2d');
  if (!ctx) return;

  chartModelosInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: datos.value.por_modelo.map(d => d.modelo),
      datasets: [{
        label: 'Impresiones',
        data: datos.value.por_modelo.map(d => d.total),
        backgroundColor: 'rgba(0, 106, 78, 0.7)',
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        y: {
          beginAtZero: true,
        },
      },
    },
  });
};

const renderizarGraficoMarcas = () => {
  if (chartMarcasInstance) {
    chartMarcasInstance.destroy();
  }

  const ctx = chartMarcas.value?.getContext('2d');
  if (!ctx) return;

  chartMarcasInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: datos.value.por_marca.map(d => d.marca),
      datasets: [{
        label: 'Impresiones',
        data: datos.value.por_marca.map(d => d.total),
        backgroundColor: 'rgba(0, 168, 107, 0.7)',
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      indexAxis: 'y',
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        x: {
          beginAtZero: true,
        },
      },
    },
  });
};

const exportarDatos = () => {
  let csv = 'Período,Total,B/N,Color\n';
  datos.value.serie_temporal.forEach(item => {
    csv += `"${item.etiqueta}",${item.total},${item.bn},${item.color}\n`;
  });

  const blob = new Blob([csv], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `informe-impresiones-${filtros.value.fechaInicio}-${filtros.value.fechaFin}.csv`;
  a.click();
  window.URL.revokeObjectURL(url);
};

const formatNumber = (num) => {
  return num ? num.toLocaleString() : '0';
};

// Lifecycle
onMounted(() => {
  cargarDatos();
});
</script>
