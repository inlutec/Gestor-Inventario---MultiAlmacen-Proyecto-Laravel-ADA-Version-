# Documentación Frontend

## Stack Tecnológico

- **Vue.js 3**: Framework JavaScript (Composition API)
- **Vite**: Build tool y dev server
- **Pinia**: Gestión de estado
- **Vue Router**: Enrutamiento
- **Tailwind CSS**: Framework CSS
- **Headless UI**: Componentes UI accesibles
- **Heroicons**: Iconos SVG
- **Chart.js**: Gráficos
- **Leaflet.js**: Mapas interactivos
- **Axios**: Cliente HTTP

## Estructura de Directorios

```
resources/js/
├── app.js                 # Entry point de Vue
├── App.vue                # Componente raíz
├── router.js              # Configuración de rutas
├── bootstrap.js           # Configuración de Axios
├── components/            # Componentes reutilizables
│   ├── ui/                # Componentes UI base
│   ├── SignatureCanvas.vue
│   ├── MapaAlmacenes.vue
│   └── ...
├── composables/           # Composables Vue
├── layouts/               # Layouts de la aplicación
├── stores/                # Stores de Pinia
│   ├── auth.js           # Store de autenticación
│   └── almacen.js        # Store de almacenes
└── views/                 # Vistas/Vistas Vue
    ├── Dashboard.vue
    ├── Login.vue
    ├── MaterialReferencias.vue
    └── ...
```

## Configuración

### Vite

**Archivo**: `vite.config.js`

```javascript
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            buildDirectory: 'build',
        }),
        vue()
    ],
    base: '/gestionmaterial/build/',
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
})
```

### Tailwind CSS

**Archivo**: `tailwind.config.js`

Configurado con colores oficiales de la Junta de Andalucía y paleta ADA.

## Componentes Principales

### Layouts

#### AppLayout.vue
Layout principal de la aplicación con:
- Header con navegación
- Sidebar (si aplica)
- Área de contenido
- Notificaciones

### Vistas Principales

#### Dashboard.vue
Panel de control con:
- KPIs principales
- Gráficos de movimientos
- Alertas
- Actividad reciente

#### MaterialReferencias.vue
Catálogo de materiales con:
- Lista de materiales
- Búsqueda y filtros
- Formulario de creación/edición

#### MaterialMovimientos.vue
Gestión de movimientos con:
- Lista de movimientos
- Formulario de creación
- Detalles de movimiento
- Firmas

#### FirmaMovil.vue
Página de firma móvil con:
- Conexión SSE
- Canvas de firma táctil
- Estados: esperando, firmando, enviando, completado

#### MaterialPeticionPublica.vue
Formulario público de peticiones con:
- Navegación por categorías
- Mapa interactivo de almacenes (Leaflet.js)
- Formulario de solicitud

### Componentes Reutilizables

#### SignatureCanvas.vue
Canvas para captura de firma con soporte táctil y mouse.

#### MapaAlmacenes.vue
Mapa interactivo con Leaflet.js para selección de almacenes.

#### AlmacenSelector.vue
Selector de almacén con filtros.

## Gestión de Estado (Pinia)

### authStore
```javascript
import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: null,
        isAuthenticated: false
    }),
    actions: {
        async login(credentials) { ... },
        async logout() { ... },
        async checkSession() { ... }
    }
})
```

### almacenStore
```javascript
export const useAlmacenStore = defineStore('almacen', {
    state: () => ({
        almacenes: [],
        almacenSeleccionado: null
    }),
    actions: {
        async cargarAlmacenes() { ... },
        seleccionarAlmacen(id) { ... }
    }
})
```

## Routing

### Configuración

**Archivo**: `resources/js/router.js`

```javascript
import { createRouter, createWebHistory } from 'vue-router'

const routes = [
    {
        path: '/',
        component: () => import('./layouts/AppLayout.vue'),
        children: [
            { path: '', component: () => import('./views/Dashboard.vue') },
            { path: 'materiales', component: () => import('./views/MaterialReferencias.vue') },
            // ...
        ]
    },
    {
        path: '/login',
        component: () => import('./views/Login.vue')
    }
]

export default createRouter({
    history: createWebHistory('/gestionmaterial/'),
    routes
})
```

