<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-semibold">Configuración</h2>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 mb-6 border-b border-gray-200 dark:border-gray-700">
      <button 
        @click="tabActiva = 'logotipos'" 
        :class="['px-4 py-2 font-medium text-sm transition-colors border-b-2', tabActiva === 'logotipos' ? 'border-junta-green-600 text-junta-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
        Logotipos Institucionales
      </button>
      <button 
        @click="tabActiva = 'categorias'" 
        :class="['px-4 py-2 font-medium text-sm transition-colors border-b-2', tabActiva === 'categorias' ? 'border-junta-green-600 text-junta-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
        Categorías de Material
      </button>
      <button 
        @click="tabActiva = 'justificantes'" 
        :class="['px-4 py-2 font-medium text-sm transition-colors border-b-2', tabActiva === 'justificantes' ? 'border-junta-green-600 text-junta-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
        Justificantes Entrada/Salida
      </button>
      <button 
        @click="tabActiva = 'campos'" 
        :class="['px-4 py-2 font-medium text-sm transition-colors border-b-2', tabActiva === 'campos' ? 'border-junta-green-600 text-junta-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
        Campos Personalizados
      </button>
      <button 
        @click="tabActiva = 'ubicaciones'" 
        :class="['px-4 py-2 font-medium text-sm transition-colors border-b-2', tabActiva === 'ubicaciones' ? 'border-junta-green-600 text-junta-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
        Sedes y Departamentos
      </button>
      <button 
        @click="tabActiva = 'usuarios'" 
        :class="['px-4 py-2 font-medium text-sm transition-colors border-b-2', tabActiva === 'usuarios' ? 'border-junta-green-600 text-junta-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
        Usuarios Administradores
      </button>
      <button 
        @click="tabActiva = 'smtp'" 
        :class="['px-4 py-2 font-medium text-sm transition-colors border-b-2', tabActiva === 'smtp' ? 'border-junta-green-600 text-junta-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
        Config. SMTP
      </button>
      <button 
        @click="tabActiva = 'notificaciones'" 
        :class="['px-4 py-2 font-medium text-sm transition-colors border-b-2', tabActiva === 'notificaciones' ? 'border-junta-green-600 text-junta-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
        Notificaciones Email
      </button>
      <button 
        @click="tabActiva = 'backup'" 
        :class="['px-4 py-2 font-medium text-sm transition-colors border-b-2', tabActiva === 'backup' ? 'border-junta-green-600 text-junta-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
        Copia de Seguridad
      </button>
      <button
        @click="tabActiva = 'almacenes'"
        :class="['px-4 py-2 font-medium text-sm transition-colors border-b-2', tabActiva === 'almacenes' ? 'border-junta-green-600 text-junta-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
        Gestión de Almacenes
      </button>
    </div>

    <!-- Tab: Logotipos -->
    <div v-if="tabActiva === 'logotipos'" class="card p-6 space-y-6">
      <div>
        <h3 class="text-lg font-semibold mb-4">Gestión de Logotipos</h3>
        <p class="text-sm text-gray-600 mb-6">Sube las imágenes oficiales de la Junta de Andalucía y la Agencia Digital. Se aplicarán automáticamente en toda la interfaz.</p>
      </div>

      <!-- Logo Junta -->
      <div class="grid md:grid-cols-2 gap-6 items-start">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Logotipo Junta de Andalucía
          </label>
          <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center">
            <img v-if="previewJunta" :src="previewJunta" alt="Preview Junta" class="h-24 mx-auto mb-4 object-contain" />
            <img v-else src="/images/junta-logo.png" alt="Junta actual" class="h-24 mx-auto mb-4 object-contain" />
            <input 
              type="file" 
              ref="inputJunta" 
              @change="handleFileJunta" 
              accept="image/png,image/jpeg,image/svg+xml" 
              class="hidden" 
            />
            <button @click="$refs.inputJunta.click()" class="btn btn-secondary">
              Seleccionar imagen
            </button>
            <p class="text-xs text-gray-500 mt-2">PNG, JPG o SVG. Se redimensionará automáticamente.</p>
          </div>
          <button 
            v-if="archivoJunta" 
            @click="subirLogo('junta')" 
            :disabled="subiendoJunta"
            class="btn btn-primary w-full mt-3">
            {{ subiendoJunta ? 'Subiendo...' : 'Guardar Logotipo Junta' }}
          </button>
        </div>

        <!-- Logo ADA -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Logotipo Agencia Digital de Andalucía
          </label>
          <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center">
            <img v-if="previewAda" :src="previewAda" alt="Preview ADA" class="h-24 mx-auto mb-4 object-contain" />
            <img v-else src="/images/ada-logo.png" alt="ADA actual" class="h-24 mx-auto mb-4 object-contain" />
            <input 
              type="file" 
              ref="inputAda" 
              @change="handleFileAda" 
              accept="image/png,image/jpeg,image/svg+xml" 
              class="hidden" 
            />
            <button @click="$refs.inputAda.click()" class="btn btn-secondary">
              Seleccionar imagen
            </button>
            <p class="text-xs text-gray-500 mt-2">PNG, JPG o SVG. Se redimensionará automáticamente.</p>
          </div>
          <button 
            v-if="archivoAda" 
            @click="subirLogo('ada')" 
            :disabled="subiendoAda"
            class="btn btn-primary w-full mt-3">
            {{ subiendoAda ? 'Subiendo...' : 'Guardar Logotipo ADA' }}
          </button>
        </div>
      </div>

      <div v-if="mensajeLogos" :class="['p-4 rounded-lg text-sm', mensajeLogos.tipo === 'success' ? 'bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-300' : 'bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-300']">
        {{ mensajeLogos.texto }}
      </div>

      <!-- Configuración de Dominio/IP -->
      <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
        <h4 class="text-md font-semibold mb-4 text-gray-900 dark:text-gray-100">Configuración de Dominio/IP</h4>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
          Configura el dominio o IP que se usará para generar los enlaces de seguimiento de pedidos en los emails.
          Puedes usar una IP (ej: http://10.66.129.108) o un dominio (ej: https://material.junta-andalucia.es)
        </p>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Dominio/IP Base
            </label>
            <input
              v-model="appConfig.app_domain"
              type="text"
              placeholder="http://10.66.129.108 o https://dominio.com"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-junta-green-500 focus:border-junta-green-500 dark:bg-gray-800 dark:text-gray-100"
            />
            <p class="text-xs text-gray-500 mt-1">Incluye el protocolo (http:// o https://)</p>
          </div>
          <button
            @click="guardarAppConfig"
            :disabled="guardandoAppConfig"
            class="btn btn-primary">
            {{ guardandoAppConfig ? 'Guardando...' : 'Guardar Configuración' }}
          </button>
          <div v-if="mensajeAppConfig" :class="['p-4 rounded-lg text-sm mt-2', mensajeAppConfig.tipo === 'success' ? 'bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-300' : 'bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-300']">
            {{ mensajeAppConfig.texto }}
          </div>
        </div>
      </div>
    </div>

    <!-- Tab: Categorías de Material -->
    <div v-if="tabActiva === 'categorias'" class="card p-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-lg font-semibold">Categorías de Material</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Organiza los materiales en categorías con imágenes para navegación visual tipo tienda online</p>
        </div>
        <button @click="abrirNuevaCategoria" class="btn btn-primary">
          Añadir Categoría
        </button>
      </div>

      <div v-if="cargandoCategorias" class="text-gray-500 text-sm">Cargando categorías...</div>

      <div v-else-if="categorias.length === 0" class="text-center py-12 text-gray-500">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
        </svg>
        <p class="mt-2">No hay categorías creadas</p>
        <p class="text-sm mt-1">Usa "Añadir Categoría" para empezar</p>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <div v-for="cat in categorias" :key="cat.id" 
          class="relative group border rounded-lg p-4 hover:shadow-md transition">
          
          <!-- Imagen -->
          <div class="aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg mb-3 overflow-hidden">
            <img v-if="cat.imagen" 
              :src="`/gestionmaterial/storage/categorias/${cat.imagen}`" 
              :alt="cat.nombre"
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
              <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
          </div>

          <!-- Info -->
          <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ cat.nombre }}</h4>
          <p v-if="cat.descripcion" class="text-sm text-gray-600 dark:text-gray-400 mb-2 line-clamp-2">{{ cat.descripcion }}</p>
          <p class="text-xs text-gray-500">{{ cat.entidades_count || 0 }} materiales</p>

          <!-- Badge activo/inactivo -->
          <div class="absolute top-2 right-2">
            <span :class="['px-2 py-1 text-xs rounded-full font-medium', cat.activo ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600']">
              {{ cat.activo ? 'Activa' : 'Inactiva' }}
            </span>
          </div>

          <!-- Acciones -->
          <div class="mt-3 flex gap-2">
            <button @click="subirImagenCategoria(cat)" class="flex-1 btn btn-secondary text-xs py-1">
              {{ cat.imagen ? 'Cambiar Imagen' : 'Subir Imagen' }}
            </button>
            <button @click="editarCategoria(cat)" class="flex-1 btn btn-secondary text-xs py-1">
              Editar
            </button>
            <button @click="toggleActivoCategoria(cat)" class="btn btn-secondary text-xs py-1 px-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
            <button @click="eliminarCategoria(cat)" class="btn bg-red-600 text-white hover:bg-red-700 text-xs py-1 px-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Mensaje feedback -->
      <div v-if="mensajeCategorias" :class="['mt-4 p-4 rounded-lg text-sm', mensajeCategorias.tipo === 'success' ? 'bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-300' : 'bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-300']">
        {{ mensajeCategorias.texto }}
      </div>
    </div>

    <!-- Tab: Justificantes de Entrada/Salida -->
    <div v-if="tabActiva === 'justificantes'" class="space-y-6">
      <!-- Entradas -->
      <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-lg font-semibold text-green-800">Justificantes de Entrada</h3>
            <p class="text-sm text-gray-600">Motivos para registrar entradas de material al almacén</p>
          </div>
          <button @click="abrirNuevoJustificante('entrada')" class="btn btn-primary">
            Añadir justificante
          </button>
        </div>

        <div v-if="cargandoJustificantes" class="text-gray-500 text-sm">Cargando...</div>
        
        <div v-else-if="justificantesEntrada.length === 0" class="text-gray-500 text-sm text-center py-8">
          No hay justificantes de entrada. Usa "Añadir justificante" para crear uno.
        </div>
        
        <ul v-else class="space-y-2">
          <li v-for="(j, idx) in justificantesEntrada" :key="j.id" 
            class="flex items-start gap-4 p-3 rounded-lg border hover:bg-gray-50 dark:hover:bg-gray-800 transition">
            <div class="flex items-center gap-2 text-gray-400">
              <button @click="moverJustificante(idx, -1, 'entrada')" 
                :disabled="idx === 0"
                class="p-1 hover:bg-gray-200 rounded disabled:opacity-30">↑</button>
              <button @click="moverJustificante(idx, 1, 'entrada')" 
                :disabled="idx === justificantesEntrada.length - 1"
                class="p-1 hover:bg-gray-200 rounded disabled:opacity-30">↓</button>
            </div>
            <div class="flex-1">
              <div class="font-medium text-gray-900 dark:text-gray-100">{{ j.nombre }}</div>
              <div v-if="j.descripcion" class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ j.descripcion }}</div>
            </div>
            <div class="flex items-center gap-2">
              <button 
                @click="toggleActivoJustificante(j)" 
                :class="['px-3 py-1 text-xs rounded-full font-medium', j.activo ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600']">
                {{ j.activo ? 'Activo' : 'Inactivo' }}
              </button>
              <button @click="editarJustificante(j)" class="btn btn-secondary">Editar</button>
              <button @click="eliminarJustificante(j)" class="btn bg-red-600 text-white hover:bg-red-700">Eliminar</button>
            </div>
          </li>
        </ul>
      </div>

      <!-- Salidas -->
      <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-lg font-semibold text-red-800">Justificantes de Salida</h3>
            <p class="text-sm text-gray-600">Motivos para registrar salidas de material del almacén</p>
          </div>
          <button @click="abrirNuevoJustificante('salida')" class="btn btn-primary">
            Añadir justificante
          </button>
        </div>

        <div v-if="cargandoJustificantes" class="text-gray-500 text-sm">Cargando...</div>
        
        <div v-else-if="justificantesSalida.length === 0" class="text-gray-500 text-sm text-center py-8">
          No hay justificantes de salida. Usa "Añadir justificante" para crear uno.
        </div>
        
        <ul v-else class="space-y-2">
          <li v-for="(j, idx) in justificantesSalida" :key="j.id" 
            class="flex items-start gap-4 p-3 rounded-lg border hover:bg-gray-50 dark:hover:bg-gray-800 transition">
            <div class="flex items-center gap-2 text-gray-400">
              <button @click="moverJustificante(idx, -1, 'salida')" 
                :disabled="idx === 0"
                class="p-1 hover:bg-gray-200 rounded disabled:opacity-30">↑</button>
              <button @click="moverJustificante(idx, 1, 'salida')" 
                :disabled="idx === justificantesSalida.length - 1"
                class="p-1 hover:bg-gray-200 rounded disabled:opacity-30">↓</button>
            </div>
            <div class="flex-1">
              <div class="font-medium text-gray-900 dark:text-gray-100">{{ j.nombre }}</div>
              <div v-if="j.descripcion" class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ j.descripcion }}</div>
            </div>
            <div class="flex items-center gap-2">
              <button 
                @click="toggleActivoJustificante(j)" 
                :class="['px-3 py-1 text-xs rounded-full font-medium', j.activo ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600']">
                {{ j.activo ? 'Activo' : 'Inactivo' }}
              </button>
              <button @click="editarJustificante(j)" class="btn btn-secondary">Editar</button>
              <button @click="eliminarJustificante(j)" class="btn bg-red-600 text-white hover:bg-red-700">Eliminar</button>
            </div>
          </li>
        </ul>
      </div>

      <div v-if="mensajeJustificantes" :class="['p-4 rounded-lg text-sm', mensajeJustificantes.tipo === 'success' ? 'bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-300' : 'bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-300']">
        {{ mensajeJustificantes.texto }}
      </div>
    </div>

    <!-- Tab: Campos Personalizados -->
    <div v-if="tabActiva === 'campos'" class="card p-6">
      <div class="flex items-center gap-3 mb-4">
        <label class="text-sm text-gray-600">Tipo de entidad</label>
        <select v-model="filtro.entity" class="select w-48">
          <option value="impresora">Impresoras</option>
          <option value="consumible">Consumibles</option>
          <option value="pedido">Pedidos</option>
          <option value="pequeño_material">Pequeño Material</option>
        </select>
        <button class="btn btn-primary ml-auto" @click="abrirNuevo">Añadir campo</button>
      </div>

      <div v-if="loading" class="text-gray-500 text-sm">Cargando...</div>

      <div v-else>
        <div v-if="campos.length === 0" class="text-gray-500 text-sm">No hay campos. Usa "Añadir campo" para crear uno.</div>
        <ul class="table-wrapper">
          <li v-for="(c, idx) in campos" :key="c.id" class="grid grid-cols-12 items-center py-2 border-b text-sm">
            <div class="col-span-1 text-gray-500">#{{ c.sort_order }}</div>
            <div class="col-span-3 font-medium">{{ c.label }}</div>
            <div class="col-span-2 text-gray-600">{{ c.key }}</div>
            <div class="col-span-2 text-gray-600">{{ c.type }}</div>
            <div class="col-span-2">
              <span :class="c.required ? 'text-red-600' : 'text-gray-500'">{{ c.required ? 'Obligatorio' : 'Opcional' }}</span>
            </div>
            <div class="col-span-2 flex justify-end gap-2">
              <button class="btn btn-secondary" @click="mover(idx, -1)">↑</button>
              <button class="btn btn-secondary" @click="mover(idx, 1)">↓</button>
              <button class="btn btn-secondary" @click="editar(c)">Editar</button>
              <button class="btn bg-red-600 text-white hover:bg-red-700" @click="eliminar(c)">Eliminar</button>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <!-- Tab: Sedes y Departamentos -->
    <div v-if="tabActiva === 'ubicaciones'" class="card p-6">
      <!-- Sub-tabs para Sedes, Departamentos y Provincias -->
      <div class="flex gap-2 mb-6 border-b border-gray-200 dark:border-gray-700">
        <button
          @click="subTabActiva = 'sedes'"
          :class="['px-4 py-2 font-medium text-sm transition-colors border-b-2', subTabActiva === 'sedes' ? 'border-junta-green-600 text-junta-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
          Sedes y Departamentos
        </button>
        <button
          @click="subTabActiva = 'provincias'"
          :class="['px-4 py-2 font-medium text-sm transition-colors border-b-2', subTabActiva === 'provincias' ? 'border-junta-green-600 text-junta-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
          Provincias
        </button>
      </div>

      <!-- Sub-tab: Sedes y Departamentos -->
      <div v-if="subTabActiva === 'sedes'">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
          <!-- Provincias -->
          <div class="lg:col-span-1">
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-semibold">Provincias</h3>
              <button class="btn btn-primary text-sm" @click="crearProvinciaPrompt">Nueva</button>
            </div>
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
              <li v-for="provincia in provincias" :key="provincia.id" class="py-2">
                <div class="flex items-center justify-between">
                  <button class="text-left flex-1 hover:text-junta-green-700 dark:hover:text-junta-green-400" @click="seleccionarProvincia(provincia)">
                    <div class="font-medium text-sm">{{ provincia.nombre }}</div>
                    <div class="text-xs text-gray-500">{{ provincia.sedes_count || 0 }} sedes</div>
                  </button>
                  <div class="flex items-center gap-1">
                    <button class="btn btn-secondary text-xs p-1" @click="editarProvinciaPrompt(provincia)" title="Editar">
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                      </svg>
                    </button>
                    <button class="btn bg-red-600 text-white hover:bg-red-700 text-xs p-1" @click="eliminarProvincia(provincia)" title="Eliminar">
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                      </svg>
                    </button>
                  </div>
                </div>
              </li>
            </ul>
          </div>

          <!-- Sedes -->
          <div class="lg:col-span-1">
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-semibold">Sedes</h3>
              <button class="btn btn-primary text-sm" @click="crearSedePrompt">Nueva</button>
            </div>
            <div v-if="provinciaSeleccionada" class="mb-3 p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
              <p class="text-sm text-blue-800 dark:text-blue-300">
                <strong>Filtrando por:</strong> {{ provinciaSeleccionada.nombre }}
              </p>
              <button @click="limpiarFiltroProvincia" class="text-xs text-blue-600 hover:text-blue-800 mt-1">
                Limpiar filtro
              </button>
            </div>
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
              <li v-for="s in sedesFiltradas" :key="s.id" class="py-2 flex items-center justify-between">
                <button class="text-left flex-1 truncate hover:text-junta-green-700 dark:hover:text-junta-green-400" @click="seleccionarSede(s)">
                  <div class="font-medium text-sm truncate">{{ s.nombre }}</div>
                  <div class="text-xs text-gray-500">
                    {{ s.departamentos?.length || 0 }} departamentos
                    <span v-if="s.provincia">· {{ s.provincia.nombre }}</span>
                  </div>
                </button>
                <div class="flex items-center gap-1">
                  <button class="btn btn-secondary text-xs p-1" @click="editarSedePrompt(s)" title="Editar">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                  </button>
                  <button class="btn bg-red-600 text-white hover:bg-red-700 text-xs p-1" @click="eliminarSede(s)" title="Eliminar">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                  </button>
                </div>
              </li>
            </ul>
          </div>

          <!-- Departamentos -->
          <div class="lg:col-span-2">
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-semibold">Departamentos <span v-if="sedeSeleccionada" class="text-gray-500">· {{ sedeSeleccionada.nombre }}</span></h3>
              <div class="flex items-center gap-2">
                <button class="btn btn-secondary text-sm" @click="syncDepartamentos">Actualizar campo "Departamento"</button>
                <button class="btn btn-primary text-sm" :disabled="!sedeSeleccionada" @click="crearDepartamentoPrompt">Nuevo departamento</button>
              </div>
            </div>
            <div v-if="!sedeSeleccionada" class="text-sm text-gray-500 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
              Selecciona una sede para gestionar sus departamentos.
            </div>
            <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
              <li v-for="d in sedeSeleccionada.departamentos" :key="d.id" class="py-2 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="truncate text-sm">{{ d.nombre }}</div>
                  <span v-if="d.es_almacen" class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Almacén</span>
                </div>
                <div class="flex items-center gap-1">
                  <button class="btn btn-secondary text-xs p-1" @click="toggleAlmacenDepartamento(d)" :title="d.es_almacen ? 'Quitar como almacén' : 'Marcar como almacén'">
                    {{ d.es_almacen ? '📦' : '🏢' }}
                  </button>
                  <button class="btn btn-secondary text-xs p-1" @click="editarDepartamentoPrompt(d)" title="Editar">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                  </button>
                  <button class="btn bg-red-600 text-white hover:bg-red-700 text-xs p-1" @click="eliminarDepartamento(d)" title="Eliminar">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                  </button>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Sub-tab: Provincias -->
      <div v-if="subTabActiva === 'provincias'">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg font-semibold">Provincias de Andalucía</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Gestiona las provincias para organizar las sedes por ubicación geográfica</p>
          </div>
          <button @click="crearProvinciaPrompt" class="btn btn-primary">
            Nueva Provincia
          </button>
        </div>

        <div v-if="cargandoProvincias" class="text-center py-8">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-junta-green-600"></div>
          <p class="text-gray-500 mt-2">Cargando provincias...</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="provincia in provincias" :key="provincia.id"
            class="border rounded-lg p-4 hover:shadow-md transition">
            
            <div class="flex items-start justify-between">
              <div>
                <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ provincia.nombre }}</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  {{ provincia.sedes_count || 0 }} sedes asignadas
                </p>
              </div>
              <div class="flex items-center gap-2">
                <button @click="editarProvinciaPrompt(provincia)" class="btn btn-secondary" title="Editar">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                  </svg>
                </button>
                <button @click="eliminarProvincia(provincia)" class="btn bg-red-600 text-white hover:bg-red-700" title="Eliminar">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="mensajeUbicaciones" :class="['mt-4 p-4 rounded-lg text-sm', mensajeUbicaciones.tipo === 'success' ? 'bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-300' : 'bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-300']">
        {{ mensajeUbicaciones.texto }}
      </div>
    </div>

    <!-- Modal crear/editar -->
    <transition name="fade">
      <div v-if="modal.open" class="modal-overlay">
        <div class="modal w-full max-w-xl">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">{{ modal.editar ? 'Editar campo' : 'Nuevo campo' }}</h3>
            <button @click="cerrarModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <form class="space-y-4" @submit.prevent="guardar">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Entidad</label>
                <select v-model="form.entity_type" class="select" required>
                  <option value="impresora">Impresoras</option>
                  <option value="consumible">Consumibles</option>
                  <option value="pedido">Pedidos</option>
                  <option value="pequeño_material">Pequeño Material</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Etiqueta</label>
                <input v-model="form.label" class="input" required />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Clave</label>
                <input v-model="form.key" class="input" placeholder="se-generará-si-se-deja-vacio" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                <select v-model="form.type" class="select">
                  <option value="text">Texto</option>
                  <option value="number">Número</option>
                  <option value="date">Fecha</option>
                  <option value="boolean">Sí/No</option>
                  <option value="select">Lista</option>
                </select>
              </div>
              <div v-if="form.type === 'select'" class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Opciones (una por línea)</label>
                <textarea v-model="form.options_raw" class="input h-24" placeholder="opcion1\nopcion2"></textarea>
              </div>
              <div class="flex items-center gap-2 md:col-span-2">
                <input id="req" type="checkbox" v-model="form.required" class="h-4 w-4"/>
                <label for="req" class="text-sm">Campo obligatorio</label>
              </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <button type="button" class="btn btn-secondary" @click="cerrarModal">Cancelar</button>
              <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
          </form>
        </div>
      </div>
    </transition>

    <!-- Modal crear/editar categoría -->
    <transition name="fade">
      <div v-if="modalCategoria.open" class="modal-overlay">
        <div class="modal w-full max-w-lg">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">
              {{ modalCategoria.editar ? 'Editar categoría' : 'Nueva categoría' }}
            </h3>
            <button @click="cerrarModalCategoria" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <form class="space-y-4" @submit.prevent="guardarCategoria">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la categoría *</label>
              <input v-model="formCategoria.nombre" class="input w-full" required placeholder="ej: Cables, Conectores, etc." />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Descripción (opcional)</label>
              <textarea v-model="formCategoria.descripcion" class="input w-full h-24" placeholder="Descripción de los materiales que incluye esta categoría"></textarea>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Orden de visualización</label>
              <input v-model.number="formCategoria.orden" type="number" class="input w-full" placeholder="0" />
              <p class="text-xs text-gray-500 mt-1">Menor número aparece primero. Déjalo en 0 para el orden por defecto.</p>
            </div>
            <div class="flex items-center gap-2">
              <input id="activo-cat" type="checkbox" v-model="formCategoria.activo" class="h-4 w-4"/>
              <label for="activo-cat" class="text-sm">Categoría activa (visible en formularios)</label>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t">
              <button type="button" class="btn btn-secondary" @click="cerrarModalCategoria">Cancelar</button>
              <button type="submit" :disabled="guardandoCategoria" class="btn btn-primary">
                {{ guardandoCategoria ? 'Guardando...' : 'Guardar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>

    <!-- Modal subir imagen categoría -->
    <transition name="fade">
      <div v-if="modalImagenCategoria.open" class="modal-overlay">
        <div class="modal w-full max-w-md">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">
              Subir Imagen - {{ modalImagenCategoria.categoria?.nombre }}
            </h3>
            <button @click="cerrarModalImagenCategoria" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="mb-4">
            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center">
              <img v-if="previewImagenCategoria" :src="previewImagenCategoria" alt="Preview" class="w-full aspect-square object-cover rounded-lg mb-4" />
              <div v-else class="w-full aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg mb-4 flex items-center justify-center">
                <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
              <input 
                type="file" 
                ref="inputImagenCategoria" 
                @change="handleFileImagenCategoria" 
                accept="image/png,image/jpeg,image/jpg,image/gif" 
                class="hidden" 
              />
              <button @click="$refs.inputImagenCategoria.click()" class="btn btn-secondary">
                Seleccionar imagen
              </button>
              <p class="text-xs text-gray-500 mt-2">PNG, JPG o GIF. Máximo 2MB. Ideal: cuadrada 500x500px.</p>
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t">
            <button type="button" class="btn btn-secondary" @click="cerrarModalImagenCategoria">Cancelar</button>
            <button @click="confirmarSubirImagenCategoria" :disabled="!archivoImagenCategoria || subiendoImagenCategoria" class="btn btn-primary">
              {{ subiendoImagenCategoria ? 'Subiendo...' : 'Subir Imagen' }}
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Modal crear/editar justificante -->
    <transition name="fade">
      <div v-if="modalJustificante.open" class="modal-overlay">
        <div class="modal w-full max-w-lg">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">
              {{ modalJustificante.editar ? 'Editar justificante' : 'Nuevo justificante' }}
              <span :class="['ml-2 px-2 py-1 text-xs rounded-full', formJustificante.tipo === 'entrada' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']">
                {{ formJustificante.tipo === 'entrada' ? 'ENTRADA' : 'SALIDA' }}
              </span>
            </h3>
            <button @click="cerrarModalJustificante" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <form class="space-y-4" @submit.prevent="guardarJustificante">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del justificante *</label>
              <input v-model="formJustificante.nombre" class="input w-full" required placeholder="ej: Compra, Donación, etc." />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Descripción (opcional)</label>
              <textarea v-model="formJustificante.descripcion" class="input w-full h-24" placeholder="Descripción detallada del justificante"></textarea>
            </div>
            <div class="flex items-center gap-2">
              <input id="activo-just" type="checkbox" v-model="formJustificante.activo" class="h-4 w-4"/>
              <label for="activo-just" class="text-sm">Justificante activo (disponible para usar)</label>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t">
              <button type="button" class="btn btn-secondary" @click="cerrarModalJustificante">Cancelar</button>
              <button type="submit" :disabled="guardandoJustificante" class="btn btn-primary">
                {{ guardandoJustificante ? 'Guardando...' : 'Guardar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>

    <!-- Tab: Notificaciones Email (SMTP) -->
    <div v-if="tabActiva === 'smtp'" class="card p-6 space-y-6">
      <div>
        <h3 class="text-lg font-semibold mb-2">Configuración de Servidor SMTP</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">Configure el servidor SMTP para enviar notificaciones por email a usuarios y gestores.</p>
      </div>

      <div v-if="mensajeSmtp" :class="['p-4 rounded-lg text-sm mb-4', mensajeSmtp.tipo === 'success' ? 'bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-400']">
        {{ mensajeSmtp.texto }}
      </div>

      <!-- Selector de proveedor -->
      <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
        <label class="block text-sm font-semibold mb-3">Proveedor de Email</label>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <button 
            @click="aplicarPresetMicrosoft365"
            type="button"
            class="p-3 border-2 rounded-lg text-sm font-medium transition-all hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/40"
            :class="configSmtp.provider === 'microsoft365' ? 'border-blue-500 bg-blue-100 dark:bg-blue-900/40' : 'border-gray-300 dark:border-gray-600'"
          >
            📧 Microsoft 365
          </button>
          <button 
            @click="aplicarPresetGmail"
            type="button"
            class="p-3 border-2 rounded-lg text-sm font-medium transition-all hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/40"
            :class="configSmtp.provider === 'gmail' ? 'border-blue-500 bg-blue-100 dark:bg-blue-900/40' : 'border-gray-300 dark:border-gray-600'"
          >
            📬 Gmail
          </button>
          <button 
            @click="aplicarPresetOutlook"
            type="button"
            class="p-3 border-2 rounded-lg text-sm font-medium transition-all hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/40"
            :class="configSmtp.provider === 'outlook' ? 'border-blue-500 bg-blue-100 dark:bg-blue-900/40' : 'border-gray-300 dark:border-gray-600'"
          >
            📨 Outlook
          </button>
          <button 
            @click="aplicarPresetPersonalizado"
            type="button"
            class="p-3 border-2 rounded-lg text-sm font-medium transition-all hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/40"
            :class="configSmtp.provider === 'custom' ? 'border-blue-500 bg-blue-100 dark:bg-blue-900/40' : 'border-gray-300 dark:border-gray-600'"
          >
            ⚙️ Personalizado
          </button>
        </div>
        
        <!-- Instrucciones según proveedor -->
        <div v-if="configSmtp.provider === 'microsoft365'" class="mt-4 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 p-3 rounded-lg">
          <p class="font-medium mb-2">📋 Instrucciones para Microsoft 365:</p>
          <ol class="list-decimal list-inside space-y-1 text-xs">
            <li>Usuario: Tu email corporativo completo (@juntadeandalucia.es)</li>
            <li>Contraseña: Tu contraseña de email corporativa</li>
            <li>Si tienes 2FA activado, necesitarás crear una contraseña de aplicación</li>
          </ol>
        </div>
        
        <div v-if="configSmtp.provider === 'gmail'" class="mt-4 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 p-3 rounded-lg">
          <p class="font-medium mb-2">📋 Instrucciones para Gmail:</p>
          <ol class="list-decimal list-inside space-y-1 text-xs">
            <li>Usuario: Tu dirección de Gmail completa</li>
            <li>Contraseña: Debes crear una "Contraseña de aplicación" en tu cuenta de Google</li>
            <li>Ve a: Cuenta Google → Seguridad → Verificación en 2 pasos → Contraseñas de aplicaciones</li>
          </ol>
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-6">
        <!-- Host SMTP -->
        <div>
          <label class="block text-sm font-medium mb-2">Servidor SMTP *</label>
          <input 
            v-model="configSmtp.host" 
            type="text" 
            placeholder="smtp.ejemplo.com"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-junta-green-500 dark:bg-gray-800 dark:border-gray-600"
            required
          />
        </div>

        <!-- Puerto -->
        <div>
          <label class="block text-sm font-medium mb-2">Puerto *</label>
          <input 
            v-model.number="configSmtp.port" 
            type="number" 
            placeholder="587"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-junta-green-500 dark:bg-gray-800 dark:border-gray-600"
            required
          />
        </div>

        <!-- Encriptación -->
        <div>
          <label class="block text-sm font-medium mb-2">Encriptación *</label>
          <select 
            v-model="configSmtp.encryption"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-junta-green-500 dark:bg-gray-800 dark:border-gray-600"
          >
            <option value="tls">TLS (recomendado)</option>
            <option value="ssl">SSL</option>
            <option value="none">Sin encriptación</option>
          </select>
        </div>

        <!-- Usuario -->
        <div>
          <label class="block text-sm font-medium mb-2">Usuario SMTP</label>
          <input 
            v-model="configSmtp.username" 
            type="text" 
            placeholder="usuario@ejemplo.com"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-junta-green-500 dark:bg-gray-800 dark:border-gray-600"
          />
        </div>

        <!-- Contraseña -->
        <div>
          <label class="block text-sm font-medium mb-2">Contraseña SMTP</label>
          <input 
            v-model="configSmtp.password" 
            type="password" 
            placeholder="••••••••"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-junta-green-500 dark:bg-gray-800 dark:border-gray-600"
          />
          <p v-if="configSmtp.tiene_password && !configSmtp.password" class="text-xs text-gray-500 mt-1">
            Dejar en blanco para mantener la contraseña actual
          </p>
        </div>

        <!-- Email remitente -->
        <div>
          <label class="block text-sm font-medium mb-2">Email Remitente *</label>
          <input 
            v-model="configSmtp.from_address" 
            type="email" 
            placeholder="noreply@juntadeandalucia.es"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-junta-green-500 dark:bg-gray-800 dark:border-gray-600"
            required
          />
        </div>

        <!-- Nombre remitente -->
        <div>
          <label class="block text-sm font-medium mb-2">Nombre Remitente *</label>
          <input 
            v-model="configSmtp.from_name" 
            type="text" 
            placeholder="Gestión de Material"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-junta-green-500 dark:bg-gray-800 dark:border-gray-600"
            required
          />
        </div>

        <!-- Email de prueba -->
        <div>
          <label class="block text-sm font-medium mb-2">Email para Prueba</label>
          <input 
            v-model="emailPrueba" 
            type="email" 
            placeholder="tu.email@ejemplo.com"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-junta-green-500 dark:bg-gray-800 dark:border-gray-600"
          />
        </div>
      </div>

      <!-- Última prueba -->
      <div v-if="configSmtp.ultima_prueba" class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
        <h4 class="text-sm font-semibold mb-2">Última prueba realizada</h4>
        <p class="text-sm text-gray-600 dark:text-gray-400">
          <span class="font-medium">Fecha:</span> {{ new Date(configSmtp.ultima_prueba).toLocaleString('es-ES') }}
        </p>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
          <span class="font-medium">Resultado:</span> 
          <span :class="configSmtp.resultado_prueba?.includes('Exitoso') ? 'text-green-600' : 'text-red-600'">
            {{ configSmtp.resultado_prueba }}
          </span>
        </p>
      </div>

      <!-- Botones de acción -->
      <div class="flex gap-3">
        <button 
          @click="guardarSmtp" 
          :disabled="guardandoSmtp"
          class="btn btn-primary">
          <svg v-if="!guardandoSmtp" class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
          <svg v-else class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ guardandoSmtp ? 'Guardando...' : 'Guardar Configuración' }}
        </button>

        <button 
          @click="probarSmtp" 
          :disabled="probandoSmtp || !emailPrueba"
          class="btn btn-secondary">
          <svg v-if="!probandoSmtp" class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
          </svg>
          <svg v-else class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ probandoSmtp ? 'Enviando...' : 'Enviar Email de Prueba' }}
        </button>
      </div>
    </div>

    <!-- Tab: Usuarios Administradores -->
    <div v-if="tabActiva === 'usuarios'" class="card p-6 space-y-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-lg font-semibold mb-2">Usuarios Administradores</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400">Gestiona los usuarios con acceso administrativo al sistema.</p>
        </div>
        <button @click="abrirNuevoUsuario" class="btn btn-primary">
          <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
          </svg>
          Nuevo Usuario
        </button>
      </div>

      <div v-if="mensajeUsuarios" :class="['p-4 rounded-lg text-sm mb-4', mensajeUsuarios.tipo === 'success' ? 'bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-400']">
        {{ mensajeUsuarios.texto }}
      </div>

      <!-- Tabla de usuarios -->
      <div v-if="cargandoUsuarios" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-junta-green-600"></div>
        <p class="text-gray-500 mt-2">Cargando usuarios...</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nombre</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rol</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Último Acceso</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="usuario in usuarios" :key="usuario.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ usuario.nombre }} {{ usuario.apellido }}</div>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-600 dark:text-gray-400">{{ usuario.email }}</div>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <span :class="['px-2 py-1 text-xs font-medium rounded-full',
                  usuario.role === 'admin' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' :
                  usuario.role === 'gestor' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' :
                  'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400']">
                  {{ usuario.role === 'admin' ? 'Administrador' : usuario.role === 'gestor' ? 'Gestor' : 'Usuario' }}
                </span>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <span :class="['px-2 py-1 text-xs font-medium rounded-full', usuario.activo ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400']">
                  {{ usuario.activo ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                {{ usuario.ultimo_acceso ? new Date(usuario.ultimo_acceso).toLocaleString('es-ES') : 'Nunca' }}
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-right text-sm">
                <button @click="editarUsuario(usuario)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mr-3">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                  </svg>
                </button>
                <button @click="confirmarEliminarUsuario(usuario)" class="text-red-600 hover:text-red-800 dark:text-red-400">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                </button>
              </td>
            </tr>
            <tr v-if="usuarios.length === 0">
              <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                No hay usuarios registrados
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Crear/Editar Usuario -->
    <transition name="fade">
      <div v-if="modalUsuario.open" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="cerrarModalUsuario">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl max-w-md w-full p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">{{ modalUsuario.editar ? 'Editar Usuario' : 'Nuevo Usuario' }}</h3>
            <button @click="cerrarModalUsuario" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <form @submit.prevent="guardarUsuario" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-2">Nombre *</label>
                <input 
                  v-model="formUsuario.nombre" 
                  type="text" 
                  required
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-junta-green-500 dark:bg-gray-800 dark:border-gray-600"
                />
              </div>
              <div>
                <label class="block text-sm font-medium mb-2">Apellido *</label>
                <input 
                  v-model="formUsuario.apellido" 
                  type="text" 
                  required
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-junta-green-500 dark:bg-gray-800 dark:border-gray-600"
                />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">Email *</label>
              <input 
                v-model="formUsuario.email" 
                type="email" 
                required
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-junta-green-500 dark:bg-gray-800 dark:border-gray-600"
              />
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">{{ modalUsuario.editar ? 'Nueva Contraseña' : 'Contraseña *' }}</label>
              <input 
                v-model="formUsuario.password" 
                type="password" 
                :required="!modalUsuario.editar"
                :placeholder="modalUsuario.editar ? 'Dejar en blanco para no cambiar' : ''"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-junta-green-500 dark:bg-gray-800 dark:border-gray-600"
              />
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">Rol *</label>
              <select
                v-model="formUsuario.role"
                required
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-junta-green-500 dark:bg-gray-800 dark:border-gray-600"
              >
                <option value="admin">Administrador</option>
                <option value="gestor">Gestor</option>
                <option value="usuario">Usuario</option>
              </select>
            </div>

            <div class="flex items-center">
              <input 
                v-model="formUsuario.activo" 
                type="checkbox" 
                id="usuario-activo"
                class="h-4 w-4 text-junta-green-600 focus:ring-junta-green-500 border-gray-300 rounded"
              />
              <label for="usuario-activo" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Usuario activo</label>
            </div>

            <div class="flex gap-3 pt-4">
              <button type="submit" :disabled="guardandoUsuario" class="btn btn-primary flex-1">
                {{ guardandoUsuario ? 'Guardando...' : 'Guardar' }}
              </button>
              <button type="button" @click="cerrarModalUsuario" class="btn btn-secondary">
                Cancelar
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>

    <!-- Tab: Notificaciones Email -->
    <div v-if="tabActiva === 'notificaciones'" class="card p-6 space-y-6">
      <div>
        <h3 class="text-lg font-semibold mb-2">Configuración de Notificaciones por Email</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Configura qué eventos del sistema enviarán notificaciones por email a usuarios y administradores.
        </p>
      </div>

      <div v-if="mensajeNotificaciones" :class="['p-4 rounded-lg text-sm mb-4', mensajeNotificaciones.tipo === 'success' ? 'bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-400']">
        {{ mensajeNotificaciones.texto }}
      </div>

      <div v-if="cargandoNotificaciones" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-junta-green-600"></div>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Cargando configuración...</p>
      </div>

      <div v-else class="space-y-6">
        <!-- Peticiones -->
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
          <h4 class="font-semibold mb-3 text-junta-green-600">📋 Peticiones de Material</h4>
          <div class="space-y-3">
            <div v-for="notif in notificacionesFiltradas('peticion')" :key="notif.id" class="flex items-start justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
              <div class="flex-1">
                <p class="text-sm font-medium">{{ getTituloEvento(notif.evento) }}</p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ notif.descripcion }}</p>
              </div>
              <div class="flex gap-4 ml-4">
                <label class="flex items-center cursor-pointer">
                  <input 
                    type="checkbox" 
                    v-model="notif.notificar_usuario"
                    @change="marcarCambio(notif.id)"
                    class="w-4 h-4 text-junta-green-600 border-gray-300 rounded focus:ring-junta-green-500"
                  />
                  <span class="ml-2 text-xs text-gray-700 dark:text-gray-300">👤 Usuario</span>
                </label>
                <label class="flex items-center cursor-pointer">
                  <input 
                    type="checkbox" 
                    v-model="notif.notificar_admin"
                    @change="marcarCambio(notif.id)"
                    class="w-4 h-4 text-junta-green-600 border-gray-300 rounded focus:ring-junta-green-500"
                  />
                  <span class="ml-2 text-xs text-gray-700 dark:text-gray-300">👨‍💼 Admin</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Movimientos -->
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
          <h4 class="font-semibold mb-3 text-junta-green-600">📦 Movimientos de Material</h4>
          <div class="space-y-3">
            <div v-for="notif in notificacionesFiltradas('movimiento')" :key="notif.id" class="flex items-start justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
              <div class="flex-1">
                <p class="text-sm font-medium">{{ getTituloEvento(notif.evento) }}</p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ notif.descripcion }}</p>
              </div>
              <div class="flex gap-4 ml-4">
                <label class="flex items-center cursor-pointer">
                  <input 
                    type="checkbox" 
                    v-model="notif.notificar_usuario"
                    @change="marcarCambio(notif.id)"
                    class="w-4 h-4 text-junta-green-600 border-gray-300 rounded focus:ring-junta-green-500"
                  />
                  <span class="ml-2 text-xs text-gray-700 dark:text-gray-300">👤 Usuario</span>
                </label>
                <label class="flex items-center cursor-pointer">
                  <input 
                    type="checkbox" 
                    v-model="notif.notificar_admin"
                    @change="marcarCambio(notif.id)"
                    class="w-4 h-4 text-junta-green-600 border-gray-300 rounded focus:ring-junta-green-500"
                  />
                  <span class="ml-2 text-xs text-gray-700 dark:text-gray-300">👨‍💼 Admin</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Recordatorios -->
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
          <h4 class="font-semibold mb-3 text-junta-green-600">⏰ Recordatorios y Alertas</h4>
          <div class="space-y-3">
            <div v-for="notif in notificacionesFiltradas('recordatorio|entrega|vencida')" :key="notif.id" class="flex items-start justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
              <div class="flex-1">
                <p class="text-sm font-medium">{{ getTituloEvento(notif.evento) }}</p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ notif.descripcion }}</p>
              </div>
              <div class="flex gap-4 ml-4">
                <label class="flex items-center cursor-pointer">
                  <input 
                    type="checkbox" 
                    v-model="notif.notificar_usuario"
                    @change="marcarCambio(notif.id)"
                    class="w-4 h-4 text-junta-green-600 border-gray-300 rounded focus:ring-junta-green-500"
                  />
                  <span class="ml-2 text-xs text-gray-700 dark:text-gray-300">👤 Usuario</span>
                </label>
                <label class="flex items-center cursor-pointer">
                  <input 
                    type="checkbox" 
                    v-model="notif.notificar_admin"
                    @change="marcarCambio(notif.id)"
                    class="w-4 h-4 text-junta-green-600 border-gray-300 rounded focus:ring-junta-green-500"
                  />
                  <span class="ml-2 text-xs text-gray-700 dark:text-gray-300">👨‍💼 Admin</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Firmas -->
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
          <h4 class="font-semibold mb-3 text-junta-green-600">✍️ Firmas Digitales</h4>
          <div class="space-y-3">
            <div v-for="notif in notificacionesFiltradas('firma|solicitud')" :key="notif.id" class="flex items-start justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
              <div class="flex-1">
                <p class="text-sm font-medium">{{ getTituloEvento(notif.evento) }}</p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ notif.descripcion }}</p>
              </div>
              <div class="flex gap-4 ml-4">
                <label class="flex items-center cursor-pointer">
                  <input 
                    type="checkbox" 
                    v-model="notif.notificar_usuario"
                    @change="marcarCambio(notif.id)"
                    class="w-4 h-4 text-junta-green-600 border-gray-300 rounded focus:ring-junta-green-500"
                  />
                  <span class="ml-2 text-xs text-gray-700 dark:text-gray-300">👤 Usuario</span>
                </label>
                <label class="flex items-center cursor-pointer">
                  <input 
                    type="checkbox" 
                    v-model="notif.notificar_admin"
                    @change="marcarCambio(notif.id)"
                    class="w-4 h-4 text-junta-green-600 border-gray-300 rounded focus:ring-junta-green-500"
                  />
                  <span class="ml-2 text-xs text-gray-700 dark:text-gray-300">👨‍💼 Admin</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Información sobre comando programado -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
          <h4 class="text-sm font-semibold mb-2 text-blue-800 dark:text-blue-400">ℹ️ Sobre los recordatorios automáticos</h4>
          <p class="text-xs text-blue-700 dark:text-blue-300">
            Los recordatorios de entrega y alertas de entregas vencidas se envían automáticamente mediante tareas programadas del sistema.
            Para activarlos, asegúrate de que el cron está configurado en el servidor:
          </p>
          <code class="block mt-2 p-2 bg-gray-900 text-green-400 text-xs rounded">
            php artisan notificaciones:recordatorios-entrega<br/>
            php artisan notificaciones:entregas-vencidas
          </code>
        </div>

        <!-- Botón guardar -->
        <div class="flex justify-end">
          <button 
            @click="guardarConfiguracionNotificaciones" 
            :disabled="guardandoNotificaciones || !hayNotificacionesCambiadas"
            class="btn btn-primary">
            <svg v-if="!guardandoNotificaciones" class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <svg v-else class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ guardandoNotificaciones ? 'Guardando...' : hayNotificacionesCambiadas ? 'Guardar Cambios' : 'Sin Cambios' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Tab: Copia de Seguridad -->
    <div v-if="tabActiva === 'backup'" class="space-y-6">
      <!-- Crear Copia de Seguridad -->
      <div class="card p-6 space-y-4">
        <div>
          <h3 class="text-lg font-semibold mb-2 flex items-center gap-2">
            <svg class="h-6 w-6 text-junta-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
            </svg>
            Crear Copia de Seguridad
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Genera una copia de seguridad completa de la base de datos. El archivo SQL se descargará automáticamente.
          </p>
        </div>

        <div v-if="mensajeBackup" :class="['p-4 rounded-lg text-sm', mensajeBackup.tipo === 'success' ? 'bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-400']">
          {{ mensajeBackup.texto }}
        </div>

        <div class="flex gap-3">
          <button 
            @click="crearBackup" 
            :disabled="creandoBackup"
            class="btn btn-primary">
            <svg v-if="!creandoBackup" class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
            </svg>
            <svg v-else class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ creandoBackup ? 'Generando backup...' : 'Descargar Copia de Seguridad' }}
          </button>
        </div>

        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
          <p class="text-xs text-blue-700 dark:text-blue-300">
            <strong>ℹ️ Información:</strong> La copia de seguridad incluye todas las tablas de la base de datos con datos y estructura completa.
            Se recomienda realizar backups periódicamente, especialmente antes de actualizaciones importantes.
          </p>
        </div>
      </div>

      <!-- Restaurar Copia de Seguridad -->
      <div class="card p-6 space-y-4">
        <div>
          <h3 class="text-lg font-semibold mb-2 flex items-center gap-2">
            <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Restaurar Copia de Seguridad
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Restaura una copia de seguridad desde un archivo SQL. <strong class="text-red-600">¡ATENCIÓN! Esto sobrescribirá todos los datos actuales.</strong>
          </p>
        </div>

        <div v-if="mensajeRestore" :class="['p-4 rounded-lg text-sm', mensajeRestore.tipo === 'success' ? 'bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-400']">
          {{ mensajeRestore.texto }}
        </div>

        <div class="space-y-3">
          <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center">
            <input 
              type="file" 
              ref="inputBackup" 
              @change="handleFileBackup" 
              accept=".sql,.sql.gz" 
              class="hidden" 
            />
            <button @click="$refs.inputBackup.click()" class="btn btn-secondary">
              <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
              </svg>
              Seleccionar archivo SQL
            </button>
            <p v-if="archivoBackup" class="text-sm text-gray-600 mt-2">
              📁 {{ archivoBackup.name }} ({{ (archivoBackup.size / 1024 / 1024).toFixed(2) }} MB)
            </p>
            <p v-else class="text-xs text-gray-500 mt-2">Archivos aceptados: .sql, .sql.gz</p>
          </div>

          <button 
            v-if="archivoBackup" 
            @click="restaurarBackup" 
            :disabled="restaurandoBackup"
            class="btn bg-amber-600 hover:bg-amber-700 text-white w-full">
            <svg v-if="!restaurandoBackup" class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <svg v-else class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ restaurandoBackup ? 'Restaurando...' : 'Restaurar Base de Datos' }}
          </button>
        </div>

        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
          <p class="text-xs text-amber-700 dark:text-amber-300">
            <strong>⚠️ ADVERTENCIA:</strong> La restauración eliminará TODOS los datos actuales y los reemplazará con el contenido del archivo de respaldo.
            Asegúrate de tener una copia de seguridad reciente antes de proceder. Esta acción NO se puede deshacer.
          </p>
        </div>
      </div>

      <!-- Resetear Sistema -->
      <div class="card p-6 space-y-4 border-2 border-red-200 dark:border-red-800">
        <div>
          <h3 class="text-lg font-semibold mb-2 flex items-center gap-2 text-red-600 dark:text-red-400">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            Resetear Sistema (Datos de Prueba)
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Elimina TODOS los datos introducidos (referencias, movimientos, peticiones, etc.) para empezar desde cero.
            <strong class="text-red-600">¡ESTA ACCIÓN ES IRREVERSIBLE!</strong>
          </p>
        </div>

        <div v-if="mensajeReset" :class="['p-4 rounded-lg text-sm', mensajeReset.tipo === 'success' ? 'bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-400']">
          {{ mensajeReset.texto }}
        </div>

        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 space-y-3">
          <p class="text-sm font-semibold text-red-800 dark:text-red-300">
            🚨 ZONA DE PELIGRO - Lo siguiente se eliminará:
          </p>
          <ul class="text-xs text-red-700 dark:text-red-300 space-y-1 ml-4 list-disc">
            <li>Todas las referencias de material</li>
            <li>Todos los movimientos y firmas</li>
            <li>Todas las peticiones públicas</li>
            <li>Todo el historial y auditoría</li>
            <li>Fotos de material</li>
            <li>Campos personalizados y sus valores</li>
          </ul>
          <p class="text-xs text-red-700 dark:text-red-300 mt-3">
            <strong>NO se eliminarán:</strong> Usuarios, categorías, sedes, departamentos ni configuración del sistema.
          </p>
        </div>

        <button 
          @click="iniciarResetSistema" 
          :disabled="resetandoSistema"
          class="btn bg-red-600 hover:bg-red-700 text-white w-full">
          <svg v-if="!resetandoSistema" class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
          </svg>
          <svg v-else class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ resetandoSistema ? 'Eliminando datos...' : '⚠️ ELIMINAR TODOS LOS DATOS' }}
        </button>
      </div>
    </div>
    <!-- Tab: Gestión de Almacenes -->
    <div v-if="tabActiva === 'almacenes'" class="card p-6">
      <ConfiguracionAlmacenes />
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import axios from 'axios';
import ConfiguracionAlmacenes from './ConfiguracionAlmacenes.vue';

