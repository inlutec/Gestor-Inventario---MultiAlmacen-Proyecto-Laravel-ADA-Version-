<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-junta-green-50">
    <!-- Header Profesional con Logos - Responsive con soporte PWA -->
    <header
      class="bg-white border-b border-gray-200 shadow-sm"
      :style="{
        paddingTop: isPWA ? 'env(safe-area-inset-top, 0)' : '0'
      }"
    >
      <div class="container mx-auto px-3 sm:px-4 py-4 sm:py-6">
        <div class="flex items-center justify-between flex-wrap gap-3 sm:gap-4">
          <div class="flex items-center gap-2 sm:gap-4 flex-1">
            <img src="/gestionmaterial/images/junta-logo.png" alt="Junta de Andalucía"
                 class="h-12 sm:h-16 md:h-20 object-contain"
                 onerror="this.style.display='none'"/>
            <div class="border-l-2 border-junta-green-600 pl-2 sm:pl-4">
              <h1 class="text-base sm:text-xl md:text-2xl font-bold text-gray-900 leading-tight">
                Solicitud de Material
              </h1>
              <p class="text-xs sm:text-sm text-gray-600">Junta de Andalucía</p>
            </div>
          </div>
          <img src="/gestionmaterial/images/ada-logo.png" alt="Agencia Digital de Andalucía"
               class="h-10 sm:h-12 md:h-16 object-contain"
               onerror="this.style.display='none'"/>
        </div>
      </div>
    </header>

    <main
      class="container mx-auto px-3 sm:px-4 py-4 sm:py-8 max-w-5xl"
      :style="{
        paddingBottom: isPWA ? 'calc(2rem + env(safe-area-inset-bottom, 0))' : '2rem'
      }"
    >
      <!-- Mensaje de éxito - Mobile optimized -->
      <transition name="fade">
        <div v-if="success" class="mb-4 sm:mb-6 bg-green-50 border-l-4 border-green-500 p-3 sm:p-5 rounded-r-lg shadow-sm">
          <div class="flex items-start">
            <svg class="h-5 w-5 sm:h-6 sm:w-6 text-green-500 mr-2 sm:mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <div>
              <h3 class="text-base sm:text-lg font-semibold text-green-900">¡Petición enviada correctamente!</h3>
              <p class="text-xs sm:text-sm text-green-700 mt-1">
                Recibirás una notificación por email cuando tu petición sea revisada por el equipo de gestión.
              </p>
            </div>
          </div>
        </div>
      </transition>

      <!-- Mensaje de error - Mobile optimized -->
      <transition name="fade">
        <div v-if="error" class="mb-4 sm:mb-6 bg-red-50 border-l-4 border-red-500 p-3 sm:p-5 rounded-r-lg shadow-sm">
          <div class="flex items-start">
            <svg class="h-5 w-5 sm:h-6 sm:w-6 text-red-500 mr-2 sm:mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <div class="flex-1">
              <h3 class="text-base sm:text-lg font-semibold text-red-900">Error al enviar la petición</h3>
              <p class="text-xs sm:text-sm text-red-700 mt-1 break-words">{{ error }}</p>
            </div>
          </div>
        </div>
      </transition>

      <!-- Información inicial - Mobile optimized -->
      <div class="mb-4 sm:mb-8 bg-blue-50 border-l-4 border-blue-500 p-3 sm:p-5 rounded-r-lg">
        <div class="flex items-start">
          <svg class="h-5 w-5 sm:h-6 sm:w-6 text-blue-500 mr-2 sm:mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
          </svg>
          <div>
            <h3 class="text-xs sm:text-sm font-semibold text-blue-900">Información sobre tu solicitud</h3>
            <p class="text-xs sm:text-sm text-blue-700 mt-1">
              Completa este formulario para solicitar material. Tu petición será revisada por el equipo de gestión 
              y recibirás una respuesta por email en el menor tiempo posible.
            </p>
          </div>
        </div>
      </div>

      <!-- Formulario Principal -->
      <form @submit.prevent="enviarPeticion" class="space-y-4 sm:space-y-6">
        <!-- Paso 1: Datos del Solicitante -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
          <div class="bg-gradient-to-r from-junta-green-600 to-junta-green-700 px-4 py-3 sm:px-6 sm:py-4">
            <h2 class="text-base sm:text-lg font-bold text-white flex items-center">
              <span class="bg-white text-junta-green-600 rounded-full w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center mr-2 sm:mr-3 font-bold text-sm sm:text-base">1</span>
              Datos del Solicitante
            </h2>
          </div>
          
          <div class="p-4 sm:p-6 space-y-4 sm:space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
              <div>
                <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                  Nombre completo <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.usuario_solicitante"
                  type="text"
                  required
                  placeholder="Ej: Juan Pérez García"
                  class="w-full px-4 py-3 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 transition-colors"
                  style="min-height: 48px; font-size: 16px;"
                  autocomplete="name"
                  inputmode="text"
                />
              </div>
              
              <div>
                <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                  Email corporativo <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.email_solicitante"
                  type="email"
                  required
                  placeholder="tu.email@juntadeandalucia.es"
                  class="w-full px-4 py-3 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 transition-colors"
                  style="min-height: 48px; font-size: 16px;"
                  autocomplete="email"
                  inputmode="email"
                />
              </div>
              
              <div>
                <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                  Teléfono de contacto
                </label>
                <input
                  v-model="form.telefono_solicitante"
                  type="tel"
                  placeholder="Ej: 955 123 456"
                  class="w-full px-4 py-3 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 transition-colors"
                  style="min-height: 48px; font-size: 16px;"
                  autocomplete="tel"
                  inputmode="tel"
                />
              </div>

              <!-- Provincia -->
              <div>
                <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                  Provincia
                </label>
                <select
                  v-model="provinciaSeleccionadaSolicitante"
                  @change="manejarCambioProvincia"
                  class="w-full px-4 py-3 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 transition-colors bg-white"
                  style="min-height: 48px; font-size: 16px;"
                >
                  <option value="">Selecciona una provincia</option>
                  <option v-for="provincia in provincias" :key="provincia.id" :value="provincia.id">
                    {{ provincia.nombre }}
                  </option>
                </select>
              </div>

              <!-- Sede -->
              <div>
                <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                  Sede
                </label>
                <select
                  v-model="sedeSeleccionada"
                  @change="manejarCambioSede"
                  class="w-full px-4 py-3 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 transition-colors bg-white"
                  style="min-height: 48px; font-size: 16px;"
                >
                  <option value="">Selecciona una sede</option>
                  <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                    {{ sede.nombre }}
                  </option>
                </select>
              </div>

              <!-- Departamento -->
              <div v-if="departamentos.length > 0">
                <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                  Departamento
                </label>
                <select
                  v-model="departamentoSeleccionado"
                  class="w-full px-4 py-3 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 transition-colors bg-white"
                  style="min-height: 48px; font-size: 16px;"
                >
                  <option value="">Selecciona un departamento</option>
                  <option v-for="dept in departamentos" :key="dept.id" :value="dept.id">
                    {{ dept.nombre }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Campos personalizados -->
            <div v-if="camposPersonalizados.length" class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 pt-3 sm:pt-4 border-t">
              <div v-for="campo in camposPersonalizados" :key="campo.id">
                <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                  {{ campo.label }}
                  <span v-if="campo.requerido" class="text-red-500">*</span>
                </label>
                
                <!-- Campo de texto -->
                <input 
                  v-if="campo.tipo === 'text'"
                  v-model="form.campos_personalizados[campo.nombre]"
                  type="text"
                  :required="campo.requerido"
                  :placeholder="campo.placeholder"
                  class="w-full px-3 py-3 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 transition-colors"
                />
                
                <!-- Campo select -->
                <select 
                  v-else-if="campo.tipo === 'select'"
                  v-model="form.campos_personalizados[campo.nombre]"
                  :required="campo.requerido"
                  class="w-full px-3 py-3 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 transition-colors bg-white"
                >
                  <option value="">Selecciona una opción</option>
                  <option v-for="opcion in campo.opciones_select" :key="opcion" :value="opcion">
                    {{ opcion }}
                  </option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Paso 2: Elegir Almacén de Pedido -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
          <div class="bg-gradient-to-r from-junta-green-600 to-junta-green-700 px-4 py-3 sm:px-6 sm:py-4">
            <h2 class="text-base sm:text-lg font-bold text-white flex items-center">
              <span class="bg-white text-junta-green-600 rounded-full w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center mr-2 sm:mr-3 font-bold text-sm sm:text-base">2</span>
              Elegir Almacén de Pedido
            </h2>
          </div>
          
          <div class="p-4 sm:p-6 space-y-4 sm:space-y-5">
            <!-- Almacén seleccionado -->
            <div v-if="almacenSeleccionado" class="bg-junta-green-50 border-2 border-junta-green-200 rounded-lg p-4">
              <div class="flex items-start">
                <div class="bg-junta-green-600 text-white rounded-lg p-2 mr-3">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                  </svg>
                </div>
                <div class="flex-1">
                  <div class="text-sm font-semibold text-junta-green-800">Almacén seleccionado</div>
                  <div class="text-base font-bold text-junta-green-900">{{ almacenSeleccionado.nombre }}</div>
                  <div class="text-sm text-junta-green-700">{{ almacenSeleccionado.direccion }}</div>
                  <div v-if="almacenSeleccionado.provincia" class="text-xs text-junta-green-600 mt-1">
                    Provincia: {{ almacenSeleccionado.provincia }}
                  </div>
                </div>
                <button
                  type="button"
                  @click="almacenSeleccionado = null; form.value.almacen_id = ''; mostrarPaso3 = false;"
                  class="text-red-600 hover:text-red-800 font-medium text-sm flex items-center transition-colors">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Mapa de Leaflet -->
            <div class="space-y-4">
              <div class="text-sm font-semibold text-gray-700">
                Selecciona un almacén en el mapa <span class="text-red-500">*</span>
              </div>
              
              <!-- Selectores desplegables -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-semibold text-gray-700 mb-2">Filtrar por provincia</label>
                  <select
                    v-model="provinciaSeleccionada"
                    @change="filtrarPorProvincia"
                    class="w-full px-4 py-3 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 transition-colors bg-white">
                    <option value="">Todas las provincias</option>
                    <option v-for="prov in provinciasMapa" :key="prov" :value="prov">{{ prov }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-700 mb-2">Seleccionar almacén</label>
                  <select
                    v-model="almacenTemporal"
                    @change="seleccionarAlmacenDesdeSelect"
                    class="w-full px-4 py-3 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 transition-colors bg-white">
                    <option value="">Selecciona un almacén</option>
                    <option v-for="alm in almacenesFiltrados" :key="alm.id" :value="alm.id">
                      {{ alm.nombre }} ({{ alm.provincia }})
                    </option>
                  </select>
                </div>
              </div>

              <!-- Contenedor del mapa -->
              <div id="mapa-almacenes" class="h-96 rounded-lg border-2 border-gray-300 overflow-hidden"></div>
              
              <div class="text-xs text-gray-500 text-center">
                Haz clic en los marcadores del mapa para seleccionar un almacén
              </div>
            </div>
          </div>
        </div>

        <!-- Paso 3: Material Solicitado - Mobile optimized -->
        <div v-if="mostrarPaso3" id="paso-3-material" class="bg-white rounded-xl shadow-md overflow-hidden">
          <div class="bg-gradient-to-r from-junta-green-600 to-junta-green-700 px-4 py-3 sm:px-6 sm:py-4">
            <h2 class="text-base sm:text-lg font-bold text-white flex items-center">
              <span class="bg-white text-junta-green-600 rounded-full w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center mr-2 sm:mr-3 font-bold text-sm sm:text-base">3</span>
              Material Solicitado
            </h2>
          </div>
          
          <div class="p-4 sm:p-6 space-y-4 sm:space-y-5">
            <!-- Navegación por categorías -->
            <div v-if="!categoriaSeleccionada">
              <div class="flex items-center justify-between mb-4">
                <label class="block text-sm font-semibold text-gray-700">
                  Explorar por categorías
                </label>
                <button 
                  type="button"
                  @click="mostrarBuscador = true" 
                  class="text-xs text-junta-green-600 hover:text-junta-green-700 font-medium flex items-center gap-1">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                  </svg>
                  Buscar directamente
                </button>
              </div>

              <!-- Grid de categorías -->
              <div v-if="categorias.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 sm:gap-4">
                <div 
                  v-for="cat in categorias" 
                  :key="cat.id"
                  @click="seleccionarCategoria(cat)"
                  class="bg-white border-2 border-gray-200 rounded-xl p-3 sm:p-4 cursor-pointer hover:border-junta-green-500 hover:shadow-lg transition-all duration-200 group">
                  
                  <!-- Imagen de categoría -->
                  <div class="aspect-square bg-gray-100 rounded-lg mb-3 overflow-hidden">
                    <img 
                      v-if="cat.imagen" 
                      :src="`/gestionmaterial/storage/categorias/${cat.imagen}`" 
                      :alt="cat.nombre"
                      class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-200"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                      <svg class="w-8 h-8 sm:w-12 sm:h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                      </svg>
                    </div>
                  </div>

                  <!-- Nombre y descripción -->
                  <h3 class="font-semibold text-gray-900 text-sm text-center mb-1 group-hover:text-junta-green-600 transition-colors">
                    {{ cat.nombre }}
                  </h3>
                  <p v-if="cat.descripcion" class="text-xs text-gray-500 text-center line-clamp-2">
                    {{ cat.descripcion }}
                  </p>
                  <div class="text-xs text-junta-green-600 text-center mt-2 font-medium">
                    {{ cat.entidades_count || 0 }} artículos
                  </div>
                </div>
              </div>

              <!-- Si no hay categorías -->
              <div v-else class="text-center py-8 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <p>No hay categorías disponibles</p>
                <button 
                  type="button"
                  @click="mostrarBuscador = true" 
                  class="mt-3 text-sm text-junta-green-600 hover:text-junta-green-700 font-medium">
                  Usar buscador →
                </button>
              </div>
            </div>

            <!-- Vista de materiales de una categoría -->
            <div v-else>
              <div class="flex items-center justify-between mb-4">
                <button 
                  type="button"
                  @click="volverACategorias" 
                  class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 font-medium">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                  </svg>
                  Volver a categorías
                </button>
                <div class="text-sm font-semibold text-gray-700">
                  {{ categoriaSeleccionada.nombre }}
                </div>
              </div>

              <!-- Grid de materiales de la categoría - Estilo Tienda Online -->
              <div v-if="materialesCategoria.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div 
                  v-for="mat in materialesCategoria" 
                  :key="mat.id"
                  class="bg-white border-2 border-gray-200 rounded-xl overflow-hidden hover:border-junta-green-500 hover:shadow-xl transition-all duration-300 group">
                  
                  <!-- Imagen del producto -->
                  <div 
                    class="relative aspect-square bg-gradient-to-br from-gray-50 to-gray-100 overflow-hidden cursor-pointer"
                    @click="verImagenGrande(mat)">
                    <img 
                      v-if="mat.foto" 
                      :src="`/gestionmaterial/storage/${mat.foto}`" 
                      :alt="mat.referencia"
                      class="w-full h-full object-contain p-4 group-hover:scale-110 transition-transform duration-300"
                      @error="onImageError"
                    />
                    <div v-else class="w-full h-full flex flex-col items-center justify-center text-gray-300">
                      <svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                      </svg>
                      <span class="text-sm">Sin imagen</span>
                    </div>
                    
                    <!-- Badge de stock -->
                    <div v-if="(mat.stock_actual || 0) > 0" class="absolute top-3 right-3 bg-junta-green-500 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg">
                      En stock
                    </div>
                    <div v-else-if="mat.prevision_llegada" class="absolute top-3 right-3 bg-orange-500 text-white px-2 py-1 rounded-full text-xs font-bold shadow-lg">
                      📅 {{ mat.prevision_llegada_texto }}
                    </div>
                    <div v-else class="absolute top-3 right-3 bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg">
                      Agotado
                    </div>

                    <!-- Botón ver imagen -->
                    <div v-if="mat.foto" class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
                      <div class="bg-white text-gray-900 px-4 py-2 rounded-lg font-medium text-sm transform scale-75 group-hover:scale-100 transition-transform">
                        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Ver imagen
                      </div>
                    </div>
                  </div>

                  <!-- Información del producto -->
                  <div class="p-4">
                    <h3 class="font-bold text-gray-900 text-base mb-1 line-clamp-1">{{ mat.referencia }}</h3>
                    <p class="text-sm text-gray-600 mb-3 line-clamp-2 min-h-[40px]">{{ mat.nombre }}</p>
                    
                    <div class="flex items-center justify-between mb-3 text-sm">
                      <span class="text-gray-500">Unidad: <span class="font-medium text-gray-700">{{ mat.unidad || 'ud' }}</span></span>
                      <span v-if="(mat.stock_actual || 0) > 0" class="font-semibold text-junta-green-600">
                        Disponible
                      </span>
                      <span v-else class="font-semibold text-red-600">
                        No disponible
                      </span>
                    </div>

                    <!-- Botón agregar (con stock o sin stock) -->
                    <button
                      type="button"
                      @click="seleccionarMaterial(mat)"
                      :class="(mat.stock_actual || 0) > 0 
                        ? 'bg-junta-green-600 hover:bg-junta-green-700' 
                        : 'bg-orange-500 hover:bg-orange-600'"
                      class="w-full py-2.5 rounded-lg font-semibold text-sm transition-all duration-200 flex items-center justify-center gap-2 text-white hover:shadow-lg active:scale-95">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                      </svg>
                      {{ (mat.stock_actual || 0) > 0 ? 'Agregar a solicitud' : '🔔 Solicitar y agregar' }}
                    </button>
                  </div>
                </div>
              </div>

              <!-- Si la categoría no tiene materiales -->
              <div v-else class="text-center py-8 text-gray-500">
                <p>No hay materiales en esta categoría</p>
              </div>
            </div>

            <!-- Buscador de material con icono (solo si se activa) -->
            <div v-if="mostrarBuscador || categoriaSeleccionada">
              <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-semibold text-gray-700">
                  Buscar material <span class="text-red-500">*</span>
                </label>
                <button 
                  v-if="!categoriaSeleccionada && mostrarBuscador"
                  type="button"
                  @click="mostrarBuscador = false" 
                  class="text-xs text-gray-600 hover:text-gray-900 font-medium">
                  ← Volver a categorías
                </button>
              </div>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                  <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                  </svg>
                </div>
                <input
                  v-model="busqueda"
                  type="text"
                  placeholder="Busca por nombre, referencia o descripción..."
                  class="w-full pl-12 pr-4 py-3 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 transition-colors"
                  style="min-height: 48px; font-size: 16px;"
                  @input="filtrarMateriales"
                  @focus="mostrarResultados = true"
                  autocomplete="off"
                  inputmode="search"
                />
              </div>
            </div>

            <!-- Lista de materiales encontrados - Estilo Tienda -->
            <transition name="slide-fade">
              <div v-if="busqueda && materialesFiltrados.length && mostrarResultados" 
                   class="border border-gray-200 rounded-lg max-h-96 overflow-y-auto shadow-lg bg-white">
                <div 
                  v-for="mat in materialesFiltrados" 
                  :key="mat.id"
                  class="p-3 hover:bg-junta-green-50 cursor-pointer border-b last:border-b-0 transition-colors">
                  <div class="flex items-center gap-3">
                    <!-- Imagen miniatura -->
                    <div 
                      class="w-16 h-16 bg-gray-100 rounded-lg flex-shrink-0 overflow-hidden cursor-pointer"
                      @click.stop="verImagenGrande(mat)">
                      <img 
                        v-if="mat.foto" 
                        :src="`/gestionmaterial/storage/${mat.foto}`" 
                        :alt="mat.referencia"
                        class="w-full h-full object-cover hover:scale-110 transition-transform"
                        @error="onImageError"
                      />
                      <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                      </div>
                    </div>

                    <!-- Info del producto -->
                    <div class="flex-1 min-w-0" @click="seleccionarMaterial(mat)">
                      <div class="font-semibold text-gray-900">{{ mat.referencia }}</div>
                      <div class="text-sm text-gray-700 mt-1 truncate">{{ mat.nombre }}</div>
                      <div v-if="mat.descripcion" class="text-xs text-gray-500 mt-1 line-clamp-1">{{ mat.descripcion }}</div>
                      <div class="flex items-center gap-2 mt-2">
                        <span v-if="(mat.stock_actual || 0) > 0" class="text-xs bg-junta-green-100 text-junta-green-700 px-2 py-1 rounded-full font-medium">
                          Disponible
                        </span>
                        <span v-else class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded-full font-medium">
                          No disponible
                        </span>
                        <span class="text-xs text-gray-500">{{ mat.unidad || 'ud' }}</span>
                      </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex flex-col gap-1">
                      <button
                        type="button"
                        v-if="mat.foto"
                        @click.stop="verImagenGrande(mat)"
                        class="p-2 text-gray-500 hover:text-junta-green-600 hover:bg-gray-100 rounded transition-colors"
                        title="Ver imagen">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                      </button>
                      <button
                        type="button"
                        @click.stop="seleccionarMaterial(mat)"
                        class="p-2 text-junta-green-600 hover:bg-junta-green-100 rounded transition-colors"
                        title="Agregar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </transition>

            <!-- Lista de referencias disponibles (si no hay búsqueda) -->
            <div v-if="!busqueda && materialesSeleccionados.length === 0" class="mt-4">
              <div class="text-sm font-semibold text-gray-700 mb-3">
                Referencias disponibles en stock ({{ materialesConStock.length }})
              </div>
              <div class="border border-gray-200 rounded-lg max-h-96 overflow-y-auto bg-gray-50">
                <div 
                  v-for="mat in materialesConStock.slice(0, 50)" 
                  :key="mat.id"
                  @click="seleccionarMaterial(mat)"
                  class="p-3 hover:bg-white cursor-pointer border-b last:border-b-0 transition-colors">
                  <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-3">
                        <div class="font-medium text-gray-900 text-sm">{{ mat.referencia }}</div>
                        <div class="text-xs bg-junta-green-100 text-junta-green-700 px-2 py-1 rounded-full font-medium">
                          Disponible
                        </div>
                      </div>
                      <div class="text-sm text-gray-600 mt-1 truncate">{{ mat.nombre }}</div>
                    </div>
                    <svg class="h-4 w-4 text-gray-400 flex-shrink-0 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                  </div>
                </div>
                <div v-if="materialesConStock.length > 50" class="p-3 text-center text-sm text-gray-500 bg-gray-100">
                  Mostrando 50 de {{ materialesConStock.length }} referencias. Usa el buscador para encontrar más.
                </div>
                <div v-if="materialesConStock.length === 0" class="p-6 text-center text-gray-500">
                  No hay materiales disponibles en stock
                </div>
              </div>
            </div>

            <!-- Materiales seleccionados -->
            <transition-group name="fade" tag="div" class="space-y-3 mt-4">
              <div v-for="(mat, index) in materialesSeleccionados" :key="mat.id" 
                   :class="mat.sin_stock 
                     ? 'bg-orange-50 border-2 border-orange-200' 
                     : 'bg-junta-green-50 border-2 border-junta-green-200'"
                   class="rounded-lg p-4">
                <div class="flex items-start justify-between mb-3">
                  <div class="flex items-start flex-1">
                    <div 
                      :class="mat.sin_stock ? 'bg-orange-500' : 'bg-junta-green-600'"
                      class="text-white rounded-lg p-2 mr-3">
                      <svg v-if="mat.sin_stock" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                      </svg>
                      <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                      </svg>
                    </div>
                    <div class="flex-1">
                      <div class="flex items-center gap-2">
                        <div class="text-xs font-semibold text-gray-600">MATERIAL #{{ index + 1 }}</div>
                        <span v-if="mat.sin_stock" class="text-xs bg-orange-500 text-white px-2 py-0.5 rounded-full font-semibold">
                          🔔 Sin stock - Solicitud de reposición
                        </span>
                        <span v-else class="text-xs bg-junta-green-500 text-white px-2 py-0.5 rounded-full font-semibold">
                          ✓ Con stock
                        </span>
                      </div>
                      <div class="text-base font-bold text-gray-900 mt-1">{{ mat.referencia }}</div>
                      <div class="text-sm text-gray-700">{{ mat.nombre }}</div>
                      <div v-if="mat.descripcion" class="text-xs text-gray-600 mt-1">{{ mat.descripcion }}</div>
                    </div>
                  </div>
                  <button 
                    type="button" 
                    @click="eliminarMaterial(index)" 
                    class="text-red-600 hover:text-red-800 font-medium text-sm flex items-center transition-colors ml-3">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </button>
                </div>

                <div class="grid md:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                      Cantidad solicitada <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model.number="mat.cantidad"
                      type="number"
                      min="1"
                      required
                      class="w-full px-3 py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 transition-colors"
                      inputmode="numeric"
                      pattern="[0-9]*"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Unidad</label>
                    <input 
                      :value="mat.unidad" 
                      type="text"
                      readonly
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600" 
                    />
                  </div>
                </div>
              </div>
            </transition-group>

            <!-- Mensaje cuando hay materiales seleccionados -->
            <div v-if="materialesSeleccionados.length > 0" class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <span class="text-sm font-medium text-blue-900">
                    {{ materialesSeleccionados.length }} {{ materialesSeleccionados.length === 1 ? 'material seleccionado' : 'materiales seleccionados' }}
                  </span>
                </div>
                <span class="text-xs text-blue-700">Puedes agregar más materiales usando el buscador</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Paso 4: Justificación/Observaciones -->
        <div v-if="mostrarPaso3" class="bg-white rounded-xl shadow-md overflow-hidden">
          <div class="bg-gradient-to-r from-junta-green-600 to-junta-green-700 px-6 py-4">
            <h2 class="text-lg font-bold text-white flex items-center">
              <span class="bg-white text-junta-green-600 rounded-full w-8 h-8 flex items-center justify-center mr-3 font-bold">4</span>
              Justificación de la Solicitud / Observaciones
            </h2>
          </div>
          
          <div class="p-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              Justificación de la petición / Observaciones
              <span class="text-gray-500 font-normal">(opcional)</span>
            </label>
            <textarea
              v-model="form.justificacion"
              rows="5"
              maxlength="1000"
              placeholder="Si lo deseas, explica brevemente por qué necesitas este material, para qué proyecto o tarea lo vas a utilizar, o añade cualquier observación relevante..."
              class="w-full px-4 py-3 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 transition-colors resize-none"
              style="min-height: 120px; font-size: 16px;"
            ></textarea>
            <div class="flex justify-between items-center mt-2">
              <p class="text-xs text-gray-500">
                Campo opcional. Añade cualquier información adicional que consideres relevante.
              </p>
              <span class="text-sm text-gray-600">
                {{ form.justificacion.length }}/1000
              </span>
            </div>
          </div>
        </div>

        <!-- Botones de acción optimizados para móviles -->
        <div class="flex flex-col sm:flex-row justify-end gap-3 sm:gap-4 pt-4">
          <button
            type="button"
            @click="limpiarFormulario"
            class="w-full sm:w-auto px-6 py-4 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors"
            style="min-height: 48px; font-size: 16px;">
            Limpiar formulario
          </button>
          <button
            type="submit"
            :disabled="!puedeEnviar || enviando"
            class="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-junta-green-600 to-junta-green-700 text-white rounded-lg hover:from-junta-green-700 hover:to-junta-green-800 disabled:from-gray-400 disabled:to-gray-400 disabled:cursor-not-allowed font-bold shadow-lg hover:shadow-xl transition-all flex items-center justify-center"
            style="min-height: 48px; font-size: 16px;">
            <svg v-if="!enviando" class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
            </svg>
            <svg v-else class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ enviando ? 'Enviando petición...' : 'Enviar Petición' }}
          </button>
        </div>
      </form>

      <!-- Información de contacto -->
      <div class="mt-8 bg-gray-50 rounded-lg p-6 border border-gray-200">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">¿Necesitas ayuda?</h3>
        <p class="text-sm text-gray-600">
          Si tienes alguna duda sobre el proceso de solicitud de material, 
          contacta con el departamento de gestión de material.
        </p>
      </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12 py-6">
      <div class="container mx-auto px-4 text-center text-sm text-gray-600">
        <p>© {{ new Date().getFullYear() }} Junta de Andalucía - Agencia Digital de Andalucía</p>
        <p class="mt-1">Sistema de Gestión de Material</p>
      </div>
    </footer>

    <!-- Modal para ver imagen en grande -->
    <transition name="modal-fade">
      <div v-if="imagenModal.visible" 
           class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-75 backdrop-blur-sm"
           @click="cerrarImagenModal">
        <div class="relative max-w-4xl w-full bg-white rounded-2xl shadow-2xl overflow-hidden" @click.stop>
          <!-- Header del modal -->
          <div class="bg-gradient-to-r from-junta-green-600 to-junta-green-700 px-6 py-4 flex items-center justify-between">
            <div>
              <h3 class="text-lg font-bold text-white">{{ imagenModal.material?.referencia }}</h3>
              <p class="text-sm text-junta-green-100">{{ imagenModal.material?.nombre }}</p>
            </div>
            <button 
              @click="cerrarImagenModal"
              class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-all">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Imagen grande -->
          <div class="bg-gradient-to-br from-gray-50 to-gray-100 p-8">
            <img 
              v-if="imagenModal.material?.foto"
              :src="`/gestionmaterial/storage/${imagenModal.material.foto}`"
              :alt="imagenModal.material?.referencia"
              class="w-full h-auto max-h-[60vh] object-contain rounded-lg shadow-lg"
            />
          </div>

          <!-- Información adicional -->
          <div class="p-6 bg-white border-t">
            <div class="grid grid-cols-2 gap-4 mb-4">
              <div>
                <span class="text-sm text-gray-500">Unidad:</span>
                <span class="text-sm font-semibold text-gray-900 ml-2">{{ imagenModal.material?.unidad || 'ud' }}</span>
              </div>
              <div>
                <span class="text-sm text-gray-500">Disponibilidad:</span>
                <span v-if="(imagenModal.material?.stock_actual || 0) > 0" class="text-sm font-semibold text-junta-green-600 ml-2">
                  En stock
                </span>
                <span v-else class="text-sm font-semibold text-red-600 ml-2">
                  No disponible
                </span>
              </div>
            </div>
            
            <div v-if="imagenModal.material?.descripcion" class="mb-4">
              <p class="text-sm text-gray-500 mb-1">Descripción:</p>
              <p class="text-sm text-gray-700">{{ imagenModal.material.descripcion }}</p>
            </div>

            <!-- Botón agregar desde el modal -->
            <button
              type="button"
              @click="seleccionarMaterialDesdeModal"
              :disabled="(imagenModal.material?.stock_actual || 0) === 0"
              class="w-full py-3 rounded-lg font-semibold transition-all duration-200 flex items-center justify-center gap-2"
              :class="(imagenModal.material?.stock_actual || 0) > 0 
                ? 'bg-junta-green-600 text-white hover:bg-junta-green-700 hover:shadow-lg' 
                : 'bg-gray-200 text-gray-500 cursor-not-allowed'">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              {{ (imagenModal.material?.stock_actual || 0) > 0 ? 'Agregar a mi solicitud' : 'No disponible' }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { usePWA } from '../composables/usePWA';