### Guards

```javascript
router.beforeEach((to, from, next) => {
    const authStore = useAuthStore()
    
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next('/login')
    } else {
        next()
    }
})
```

## Comunicación con API

### Configuración de Axios

**Archivo**: `resources/js/bootstrap.js`

```javascript
import axios from 'axios'

axios.defaults.baseURL = '/gestionmaterial/api'
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// Interceptor para añadir token
axios.interceptors.request.use(config => {
    const token = localStorage.getItem('token')
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }
    return config
})

// Interceptor para manejar errores
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            // Redirigir a login
        }
        return Promise.reject(error)
    }
)
```

### Ejemplo de Uso

```javascript
import axios from 'axios'

// GET
const response = await axios.get('/entidades', {
    params: { categoria_id: 1 }
})

// POST
const response = await axios.post('/material-movimientos', {
    tipo: 'salida',
    detalles: [...]
})

// PUT
const response = await axios.put(`/entidades/${id}`, {
    datos: { nombre: 'Nuevo nombre' }
})

// DELETE
await axios.delete(`/entidades/${id}`)
```

## Composables

### useAuth
```javascript
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function useAuth() {
    const authStore = useAuthStore()
    
    const isAdmin = computed(() => authStore.user?.rol === 'admin')
    
    return {
        user: computed(() => authStore.user),
        isAuthenticated: computed(() => authStore.isAuthenticated),
        isAdmin
    }
}
```

### useAlmacenes
```javascript
export function useAlmacenes() {
    const almacenStore = useAlmacenStore()
    
    const cargarAlmacenes = async () => {
        await almacenStore.cargarAlmacenes()
    }
    
    return {
        almacenes: computed(() => almacenStore.almacenes),
        cargarAlmacenes
    }
}
```

## Integración con Leaflet.js

### Mapa de Almacenes

**Componente**: `components/MapaAlmacenes.vue`

```javascript
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

export default {
    setup() {
        const map = ref(null)
        
        onMounted(() => {
            // Inicializar mapa
            map.value = L.map('mapa-almacenes').setView([37.5, -4.5], 7)
            
            // Añadir tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map.value)
            
            // Añadir marcadores
            almacenes.value.forEach(almacen => {
                const marker = L.marker([almacen.lat, almacen.lng])
                    .addTo(map.value)
                    .bindPopup(`<b>${almacen.nombre}</b><br>${almacen.direccion}`)
            })
        })
    }
}
```

## Integración con Chart.js

### Gráficos

```javascript
import { Line } from 'vue-chartjs'
import { Chart as ChartJS } from 'chart.js'

export default {
    extends: Line,
    props: {
        data: Object,
        options: Object
    },
    mounted() {
        this.renderChart(this.data, this.options)
    }
}
```

## PWA

### Service Worker

**Archivo**: `public/service-worker.js`

Cachea assets estáticos para funcionamiento offline.

### Manifest

**Archivo**: `public/manifest.json`

Configuración de la PWA con iconos, nombre, colores, etc.

## Estilos

### Tailwind CSS

Usar clases de Tailwind para estilos. Colores personalizados:

- `junta-green-*`: Colores verdes oficiales
- `junta-yellow-*`: Colores amarillos oficiales
- `ada-primary-*`: Paleta ADA
- `ada-accent-*`: Acentos ADA

### Componentes UI

Usar componentes de Headless UI para accesibilidad:
- Dialog
- Menu
- Popover
- etc.

## Build y Deployment

### Desarrollo
```bash
npm run dev
```

### Producción
```bash
npm run build
```

Los assets se compilan en `public/build/` y se referencian desde `resources/views/app.blade.php`.

## Mejores Prácticas

1. **Composition API**: Usar Composition API en lugar de Options API
2. **Composables**: Extraer lógica reutilizable a composables
3. **TypeScript**: Considerar migración a TypeScript (futuro)
4. **Testing**: Añadir tests unitarios y E2E (futuro)
5. **Accesibilidad**: Usar Headless UI para componentes accesibles
6. **Performance**: Lazy loading de rutas y componentes grandes
7. **SEO**: Considerar SSR si es necesario (futuro)