// Tabs
const tabActiva = ref('logotipos');

// --- USUARIOS ---
const usuarios = ref([]);
const cargandoUsuarios = ref(false);
const guardandoUsuario = ref(false);
const mensajeUsuarios = ref(null);

const modalUsuario = ref({ open: false, editar: false });
const formUsuario = ref({
  id: null,
  nombre: '',
  apellido: '',
  email: '',
  password: '',
  role: 'usuario',
  activo: true
});

// --- NOTIFICACIONES EMAIL ---
const notificacionesSettings = ref([]);
const cargandoNotificaciones = ref(false);
const guardandoNotificaciones = ref(false);
const mensajeNotificaciones = ref(null);
const notificacionesCambiadas = ref(new Set());

// --- JUSTIFICANTES ---
const justificantesEntrada = ref([]);
const justificantesSalida = ref([]);
const cargandoJustificantes = ref(false);
const guardandoJustificante = ref(false);
const mensajeJustificantes = ref(null);

const modalJustificante = ref({ open: false, editar: false });
const formJustificante = ref({ 
  id: null, 
  tipo: 'entrada', 
  nombre: '', 
  descripcion: '', 
  activo: true 
});

const cargarJustificantes = async () => {
  cargandoJustificantes.value = true;
  try {
    const [resEntrada, resSalida] = await Promise.all([
      axios.get('/config/justificantes', { params: { tipo: 'entrada' } }),
      axios.get('/config/justificantes', { params: { tipo: 'salida' } })
    ]);
    justificantesEntrada.value = resEntrada.data.data || [];
    justificantesSalida.value = resSalida.data.data || [];
  } catch (e) {
    console.error('Error cargando justificantes:', e);
    mensajeJustificantes.value = { tipo: 'error', texto: 'Error al cargar justificantes' };
  } finally {
    cargandoJustificantes.value = false;
  }
};