const axios = window.axios; // Usar la instancia configurada de axios
const { isPWA } = usePWA();

const busqueda = ref('');
const materiales = ref([]);
const materialesFiltrados = ref([]);
const materialesSeleccionados = ref([]);
const mostrarResultados = ref(false);
const mostrarBuscador = ref(false);
const enviando = ref(false);
const success = ref(false);
const error = ref('');

// Modal de imagen
const imagenModal = ref({
  visible: false,
  material: null
});

// Categorías
const categorias = ref([]);
const categoriaSeleccionada = ref(null);
const materialesCategoria = ref([]);

// Datos de configuración
const sedes = ref([]);
const departamentos = ref([]);
const camposPersonalizados = ref([]);
const provincias = ref([]); // Para el solicitante
const provinciaSeleccionadaSolicitante = ref('');
const sedeSeleccionada = ref('');
const departamentoSeleccionado = ref('');

// Variables para el mapa y almacén
const almacenes = ref([]);
const provinciasMapa = ref([]); // Para el mapa (evitar duplicación)
const almacenSeleccionado = ref(null);
const mostrarPaso3 = ref(false);
const provinciaSeleccionada = ref(''); // Para filtrar mapa
const almacenTemporal = ref('');
const mapa = ref(null);
const marcadores = ref([]);

