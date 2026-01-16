# Guía de Desarrollo

## Configuración del Entorno Local

### Requisitos

- PHP 8.3+
- Composer 2.x
- Node.js 18.x+
- NPM 9.x+
- MySQL 8.0+ o MariaDB 10.3+
- Git

### Instalación

```bash
# Clonar repositorio
git clone <url-del-repositorio>
cd gestor-inventario-material

# Instalar dependencias PHP
composer install

# Instalar dependencias Node.js
npm install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Configurar base de datos en .env
# Luego ejecutar migraciones
php artisan migrate
php artisan db:seed --class=ProvinciaSeeder

# Compilar assets
npm run dev
```

### Servidor de Desarrollo

```bash
# Terminal 1: Servidor Laravel
php artisan serve

# Terminal 2: Servidor Vite (hot reload)
npm run dev
```

Acceder a: `http://localhost:8000`

## Estructura del Proyecto

### Backend (Laravel)

#### Controladores
- `app/Http/Controllers/` - Controladores HTTP
- `app/Http/Controllers/Api/` - Controladores API específicos
- `app/Http/Controllers/Proyectos/` - Controladores de proyectos

#### Modelos
- `app/Models/` - Modelos Eloquent
- `app/Models/Proyectos/` - Modelos de proyectos

#### Servicios
- `app/Services/` - Lógica de negocio compleja

#### Middleware
- `app/Http/Middleware/` - Middleware personalizado
  - `CheckAdmin.php` - Verificar rol admin
  - `FilterByAlmacen.php` - Filtrar por almacén

#### Rutas
- `routes/api.php` - Rutas API
- `routes/web.php` - Rutas web

### Frontend (Vue.js)

#### Estructura
```
resources/js/
├── app.js              # Entry point
├── App.vue             # Componente raíz
├── router.js           # Configuración Vue Router
├── bootstrap.js        # Configuración Axios
├── components/         # Componentes reutilizables
├── composables/        # Composables Vue
├── layouts/            # Layouts
├── stores/             # Stores Pinia
└── views/              # Vistas/Vistas Vue
```

#### Componentes Principales
- `components/SignatureCanvas.vue` - Canvas de firma
- `components/MapaAlmacenes.vue` - Mapa de almacenes
- `components/AlmacenSelector.vue` - Selector de almacén

#### Vistas Principales
- `views/Dashboard.vue` - Panel de control
- `views/MaterialReferencias.vue` - Catálogo de materiales
- `views/MaterialMovimientos.vue` - Movimientos
- `views/FirmaMovil.vue` - Firma móvil
- `views/MaterialPeticionPublica.vue` - Petición pública

## Convenciones de Código

### PHP

#### Nombres
- Clases: PascalCase (`MaterialMovimientoController`)
- Métodos: camelCase (`crearMovimiento`)
- Variables: camelCase (`$movimientoId`)
- Constantes: UPPER_SNAKE_CASE (`MAX_INTENTOS`)

#### Estructura de Controladores
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaterialMovimiento;

class MaterialMovimientoController extends Controller
{
    /**
     * Listar movimientos
     */
    public function index(Request $request)
    {
        // Lógica aquí
    }
}
```

### JavaScript/Vue.js

#### Nombres
- Componentes: PascalCase (`MaterialMovimientos.vue`)
- Variables/funciones: camelCase (`movimientoId`, `crearMovimiento`)
- Constantes: UPPER_SNAKE_CASE (`API_BASE_URL`)

#### Estructura de Componentes Vue
```vue
<template>
  <!-- Template aquí -->
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

// Props
const props = defineProps({
  movimiento: Object
})

// Estado
const loading = ref(false)

// Computed
const total = computed(() => {
  // Lógica
})

// Métodos
const guardar = () => {
  // Lógica
}

// Lifecycle
onMounted(() => {
  // Lógica
})
</script>

<style scoped>
/* Estilos aquí */
</style>
```

## Base de Datos

### Migraciones

```bash
# Crear nueva migración
php artisan make:migration nombre_migracion

# Ejecutar migraciones
php artisan migrate

# Rollback
php artisan migrate:rollback

# Ver estado
php artisan migrate:status
```

### Seeders

```bash
# Crear seeder
php artisan make:seeder NombreSeeder

# Ejecutar seeder
php artisan db:seed --class=NombreSeeder

# Ejecutar todos
php artisan db:seed
```

## Testing

### Pruebas Manuales

Antes de hacer commit:
1. Probar funcionalidad manualmente
2. Verificar consola del navegador (sin errores)
3. Verificar que assets se compilen
4. Probar en diferentes navegadores

### Comandos Útiles

```bash
# Limpiar caché
php artisan optimize:clear

# Ver rutas
php artisan route:list

# Ver configuración
php artisan config:show

# Tinker (consola interactiva)
php artisan tinker
```

## Debugging

### Laravel

```php
// Logging
Log::info('Mensaje', ['data' => $data]);
Log::error('Error', ['exception' => $e]);

// Debug
dd($variable);
dump($variable);
```

### Vue.js

```javascript
// Console
console.log('Mensaje', data);
console.error('Error', error);

// Vue DevTools
// Instalar extensión del navegador
```

### Logs

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log
```

## Git Workflow

### Ramas

- `main` - Código de producción
- `develop` - Desarrollo (si existe)
- `feature/nombre` - Nueva funcionalidad
- `fix/nombre` - Corrección de bug
- `hotfix/nombre` - Fix urgente

### Commits

Usar formato Conventional Commits:
```
feat: añadir sistema de notificaciones
fix: corregir cálculo de stock
docs: actualizar README
refactor: mejorar estructura de controladores
```

### Pull Requests

1. Crear rama desde `main`
2. Hacer cambios
3. Commit con mensajes descriptivos
4. Push a tu fork
5. Crear PR con descripción detallada

## Herramientas de Desarrollo

### PHP
- **Laravel Debugbar**: Debugging en desarrollo
- **Tinker**: Consola interactiva
- **Laravel Telescope**: Profiling (opcional)

### JavaScript
- **Vue DevTools**: Extensión del navegador
- **Vite HMR**: Hot Module Replacement
- **ESLint**: Linting (si está configurado)

## Comandos Útiles

```bash
# Desarrollo
php artisan serve          # Servidor Laravel
npm run dev                # Vite dev server

# Producción
npm run build             # Compilar assets
php artisan optimize       # Optimizar Laravel

# Base de datos
php artisan migrate        # Ejecutar migraciones
php artisan migrate:fresh  # Resetear BD
php artisan db:seed       # Ejecutar seeders

# Limpieza
php artisan cache:clear   # Limpiar caché
php artisan config:clear  # Limpiar config
php artisan route:clear   # Limpiar rutas
php artisan view:clear    # Limpiar vistas
php artisan optimize:clear # Limpiar todo

# Información
php artisan about         # Info del sistema
php artisan route:list     # Listar rutas
```

## Recursos

- [Laravel 11 Documentation](https://laravel.com/docs/11.x)
- [Vue.js 3 Documentation](https://vuejs.org/)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Vite Documentation](https://vitejs.dev/)