const abrirNuevoJustificante = (tipo) => {
  modalJustificante.value = { open: true, editar: false };
  formJustificante.value = { 
    id: null, 
    tipo, 
    nombre: '', 
    descripcion: '', 
    activo: true 
  };
};

const editarJustificante = (j) => {
  modalJustificante.value = { open: true, editar: true };
  formJustificante.value = { 
    id: j.id, 
    tipo: j.tipo, 
    nombre: j.nombre, 
    descripcion: j.descripcion || '', 
    activo: j.activo 
  };
};

const cerrarModalJustificante = () => {
  modalJustificante.value.open = false;
  mensajeJustificantes.value = null;
};

const guardarJustificante = async () => {
  guardandoJustificante.value = true;
  mensajeJustificantes.value = null;
  
  try {
    const payload = { ...formJustificante.value };
    delete payload.id;
    
    if (modalJustificante.value.editar) {
      await axios.put(`/config/justificantes/${formJustificante.value.id}`, payload);
      mensajeJustificantes.value = { tipo: 'success', texto: 'Justificante actualizado correctamente' };
    } else {
      await axios.post('/config/justificantes', payload);
      mensajeJustificantes.value = { tipo: 'success', texto: 'Justificante creado correctamente' };
    }
    
    modalJustificante.value.open = false;
    await cargarJustificantes();
    
  } catch (e) {
    console.error('Error guardando justificante:', e);
    mensajeJustificantes.value = { 
      tipo: 'error', 
      texto: e.response?.data?.message || 'Error al guardar el justificante' 
    };
  } finally {
    guardandoJustificante.value = false;
  }
};