const form = ref({
  justificacion: '',
  usuario_solicitante: '',
  email_solicitante: '',
  telefono_solicitante: '',
  sede_id: '',
  departamento_id: '',
  almacen_id: '',
  campos_personalizados: {}
});

const puedeEnviar = computed(() => {
  // Validación básica
  const basico = materialesSeleccionados.value.length > 0 &&
         form.value.usuario_solicitante.trim() &&
         form.value.email_solicitante.trim() &&
         form.value.almacen_id; // Almacén es obligatorio
  
  if (!basico) return false;

  // Verificar que todos los materiales tengan cantidad > 0
  const todasCantidadesValidas = materialesSeleccionados.value.every(m => m.cantidad > 0);
  if (!todasCantidadesValidas) return false;

  // Validar campos requeridos de sede y departamento
  if (campoObligatorio('sede') && !form.value.sede_id) return false;
  if (campoObligatorio('departamento') && !form.value.departamento_id) return false;

  // Validar campos personalizados requeridos
  if (Array.isArray(camposPersonalizados.value)) {
    for (const campo of camposPersonalizados.value) {
      if (campo.requerido && !form.value.campos_personalizados[campo.nombre]) {
        return false;
      }
    }
  }

  return true;
});

const campoObligatorio = (campo) => {
  // Por ahora, sede y departamento son opcionales
  // Puedes cambiar esto según tus necesidades
  return false;
};

