# Sistema de Gestión de Inventario de Material - Junta de Andalucía

## 📋 Índice

1. [Descripción del Proyecto](#descripción-del-proyecto)
2. [Características Principales](#características-principales)
3. [Requisitos del Sistema](#requisitos-del-sistema)
4. [Instalación](#instalación)
5. [Configuración](#configuración)
6. [Estructura del Proyecto](#estructura-del-proyecto)
7. [Estructura de Base de Datos](#estructura-de-base-de-datos)
8. [API y Endpoints](#api-y-endpoints)
9. [Funcionalidades Detalladas](#funcionalidades-detalladas)
10. [Guías de Uso](#guías-de-uso)
11. [Resolución de Problemas](#resolución-de-problemas)
12. [Mantenimiento](#mantenimiento)
13. [Despliegue en Producción](#despliegue-en-producción)
14. [Seguridad](#seguridad)
15. [Contribución](#contribución)

---

## 📖 Descripción del Proyecto

Sistema web desarrollado para la **Junta de Andalucía** que permite la gestión integral del inventario de pequeño material. El sistema facilita el control de stock, movimientos de entrada y salida, peticiones públicas de material, solicitudes de reposición, y gestión de almacenes distribuidos geográficamente.

### Tecnologías Utilizadas

- **Backend**: Laravel 11 (PHP 8.3)
- **Frontend**: Vue.js 3 + Vite
- **Base de Datos**: MySQL/MariaDB
- **Autenticación**: Laravel Sanctum
- **Estilos**: Tailwind CSS
- **Mapas**: Leaflet.js
- **Gráficos**: Chart.js
- **PWA**: Service Worker + Manifest

---

## ✨ Características Principales

### 1. Gestión de Material
- Catálogo completo de materiales con categorías
- Control de stock en tiempo real
- Historial completo de movimientos
- Gestión de ubicaciones físicas
- Fotos y documentación asociada

### 2. Movimientos de Entrada/Salida
- Registro de entradas y salidas de material
- Selección de origen/destino por provincia, sede y departamento
- Justificantes configurables por tipo de movimiento
- Generación de albaranes en PDF
- Sistema de firmas digitales (emisor y receptor)
- Enlaces públicos para firma externa
- **Firma móvil remota con SSE (Server-Sent Events)**:
  - Firma desde dispositivos móviles en tiempo real
  - Comunicación bidireccional mediante SSE
  - Generación de ID de sesión único (4 dígitos)
  - Notificaciones push instantáneas al dispositivo móvil
  - Canvas táctil para firma con dedo o stylus
  - Reconexión automática en caso de pérdida de conexión

### 3. Peticiones Públicas
- Formulario web público para solicitar material
- No requiere autenticación
- Selección de materiales disponibles con stock
- **Mapa interactivo de almacenes (Leaflet.js)**:
  - Visualización geográfica de todos los almacenes en un mapa de Andalucía
  - Utiliza **Leaflet.js 1.9.4** como librería de mapas
  - Utiliza **OpenStreetMap** como proveedor de tiles
  - Marcadores personalizados con iconos y nombres de almacenes
  - Distribución automática de marcadores para evitar superposición
  - Popups informativos con datos del almacén (dirección, provincia)
  - Selección directa de almacén desde el mapa
  - Filtrado por provincia
  - Implementado en `MaterialPeticionPublica.vue`
- Campos personalizados configurables
- Aprobación/rechazo por administradores

### 4. Dashboard y Estadísticas
- Panel de control con KPIs principales
- Gráficos de movimientos mensuales
- Alertas de stock bajo/crítico
- Actividad reciente del sistema
- Materiales más solicitados
- Resumen por categorías

### 5. Gestión de Almacenes
- Múltiples almacenes por provincia
- Asignación de usuarios a almacenes específicos
- Filtrado de datos por almacén
- Visualización geográfica en mapa

### 6. Solicitudes de Reposición
- Solicitudes automáticas cuando el stock está bajo
- Previsión de llegada
- Notificaciones por email
- Seguimiento de estado

### 7. Sistema de Notificaciones
- Notificaciones en tiempo real
- Configuración de eventos notificables
- Notificaciones por email (SMTP configurable)
- Push notifications (PWA)

### 8. PWA (Progressive Web App)
- Instalable en dispositivos móviles
- Funciona offline
- Service Worker para caché
- Firma móvil de albaranes

### 9. Firma Móvil Remota (SSE)
- **Tecnología**: Server-Sent Events (SSE) para comunicación en tiempo real
- **Funcionamiento**:
  - Dispositivo móvil se conecta al stream SSE con un ID de sesión único
  - Conexión persistente que mantiene el dispositivo en espera
  - Pings automáticos cada 15 segundos para mantener la conexión viva
  - Cuando se solicita una firma desde la web, se envía instantáneamente al móvil
  - Canvas HTML5 táctil para capturar la firma con dedo o stylus
  - Reconexión automática en caso de pérdida de conexión
- **Casos de uso**: Firmas presenciales, firmas remotas sin compartir enlaces, múltiples dispositivos simultáneos
- **Seguridad**: ID de sesión único, expiración automática de sesiones (24 horas), validación de firmas

### 9. Sistema de Backups
- Backups automáticos de la base de datos
- Restauración desde interfaz web
- Exportación/importación de datos

---

## 🔧 Requisitos del Sistema

### Servidor
- **PHP**: 8.3 o superior
- **Extensiones PHP requeridas**:
  - BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
  - GD o Imagick (para procesamiento de imágenes)
  - Zip (para backups)
- **Base de Datos**: MySQL 8.0+ o MariaDB 10.3+
- **Servidor Web**: Nginx o Apache
- **Node.js**: 18.x o superior
- **NPM**: 9.x o superior

### Cliente
- Navegador moderno (Chrome, Firefox, Safari, Edge)
- JavaScript habilitado
- Para PWA: Navegador compatible con Service Workers

---

## 🚀 Instalación

### 1. Clonar el Repositorio

```bash
git clone <url-del-repositorio>
cd gestor-inventario-material
```

### 2. Instalar Dependencias PHP

```bash
composer install
```

### 3. Instalar Dependencias Node.js

```bash
npm install
```

### 4. Configurar Variables de Entorno

```bash
cp .env.example .env
php artisan key:generate
```

Editar el archivo `.env` con la configuración adecuada:

```env
APP_NAME="Gestión de Material"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://tu-dominio.com/gestionmaterial

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_base_datos
DB_USERNAME=usuario_db
DB_PASSWORD=contraseña_db

ASSET_URL=/gestionmaterial
```

### 5. Crear Base de Datos y Ejecutar Migraciones

#### 5.1. Crear la Base de Datos

```bash
# Conectarse a MySQL/MariaDB
mysql -u root -p

# Crear la base de datos
CREATE DATABASE nombre_base_datos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Crear usuario (opcional, si no usas root)
CREATE USER 'usuario_db'@'localhost' IDENTIFIED BY 'contraseña_db';
GRANT ALL PRIVILEGES ON nombre_base_datos.* TO 'usuario_db'@'localhost';
FLUSH PRIVILEGES;

# Salir
EXIT;
```

#### 5.2. Ejecutar Migraciones

Laravel ejecutará automáticamente todas las migraciones en el orden correcto según sus fechas:

```bash
php artisan migrate
```

Este comando creará todas las tablas necesarias en el siguiente orden:

**Tablas Base (2024_01_01_000000)**:
- `usuarios` - Usuarios del sistema
- `sesiones` - Sesiones de usuario
- `intentos_login` - Control de intentos de login
- `tipos_entidad` - Tipos de entidades/materiales
- `campos` - Campos personalizados por tipo de entidad
- `planos` - Planos/plantas de edificios
- `entidades` - Tabla principal de materiales/entidades
- `pedidos` - Pedidos y peticiones
- `detalles_pedido` - Detalles de pedidos
- `notificaciones` - Notificaciones del sistema
- `registro_cambios` - Auditoría de cambios

**Tablas de Material (2025_11_06_000003 - desde _disabled)**:
- `material_movimientos` - Movimientos de entrada/salida
- `material_movimiento_detalles` - Detalles de movimientos
- `material_firmas` - Firmas digitales de movimientos

**Tablas Geográficas (2025_11_05_000003, 2025_11_21_000001)**:
- `provincias` - Provincias de Andalucía
- `sedes` - Sedes/centros
- `departamentos` - Departamentos (pueden ser almacenes)

**Tablas de Configuración**:
- `categorias` - Categorías de materiales
- `justificantes` - Justificantes para movimientos (con datos por defecto)
- `custom_fields` - Campos personalizados globales
- `custom_field_values` - Valores de campos personalizados
- `smtp_config` - Configuración SMTP
- `notification_settings` - Configuración de notificaciones

**Tablas de Funcionalidades**:
- `solicitudes_reposicion` - Solicitudes de reposición de stock
- `pedidos_historial` - Historial de pedidos
- `material_movimientos_historial` - Historial de movimientos
- `push_subscriptions` - Suscripciones para push notifications
- `user_almacen` - Relación usuarios-almacenes
- `entidad_ubicaciones` - Ubicaciones de entidades

**Tablas de Proyectos** (base de datos separada):
- `proyectos` - Proyectos
- `tareas` - Tareas de proyectos
- `comentarios` - Comentarios
- `adjuntos` - Adjuntos

#### 5.3. Ejecutar Seeders (Datos Iniciales)

Después de las migraciones, ejecutar los seeders para datos iniciales:

```bash
# Seeders principales
php artisan db:seed --class=ProvinciaSeeder
php artisan db:seed --class=DatabaseSeeder

# O ejecutar todos los seeders
php artisan db:seed
```

**Datos que se insertan automáticamente**:
- **Provincias**: Las 8 provincias de Andalucía (Almería, Cádiz, Córdoba, Granada, Huelva, Jaén, Málaga, Sevilla)
- **Justificantes**: Justificantes por defecto para entradas y salidas (se insertan en la migración)

#### 5.4. Verificar Migraciones

Para verificar que todas las migraciones se ejecutaron correctamente:

```bash
# Ver estado de las migraciones
php artisan migrate:status

# Ver todas las tablas creadas
php artisan db:show

# O desde MySQL
mysql -u usuario_db -p nombre_base_datos -e "SHOW TABLES;"
```

#### 5.5. Si hay Problemas con las Migraciones

Si necesitas resetear la base de datos (¡CUIDADO: borra todos los datos!):

```bash
# Rollback de todas las migraciones
php artisan migrate:rollback

# O resetear completamente
php artisan migrate:fresh

# Resetear y ejecutar seeders
php artisan migrate:fresh --seed
```

**Nota importante**: El comando `php artisan migrate` ejecuta automáticamente todas las migraciones pendientes en el orden correcto según sus nombres de archivo (fecha). No es necesario ejecutarlas manualmente una por una.

### 6. Compilar Assets Frontend

```bash
npm run build
```

### 7. Configurar Permisos

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 8. Configurar Nginx

Ver sección [Configuración de Nginx](#configuración-de-nginx)

---

## ⚙️ Configuración

### Variables de Entorno Importantes

| Variable | Descripción | Ejemplo |
|----------|-------------|---------|
| `APP_URL` | URL base de la aplicación | `https://dominio.com/gestionmaterial` |
| `ASSET_URL` | Prefijo para assets estáticos | `/gestionmaterial` |
| `DB_*` | Configuración de base de datos | - |
| `MAIL_*` | Configuración SMTP para emails | - |

### Configuración de Nginx

Ejemplo de configuración para servir la aplicación desde `/gestionmaterial/`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name tu-dominio.com;
    root /var/www/html;
    index index.php index.html index.htm;

    # Redirigir /gestionmaterial a /gestionmaterial/
    location = /gestionmaterial {
        return 301 /gestionmaterial/;
    }

    # Configuración para /gestionmaterial/ - Laravel
    location /gestionmaterial/ {
        alias /var/www/gestor-inventario-material/public/;
        try_files $uri $uri/ @gestionmaterial_fallback;
        
        # Configuración para archivos estáticos
        location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
            expires 1y;
            add_header Cache-Control "public, immutable";
            add_header X-Content-Type-Options "nosniff";
            access_log off;
        }
        
        # Configuración para PHP
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            fastcgi_param PATH_INFO $fastcgi_path_info;
            include fastcgi_params;
        }
    }
    
    # Fallback para Laravel routing
    location @gestionmaterial_fallback {
        rewrite ^/gestionmaterial/(.*)$ /gestionmaterial/index.php?$query_string last;
    }
}
```

### Configuración de PHP-FPM

Asegurar que PHP-FPM esté configurado correctamente:

```ini
; /etc/php/8.3/fpm/pool.d/www.conf
user = www-data
group = www-data
listen = /var/run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data
```

---

## 📁 Estructura del Proyecto

```
gestor-inventario-material/
├── app/                          # Código de la aplicación Laravel
│   ├── Console/                  # Comandos Artisan
│   ├── Http/
│   │   ├── Controllers/          # Controladores
│   │   │   ├── Api/              # Controladores API
│   │   │   └── Proyectos/        # Controladores de proyectos
│   │   ├── Middleware/           # Middleware personalizado
│   │   └── Kernel.php            # Kernel HTTP
│   ├── Models/                   # Modelos Eloquent
│   │   └── Proyectos/            # Modelos de proyectos
│   └── Services/                 # Servicios de negocio
│       ├── NotificationService.php
│       └── PushNotificationService.php
├── bootstrap/                    # Archivos de arranque
├── config/                       # Archivos de configuración
├── database/
│   ├── migrations/               # Migraciones de BD
│   └── seeders/                  # Seeders de datos iniciales
├── public/                       # Punto de entrada público
│   ├── build/                    # Assets compilados (Vite)
│   ├── images/                   # Imágenes estáticas
│   ├── manifest.json             # Manifest PWA
│   ├── service-worker.js         # Service Worker PWA
│   └── index.php                 # Entry point Laravel
├── resources/
│   ├── css/                      # Estilos CSS
│   ├── js/                       # Código JavaScript/Vue
│   │   ├── app.js                # Entry point Vue
│   │   ├── App.vue               # Componente raíz
│   │   ├── router.js             # Configuración Vue Router
│   │   ├── bootstrap.js          # Configuración Axios
│   │   ├── components/           # Componentes Vue reutilizables
│   │   ├── composables/          # Composables Vue
│   │   ├── layouts/              # Layouts Vue
│   │   ├── stores/               # Stores Pinia
│   │   └── views/                # Vistas/Vistas Vue
│   └── views/                    # Vistas Blade
│       └── app.blade.php         # Template principal
├── routes/
│   ├── api.php                   # Rutas API
│   ├── web.php                   # Rutas web
│   └── console.php               # Rutas consola
├── storage/                      # Archivos de almacenamiento
│   ├── app/
│   │   ├── backups/              # Backups de BD
│   │   ├── firmas/               # Firmas digitales
│   │   └── public/               # Archivos públicos
│   ├── framework/                # Cache y sesiones
│   └── logs/                     # Logs de aplicación
├── vendor/                       # Dependencias Composer
├── node_modules/                 # Dependencias NPM
├── composer.json                 # Dependencias PHP
├── package.json                  # Dependencias Node.js
├── vite.config.js                # Configuración Vite
├── tailwind.config.js            # Configuración Tailwind
└── artisan                       # CLI de Laravel
```

### Descripción de Directorios Clave

#### `app/Http/Controllers/`
- **AuthController.php**: Autenticación y gestión de sesiones
- **DashboardController.php**: Estadísticas y datos del dashboard
- **EntidadController.php**: CRUD de materiales/entidades
- **MaterialMovimientoController.php**: Gestión de movimientos
  - Incluye funcionalidad para solicitar firma móvil mediante SSE
- **MaterialPeticionController.php**: Gestión de peticiones públicas
- **FirmaMovilController.php**: Gestión de firma móvil con SSE
  - Stream SSE para mantener conexión con dispositivos móviles
  - Gestión de sesiones activas en caché
  - Envío de solicitudes de firma en tiempo real
- **ConfigController.php**: Configuración del sistema
- **SolicitudReposicionController.php**: Solicitudes de reposición
- **BackupController.php**: Gestión de backups

#### `resources/js/views/`
- **Dashboard.vue**: Panel de control principal
- **MaterialReferencias.vue**: Catálogo de materiales
- **MaterialExistencias.vue**: Gestión de stock
- **MaterialMovimientos.vue**: Formulario de movimientos
- **MaterialPeticionPublica.vue**: Formulario público de peticiones
- **MaterialPeticiones.vue**: Gestión de peticiones (admin)
- **Configuracion.vue**: Panel de configuración
- **Login.vue**: Página de inicio de sesión

#### `database/migrations/`
Contiene todas las migraciones de base de datos en orden cronológico.

---

## 🗄️ Estructura de Base de Datos

### Tablas Principales

#### `usuarios`
Almacena los usuarios del sistema.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| nombre | varchar(100) | Nombre del usuario |
| apellido | varchar(100) | Apellido del usuario |
| email | varchar(100) | Email (único) |
| password | varchar | Contraseña hasheada |
| rol | enum | 'admin' o 'usuario' |
| activo | boolean | Si el usuario está activo |
| ultimo_acceso | timestamp | Último acceso |
| created_at, updated_at | timestamp | Timestamps |

#### `sedes`
Sedes/centros de la organización.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| nombre | varchar | Nombre de la sede |
| clave | varchar | Clave única |
| provincia_id | bigint | FK a provincias |
| es_almacen_central | boolean | Si es almacén central |
| created_at, updated_at | timestamp | Timestamps |

#### `departamentos`
Departamentos dentro de las sedes. Pueden ser almacenes.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| sede_id | bigint | FK a sedes |
| nombre | varchar | Nombre del departamento |
| clave | varchar | Clave única |
| es_almacen | boolean | Si es un almacén |
| created_at, updated_at | timestamp | Timestamps |

#### `provincias`
Provincias de Andalucía.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| nombre | varchar | Nombre de la provincia |
| clave | varchar | Clave única |
| activo | boolean | Si está activa |
| created_at, updated_at | timestamp | Timestamps |

#### `categorias`
Categorías para organizar materiales.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| nombre | varchar | Nombre de la categoría |
| descripcion | text | Descripción |
| imagen | varchar | Ruta de imagen |
| orden | integer | Orden de visualización |
| activo | boolean | Si está activa |
| provincia_id | bigint | FK a provincias (opcional) |
| created_at, updated_at | timestamp | Timestamps |

#### `entidades`
Tabla principal que almacena todos los materiales/entidades.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| tipo_entidad_id | bigint | FK a tipos_entidad |
| categoria_id | bigint | FK a categorias (opcional) |
| datos | json | Datos del material (nombre, referencia, stock_minimo, etc.) |
| departamento_id | bigint | FK a departamentos (almacén) |
| plano_id | bigint | FK a planos (opcional) |
| posicion_x, posicion_y | decimal | Posición en plano (opcional) |
| fotos | json | Array de rutas de fotos |
| foto_visible_publico | boolean | Si la foto es visible en web pública |
| usuario_creador_id | bigint | FK a usuarios |
| created_at, updated_at | timestamp | Timestamps |

**Estructura del campo `datos` (JSON)**:
```json
{
  "nombre": "Nombre del material",
  "referencia": "REF-001",
  "descripcion": "Descripción detallada",
  "stock_minimo": 10,
  "unidad": "ud",
  "ubicacion": "Estantería A-1",
  "precio": 25.50,
  "proveedor": "Proveedor S.L.",
  "fecha_compra": "2024-01-15"
}
```

#### `material_movimientos`
Movimientos de entrada y salida de material.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| tipo | enum | 'entrada' o 'salida' |
| numero_documento | varchar | Número único de albarán |
| fecha_movimiento | timestamp | Fecha del movimiento |
| usuario_id | bigint | FK a usuarios (quien registra) |
| origen | varchar | Origen del movimiento |
| destino | varchar | Destino del movimiento |
| origen_sede_id | bigint | FK a sedes (origen) |
| origen_departamento_id | bigint | FK a departamentos (origen) |
| destino_sede_id | bigint | FK a sedes (destino) |
| destino_departamento_id | bigint | FK a departamentos (destino) |
| justificante_id | bigint | FK a justificantes |
| observaciones | text | Observaciones |
| estado | enum | 'pendiente', 'pendiente_firma', 'firmado', 'entregado', 'cancelado' |
| enlace_publico | varchar | Token para enlace público |
| enlace_expira | timestamp | Expiración del enlace |
| fecha_entrega | timestamp | Fecha de entrega real |
| fecha_prevista_entrega | timestamp | Fecha prevista |
| entregado_por | bigint | FK a usuarios |
| created_at, updated_at | timestamp | Timestamps |

#### `material_movimiento_detalles`
Detalle de cada línea de un movimiento.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| movimiento_id | bigint | FK a material_movimientos |
| entidad_id | bigint | FK a entidades |
| descripcion | varchar | Descripción del material |
| cantidad | integer | Cantidad |
| unidad | varchar | Unidad de medida |
| observaciones | text | Observaciones |
| created_at, updated_at | timestamp | Timestamps |

#### `material_firmas`
Firmas digitales de los movimientos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| movimiento_id | bigint | FK a material_movimientos |
| tipo_firmante | enum | 'emisor' o 'receptor' |
| nombre | varchar | Nombre del firmante |
| apellidos | varchar | Apellidos del firmante |
| dni | varchar | DNI (opcional) |
| firma_rubrica | text | Base64 de la rúbrica |
| pdf_firmado | varchar | Ruta al PDF firmado |
| ip_address | varchar | IP desde donde se firmó |
| fecha_firma | timestamp | Fecha de la firma |
| datos_adicionales | json | Metadata adicional |
| created_at, updated_at | timestamp | Timestamps |

#### `pedidos`
Pedidos/peticiones de material.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| tipo | enum | 'pedido' o 'peticion' |
| numero_pedido | varchar(50) | Número único (nullable para peticiones) |
| fecha | date | Fecha de la petición |
| fecha_pedido | date | Fecha del pedido |
| fecha_recepcion | date | Fecha de recepción |
| estado | enum | 'pendiente', 'aprobado', 'denegado', 'recibido', 'cancelado' |
| usuario_solicitante | varchar | Nombre del solicitante (peticiones públicas) |
| email_solicitante | varchar | Email del solicitante |
| telefono_solicitante | varchar | Teléfono |
| sede_id | bigint | FK a sedes |
| departamento_id | bigint | FK a departamentos |
| observaciones | text | Justificación/observaciones |
| notas | text | Notas internas |
| datos_personalizados | json | Campos personalizados |
| movimiento_id | bigint | FK a material_movimientos (si se aprobó) |
| usuario_creador_id | bigint | FK a usuarios |
| created_at, updated_at | timestamp | Timestamps |

#### `detalles_pedido`
Detalle de materiales en un pedido.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| pedido_id | bigint | FK a pedidos |
| entidad_id | bigint | FK a entidades |
| cantidad | integer | Cantidad solicitada |
| cantidad_aprobada | integer | Cantidad aprobada (puede ser parcial) |
| precio_unitario | decimal | Precio (opcional) |
| unidad | varchar | Unidad de medida |
| created_at, updated_at | timestamp | Timestamps |

#### `justificantes`
Justificantes para movimientos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| tipo | enum | 'entrada' o 'salida' |
| nombre | varchar | Nombre del justificante |
| descripcion | text | Descripción |
| activo | boolean | Si está activo |
| orden | integer | Orden de visualización |
| created_at, updated_at | timestamp | Timestamps |

#### `solicitudes_reposicion`
Solicitudes de reposición de stock.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| entidad_id | bigint | FK a entidades |
| usuario_id | bigint | FK a usuarios |
| cantidad_solicitada | integer | Cantidad solicitada |
| estado | enum | 'pendiente', 'en_proceso', 'recibida', 'cancelada' |
| fecha_solicitud | date | Fecha de solicitud |
| prevision_llegada | date | Fecha prevista de llegada |
| telefono | varchar | Teléfono de contacto |
| observaciones | text | Observaciones |
| created_at, updated_at | timestamp | Timestamps |

#### `notificaciones`
Notificaciones del sistema.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| usuario_id | bigint | FK a usuarios |
| titulo | varchar | Título |
| mensaje | text | Mensaje |
| tipo | varchar | Tipo de notificación |
| leido | boolean | Si está leída |
| datos | json | Datos adicionales |
| created_at, updated_at | timestamp | Timestamps |

#### `user_almacen`
Relación muchos a muchos entre usuarios y almacenes.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| usuario_id | bigint | FK a usuarios |
| departamento_id | bigint | FK a departamentos (almacén) |
| created_at, updated_at | timestamp | Timestamps |

### Relaciones entre Tablas

```
provincias (1) ──< (N) sedes
sedes (1) ──< (N) departamentos
departamentos (1) ──< (N) entidades
categorias (1) ──< (N) entidades
usuarios (1) ──< (N) material_movimientos
material_movimientos (1) ──< (N) material_movimiento_detalles
material_movimientos (1) ──< (N) material_firmas
pedidos (1) ──< (N) detalles_pedido
usuarios (N) ──< (N) departamentos (user_almacen)
```

### Listado Completo de Tablas

**Tablas Principales** (creadas por `2024_01_01_000000_create_all_tables.php`):
1. `usuarios` - Usuarios del sistema
2. `sesiones` - Sesiones activas de usuarios
3. `intentos_login` - Control de intentos de login fallidos
4. `tipos_entidad` - Tipos de entidades (impresoras, pequeño material, etc.)
5. `campos` - Campos personalizados por tipo de entidad
6. `planos` - Planos/plantas de edificios
7. `entidades` - Tabla principal de materiales/entidades
8. `pedidos` - Pedidos y peticiones de material
9. `detalles_pedido` - Líneas de detalle de pedidos
10. `notificaciones` - Notificaciones del sistema
11. `registro_cambios` - Auditoría de cambios en el sistema

**Tablas de Movimientos de Material** (creadas por `2025_11_06_000003_create_material_movimientos_tables.php`):
12. `material_movimientos` - Movimientos de entrada/salida de material
13. `material_movimiento_detalles` - Detalles/líneas de cada movimiento
14. `material_firmas` - Firmas digitales de movimientos

**Tablas Geográficas y Organizativas**:
15. `provincias` - Provincias de Andalucía (2025_11_21_000001)
16. `sedes` - Sedes/centros de la organización (2025_11_05_000003)
17. `departamentos` - Departamentos dentro de sedes (pueden ser almacenes) (2025_11_05_000003)

**Tablas de Configuración**:
18. `categorias` - Categorías de materiales (2025_11_11_002355)
19. `justificantes` - Justificantes para movimientos (2025_11_10_000001)
20. `custom_fields` - Campos personalizados globales (2025_11_05_000002)
21. `custom_field_values` - Valores de campos personalizados (2025_11_05_000002)
22. `smtp_config` - Configuración SMTP para emails (2025_11_10_000003)
23. `notification_settings` - Configuración de notificaciones (2025_11_12_000001)

**Tablas de Funcionalidades Específicas**:
24. `solicitudes_reposicion` - Solicitudes de reposición de stock (2025_11_11_223313)
25. `pedidos_historial` - Historial de cambios en pedidos (2025_11_11_230045)
26. `material_movimientos_historial` - Historial de cambios en movimientos (2025_11_11_231400)
27. `push_subscriptions` - Suscripciones para push notifications (2025_11_10_211210)
28. `user_almacen` - Relación muchos a muchos usuarios-almacenes (2025_11_21_000004)
29. `entidad_ubicaciones` - Ubicaciones físicas de entidades (2025_11_22_000002)
30. `planos_ubicaciones` - Ubicaciones en planos (2025_11_05_000002)

**Tablas de Proyectos** (base de datos separada `proyectos`):
31. `proyectos` - Proyectos (2024_11_14_000001)
32. `tareas` - Tareas de proyectos (2024_11_14_000001)
33. `comentarios` - Comentarios en proyectos (2024_11_14_000001)
34. `adjuntos` - Adjuntos de proyectos (2024_11_14_000001)
35. `ubicaciones` - Ubicaciones/sitios de proyectos (2024_11_14_000001)

**Total: 30 tablas principales + 5 tablas de proyectos = 35 tablas**

### Comandos SQL de Referencia

Si necesitas crear las tablas manualmente (no recomendado, usar migraciones):

```sql
-- Ver todas las tablas
SHOW TABLES;

-- Ver estructura de una tabla
DESCRIBE nombre_tabla;

-- Ver índices de una tabla
SHOW INDEX FROM nombre_tabla;

-- Ver claves foráneas
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'nombre_base_datos'
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Verificar integridad referencial
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    CONSTRAINT_TYPE
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = 'nombre_base_datos';
```

---

## 🔌 API y Endpoints

### Rutas Públicas (sin autenticación)

#### Web Pública
- `GET /api/categorias-publicas` - Lista de categorías activas
- `GET /api/provincias` - Lista de provincias
- `GET /api/sedes-publicas` - Lista de sedes públicas
- `GET /api/sedes-publicas/{sedeId}/departamentos` - Departamentos de una sede
- `GET /api/custom-fields-publicos` - Campos personalizados públicos
- `GET /api/almacenes-publicos` - Lista de almacenes con coordenadas
- `GET /api/almacenes-por-provincia` - Almacenes filtrados por provincia
- `GET /api/sedes-por-provincia` - Sedes filtradas por provincia
- `GET /api/materiales-disponibles` - Materiales con stock disponible
- `POST /api/peticiones` - Crear petición pública de material
- `POST /api/solicitudes-reposicion-publicas` - Crear solicitud de reposición pública

#### Albaranes Públicos
- `GET /api/albaran/{token}` - Ver albarán por token
- `POST /api/albaran/{token}/firmar` - Firmar albarán
- `GET /api/albaran/{token}/pdf` - Descargar PDF del albarán
- `GET /api/albaran/{token}/pdf-sin-firmar` - PDF sin firmar
- `POST /api/albaran/{token}/subir-pdf-firmado` - Subir PDF firmado

#### Firma Móvil (SSE)
- `GET /api/firma-movil/stream?session={sessionId}` - Stream SSE para recibir solicitudes de firma
  - Mantiene conexión abierta mediante Server-Sent Events
  - Envía pings cada 15 segundos para mantener conexión viva
  - Duración máxima: 1 hora
  - Formato de respuesta: `text/event-stream`
- `POST /api/firma-movil/firmar` - Enviar firma desde dispositivo móvil
- `GET /api/firma-movil/sesiones` - Listar sesiones activas (admin)

### Rutas Autenticadas (requieren token Sanctum)

#### Autenticación
- `POST /api/login` - Iniciar sesión
- `POST /api/logout` - Cerrar sesión
- `GET /api/user` - Obtener usuario actual

#### Dashboard
- `GET /api/dashboard/stats` - Estadísticas del dashboard
  - Parámetros: `almacen_ids[]` (opcional)

#### Materiales/Entidades
- `GET /api/entidades` - Listar entidades
  - Parámetros: `tipo_entidad_id`, `almacen_ids[]`, `categoria_id`, `search`
- `POST /api/entidades` - Crear entidad
- `GET /api/entidades/{id}` - Obtener entidad
- `PUT /api/entidades/{id}` - Actualizar entidad
- `DELETE /api/entidades/{id}` - Eliminar entidad
- `GET /api/entidades/{id}/historial-stock` - Historial de stock de una entidad
  - Parámetros: `almacen_ids[]` (opcional)

#### Movimientos
- `GET /api/material-movimientos` - Listar movimientos
- `POST /api/material-movimientos` - Crear movimiento
- `GET /api/material-movimientos/{id}` - Obtener movimiento
- `PUT /api/material-movimientos/{id}` - Actualizar movimiento
- `DELETE /api/material-movimientos/{id}` - Eliminar movimiento
- `POST /api/material-movimientos/{id}/aprobar` - Aprobar movimiento
- `POST /api/material-movimientos/{id}/rechazar` - Rechazar movimiento
- `GET /api/material-movimientos/{id}/pdf` - Descargar PDF

#### Peticiones
- `GET /api/peticiones` - Listar peticiones (admin)
- `GET /api/peticiones/{id}` - Obtener petición
- `POST /api/peticiones/{id}/aprobar` - Aprobar petición
- `POST /api/peticiones/{id}/denegar` - Denegar petición

#### Configuración
- `GET /api/config/categorias` - Listar categorías
- `POST /api/config/categorias` - Crear categoría
- `PUT /api/config/categorias/{id}` - Actualizar categoría
- `DELETE /api/config/categorias/{id}` - Eliminar categoría
- `GET /api/config/provincias` - Listar provincias
- `GET /api/config/sedes` - Listar sedes
- `GET /api/config/justificantes` - Listar justificantes
- `GET /api/mis-almacenes` - Almacenes del usuario actual

#### Backups
- `GET /api/config/backup/list` - Listar backups
- `POST /api/config/backup/create` - Crear backup
- `POST /api/config/backup/restore` - Restaurar backup
- `DELETE /api/config/backup/{filename}` - Eliminar backup

### Formato de Respuestas

#### Respuesta Exitosa
```json
{
  "success": true,
  "data": { ... },
  "message": "Operación exitosa"
}
```

#### Respuesta de Error
```json
{
  "success": false,
  "message": "Mensaje de error",
  "errors": {
    "campo": ["Error de validación"]
  }
}
```

---

## 🎯 Funcionalidades Detalladas

### 1. Gestión de Materiales

#### Crear Material
1. Ir a **Referencias** → **Nuevo Material**
2. Seleccionar categoría
3. Completar datos: nombre, referencia, descripción, stock mínimo, unidad
4. Subir fotos (opcional)
5. Asignar almacén
6. Guardar

#### Editar Material
1. Buscar material en **Referencias**
2. Hacer clic en el material
3. Modificar datos necesarios
4. Guardar cambios

#### Eliminar Material
- Solo usuarios con rol `admin`
- Se elimina físicamente de la base de datos
- **Advertencia**: Se pierden todos los movimientos asociados

### 2. Movimientos de Entrada/Salida

#### Crear Movimiento de Salida
1. Ir a **Movimientos** → **Nuevo movimiento**
2. Seleccionar tipo: **Salida**
3. Seleccionar fecha
4. Elegir justificante
5. Configurar **Origen**:
   - Modo Manual: Escribir texto libre
   - Modo Sede/Departamento: Seleccionar Provincia → Sede → Departamento
6. Configurar **Destino** (similar a origen)
7. Añadir líneas de material:
   - Buscar material
   - Indicar cantidad y unidad
   - Añadir observaciones (opcional)
8. Guardar movimiento

#### Crear Movimiento de Entrada
1. Similar a salida, pero tipo **Entrada**
2. **Importante**: El destino (sede y departamento) es obligatorio
3. Al guardar, se genera un enlace público para firma
4. Compartir enlace con el receptor para que firme

#### Firmar Movimiento
- **Desde la aplicación**: El receptor puede firmar desde su cuenta
- **Enlace público**: Acceder al enlace y firmar sin autenticación
- Se requiere firma del emisor y receptor para salidas
- Solo se requiere firma del receptor para entradas

### 3. Peticiones Públicas

#### Solicitar Material (Usuario Público)
1. Acceder a `/peticion` (sin login)
2. Navegar por categorías o buscar material
3. Seleccionar materiales y cantidades
4. **Seleccionar almacén**:
   - Opción 1: Usar el **mapa interactivo** (Leaflet.js)
     - Ver todos los almacenes en el mapa
     - Hacer clic en un marcador para seleccionarlo
     - Ver información del almacén (dirección, provincia)
   - Opción 2: Seleccionar manualmente
     - Seleccionar provincia → sede → departamento
5. Completar datos del solicitante:
   - Nombre, email, teléfono
6. Añadir justificación
7. Enviar petición

**Nota técnica sobre el mapa**: El componente `MaterialPeticionPublica.vue` utiliza la librería **Leaflet.js** (versión 1.9.4, incluida en `package.json`) para renderizar el mapa interactivo. 

**Implementación del mapa**:
- **Proveedor de tiles**: OpenStreetMap (`https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png`)
- **Vista inicial**: Centrada en Andalucía (lat: 37.5, lng: -4.5) con zoom nivel 7
- **Marcadores**: Iconos personalizados (`L.divIcon`) con nombres de almacenes
- **Distribución**: Algoritmo que distribuye marcadores en patrón circular para evitar superposición cuando múltiples almacenes están en la misma provincia
- **Interactividad**: 
  - Click en marcador muestra popup con información del almacén
  - Botón "Seleccionar este almacén" actualiza el formulario
  - Los marcadores se agrupan por provincia para mejor visualización
- **Datos**: Los almacenes se obtienen del endpoint `/api/almacenes-publicos` que debe devolver `lat` y `lng` para cada almacén

#### Aprobar/Denegar Petición (Admin)
1. Ir a **Peticiones**
2. Ver lista de peticiones pendientes
3. Revisar detalles y materiales solicitados
4. Opciones:
   - **Aprobar**: Se crea un movimiento de salida automáticamente
   - **Aprobar parcialmente**: Aprobar solo algunos materiales
   - **Denegar**: Rechazar la petición con comentarios

### 4. Gestión de Stock

#### Ver Existencias
1. Ir a **Existencias**
2. Filtrar por almacén (opcional)
3. Ver lista de materiales con:
   - Stock actual
   - Stock mínimo
   - Estado (normal, bajo, crítico)

#### Regularizar Stock
1. En **Existencias**, hacer clic en **Regularizar** de un material
2. Indicar nuevo stock real
3. Se crea un movimiento automático de entrada/salida para ajustar

#### Ver Historial de Stock
1. En **Existencias**, hacer clic en **Historial**
2. Ver tabla con:
   - Fecha
   - Tipo (entrada/salida)
   - Cantidad
   - Usuario
   - Almacén
   - Estado
   - **Justificación** (tipo de movimiento y observaciones)

### 5. Dashboard

El dashboard muestra:
- **KPIs**: Total materiales, pendientes de firma, alertas de stock, peticiones
- **Gráficos**: Movimientos mensuales (entradas, salidas, peticiones)
- **Alertas críticas**: Stock crítico, movimientos urgentes, solicitudes pendientes
- **Movimientos recientes**: Últimos 10 movimientos
- **Actividad reciente**: Últimas acciones del sistema
- **Materiales más solicitados**: Top 10 del mes
- **Stock por categoría**: Resumen por categorías

### 6. Configuración del Sistema

#### Gestión de Usuarios
- Crear, editar, eliminar usuarios
- Asignar roles (admin/usuario)
- Asignar almacenes a usuarios
- Activar/desactivar usuarios

#### Gestión de Categorías
- Crear, editar, eliminar categorías
- Subir imágenes
- Ordenar categorías
- Asignar a provincias

#### Gestión de Provincias, Sedes y Departamentos
- Crear estructura geográfica
- Marcar departamentos como almacenes
- Asignar provincias a sedes

#### Justificantes
- Crear justificantes para entradas y salidas
- Activar/desactivar
- Ordenar

#### Configuración SMTP
- Configurar servidor SMTP para emails
- Probar conexión
- Múltiples configuraciones SMTP

#### Notificaciones
- Configurar qué eventos envían notificaciones
- Configurar destinatarios

#### Backups
- Crear backups manuales
- Restaurar desde backup
- Eliminar backups antiguos

---

## 📚 Guías de Uso

### Para Administradores

#### Configuración Inicial
1. **Crear primer usuario admin**:
   ```bash
   php artisan tinker
   ```
   ```php
   $usuario = new \App\Models\Usuario();
   $usuario->nombre = 'Admin';
   $usuario->apellido = 'Sistema';
   $usuario->email = 'admin@example.com';
   $usuario->password = bcrypt('contraseña_segura');
   $usuario->rol = 'admin';
   $usuario->activo = true;
   $usuario->save();
   ```

2. **Crear provincias**: Ir a Configuración → Provincias
3. **Crear sedes**: Configuración → Sedes
4. **Crear departamentos/almacenes**: Configuración → Departamentos
5. **Crear categorías**: Configuración → Categorías
6. **Configurar SMTP**: Configuración → SMTP

#### Gestión Diaria
- Revisar dashboard cada mañana
- Aprobar/denegar peticiones pendientes
- Revisar movimientos pendientes de firma
- Revisar alertas de stock bajo
- Crear backups periódicos

### Para Usuarios

#### Registrar Entrada de Material
1. Ir a **Movimientos**
2. Crear nuevo movimiento tipo **Entrada**
3. Seleccionar destino (almacén)
4. Añadir materiales recibidos
5. Guardar y compartir enlace de firma con proveedor

#### Registrar Salida de Material
1. Crear movimiento tipo **Salida**
2. Seleccionar origen (almacén de donde sale)
3. Seleccionar destino (departamento/persona)
4. Añadir materiales
5. Guardar

#### Consultar Stock
1. Ir a **Existencias**
2. Filtrar por almacén si es necesario
3. Buscar material específico
4. Ver historial si es necesario

---

## 🔧 Resolución de Problemas

### Problema: La aplicación no carga

**Síntomas**: Error 404 o página en blanco

**Soluciones**:
1. Verificar configuración de Nginx:
   ```bash
   sudo nginx -t
   sudo systemctl reload nginx
   ```

2. Verificar permisos:
   ```bash
   sudo chown -R www-data:www-data /var/www/gestor-inventario-material/storage
   sudo chmod -R 775 /var/www/gestor-inventario-material/storage
   ```

3. Limpiar caché de Laravel:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   php artisan route:clear
   ```

4. Verificar que los assets estén compilados:
   ```bash
   npm run build
   ```

### Problema: Los assets no se cargan (CSS/JS)

**Síntomas**: Página sin estilos o JavaScript no funciona

**Soluciones**:
1. Verificar que `ASSET_URL` en `.env` sea correcto:
   ```env
   ASSET_URL=/gestionmaterial
   ```

2. Recompilar assets:
   ```bash
   npm run build
   ```

3. Verificar que `app.blade.php` tenga los nombres correctos de archivos compilados (ver `public/build/manifest.json`)

4. Limpiar caché del navegador (Ctrl+F5)

### Problema: Error 500 en producción

**Síntomas**: Error 500 Internal Server Error

**Soluciones**:
1. Verificar logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. Verificar permisos de storage:
   ```bash
   sudo chown -R www-data:www-data storage bootstrap/cache
   ```

3. Verificar configuración de PHP:
   ```bash
   php -v
   php -m  # Verificar extensiones
   ```

4. Verificar variables de entorno:
   ```bash
   php artisan config:cache
   ```

### Problema: No se pueden crear movimientos

**Síntomas**: Error al guardar movimiento

**Soluciones**:
1. Verificar que existan justificantes activos
2. Verificar que los materiales tengan stock suficiente (para salidas)
3. Verificar que el destino tenga sede y departamento (para entradas)
4. Revisar logs de Laravel

### Problema: Las peticiones públicas no funcionan

**Síntomas**: No se ven categorías o materiales en `/peticion`

**Soluciones**:
1. Verificar que las categorías estén activas
2. Verificar que los materiales tengan `foto_visible_publico = true` si quieren mostrar foto
3. Verificar que los materiales tengan stock > 0
4. Verificar que las rutas públicas estén en `routes/web.php` (no en `api.php`)

### Problema: Los emails no se envían

**Síntomas**: Notificaciones no llegan

**Soluciones**:
1. Verificar configuración SMTP en Configuración → SMTP
2. Probar conexión SMTP desde la interfaz
3. Verificar logs de Laravel para errores de email
4. Verificar que `MAIL_*` esté configurado en `.env`

### Problema: La firma móvil no funciona

**Síntomas**: El dispositivo móvil no recibe solicitudes de firma o se desconecta

**Soluciones**:
1. **Verificar conexión SSE**:
   - Verificar que el endpoint `/api/firma-movil/stream` esté accesible
   - Verificar que Nginx no esté haciendo buffering (debe tener `X-Accel-Buffering: no`)
   - Verificar logs del navegador para errores de EventSource

2. **Verificar ID de sesión**:
   - El ID de sesión debe ser de 4 dígitos
   - Verificar que el ID introducido en la web coincida con el del móvil
   - La sesión expira después de 24 horas de inactividad

3. **Verificar configuración de Nginx para SSE**:
   ```nginx
   # En la configuración de /gestionmaterial/
   proxy_buffering off;
   proxy_cache off;
   proxy_read_timeout 3600s;
   ```

4. **Verificar caché de Laravel**:
   - Las sesiones SSE se almacenan en caché de Laravel
   - Verificar que el driver de caché esté funcionando correctamente
   - Limpiar caché si es necesario: `php artisan cache:clear`

5. **Reconexión automática**:
   - El cliente SSE tiene reconexión automática cada 3 segundos
   - Si la conexión se pierde, se reconecta automáticamente
   - Verificar que no haya firewalls bloqueando conexiones persistentes

### Problema: El mapa de almacenes no se muestra

**Síntomas**: Mapa en blanco en petición pública

**Soluciones**:
1. **Verificar que Leaflet.js esté cargado**:
   - El mapa utiliza la librería **Leaflet.js** (versión 1.9.4, incluida en `package.json`)
   - Verificar que se importe correctamente en `MaterialPeticionPublica.vue`
   - Verificar que los CSS de Leaflet se carguen (normalmente desde CDN o node_modules)
   - Verificar consola del navegador para errores de carga de Leaflet
   - El mapa se inicializa con `L.map()` y utiliza tiles de OpenStreetMap

2. **Verificar que los almacenes tengan coordenadas**:
   - Los almacenes deben tener `lat` y `lng` en sus datos
   - Verificar en la base de datos que los departamentos marcados como almacenes (`es_almacen = true`) tengan coordenadas
   - El endpoint `/api/almacenes-publicos` debe devolver estos campos

3. **Verificar consola del navegador**:
   - Buscar errores JavaScript relacionados con Leaflet
   - Verificar que no haya conflictos con otras librerías
   - Verificar que el contenedor `#mapa-almacenes` exista en el DOM

4. **Verificar que la API devuelva datos**:
   - Probar endpoint: `GET /api/almacenes-publicos`
   - Verificar que devuelva `{ almacenes: [...], provincias: [...] }`
   - Cada almacén debe tener: `id`, `nombre`, `lat`, `lng`, `provincia`, `direccion`

5. **Verificar CSS de Leaflet**:
   - Asegurar que los estilos de Leaflet se carguen correctamente
   - Verificar que el contenedor del mapa tenga altura definida (ej: `height: 400px`)
   - Verificar que no haya conflictos con estilos de Tailwind

---

## 🔄 Mantenimiento

### Tareas Periódicas

#### Diarias
- Revisar logs de errores: `storage/logs/laravel.log`
- Revisar dashboard para alertas

#### Semanales
- Crear backup de base de datos
- Revisar y limpiar logs antiguos
- Verificar espacio en disco

#### Mensuales
- Actualizar dependencias (con precaución):
  ```bash
  composer update
  npm update
  ```
- Revisar y optimizar base de datos
- Revisar permisos de archivos

### Comandos Útiles

```bash
# Limpiar todas las cachés
php artisan optimize:clear

# Cachear configuración (producción)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Recompilar assets
npm run build

# Verificar estado de la aplicación
php artisan about

# Ver rutas registradas
php artisan route:list

# Verificar migraciones pendientes
php artisan migrate:status
```

### Optimización

#### Producción
1. Habilitar OPcache en PHP
2. Cachear configuración y rutas
3. Usar CDN para assets estáticos (opcional)
4. Habilitar compresión gzip en Nginx
5. Configurar cache de archivos estáticos

#### Base de Datos
- Índices ya están configurados en las migraciones
- Considerar particionado de tablas grandes (`material_movimientos`, `registro_cambios`)
- Limpiar registros antiguos periódicamente

---

## 🚢 Despliegue en Producción

### Checklist Pre-Despliegue

- [ ] Variables de entorno configuradas (`.env`)
- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Base de datos creada y migrada
- [ ] Assets compilados (`npm run build`)
- [ ] Permisos de storage correctos
- [ ] Nginx configurado
- [ ] PHP-FPM configurado
- [ ] SMTP configurado
- [ ] Backup inicial creado
- [ ] Usuario admin creado

### Pasos de Despliegue

1. **Subir código al servidor**
   ```bash
   git pull origin main
   ```

2. **Instalar/actualizar dependencias**
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci
   ```

3. **Ejecutar migraciones**
   ```bash
   php artisan migrate --force
   ```

4. **Compilar assets**
   ```bash
   npm run build
   ```

5. **Optimizar Laravel**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

6. **Verificar permisos**
   ```bash
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R 775 storage bootstrap/cache
   ```

7. **Recargar servicios**
   ```bash
   sudo systemctl reload php8.3-fpm
   sudo systemctl reload nginx
   ```

### Rollback

Si algo sale mal:

1. **Restaurar backup**
   ```bash
   php artisan backup:restore nombre_backup.sql
   ```

2. **Revertir migraciones** (si es necesario)
   ```bash
   php artisan migrate:rollback
   ```

3. **Revertir código**
   ```bash
   git checkout <commit-anterior>
   ```

---

## 🔒 Seguridad

### Buenas Prácticas Implementadas

1. **Autenticación**: Laravel Sanctum con tokens
2. **Validación**: Validación de datos en todos los endpoints
3. **CSRF Protection**: Tokens CSRF en formularios
4. **SQL Injection**: Uso de Eloquent ORM (protección automática)
5. **XSS Protection**: Vue.js escapa automáticamente
6. **Password Hashing**: Bcrypt para contraseñas
7. **Rate Limiting**: En endpoints de autenticación
8. **HTTPS**: Recomendado en producción

### Recomendaciones Adicionales

1. **Usar HTTPS** en producción
2. **Configurar firewall** para limitar acceso
3. **Backups automáticos** periódicos
4. **Actualizar dependencias** regularmente
5. **Monitorear logs** de seguridad
6. **Usar contraseñas seguras** para usuarios admin
7. **Limitar intentos de login** (ya implementado)

### Configuración de Seguridad en Nginx

```nginx
# Headers de seguridad
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";
add_header X-XSS-Protection "1; mode=block";

# Ocultar versión de Nginx
server_tokens off;

# Limitar tamaño de uploads
client_max_body_size 10M;
```

---

## 👥 Contribución

### Flujo de Trabajo

1. Crear rama desde `main`:
   ```bash
   git checkout -b feature/nueva-funcionalidad
   ```

2. Desarrollar y probar cambios

3. Commit con mensaje descriptivo:
   ```bash
   git commit -m "feat: añadir nueva funcionalidad X"
   ```

4. Push y crear Pull Request

5. Revisión y merge

### Estándares de Código

- **PHP**: Seguir PSR-12
- **JavaScript**: Seguir estándares de Vue.js 3
- **Comentarios**: Documentar funciones complejas
- **Nombres**: Usar nombres descriptivos en español para el dominio de negocio

### Testing

Antes de hacer commit:
- Probar funcionalidad manualmente
- Verificar que no haya errores en consola
- Verificar que los assets se compilen correctamente
- Verificar permisos y configuración

---

## 📞 Soporte

Para problemas o dudas:
1. Revisar esta documentación
2. Revisar logs: `storage/logs/laravel.log`
3. Consultar con el equipo de desarrollo
4. Revisar issues en el repositorio

---

## 📄 Licencia

Propietaria - Junta de Andalucía

---

## 📝 Changelog

### Versión 3.0.0 (Noviembre 2024)
- Sistema completo de gestión de inventario
- Peticiones públicas de material
- Dashboard con estadísticas
- Sistema de firmas digitales
- **Firma móvil remota con SSE (Server-Sent Events)**
- Gestión multi-almacén
- Integración con provincias y sedes
- PWA funcional
- Sistema de notificaciones
- Backups automáticos

---

## 🎓 Recursos Adicionales

### Documentación Oficial
- [Laravel 11 Documentation](https://laravel.com/docs/11.x)
- [Vue.js 3 Documentation](https://vuejs.org/)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/11.x/sanctum)
- [Leaflet.js Documentation](https://leafletjs.com/reference.html) - Para el mapa de almacenes en la web pública
- [OpenStreetMap](https://www.openstreetmap.org/) - Proveedor de tiles del mapa

### Archivos de Configuración Importantes
- `.env` - Variables de entorno
- `config/app.php` - Configuración de la aplicación
- `config/database.php` - Configuración de base de datos
- `config/auth.php` - Configuración de autenticación
- `vite.config.js` - Configuración de Vite
- `tailwind.config.js` - Configuración de Tailwind

---

**Última actualización**: Noviembre 2024  
**Versión del documento**: 1.0  
**Mantenido por**: Equipo de Desarrollo - Junta de Andalucía