const eliminarJustificante = async (j) => {
  if (!confirm(`¿Eliminar el justificante "${j.nombre}"?`)) return;
  
  try {
    await axios.delete(`/config/justificantes/${j.id}`);
    mensajeJustificantes.value = { tipo: 'success', texto: 'Justificante eliminado correctamente' };
    await cargarJustificantes();
  } catch (e) {
    console.error('Error eliminando justificante:', e);
    mensajeJustificantes.value = { tipo: 'error', texto: 'Error al eliminar el justificante' };
  }
};

const toggleActivoJustificante = async (j) => {
  try {
    await axios.put(`/config/justificantes/${j.id}`, { activo: !j.activo });
    mensajeJustificantes.value = { 
      tipo: 'success', 
      texto: `Justificante ${!j.activo ? 'activado' : 'desactivado'} correctamente` 
    };
    await cargarJustificantes();
  } catch (e) {
    console.error('Error actualizando justificante:', e);
    mensajeJustificantes.value = { tipo: 'error', texto: 'Error al actualizar el justificante' };
  }
};

const moverJustificante = async (idx, delta, tipo) => {
  const lista = tipo === 'entrada' ? justificantesEntrada.value : justificantesSalida.value;
  const i2 = idx + delta;
  
  if (i2 < 0 || i2 >= lista.length) return;
  
  const a = lista[idx];
  const b = lista[i2];
  const tmp = a.orden;
  a.orden = b.orden;
  b.orden = tmp;
  
  // Persistir
  const orders = {};
  lista.forEach((j, i) => { orders[j.id] = j.orden ?? i; });
  
  try {
    await axios.patch('/config/justificantes/reordenar', { orders });
    // Reordenar localmente
    lista.splice(idx, 1);
    lista.splice(i2, 0, a);
  } catch (e) {
    console.error('Error reordenando:', e);
    mensajeJustificantes.value = { tipo: 'error', texto: 'Error al reordenar' };
    await cargarJustificantes(); // Recargar si falla
  }
};