const cargarMateriales = async () => {
  try {
    const response = await axios.get('/materiales-disponibles');
    materiales.value = response.data;
  } catch (err) {
    console.error('Error al cargar materiales:', err);
    error.value = 'No se pudieron cargar los materiales disponibles';
    materiales.value = []; // Asegurar que sea un array
  }
};

const materialesConStock = computed(() => {
  if (!Array.isArray(materiales.value)) return [];
  
  return materiales.value
    .filter(m => (m.stock_actual || 0) > 0)
    .sort((a, b) => {
      // Ordenar por stock descendente
      const stockA = a.stock_actual || 0;
      const stockB = b.stock_actual || 0;
      if (stockB !== stockA) return stockB - stockA;
      // Luego por referencia alfabéticamente
      return (a.referencia || '').localeCompare(b.referencia || '');
    });
});

const sedeNombre = computed(() => {
  const sede = sedes.value.find(s => s.id == form.value.sede_id);
  return sede ? sede.nombre : '';
});

const departamentoNombre = computed(() => {
  const dept = departamentos.value.find(d => d.id == form.value.departamento_id);
  return dept ? dept.nombre : '';
});

const cargarSedes = async () => {
  try {
    const response = await axios.get('/sedes-publicas');
    if (response.data.success) {
      sedes.value = response.data.data;
    } else {
      sedes.value = response.data;
    }
  } catch (err) {
    console.error('Error al cargar sedes:', err);
    sedes.value = []; // Asegurar que sea un array
  }
};

