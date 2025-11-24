<template>
    <div class="p-4">
        <div class="mb-4 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
            <h2 class="text-2xl font-bold">Solicitudes de Reposición</h2>
            <div class="flex flex-wrap items-center gap-2">
                <AlmacenSelector v-model="almacenId" @change="onAlmacenChange" />
                <select v-model="filtroEstado" @change="cargarSolicitudes"
                        class="px-3 py-2 border rounded">
                    <option value="todos">Todos los estados</option>
                    <option value="pendiente">Pendientes</option>
                    <option value="notificado">Notificados</option>
                    <option value="cancelado">Cancelados</option>
                </select>
                <button @click="cargarSolicitudes"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    🔄 Actualizar
                </button>
            </div>
        </div>

        <!-- Tabla de solicitudes -->
        <div v-if="cargando" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-gray-600">Cargando solicitudes...</p>
        </div>

        <div v-else-if="solicitudes.length === 0" class="text-center py-8 bg-gray-50 rounded">
            <p class="text-gray-500">No hay solicitudes {{ filtroEstado !== 'todos' ? 'en estado ' + filtroEstado : '' }}</p>
        </div>

        <div v-else class="overflow-x-auto">
            <table class="min-w-full bg-white border rounded shadow">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Material</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Usuario</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase">Cantidad</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Fecha Solicitud</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Previsión Llegada</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase">Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="solicitud in solicitudes" :key="solicitud.id" 
                        :class="{'bg-green-50': solicitud.estado === 'notificado', 'bg-gray-50': solicitud.estado === 'cancelado'}">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-900">{{ solicitud.material.nombre }}</div>
                            <div v-if="solicitud.material.descripcion" class="text-sm text-gray-600 mt-1">
                                {{ solicitud.material.descripcion }}
                            </div>
                            <div class="text-sm text-gray-500 mt-1">
                                <span class="font-medium">Ref:</span> {{ solicitud.material.referencia || 'Sin referencia' }}
                            </div>
                            <div v-if="solicitud.material.categoria" class="text-xs text-gray-400 mt-1">
                                <span class="font-medium">Categoría:</span> {{ solicitud.material.categoria }}
                            </div>
                            <div v-if="solicitud.motivo" class="text-xs text-blue-600 mt-1 italic">
                                💬 {{ solicitud.motivo }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900">{{ solicitud.usuario.nombre }}</div>
                            <div class="text-xs text-gray-500">{{ solicitud.usuario.email }}</div>
                            <div v-if="solicitud.usuario.telefono" class="text-xs text-gray-500 mt-1">
                                📞 {{ solicitud.usuario.telefono }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-semibold">{{ solicitud.cantidad_solicitada }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ formatDate(solicitud.fecha_solicitud) }}
                        </td>
                        <td class="px-4 py-3">
                            <div v-if="editandoPrevision === solicitud.id" class="space-y-2">
                                <input type="date" v-model="previsionTemp" 
                                       class="w-full px-2 py-1 border rounded text-sm">
                                <textarea v-model="notasTemp" placeholder="Notas..."
                                          class="w-full px-2 py-1 border rounded text-sm" rows="2"></textarea>
                                <div class="flex gap-1">
                                    <button @click="guardarPrevision(solicitud.id)" 
                                            class="px-2 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700">
                                        ✓ Guardar
                                    </button>
                                    <button @click="cancelarEdicion" 
                                            class="px-2 py-1 bg-gray-400 text-white rounded text-xs hover:bg-gray-500">
                                        ✗ Cancelar
                                    </button>
                                </div>
                            </div>
                            <div v-else>
                                <div v-if="solicitud.prevision_llegada" class="text-sm font-medium text-blue-600">
                                    📅 {{ solicitud.prevision_llegada_texto }}
                                </div>
                                <div v-else class="text-xs text-gray-400">Sin previsión</div>
                                <div v-if="solicitud.notas" class="text-xs text-gray-500 mt-1">{{ solicitud.notas }}</div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span v-if="solicitud.estado === 'pendiente'" 
                                  class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">
                                ⏳ Pendiente
                            </span>
                            <span v-else-if="solicitud.estado === 'notificado'" 
                                  class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
                                ✅ Notificado
                            </span>
                            <span v-else-if="solicitud.estado === 'cancelado'" 
                                  class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-semibold">
                                ✗ Cancelado
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-1 items-center">
                                <button v-if="solicitud.estado === 'pendiente'" 
                                        @click="editarPrevision(solicitud)"
                                        class="px-3 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700 w-full">
                                    📝 Previsión
                                </button>
                                <button v-if="solicitud.estado === 'pendiente'" 
                                        @click="notificarUsuario(solicitud.id)"
                                        class="px-3 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700 w-full">
                                    🔔 Notificar
                                </button>
                                <button v-if="solicitud.estado === 'pendiente'" 
                                        @click="irAMovimiento(solicitud.material.id)"
                                        class="px-3 py-1 bg-purple-600 text-white rounded text-xs hover:bg-purple-700 w-full">
                                    📦 Entrada
                                </button>
                                <button v-if="solicitud.estado !== 'cancelado'" 
                                        @click="cancelarSolicitud(solicitud.id)"
                                        class="px-3 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700 w-full">
                                    ✗ Cancelar
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Resumen estadístico -->
        <div class="mt-6 grid grid-cols-3 gap-4">
            <div class="bg-yellow-50 border border-yellow-200 rounded p-4">
                <div class="text-2xl font-bold text-yellow-700">{{ contadores.pendientes }}</div>
                <div class="text-sm text-yellow-600">Pendientes</div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded p-4">
                <div class="text-2xl font-bold text-green-700">{{ contadores.notificados }}</div>
                <div class="text-sm text-green-600">Notificados</div>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded p-4">
                <div class="text-2xl font-bold text-gray-700">{{ contadores.cancelados }}</div>
                <div class="text-sm text-gray-600">Cancelados</div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import AlmacenSelector from '../components/AlmacenSelector.vue';

export default {
    name: 'SolicitudesReposicion',
    components: {
        AlmacenSelector
    },
    
    data() {
        return {
            solicitudes: [],
            filtroEstado: 'pendiente',
            cargando: false,
            editandoPrevision: null,
            previsionTemp: null,
            notasTemp: '',
            almacenId: '',
            contadores: {
                pendientes: 0,
                notificados: 0,
                cancelados: 0
            }
        };
    },

    mounted() {
        this.cargarSolicitudes();
    },

    methods: {
        async cargarSolicitudes() {
            this.cargando = true;
            try {
                const params = this.filtroEstado !== 'todos' ? { estado: this.filtroEstado } : {};
                if (this.almacenId) {
                    params.almacen_seleccionado = this.almacenId;
                }
                const response = await axios.get('/solicitudes-reposicion', {
                    params,
                    headers: {
                        'Cache-Control': 'no-cache',
                        'Pragma': 'no-cache',
                        'Expires': '0'
                    }
                });
                this.solicitudes = response.data;
                
                // Calcular contadores
                this.contadores.pendientes = this.solicitudes.filter(s => s.estado === 'pendiente').length;
                this.contadores.notificados = this.solicitudes.filter(s => s.estado === 'notificado').length;
                this.contadores.cancelados = this.solicitudes.filter(s => s.estado === 'cancelado').length;
            } catch (error) {
                console.error('Error cargando solicitudes:', error);
                alert('Error al cargar las solicitudes');
            } finally {
                this.cargando = false;
            }
        },

        onAlmacenChange() {
            this.cargarSolicitudes();
        },

        editarPrevision(solicitud) {
            this.editandoPrevision = solicitud.id;
            this.previsionTemp = solicitud.prevision_llegada || '';
            this.notasTemp = solicitud.notas || '';
        },

        cancelarEdicion() {
            this.editandoPrevision = null;
            this.previsionTemp = null;
            this.notasTemp = '';
        },

        async guardarPrevision(solicitudId) {
            try {
                await axios.put(`/solicitudes-reposicion/${solicitudId}/prevision`, {
                    prevision_llegada: this.previsionTemp || null,
                    notas: this.notasTemp
                });
                
                this.cancelarEdicion();
                await this.cargarSolicitudes();
                alert('✓ Previsión actualizada correctamente');
            } catch (error) {
                console.error('Error guardando previsión:', error);
                alert('Error al guardar la previsión');
            }
        },

        async notificarUsuario(solicitudId) {
            if (!confirm('¿Notificar al usuario que hay stock disponible?')) return;
            
            try {
                await axios.post(`/solicitudes-reposicion/${solicitudId}/notificar`);
                await this.cargarSolicitudes();
                alert('✓ Usuario notificado correctamente');
            } catch (error) {
                console.error('Error notificando usuario:', error);
                alert('Error al notificar al usuario');
            }
        },

        async cancelarSolicitud(solicitudId) {
            if (!confirm('¿Cancelar esta solicitud?')) return;
            
            try {
                await axios.post(`/solicitudes-reposicion/${solicitudId}/cancelar`);
                await this.cargarSolicitudes();
                alert('✓ Solicitud cancelada');
            } catch (error) {
                console.error('Error cancelando solicitud:', error);
                alert('Error al cancelar la solicitud');
            }
        },

        irAMovimiento(materialId) {
            // Redirigir a la vista de movimientos
            this.$router.push({ 
                name: 'Movimientos'
            });
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString('es-ES', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
};
</script>

<style scoped>
/* Estilos adicionales si es necesario */
</style>