// --- USUARIOS ---
const cargarUsuarios = async () => {
  cargandoUsuarios.value = true;
  try {
    const res = await axios.get('/usuarios');
    usuarios.value = res.data.data || res.data || [];
  } catch (e) {
    console.error('Error cargando usuarios:', e);
    mensajeUsuarios.value = { tipo: 'error', texto: 'Error al cargar usuarios' };
  } finally {
    cargandoUsuarios.value = false;
  }
};

const abrirNuevoUsuario = () => {
  modalUsuario.value = { open: true, editar: false };
  formUsuario.value = {
    id: null,
    nombre: '',
    apellido: '',
    email: '',
    password: '',
    role: 'usuario',
    activo: true
  };
  mensajeUsuarios.value = null;
};

const editarUsuario = (u) => {
  modalUsuario.value = { open: true, editar: true };
  formUsuario.value = {
    id: u.id,
    nombre: u.nombre,
    apellido: u.apellido,
    email: u.email,
    password: '',
    role: u.role,
    activo: u.activo
  };
  mensajeUsuarios.value = null;
};

const cerrarModalUsuario = () => {
  modalUsuario.value.open = false;
  mensajeUsuarios.value = null;
};

const guardarUsuario = async () => {
  guardandoUsuario.value = true;
  mensajeUsuarios.value = null;
  
  try {
    const payload = { ...formUsuario.value };
    delete payload.id;
    
    // No enviar password si está vacío en edición
    if (modalUsuario.value.editar && !payload.password) {
      delete payload.password;
    }
    
    if (modalUsuario.value.editar) {
      await axios.put(`/usuarios/${formUsuario.value.id}`, payload);
      mensajeUsuarios.value = { tipo: 'success', texto: 'Usuario actualizado correctamente' };
    } else {
      await axios.post('/usuarios', payload);
      mensajeUsuarios.value = { tipo: 'success', texto: 'Usuario creado correctamente' };
    }
    
    modalUsuario.value.open = false;
    await cargarUsuarios();
    
  } catch (e) {
    console.error('Error guardando usuario:', e);
    mensajeUsuarios.value = { 
      tipo: 'error', 
      texto: e.response?.data?.message || 'Error al guardar el usuario' 
    };
  } finally {
    guardandoUsuario.value = false;
  }
};