// Cargar provincias para el solicitante
const cargarProvincias = async () => {
  try {
    const response = await axios.get('/provincias');
    if (response.data.success) {
      provincias.value = response.data.data;
    } else {
      provincias.value = response.data;
    }
  } catch (err) {
    console.error('Error al cargar provincias:', err);
    provincias.value = []; // Asegurar que sea un array
  }
};

// Cargar departamentos según la sede seleccionada (para el solicitante)
const cargarDepartamentosSolicitante = async () => {
  if (!sedeSeleccionada.value) {
    departamentos.value = [];
    return;
  }

  try {
    const response = await axios.get(`/sedes-publicas/${sedeSeleccionada.value}/departamentos`);
    departamentos.value = response.data || [];
  } catch (err) {
    console.error('Error al cargar departamentos:', err);
    departamentos.value = []; // Asegurar que sea un array
  }
};

// Manejar cambio de provincia
const manejarCambioProvincia = () => {
  sedeSeleccionada.value = '';
  departamentoSeleccionado.value = '';
  departamentos.value = [];
  cargarSedesPorProvincia();
  cargarDepartamentosSolicitante();
};

// Manejar cambio de sede
const manejarCambioSede = () => {
  departamentoSeleccionado.value = '';
  departamentos.value = [];
  cargarDepartamentosSolicitante();
};

// Cargar sedes por provincia
const cargarSedesPorProvincia = async () => {
  if (!provinciaSeleccionadaSolicitante.value) {
    sedes.value = [];
    return;
  }

  try {
    const response = await axios.get(`/sedes-por-provincia?provincia_id=${provinciaSeleccionadaSolicitante.value}`);
    if (response.data.success) {
      sedes.value = response.data.data;
    } else {
      sedes.value = response.data;
    }
  } catch (err) {
    console.error('Error al cargar sedes por provincia:', err);
    sedes.value = [];
  }
};


const cargarCamposPersonalizados = async () => {
  try {
    const response = await axios.get('/custom-fields-publicos', {
      params: { entidad: 'peticiones' }
    });
    const data = response.data.success ? response.data.data : response.data;
    camposPersonalizados.value = (Array.isArray(data) ? data : []).filter(c => c.activo);
  } catch (err) {
    console.error('Error al cargar campos personalizados:', err);
  }
};

// Cargar categorías
const cargarCategorias = async () => {
  try {
    const response = await axios.get('/categorias-publicas');
    if (response.data.success) {
      categorias.value = response.data.data;
    } else {
      categorias.value = [];
    }
  } catch (err) {
    console.error('Error al cargar categorías:', err);
    categorias.value = [];
  }
};

// Cargar almacenes para el mapa
const cargarAlmacenes = async () => {
  try {
    const response = await axios.get('/almacenes-publicos');
    almacenes.value = response.data.almacenes || [];
    provinciasMapa.value = response.data.provincias || [];
  } catch (err) {
    console.error('Error al cargar almacenes:', err);
    almacenes.value = [];
    provinciasMapa.value = [];
  }
};

// Manejar selección de almacén desde el mapa
const manejarSeleccionAlmacenDesdeMapa = (almacen) => {
  almacenSeleccionado.value = almacen;
  form.value.almacen_id = almacen.id;
  mostrarPaso3.value = true;
  
  // Scroll suave al siguiente paso
  setTimeout(() => {
    const paso3 = document.getElementById('paso-3-material');
    if (paso3) {
      paso3.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }, 300);
};

// Watch para cuando se selecciona un almacén
watch(almacenSeleccionado, (nuevoValor) => {
  if (nuevoValor) {
    form.value.almacen_id = nuevoValor.id;
    mostrarPaso3.value = true;
  } else {
    form.value.almacen_id = '';
    mostrarPaso3.value = false;
  }
});

// Funciones adicionales para el mapa
const almacenesFiltrados = computed(() => {
  if (!provinciaSeleccionada.value) {
    return almacenes.value;
  }
  return almacenes.value.filter(alm => alm.provincia === provinciaSeleccionada.value);
});

const filtrarPorProvincia = () => {
  // Filtrar almacenes en el mapa cuando se selecciona una provincia
  if (mapa.value) {
    // Limpiar marcadores existentes
    marcadores.value.forEach(marker => {
      mapa.value.removeLayer(marker);
    });
    marcadores.value = [];

    // Añadir marcadores filtrados
    almacenesFiltrados.value.forEach(almacen => {
      if (almacen.lat && almacen.lng) {
        const marker = L.marker([almacen.lat, almacen.lng])
          .addTo(mapa.value)
          .bindPopup(`
            <div class="p-2">
              <h4 class="font-bold text-sm">${almacen.nombre}</h4>
              <p class="text-xs text-gray-600">${almacen.direccion}</p>
              <p class="text-xs text-gray-500">${almacen.provincia}</p>
              <button
                onclick="window.seleccionarAlmacenDesdeMapa(${almacen.id})"
                class="mt-2 bg-junta-green-600 text-white px-3 py-1 rounded text-xs hover:bg-junta-green-700">
                Seleccionar
              </button>
            </div>
          `);
        
        marker.on('click', () => {
          manejarSeleccionAlmacenDesdeMapa(almacen);
        });
        
        marcadores.value.push(marker);
      }
    });
  }
};

const seleccionarAlmacenDesdeSelect = () => {
  if (almacenTemporal.value) {
    const almacen = almacenes.value.find(alm => alm.id == almacenTemporal.value);
    if (almacen) {
      manejarSeleccionAlmacenDesdeMapa(almacen);
    }
  }
};

// Hacer disponible la función globalmente para el popup
window.seleccionarAlmacenDesdeMapa = (almacenId) => {
  const almacen = almacenes.value.find(alm => alm.id === almacenId);
  if (almacen) {
    manejarSeleccionAlmacenDesdeMapa(almacen);
  }
};

// Inicializar el mapa cuando se carguen los almacenes
watch(almacenes, (nuevosAlmacenes) => {
  if (nuevosAlmacenes.length > 0 && !mapa.value) {
    // Inicializar mapa después de que el DOM esté listo
    setTimeout(() => {
      // Verificar que Leaflet esté disponible
      if (typeof L !== 'undefined') {
        mapa.value = L.map('mapa-almacenes').setView([37.5, -4.5], 7);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '© OpenStreetMap contributors'
        }).addTo(mapa.value);
        
        // Agrupar almacenes por provincia para distribuir las etiquetas
        const almacenesPorProvincia = {};
        almacenes.value.forEach(almacen => {
          if (!almacenesPorProvincia[almacen.provincia]) {
            almacenesPorProvincia[almacen.provincia] = [];
          }
          almacenesPorProvincia[almacen.provincia].push(almacen);
        });
        
        // Añadir almacenes distribuidos para evitar superposición
        Object.keys(almacenesPorProvincia).forEach(provincia => {
          const almacenesDeProvincia = almacenesPorProvincia[provincia];
          const latCentral = almacenesDeProvincia[0].lat;
          const lngCentral = almacenesDeProvincia[0].lng;
          
          almacenesDeProvincia.forEach((almacen, index) => {
            if (almacen.lat && almacen.lng) {
              // Calcular offset para evitar superposición
              const offset = calcularOffsetParaEtiqueta(index, almacenesDeProvincia.length);
              const latAjustada = latCentral + offset.lat;
              const lngAjustada = lngCentral + offset.lng;
              
              // Crear icono personalizado para los almacenes
              const iconoAlmacen = L.divIcon({
                className: 'almacen-etiqueta',
                html: `
                  <div class="flex items-center bg-white rounded-full shadow-lg border-2 border-junta-green-600 px-2 py-1 cursor-pointer hover:bg-junta-green-50 transition-colors" style="z-index: ${1000 + index};">
                    <svg class="w-4 h-4 text-junta-green-600 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M4 3a2 2 0 100 4 2 2 0 000-4zm0 8a2 2 0 100 4 2 2 0 000-4zm12-8a2 2 0 100 4 2 2 0 000-4zm0 8a2 2 0 100 4 2 2 0 000-4zM8 15a1 1 0 011 1h2a1 1 0 001-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2z"/>
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 1.414L10.586 9.5H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-xs font-semibold text-junta-green-800">${almacen.nombre}</span>
                  </div>
                `,
                iconSize: [140, 35],
                iconAnchor: [70, 17],
                popupAnchor: [0, -20]
              });
              
              const marker = L.marker([latAjustada, lngAjustada], { icon: iconoAlmacen })
                .addTo(mapa.value)
                .bindPopup(`
                  <div class="p-3 min-w-[200px]">
                    <h4 class="font-bold text-sm mb-2">${almacen.nombre}</h4>
                    <p class="text-xs text-gray-600 mb-1">${almacen.direccion || 'Sin dirección'}</p>
                    <p class="text-xs text-gray-500 mb-3">${almacen.provincia}</p>
                    <button
                      onclick="window.seleccionarAlmacenDesdeMapa(${almacen.id})"
                      class="w-full bg-junta-green-600 text-white px-3 py-2 rounded text-xs hover:bg-junta-green-700 transition-colors">
                      Seleccionar este almacén
                    </button>
                  </div>
                `);
              
              marker.on('click', () => {
                manejarSeleccionAlmacenDesdeMapa(almacen);
              });
              
              marcadores.value.push(marker);
            }
          });
        });
        
      } else {
        console.error('Leaflet no está disponible. Asegúrate de que la librería esté cargada.');
      }
    }, 1000); // Aumentar el tiempo para asegurar que el DOM esté listo
  }
});