const confirmarEliminarUsuario = async (u) => {
  if (!confirm(`¿Estás seguro de eliminar al usuario "${u.nombre} ${u.apellido}"?\n\nEsta acción no se puede deshacer.`)) {
    return;
  }
  
  try {
    await axios.delete(`/usuarios/${u.id}`);
    mensajeUsuarios.value = { tipo: 'success', texto: 'Usuario eliminado correctamente' };
    await cargarUsuarios();
  } catch (e) {
    console.error('Error eliminando usuario:', e);
    mensajeUsuarios.value = { 
      tipo: 'error', 
      texto: e.response?.data?.message || 'Error al eliminar el usuario' 
    };
  }
};

// --- LOGOTIPOS ---
const inputJunta = ref(null);
const inputAda = ref(null);
const archivoJunta = ref(null);
const archivoAda = ref(null);
const previewJunta = ref(null);
const previewAda = ref(null);
const subiendoJunta = ref(false);
const subiendoAda = ref(false);
const mensajeLogos = ref(null);
const appConfig = ref({ app_domain: 'http://10.66.129.108' });
const guardandoAppConfig = ref(false);
const mensajeAppConfig = ref(null);

const handleFileJunta = (e) => {
  const file = e.target.files[0];
  if (!file) return;
  archivoJunta.value = file;
  const reader = new FileReader();
  reader.onload = (ev) => { previewJunta.value = ev.target.result; };
  reader.readAsDataURL(file);
};

const handleFileAda = (e) => {
  const file = e.target.files[0];
  if (!file) return;
  archivoAda.value = file;
  const reader = new FileReader();
  reader.onload = (ev) => { previewAda.value = ev.target.result; };
  reader.readAsDataURL(file);
};