// Función para calcular offset y evitar superposición de etiquetas
const calcularOffsetParaEtiqueta = (index, total) => {
  // Crear un patrón circular para distribuir las etiquetas
  const angulo = (2 * Math.PI * index) / total;
  const radio = 0.3; // Aumentado para mejor separación visual
  
  return {
    lat: Math.cos(angulo) * radio,
    lng: Math.sin(angulo) * radio * 1.5 // Ajustar longitud para compensar la proyección
  };
};

// Seleccionar categoría y cargar sus materiales
const seleccionarCategoria = (cat) => {
  categoriaSeleccionada.value = cat;
  mostrarBuscador.value = false;
  busqueda.value = '';
  
  // Filtrar materiales por categoría (mostrar todos, incluso sin stock)
  materialesCategoria.value = materiales.value.filter(m => 
    m.categoria_id === cat.id
  );
};

// Volver a la vista de categorías
const volverACategorias = () => {
  categoriaSeleccionada.value = null;
  materialesCategoria.value = [];
  busqueda.value = '';
};

const filtrarMateriales = () => {
  if (!busqueda.value || busqueda.value.length < 2) {
    materialesFiltrados.value = [];
    mostrarResultados.value = false;
    return;
  }

  if (!Array.isArray(materiales.value)) {
    materialesFiltrados.value = [];
    mostrarResultados.value = false;
    return;
  }

  const termino = busqueda.value.toLowerCase().trim();
  materialesFiltrados.value = materiales.value
    .filter(mat => {
      const ref = (mat.referencia || '').toLowerCase();
      const nom = (mat.nombre || '').toLowerCase();
      const desc = (mat.descripcion || '').toLowerCase();
      return ref.includes(termino) || nom.includes(termino) || desc.includes(termino);
    })
    .sort((a, b) => {
      // Priorizar coincidencias en referencia
      const aRef = (a.referencia || '').toLowerCase();
      const bRef = (b.referencia || '').toLowerCase();
      const aStartsWithRef = aRef.startsWith(termino);
      const bStartsWithRef = bRef.startsWith(termino);
      
      if (aStartsWithRef && !bStartsWithRef) return -1;
      if (!aStartsWithRef && bStartsWithRef) return 1;
      
      // Luego por nombre
      const aNom = (a.nombre || '').toLowerCase();
      const bNom = (b.nombre || '').toLowerCase();
      const aStartsWithNom = aNom.startsWith(termino);
      const bStartsWithNom = bNom.startsWith(termino);
      
      if (aStartsWithNom && !bStartsWithNom) return -1;
      if (!aStartsWithNom && bStartsWithNom) return 1;
      
      // Finalmente por stock (mayor primero)
      return (b.stock_actual || 0) - (a.stock_actual || 0);
    })
    .slice(0, 20);
  
  mostrarResultados.value = true;
};

const seleccionarMaterial = (mat) => {
  // Verificar si el material ya está agregado
  const yaAgregado = materialesSeleccionados.value.find(m => m.id === mat.id);
  if (yaAgregado) {
    alert('Este material ya ha sido agregado a la solicitud');
    return;
  }

  // Agregar el material con cantidad predeterminada
  // Marcar si tiene stock o no para procesar diferente al enviar
  materialesSeleccionados.value.push({
    id: mat.id,
    referencia: mat.referencia,
    nombre: mat.nombre,
    descripcion: mat.descripcion,
    unidad: mat.unidad || 'ud',
    cantidad: 1,
    sin_stock: (mat.stock_actual || 0) === 0
  });

  // Limpiar búsqueda
  busqueda.value = '';
  materialesFiltrados.value = [];
  mostrarResultados.value = false;
};
const eliminarMaterial = (index) => {
  materialesSeleccionados.value.splice(index, 1);
};

// Funciones del modal de imagen
const verImagenGrande = (material) => {
  imagenModal.value = {
    visible: true,
    material: material
  };
};

const cerrarImagenModal = () => {
  imagenModal.value = {
    visible: false,
    material: null
  };
};

const seleccionarMaterialDesdeModal = () => {
  if (imagenModal.value.material) {
    seleccionarMaterial(imagenModal.value.material);
    cerrarImagenModal();
  }
};

const onImageError = (event) => {
  event.target.style.display = 'none';
  const parent = event.target.parentElement;
  if (parent) {
    parent.innerHTML = '<div class="w-full h-full flex flex-col items-center justify-center text-gray-300"><svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg><span class="text-sm">Imagen no disponible</span></div>';
  }
};

const limpiarFormulario = () => {
  if (confirm('¿Estás seguro de que quieres limpiar todo el formulario?')) {
    form.value = {
      justificacion: '',
      usuario_solicitante: '',
      email_solicitante: '',
      telefono_solicitante: '',
      sede_id: '',
      departamento_id: '',
      almacen_id: '',
      campos_personalizados: {}
    };
    materialesSeleccionados.value = [];
    busqueda.value = '';
    departamentos.value = [];
    almacenSeleccionado.value = null;
    mostrarPaso3.value = false;
    provinciaSeleccionada.value = '';
    almacenTemporal.value = '';
  }
};

const enviarPeticion = async () => {
  if (!puedeEnviar.value) return;
  
  enviando.value = true;
  error.value = '';
  success.value = false;

  try {
    // Separar materiales con stock y sin stock
    const materialesConStock = materialesSeleccionados.value.filter(m => !m.sin_stock);
    const materialesSinStock = materialesSeleccionados.value.filter(m => m.sin_stock);

    // 1. Enviar pedido normal para materiales con stock
    if (materialesConStock.length > 0) {
      const payloadPedido = {
        ...form.value,
        materiales: materialesConStock.map(m => ({
          material_id: m.id,
          cantidad: m.cantidad,
          unidad: m.unidad
        }))
      };

      await axios.post('/peticiones', payloadPedido);
    }

    // 2. Crear solicitudes de reposición para materiales sin stock
    if (materialesSinStock.length > 0) {
      for (const mat of materialesSinStock) {
        try {
          await axios.post('/solicitudes-reposicion-publicas', {
            entidad_id: mat.id,
            cantidad_solicitada: mat.cantidad,
            usuario_solicitante: form.value.usuario_solicitante,
            email_solicitante: form.value.email_solicitante,
            telefono_solicitante: form.value.telefono_solicitante,
            notas: `Justificación: ${form.value.justificacion}\nSede: ${sedeNombre.value}\nDepartamento: ${departamentoNombre.value}\nAlmacén seleccionado: ${almacenSeleccionado.value?.nombre || 'No especificado'}`
          });
        } catch (err) {
          console.error(`Error creando solicitud para ${mat.referencia}:`, err);
          // Continuar con los demás aunque falle uno
        }
      }
    }

    success.value = true;
    
    // Mostrar mensaje diferenciado
    let mensaje = '';
    if (materialesConStock.length > 0 && materialesSinStock.length > 0) {
      mensaje = `✓ Petición enviada correctamente!\n\n` +
                `• ${materialesConStock.length} materiales con stock → Pedido registrado\n` +
                `• ${materialesSinStock.length} materiales sin stock → Solicitudes de reposición creadas\n\n` +
                `Recibirás notificaciones por email.`;
    } else if (materialesConStock.length > 0) {
      mensaje = '✓ Petición enviada correctamente! Recibirás una respuesta por email.';
    } else {
      mensaje = `✓ Solicitudes de reposición creadas!\n\nTe avisaremos cuando haya stock disponible.`;
    }
    
    alert(mensaje);
    
    // Limpiar formulario
    form.value = {
      justificacion: '',
      usuario_solicitante: '',
      email_solicitante: '',
      telefono_solicitante: '',
      sede_id: '',
      departamento_id: '',
      almacen_id: '',
      campos_personalizados: {}
    };
    materialesSeleccionados.value = [];
    busqueda.value = '';
    departamentos.value = [];
    almacenSeleccionado.value = null;
    mostrarPaso3.value = false;
    provinciaSeleccionada.value = '';
    almacenTemporal.value = '';

    // Scroll al inicio
    window.scrollTo({ top: 0, behavior: 'smooth' });

    // Ocultar mensaje de éxito después de 10 segundos
    setTimeout(() => {
      success.value = false;
    }, 10000);
  } catch (err) {
    console.error('Error al enviar petición:', err);
    error.value = err.response?.data?.message || 'Error al enviar la petición. Por favor, inténtalo de nuevo.';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } finally {
    enviando.value = false;
  }
};

// Cerrar resultados al hacer clic fuera
const cerrarResultados = (event) => {
  if (!event.target.closest('.relative')) {
    mostrarResultados.value = false;
  }
};

onMounted(() => {
  cargarMateriales();
  cargarCategorias();
  cargarSedes();
  cargarProvincias(); // Cargar provincias para el solicitante
  cargarCamposPersonalizados();
  cargarAlmacenes();
  document.addEventListener('click', cerrarResultados);
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.slide-fade-enter-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-fade-leave-active {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-fade-enter-from {
  transform: translateY(-10px);
  opacity: 0;
}
.slide-fade-leave-to {
  transform: translateY(-5px);
  opacity: 0;
}

/* Animación del modal */
.modal-fade-enter-active, .modal-fade-leave-active {
  transition: all 0.3s ease;
}
.modal-fade-enter-from, .modal-fade-leave-to {
  opacity: 0;
}
.modal-fade-enter-from > div {
  transform: scale(0.9);
}
.modal-fade-leave-to > div {
  transform: scale(0.9);
}

/* Utilidades para líneas cortadas */
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Mejoras para móviles */
@media (max-width: 640px) {
  .container {
    padding-left: 1rem;
    padding-right: 1rem;
  }
  
  /* Prevenir zoom en inputs iOS */
  input[type="text"],
  input[type="email"],
  input[type="tel"],
  input[type="number"],
  textarea,
  select {
    font-size: 16px !important;
    transform: scale(0.95);
    transform-origin: left center;
  }
  
  /* Mejorar espaciado en móviles */
  .space-y-4 > * + * {
    margin-top: 1.25rem;
  }
}

/* Optimizaciones para tablets */
@media (min-width: 641px) and (max-width: 1024px) {
  .container {
    max-width: 100%;
  }
}

/* Soporte para modo oscuro en PWA */
@media (prefers-color-scheme: dark) and (display-mode: standalone) {
  .bg-gradient-to-br {
    background: linear-gradient(to bottom right, rgb(17, 24, 39), rgb(31, 41, 55), rgb(6, 78, 59));
  }
}

/* Mejoras para notch en iPhone */
@supports (padding: env(safe-area-inset-top)) {
  header {
    padding-top: env(safe-area-inset-top, 0);
  }
}

/* Optimizaciones para pantallas táctiles */
@media (hover: none) and (pointer: coarse) {
  button:hover {
    transform: none;
  }
  
  button:active {
    transform: scale(0.98);
  }
}

/* Scrollbar personalizado para el modal */
.overflow-y-auto::-webkit-scrollbar {
  width: 8px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>