const subirLogo = async (tipo) => {
  const archivo = tipo === 'junta' ? archivoJunta.value : archivoAda.value;
  if (!archivo) return;
  
  const formData = new FormData();
  formData.append('logo', archivo);
  
  if (tipo === 'junta') subiendoJunta.value = true;
  else subiendoAda.value = true;
  
  mensajeLogos.value = null;
  
  try {
    await axios.post(`/config/upload-logo/${tipo}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    
    mensajeLogos.value = { tipo: 'success', texto: `Logotipo ${tipo === 'junta' ? 'Junta' : 'ADA'} actualizado correctamente. Recarga la página para ver los cambios.` };
    
    // Limpiar
    if (tipo === 'junta') {
      archivoJunta.value = null;
      previewJunta.value = null;
      inputJunta.value.value = '';
    } else {
      archivoAda.value = null;
      previewAda.value = null;
      inputAda.value.value = '';
    }
    
    // Forzar recarga de imagen añadiendo timestamp
    setTimeout(() => {
      const timestamp = new Date().getTime();
      if (tipo === 'junta') {
        document.querySelectorAll('img[src*="junta-logo"]').forEach(img => {
          img.src = `/images/junta-logo.png?v=${timestamp}`;
        });
      } else {
        document.querySelectorAll('img[src*="ada-logo"]').forEach(img => {
          img.src = `/images/ada-logo.png?v=${timestamp}`;
        });
      }
    }, 500);
    
  } catch (error) {
    console.error('Error subiendo logo:', error);
    mensajeLogos.value = { tipo: 'error', texto: `Error al subir el logotipo: ${error.response?.data?.message || error.message}` };
  } finally {
    if (tipo === 'junta') subiendoJunta.value = false;
    else subiendoAda.value = false;
  }
};

const cargarAppConfig = async () => {
  try {
    const { data } = await axios.get('/config/app-config');
    if (data.success && data.data) {
      appConfig.value = data.data;
    }
  } catch (error) {
    console.error('Error cargando configuración de app:', error);
  }
};

const guardarAppConfig = async () => {
  guardandoAppConfig.value = true;
  mensajeAppConfig.value = null;
  
  try {
    const { data } = await axios.put('/config/app-config', {
      app_domain: appConfig.value.app_domain
    });
    
    if (data.success) {
      mensajeAppConfig.value = { tipo: 'success', texto: 'Configuración guardada correctamente' };
    }
  } catch (error) {
    console.error('Error guardando configuración:', error);
    mensajeAppConfig.value = { tipo: 'error', texto: error.response?.data?.message || 'Error al guardar la configuración' };
  } finally {
    guardandoAppConfig.value = false;
  }
};

// --- CATEGORÍAS ---
const categorias = ref([]);
const cargandoCategorias = ref(false);
const guardandoCategoria = ref(false);
const mensajeCategorias = ref(null);

const modalCategoria = ref({ open: false, editar: false });
const formCategoria = ref({ 
  id: null, 
  nombre: '', 
  descripcion: '', 
  orden: 0,
  activo: true 
});

const modalImagenCategoria = ref({ open: false, categoria: null });
const archivoImagenCategoria = ref(null);
const previewImagenCategoria = ref(null);
const inputImagenCategoria = ref(null);
const subiendoImagenCategoria = ref(false);

const cargarCategorias = async () => {
  cargandoCategorias.value = true;
  try {
    const res = await axios.get('/config/categorias');
    categorias.value = res.data.data || [];
  } catch (e) {
    console.error('Error cargando categorías:', e);
    mensajeCategorias.value = { tipo: 'error', texto: 'Error al cargar categorías' };
  } finally {
    cargandoCategorias.value = false;
  }
};

const abrirNuevaCategoria = () => {
  modalCategoria.value = { open: true, editar: false };
  formCategoria.value = { 
    id: null, 
    nombre: '', 
    descripcion: '', 
    orden: 0,
    activo: true 
  };
};

const editarCategoria = (cat) => {
  modalCategoria.value = { open: true, editar: true };
  formCategoria.value = { ...cat };
};

const cerrarModalCategoria = () => {
  modalCategoria.value.open = false;
};

const guardarCategoria = async () => {
  guardandoCategoria.value = true;
  try {
    if (modalCategoria.value.editar) {
      await axios.put(`/config/categorias/${formCategoria.value.id}`, formCategoria.value);
      mensajeCategorias.value = { tipo: 'success', texto: 'Categoría actualizada correctamente' };
    } else {
      await axios.post('/config/categorias', formCategoria.value);
      mensajeCategorias.value = { tipo: 'success', texto: 'Categoría creada correctamente' };
    }
    modalCategoria.value.open = false;
    await cargarCategorias();
  } catch (e) {
    console.error('Error guardando categoría:', e);
    mensajeCategorias.value = { tipo: 'error', texto: e.response?.data?.message || 'Error al guardar categoría' };
  } finally {
    guardandoCategoria.value = false;
  }
};

const toggleActivoCategoria = async (cat) => {
  try {
    await axios.put(`/config/categorias/${cat.id}`, { ...cat, activo: !cat.activo });
    mensajeCategorias.value = { tipo: 'success', texto: cat.activo ? 'Categoría desactivada' : 'Categoría activada' };
    await cargarCategorias();
  } catch (e) {
    console.error('Error cambiando estado categoría:', e);
    mensajeCategorias.value = { tipo: 'error', texto: 'Error al cambiar estado' };
  }
};

const eliminarCategoria = async (cat) => {
  if (!confirm(`¿Eliminar la categoría "${cat.nombre}"?\n\nSolo se puede eliminar si no tiene materiales asignados.`)) return;
  
  try {
    await axios.delete(`/config/categorias/${cat.id}`);
    mensajeCategorias.value = { tipo: 'success', texto: 'Categoría eliminada correctamente' };
    await cargarCategorias();
  } catch (e) {
    console.error('Error eliminando categoría:', e);
    mensajeCategorias.value = { tipo: 'error', texto: e.response?.data?.message || 'Error al eliminar categoría' };
  }
};

// Upload imagen categoría
const subirImagenCategoria = (cat) => {
  modalImagenCategoria.value = { open: true, categoria: cat };
  archivoImagenCategoria.value = null;
  previewImagenCategoria.value = null;
};

const cerrarModalImagenCategoria = () => {
  modalImagenCategoria.value.open = false;
  archivoImagenCategoria.value = null;
  previewImagenCategoria.value = null;
  if (inputImagenCategoria.value) {
    inputImagenCategoria.value.value = '';
  }
};

const handleFileImagenCategoria = (event) => {
  const file = event.target.files[0];
  if (!file) return;
  
  if (file.size > 2 * 1024 * 1024) {
    alert('La imagen es demasiado grande. Máximo 2MB.');
    return;
  }
  
  archivoImagenCategoria.value = file;
  
  const reader = new FileReader();
  reader.onload = (e) => {
    previewImagenCategoria.value = e.target.result;
  };
  reader.readAsDataURL(file);
};

const confirmarSubirImagenCategoria = async () => {
  if (!archivoImagenCategoria.value) return;
  
  subiendoImagenCategoria.value = true;
  try {
    const formData = new FormData();
    formData.append('imagen', archivoImagenCategoria.value);
    
    const res = await axios.post(`/config/categorias/${modalImagenCategoria.value.categoria.id}/upload-imagen`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    
    mensajeCategorias.value = { tipo: 'success', texto: 'Imagen subida correctamente' };
    cerrarModalImagenCategoria();
    await cargarCategorias();
  } catch (e) {
    console.error('Error subiendo imagen:', e);
    mensajeCategorias.value = { tipo: 'error', texto: e.response?.data?.message || 'Error al subir imagen' };
  } finally {
    subiendoImagenCategoria.value = false;
  }
};

// --- CAMPOS PERSONALIZADOS ---
const filtro = ref({ entity: 'impresora' });
const campos = ref([]);
const loading = ref(false);

const modal = ref({ open: false, editar: false });
const form = ref({ id: null, entity_type: 'impresora', label: '', key: '', type: 'text', required: false, options_raw: '' });

const cargar = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/config/campos', { params: { entity_type: filtro.value.entity } });
    campos.value = res.data.data || [];
  } finally { loading.value = false; }
};

const abrirNuevo = () => { modal.value = { open: true, editar: false }; form.value = { id: null, entity_type: filtro.value.entity, label: '', key: '', type: 'text', required: false, options_raw: '' }; };
const editar = (c) => { modal.value = { open: true, editar: true }; form.value = { id: c.id, entity_type: c.entity_type, label: c.label, key: c.key, type: c.type, required: c.required, options_raw: (c.options||[]).join('\n') }; };
const cerrarModal = () => { modal.value.open = false; };

const guardar = async () => {
  const payload = { ...form.value };
  if (payload.type === 'select') payload.options = (payload.options_raw||'').split('\n').map(s=>s.trim()).filter(Boolean);
  delete payload.options_raw;
  if (modal.value.editar) {
    await axios.put(`/config/campos/${payload.id}`, payload);
  } else {
    await axios.post('/config/campos', payload);
  }
  modal.value.open = false;
  await cargar();
};

const mover = async (idx, delta) => {
  const i2 = idx + delta; if (i2 < 0 || i2 >= campos.value.length) return;
  const a = campos.value[idx]; const b = campos.value[i2];
  const tmp = a.sort_order; a.sort_order = b.sort_order; b.sort_order = tmp;
  // Persistir
  const orders = {}; campos.value.forEach((c,i)=> orders[c.id] = c.sort_order ?? i);
  await axios.patch('/config/campos/reordenar', { orders });
  // Reordenar local
  campos.value.splice(idx, 1); campos.value.splice(i2, 0, a);
};

const eliminar = async (c) => {
  if (!confirm(`¿Eliminar el campo "${c.label}"?`)) return;
  await axios.delete(`/config/campos/${c.id}`);
  await cargar();
};

// --- BACKUP Y RESTORE ---
const creandoBackup = ref(false);
const restaurandoBackup = ref(false);
const resetandoSistema = ref(false);
const mensajeBackup = ref(null);
const mensajeRestore = ref(null);
const mensajeReset = ref(null);
const archivoBackup = ref(null);
const inputBackup = ref(null);

const crearBackup = async () => {
  if (!confirm('¿Deseas crear una copia de seguridad completa de la base de datos?')) return;
  
  creandoBackup.value = true;
  mensajeBackup.value = null;
  
  try {
    const response = await axios.get('/config/backup/crear', {
      responseType: 'blob'
    });
    
    // Crear enlace de descarga
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    
    // Generar nombre de archivo con fecha
    const fecha = new Date().toISOString().split('T')[0];
    link.setAttribute('download', `backup_gestor_material_${fecha}.sql`);
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    mensajeBackup.value = { 
      tipo: 'success', 
      texto: '✅ Copia de seguridad creada y descargada correctamente' 
    };
  } catch (e) {
    console.error('Error creando backup:', e);
    mensajeBackup.value = { 
      tipo: 'error', 
      texto: e.response?.data?.message || 'Error al crear la copia de seguridad' 
    };
  } finally {
    creandoBackup.value = false;
  }
};

const handleFileBackup = (event) => {
  const file = event.target.files[0];
  if (file) {
    archivoBackup.value = file;
    mensajeRestore.value = null;
  }
};

const restaurarBackup = async () => {
  if (!archivoBackup.value) {
    mensajeRestore.value = { tipo: 'error', texto: 'Selecciona un archivo SQL primero' };
    return;
  }
  
  const confirmacion1 = prompt('⚠️ ADVERTENCIA: Esto eliminará TODOS los datos actuales.\n\nEscribe "CONFIRMAR" para continuar:');
  if (confirmacion1 !== 'CONFIRMAR') {
    mensajeRestore.value = { tipo: 'error', texto: 'Restauración cancelada' };
    return;
  }
  
  const confirmacion2 = prompt('¿Estás COMPLETAMENTE seguro? Esta acción NO se puede deshacer.\n\nEscribe "SI ESTOY SEGURO" para proceder:');
  if (confirmacion2 !== 'SI ESTOY SEGURO') {
    mensajeRestore.value = { tipo: 'error', texto: 'Restauración cancelada' };
    return;
  }
  
  restaurandoBackup.value = true;
  mensajeRestore.value = null;
  
  try {
    const formData = new FormData();
    formData.append('backup', archivoBackup.value);
    
    const { data } = await axios.post('/config/backup/restaurar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      timeout: 300000 // 5 minutos timeout
    });
    
    if (data.success) {
      mensajeRestore.value = { 
        tipo: 'success', 
        texto: '✅ Base de datos restaurada correctamente. La página se recargará...' 
      };
      
      // Recargar la página después de 2 segundos
      setTimeout(() => {
        window.location.reload();
      }, 2000);
    } else {
      mensajeRestore.value = { 
        tipo: 'error', 
        texto: data.message || 'Error al restaurar la base de datos' 
      };
    }
  } catch (e) {
    console.error('Error restaurando backup:', e);
    mensajeRestore.value = { 
      tipo: 'error', 
      texto: e.response?.data?.message || 'Error al restaurar la base de datos' 
    };
  } finally {
    restaurandoBackup.value = false;
    archivoBackup.value = null;
    if (inputBackup.value) inputBackup.value.value = '';
  }
};

const iniciarResetSistema = async () => {
  const confirmacion1 = prompt('🚨 PELIGRO: Vas a ELIMINAR TODOS LOS DATOS del sistema.\n\nEscribe "ELIMINAR TODO" para continuar:');
  if (confirmacion1 !== 'ELIMINAR TODO') {
    mensajeReset.value = { tipo: 'error', texto: 'Operación cancelada' };
    return;
  }
  
  const confirmacion2 = prompt('Esta es la última advertencia. Se eliminarán:\n- Referencias de material\n- Movimientos y firmas\n- Peticiones\n- Historial completo\n\n¿Continuar? Escribe "SI, BORRAR TODO":');
  if (confirmacion2 !== 'SI, BORRAR TODO') {
    mensajeReset.value = { tipo: 'error', texto: 'Operación cancelada' };
    return;
  }
  
  const confirmacion3 = prompt('Confirmación final. Escribe "CONFIRMO ELIMINACION":');
  if (confirmacion3 !== 'CONFIRMO ELIMINACION') {
    mensajeReset.value = { tipo: 'error', texto: 'Operación cancelada' };
    return;
  }
  
  resetandoSistema.value = true;
  mensajeReset.value = null;
  
  try {
    const { data } = await axios.post('/config/backup/reset-sistema', {}, {
      timeout: 120000 // 2 minutos timeout
    });
    
    if (data.success) {
      mensajeReset.value = { 
        tipo: 'success', 
        texto: `✅ Sistema reseteado correctamente. ${data.message}` 
      };
      
      // Recargar la página después de 2 segundos
      setTimeout(() => {
        window.location.reload();
      }, 2000);
    } else {
      mensajeReset.value = { 
        tipo: 'error', 
        texto: data.message || 'Error al resetear el sistema' 
      };
    }
  } catch (e) {
    console.error('Error reseteando sistema:', e);
    mensajeReset.value = { 
      tipo: 'error', 
      texto: e.response?.data?.message || 'Error al resetear el sistema' 
    };
  } finally {
    resetandoSistema.value = false;
  }
};

watch(() => filtro.value.entity, cargar);
onMounted(() => {
  if (tabActiva.value === 'campos') cargar();
  else if (tabActiva.value === 'justificantes') cargarJustificantes();
  else if (tabActiva.value === 'usuarios') cargarUsuarios();
  else if (tabActiva.value === 'logotipos') cargarAppConfig();
});
watch(() => tabActiva.value, (newTab) => {
  if (newTab === 'campos') cargar();
  else if (newTab === 'justificantes') cargarJustificantes();
  else if (newTab === 'categorias') cargarCategorias();
  else if (newTab === 'ubicaciones') {
    cargarSedes();
    cargarProvincias();
  }
  else if (newTab === 'smtp') cargarConfigSmtp();
  else if (newTab === 'usuarios') cargarUsuarios();
  else if (newTab === 'logotipos') cargarAppConfig();
});

// --- SEDES Y DEPARTAMENTOS ---
const sedes = ref([]);
const provincias = ref([]);
const sedeSeleccionada = ref(null);
const provinciaSeleccionada = ref(null);
const mensajeUbicaciones = ref(null);

// Computed property para filtrar sedes por provincia
const sedesFiltradas = computed(() => {
  if (!provinciaSeleccionada.value) {
    return sedes.value;
  }
  return sedes.value.filter(sede => sede.provincia_id === provinciaSeleccionada.value.id);
});

const cargarSedes = async () => {
  try {
    const res = await axios.get('/config/sedes');
    sedes.value = res.data.data || [];
    if (!sedeSeleccionada.value && sedes.value.length) sedeSeleccionada.value = sedes.value[0];
  } catch (e) {
    console.error(e);
  }
};

const cargarProvincias = async () => {
  try {
    const res = await axios.get('/config/provincias');
    provincias.value = res.data.data || [];
  } catch (e) {
    console.error('Error cargando provincias:', e);
  }
};

const seleccionarSede = (s) => { sedeSeleccionada.value = s; };

const seleccionarProvincia = (provincia) => {
  if (provinciaSeleccionada.value?.id === provincia.id) {
    // Si ya está seleccionada, limpiar el filtro
    provinciaSeleccionada.value = null;
  } else {
    provinciaSeleccionada.value = provincia;
  }
};

const limpiarFiltroProvincia = () => {
  provinciaSeleccionada.value = null;
};

const crearSedePrompt = async () => {
  const nombre = prompt('Nombre de la sede:');
  if (!nombre) return;
  
  // Mostrar selector de provincia
  const opcionesProvincias = provincias.value.map(p => `${p.id}: ${p.nombre}`).join('\n');
  const seleccionProvincia = prompt(`Selecciona una provincia (número):\n${opcionesProvincias}`);
  if (!seleccionProvincia) return;
  
  const provinciaId = parseInt(seleccionProvincia);
  const provincia = provincias.value.find(p => p.id === provinciaId);
  if (!provincia) {
    alert('Provincia no válida');
    return;
  }
  
  await axios.post('/config/sedes', { nombre, provincia_id: provinciaId });
  await cargarSedes();
  mensajeUbicaciones.value = { tipo: 'success', texto: `Sede creada en ${provincia.nombre}` };
};

const editarSedePrompt = async (s) => {
  const nombre = prompt('Nuevo nombre de la sede:', s.nombre);
  if (!nombre) return;
  
  // Mostrar selector de provincia
  const opcionesProvincias = provincias.value.map(p => `${p.id}: ${p.nombre}`).join('\n');
  const provinciaActual = provincias.value.find(p => p.id === s.provincia_id);
  const seleccionProvincia = prompt(`Selecciona una provincia (número):\n${opcionesProvincias}\n\nActual: ${provinciaActual ? provinciaActual.nombre : 'Sin provincia'}`);
  if (!seleccionProvincia) return;
  
  const provinciaId = parseInt(seleccionProvincia);
  const provincia = provincias.value.find(p => p.id === provinciaId);
  if (!provincia) {
    alert('Provincia no válida');
    return;
  }
  
  await axios.put(`/config/sedes/${s.id}`, { nombre, provincia_id: provinciaId });
  await cargarSedes();
  mensajeUbicaciones.value = { tipo: 'success', texto: `Sede actualizada en ${provincia.nombre}` };
};

const eliminarSede = async (s) => {
  if (!confirm(`¿Eliminar la sede "${s.nombre}" y sus departamentos?`)) return;
  await axios.delete(`/config/sedes/${s.id}`);
  await cargarSedes();
  sedeSeleccionada.value = sedes.value[0] || null;
  mensajeUbicaciones.value = { tipo: 'success', texto: 'Sede eliminada' };
};

const crearDepartamentoPrompt = async () => {
  if (!sedeSeleccionada.value) return;
  const nombre = prompt('Nombre del departamento:');
  if (!nombre) return;
  
  try {
    await axios.post(`/config/sedes/${sedeSeleccionada.value.id}/departamentos`, { nombre });
    await cargarSedes();
    // Reselect sede object after reload
    sedeSeleccionada.value = sedes.value.find(x => x.id === sedeSeleccionada.value.id) || sedes.value[0] || null;
    mensajeUbicaciones.value = { tipo: 'success', texto: 'Departamento creado' };
  } catch (e) {
    console.error('Error al crear departamento:', e);
    mensajeUbicaciones.value = { tipo: 'error', texto: 'Error al crear el departamento: ' + (e.response?.data?.message || e.message) };
  }
};

const editarDepartamentoPrompt = async (d) => {
  const nombre = prompt('Nuevo nombre del departamento:', d.nombre);
  if (!nombre) return;
  await axios.put(`/config/departamentos/${d.id}`, { nombre });
  await cargarSedes();
  sedeSeleccionada.value = sedes.value.find(x => x.id === sedeSeleccionada.value.id) || sedes.value[0] || null;
  mensajeUbicaciones.value = { tipo: 'success', texto: 'Departamento actualizado' };
};

const eliminarDepartamento = async (d) => {
  if (!confirm(`¿Eliminar el departamento "${d.nombre}"?`)) return;
  await axios.delete(`/config/departamentos/${d.id}`);
  await cargarSedes();
  sedeSeleccionada.value = sedes.value.find(x => x.id === sedeSeleccionada.value.id) || sedes.value[0] || null;
  mensajeUbicaciones.value = { tipo: 'success', texto: 'Departamento eliminado' };
};

const syncDepartamentos = async () => {
  try {
    await axios.post('/config/departamentos/sync');
    mensajeUbicaciones.value = { tipo: 'success', texto: 'Campo "Departamento" actualizado en todas las entidades' };
  } catch (e) {
    mensajeUbicaciones.value = { tipo: 'error', texto: 'No se pudo actualizar el campo Departamento' };
  }
};

// --- GESTIÓN DE PROVINCIAS ---
const subTabActiva = ref('sedes');
const cargandoProvincias = ref(false);

const crearProvinciaPrompt = async () => {
  const nombre = prompt('Nombre de la provincia:');
  if (!nombre) return;
  
  try {
    await axios.post('/config/provincias', { nombre });
    await cargarProvincias();
    mensajeUbicaciones.value = { tipo: 'success', texto: 'Provincia creada correctamente' };
  } catch (e) {
    console.error('Error al crear provincia:', e);
    mensajeUbicaciones.value = { tipo: 'error', texto: 'Error al crear la provincia: ' + (e.response?.data?.message || e.message) };
  }
};

const editarProvinciaPrompt = async (provincia) => {
  const nombre = prompt('Nuevo nombre de la provincia:', provincia.nombre);
  if (!nombre) return;
  
  try {
    await axios.put(`/config/provincias/${provincia.id}`, { nombre });
    await cargarProvincias();
    await cargarSedes(); // Actualizar sedes para mostrar los cambios
    mensajeUbicaciones.value = { tipo: 'success', texto: 'Provincia actualizada correctamente' };
  } catch (e) {
    console.error('Error al actualizar provincia:', e);
    mensajeUbicaciones.value = { tipo: 'error', texto: 'Error al actualizar la provincia: ' + (e.response?.data?.message || e.message) };
  }
};

const eliminarProvincia = async (provincia) => {
  if (!confirm(`¿Eliminar la provincia "${provincia.nombre}"?\n\nLas sedes asignadas a esta provincia quedarán sin provincia asignada.`)) return;
  
  try {
    await axios.delete(`/config/provincias/${provincia.id}`);
    await cargarProvincias();
    await cargarSedes(); // Actualizar sedes para mostrar los cambios
    mensajeUbicaciones.value = { tipo: 'success', texto: 'Provincia eliminada correctamente' };
  } catch (e) {
    console.error('Error al eliminar provincia:', e);
    mensajeUbicaciones.value = { tipo: 'error', texto: 'Error al eliminar la provincia: ' + (e.response?.data?.message || e.message) };
  }
};

// --- GESTIÓN DE ALMACENES EN DEPARTAMENTOS ---
const toggleAlmacenDepartamento = async (departamento) => {
  try {
    const nuevoEstado = !departamento.es_almacen;
    
    // Actualizar optimistamente en la interfaz
    departamento.es_almacen = nuevoEstado;
    
    // Usar la ruta específica para actualizar el estado de almacén
    const response = await axios.patch(`/config/departamentos/${departamento.id}/almacen`, {
      es_almacen: nuevoEstado
    });
    
    // Verificar que la respuesta del servidor sea correcta
    if (response.data && response.data.data) {
      // Actualizar con los datos del servidor por si hay alguna diferencia
      Object.assign(departamento, response.data.data);
    }
    
    mensajeUbicaciones.value = {
      tipo: 'success',
      texto: nuevoEstado ? 'Departamento marcado como almacén' : 'Departamento desmarcado como almacén'
    };
  } catch (e) {
    // Revertir el cambio si hay error
    departamento.es_almacen = !departamento.es_almacen;
    
    console.error('Error al actualizar estado de almacén:', e);
    mensajeUbicaciones.value = {
      tipo: 'error',
      texto: 'Error al actualizar el estado del almacén: ' + (e.response?.data?.message || e.message)
    };
  }
};

// --- SMTP ---
const configSmtp = ref({
  id: null,
  provider: 'custom',
  host: '',
  port: 587,
  encryption: 'tls',
  username: '',
  password: '',
  from_address: '',
  from_name: 'Gestión de Material',
  tiene_password: false,
  ultima_prueba: null,
  resultado_prueba: null,
});

const emailPrueba = ref('');
const guardandoSmtp = ref(false);
const probandoSmtp = ref(false);
const mensajeSmtp = ref(null);

// Presets de configuración
const aplicarPresetMicrosoft365 = () => {
  configSmtp.value.provider = 'microsoft365';
  configSmtp.value.host = 'smtp.office365.com';
  configSmtp.value.port = 587;
  configSmtp.value.encryption = 'tls';
  mensajeSmtp.value = { 
    tipo: 'success', 
    texto: 'Configuración de Microsoft 365 aplicada. Completa usuario y contraseña.' 
  };
};

const aplicarPresetGmail = () => {
  configSmtp.value.provider = 'gmail';
  configSmtp.value.host = 'smtp.gmail.com';
  configSmtp.value.port = 587;
  configSmtp.value.encryption = 'tls';
  mensajeSmtp.value = { 
    tipo: 'success', 
    texto: 'Configuración de Gmail aplicada. Usa una contraseña de aplicación.' 
  };
};

const aplicarPresetOutlook = () => {
  configSmtp.value.provider = 'outlook';
  configSmtp.value.host = 'smtp-mail.outlook.com';
  configSmtp.value.port = 587;
  configSmtp.value.encryption = 'tls';
  mensajeSmtp.value = { 
    tipo: 'success', 
    texto: 'Configuración de Outlook aplicada. Completa usuario y contraseña.' 
  };
};

const aplicarPresetPersonalizado = () => {
  configSmtp.value.provider = 'custom';
  mensajeSmtp.value = { 
    tipo: 'success', 
    texto: 'Modo personalizado activado. Configura manualmente los datos.' 
  };
};

const cargarConfigSmtp = async () => {
  try {
    const res = await axios.get('/config/smtp');
    if (res.data.success && res.data.data) {
      const data = res.data.data;
      configSmtp.value = {
        id: data.id,
        provider: data.provider || 'custom',
        host: data.host || '',
        port: data.port || 587,
        encryption: data.encryption || 'tls',
        username: data.username || '',
        password: '', // No cargar la contraseña por seguridad
        from_address: data.from_address || '',
        from_name: data.from_name || 'Gestión de Material',
        tiene_password: data.tiene_password || false,
        ultima_prueba: data.ultima_prueba || null,
        resultado_prueba: data.resultado_prueba || null,
      };
    }
  } catch (e) {
    console.error('Error cargando configuración SMTP:', e);
  }
};

const guardarSmtp = async () => {
  if (!configSmtp.value.host || !configSmtp.value.from_address || !configSmtp.value.from_name) {
    mensajeSmtp.value = { tipo: 'error', texto: 'Por favor complete todos los campos obligatorios' };
    return;
  }

  guardandoSmtp.value = true;
  mensajeSmtp.value = null;

  try {
    const payload = {
      provider: configSmtp.value.provider,
      host: configSmtp.value.host,
      port: configSmtp.value.port,
      encryption: configSmtp.value.encryption,
      username: configSmtp.value.username,
      from_address: configSmtp.value.from_address,
      from_name: configSmtp.value.from_name,
    };

    // Solo enviar password si se modificó
    if (configSmtp.value.password) {
      payload.password = configSmtp.value.password;
    }

    const res = await axios.post('/config/smtp', payload);
    
    if (res.data.success) {
      mensajeSmtp.value = { tipo: 'success', texto: 'Configuración SMTP guardada correctamente' };
      configSmtp.value.id = res.data.data.id;
      configSmtp.value.tiene_password = true;
      configSmtp.value.password = ''; // Limpiar el campo de contraseña
    }
  } catch (e) {
    console.error('Error guardando configuración SMTP:', e);
    mensajeSmtp.value = { 
      tipo: 'error', 
      texto: e.response?.data?.message || 'Error al guardar la configuración SMTP' 
    };
  } finally {
    guardandoSmtp.value = false;
  }
};

const probarSmtp = async () => {
  if (!emailPrueba.value) {
    mensajeSmtp.value = { tipo: 'error', texto: 'Por favor ingrese un email para la prueba' };
    return;
  }

  probandoSmtp.value = true;
  mensajeSmtp.value = null;

  try {
    const res = await axios.post('/config/smtp/test', { email_prueba: emailPrueba.value });
    
    if (res.data.success) {
      mensajeSmtp.value = { tipo: 'success', texto: res.data.message };
      await cargarConfigSmtp(); // Recargar para obtener el resultado actualizado
    }
  } catch (e) {
    console.error('Error probando configuración SMTP:', e);
    mensajeSmtp.value = { 
      tipo: 'error', 
      texto: e.response?.data?.message || 'Error al enviar el email de prueba' 
    };
  } finally {
    probandoSmtp.value = false;
  }
};

// --- NOTIFICACIONES EMAIL ---
const cargarConfiguracionNotificaciones = async () => {
  cargandoNotificaciones.value = true;
  try {
    const res = await axios.get('/notification-settings');
    if (res.data.success) {
      notificacionesSettings.value = res.data.data;
    }
  } catch (e) {
    console.error('Error cargando configuración de notificaciones:', e);
    mensajeNotificaciones.value = { 
      tipo: 'error', 
      texto: 'Error al cargar la configuración de notificaciones' 
    };
  } finally {
    cargandoNotificaciones.value = false;
  }
};

const notificacionesFiltradas = (patron) => {
  const regex = new RegExp(patron, 'i');
  return notificacionesSettings.value.filter(n => regex.test(n.evento));
};

const getTituloEvento = (evento) => {
  const titulos = {
    'peticion_creada': 'Nueva Petición Creada',
    'peticion_aprobada': 'Petición Aprobada',
    'peticion_denegada': 'Petición Denegada',
    'movimiento_creado': 'Movimiento Creado',
    'movimiento_firmado': 'Movimiento Firmado',
    'movimiento_entregado': 'Material Entregado',
    'recordatorio_entrega': 'Recordatorio de Entrega (día anterior)',
    'entrega_vencida': 'Fecha de Entrega Vencida',
    'solicitud_reposicion': 'Solicitud de Reposición',
    'firma_solicitada': 'Firma Solicitada'
  };
  return titulos[evento] || evento.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const marcarCambio = (id) => {
  notificacionesCambiadas.value.add(id);
};

const hayNotificacionesCambiadas = computed(() => {
  return notificacionesCambiadas.value.size > 0;
});

const guardarConfiguracionNotificaciones = async () => {
  if (!hayNotificacionesCambiadas.value) return;

  guardandoNotificaciones.value = true;
  mensajeNotificaciones.value = null;

  try {
    const configuraciones = notificacionesSettings.value
      .filter(n => notificacionesCambiadas.value.has(n.id))
      .map(n => ({
        id: n.id,
        notificar_usuario: n.notificar_usuario,
        notificar_admin: n.notificar_admin
      }));

    const res = await axios.post('/notification-settings/batch', { configuraciones });
    
    if (res.data.success) {
      mensajeNotificaciones.value = { 
        tipo: 'success', 
        texto: 'Configuración de notificaciones guardada correctamente' 
      };
      notificacionesCambiadas.value.clear();
      await cargarConfiguracionNotificaciones(); // Recargar
    }
  } catch (e) {
    console.error('Error guardando configuración de notificaciones:', e);
    mensajeNotificaciones.value = { 
      tipo: 'error', 
      texto: e.response?.data?.message || 'Error al guardar la configuración' 
    };
  } finally {
    guardandoNotificaciones.value = false;
  }
};

// Cargar configuración de notificaciones cuando se abre esa pestaña
watch(tabActiva, (newTab) => {
  if (newTab === 'notificaciones' && notificacionesSettings.value.length === 0) {
    cargarConfiguracionNotificaciones();
  }
});
</script>
