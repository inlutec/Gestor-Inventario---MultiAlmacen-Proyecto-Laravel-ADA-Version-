# Funcionalidades del Sistema

## Índice

1. [Gestión de Materiales](#gestión-de-materiales)
2. [Movimientos de Entrada/Salida](#movimientos-de-entrada-salida)
3. [Firma Móvil Remota (SSE)](#firma-móvil-remota-sse)
4. [Peticiones Públicas](#peticiones-públicas)
5. [Dashboard y Estadísticas](#dashboard-y-estadísticas)
6. [Gestión de Almacenes](#gestión-de-almacenes)
7. [Solicitudes de Reposición](#solicitudes-de-reposición)
8. [Sistema de Notificaciones](#sistema-de-notificaciones)
9. [PWA (Progressive Web App)](#pwa-progressive-web-app)
10. [Sistema de Proyectos](#sistema-de-proyectos)
11. [Backups](#backups)

---

## Gestión de Materiales

### Descripción
Sistema completo de catálogo y gestión de materiales con control de stock en tiempo real.

### Funcionalidades

#### Catálogo de Materiales
- **Crear material**: Formulario completo con categorías, datos personalizados, fotos
- **Editar material**: Modificación de todos los campos
- **Eliminar material**: Solo administradores (con advertencia)
- **Búsqueda**: Por nombre, referencia, categoría
- **Filtrado**: Por almacén, categoría, tipo de entidad

#### Control de Stock
- **Stock actual**: Calculado automáticamente desde movimientos
- **Stock mínimo**: Configurable por material
- **Alertas**: Visualización de stock bajo/crítico
- **Regularización**: Ajuste manual de stock con movimiento automático
- **Historial**: Historial completo de movimientos por material

#### Ubicaciones
- **Ubicación física**: Campo de texto libre
- **Ubicación en plano**: Coordenadas X/Y en planos de edificios
- **Múltiples ubicaciones**: Soporte para materiales en varios almacenes

### Archivos Relacionados
- **Backend**: `app/Http/Controllers/EntidadController.php`
- **Frontend**: `resources/js/views/MaterialReferencias.vue`
- **Modelo**: `app/Models/Entidad.php`

---

## Movimientos de Entrada/Salida

### Descripción
Sistema completo de registro de movimientos de material con albaranes y firmas digitales.

### Funcionalidades

#### Crear Movimiento
- **Tipo**: Entrada o Salida
- **Origen/Destino**: 
  - Modo manual: Texto libre
  - Modo estructurado: Provincia → Sede → Departamento
- **Justificante**: Selección de justificante configurado
- **Líneas de material**: Múltiples materiales con cantidades
- **Validaciones**: 
  - Stock suficiente para salidas
  - Destino obligatorio para entradas

#### Albaranes
- **Generación automática**: Al crear movimiento
- **Número único**: Generado automáticamente
- **PDF**: Generación de PDF con DomPDF
- **Firmas**: Inclusión de firmas en PDF
- **Enlace público**: Para firma externa

#### Firmas Digitales
- **Firma del emisor**: Para salidas
- **Firma del receptor**: Para entradas y salidas
- **Múltiples métodos**:
  - Firma desde aplicación (autenticado)
  - Firma desde enlace público
  - Firma móvil remota (SSE)
- **Validación**: Verificación de firmas pendientes

#### Estados del Movimiento
- `pendiente`: Creado, pendiente de procesar
- `pendiente_firma`: Esperando firmas
- `firmado`: Completamente firmado
- `entregado`: Entregado físicamente
- `cancelado`: Cancelado

### Archivos Relacionados
- **Backend**: `app/Http/Controllers/MaterialMovimientoController.php`
- **Frontend**: `resources/js/views/MaterialMovimientos.vue`
- **Modelo**: `app/Models/MaterialMovimiento.php`

---

## Firma Móvil Remota (SSE)

### Descripción
Sistema de firma móvil en tiempo real mediante Server-Sent Events (SSE).

### Funcionalidades

#### Conexión SSE
- **ID de sesión**: Generación automática de 4 dígitos
- **Conexión persistente**: Mantiene conexión abierta
- **Pings automáticos**: Cada 15 segundos
- **Reconexión automática**: Si se pierde la conexión

#### Solicitud de Firma
- **Desde web**: Introducir ID de sesión
- **Tipo de firma**: Emisor o Receptor
- **Envío instantáneo**: Via SSE al dispositivo móvil
- **Notificación visual**: En dispositivo móvil

#### Captura de Firma
- **Canvas táctil**: Soporte para dedo o stylus
- **Canvas mouse**: Soporte para desktop
- **Limpiar firma**: Botón para reiniciar
- **Confirmación**: Envío al servidor

#### Seguridad
- **Expiración**: Sesiones expiran en 24 horas
- **Validación**: Verificación de sesión antes de enviar
- **Límite de tiempo**: Conexión máxima de 1 hora

### Archivos Relacionados
- **Backend**: 
  - `app/Http/Controllers/FirmaMovilController.php`
  - `app/Http/Controllers/MaterialMovimientoController.php` (métodos remotos)
- **Frontend**: `resources/js/views/FirmaMovil.vue`
- **Documentación**: Ver `docs/SSE_IMPLEMENTATION.md`

---

## Peticiones Públicas

### Descripción
Formulario web público para solicitar material sin necesidad de autenticación.

### Funcionalidades

#### Formulario Público
- **Acceso sin login**: Disponible en `/peticion`
- **Navegación por categorías**: Visualización de categorías activas
- **Búsqueda de materiales**: Por nombre o referencia
- **Selección de materiales**: Con cantidades
- **Campos personalizados**: Configurables por administrador

#### Mapa Interactivo de Almacenes
- **Librería**: Leaflet.js 1.9.4
- **Proveedor de tiles**: OpenStreetMap
- **Marcadores**: Personalizados con nombres de almacenes
- **Distribución automática**: Evita superposición
- **Popups informativos**: Datos del almacén
- **Selección directa**: Click en marcador para seleccionar
- **Filtrado por provincia**: Filtro opcional

#### Selección Manual
- **Provincia → Sede → Departamento**: Selectores anidados
- **Alternativa al mapa**: Si no se usa el mapa

#### Datos del Solicitante
- **Nombre**: Obligatorio
- **Email**: Obligatorio, validado
- **Teléfono**: Opcional
- **Justificación**: Texto libre

#### Aprobación/Denegación
- **Panel de administración**: Lista de peticiones pendientes
- **Aprobación total**: Aprobar todos los materiales
- **Aprobación parcial**: Aprobar solo algunos materiales
- **Denegación**: Con motivo
- **Creación automática**: Movimiento de salida al aprobar

### Archivos Relacionados
- **Backend**: `app/Http/Controllers/MaterialPeticionController.php`
- **Frontend**: `resources/js/views/MaterialPeticionPublica.vue`
- **Componente Mapa**: `resources/js/components/MapaAlmacenes.vue`
- **Modelo**: `app/Models/Pedido.php`

---

## Dashboard y Estadísticas

### Descripción
Panel de control con KPIs, gráficos y alertas del sistema.

### Funcionalidades

#### KPIs Principales
- **Total materiales**: Contador de materiales en sistema
- **Pendientes de firma**: Movimientos esperando firmas
- **Alertas de stock**: Materiales con stock bajo/crítico
- **Peticiones pendientes**: Peticiones sin aprobar

#### Gráficos
- **Movimientos mensuales**: Entradas vs Salidas
- **Peticiones mensuales**: Evolución de peticiones
- **Stock por categoría**: Distribución de stock
- **Materiales más solicitados**: Top 10 del mes

#### Alertas
- **Stock crítico**: Materiales con stock < mínimo
- **Movimientos urgentes**: Pendientes de procesar
- **Solicitudes pendientes**: Sin revisar

#### Actividad Reciente
- **Últimos movimientos**: Últimos 10 movimientos
- **Actividad del sistema**: Últimas acciones
- **Notificaciones**: Notificaciones recientes

### Archivos Relacionados
- **Backend**: `app/Http/Controllers/DashboardController.php`
- **Frontend**: `resources/js/views/Dashboard.vue`
- **Librería gráficos**: Chart.js

---

## Gestión de Almacenes

### Descripción
Sistema multi-almacén con asignación de usuarios y filtrado de datos.

### Funcionalidades

#### Estructura Geográfica
- **Provincias**: 8 provincias de Andalucía
- **Sedes**: Centros dentro de provincias
- **Departamentos**: Departamentos dentro de sedes
- **Almacenes**: Departamentos marcados como almacenes

#### Asignación de Usuarios
- **Múltiples almacenes**: Usuario puede tener acceso a varios
- **Filtrado automático**: Datos filtrados por almacenes asignados
- **Gestión por admin**: Solo administradores pueden asignar

#### Coordenadas Geográficas
- **Latitud/Longitud**: Para visualización en mapa
- **Dirección**: Dirección física del almacén
- **Visualización**: En mapa de peticiones públicas

### Archivos Relacionados
- **Backend**: 
  - `app/Http/Controllers/UserAlmacenController.php`
  - `app/Http/Controllers/GeoAlmacenController.php`
- **Middleware**: `app/Http/Middleware/FilterByAlmacen.php`
- **Modelos**: `app/Models/Provincia.php`, `app/Models/Sede.php`, `app/Models/Departamento.php`

---

## Solicitudes de Reposición

### Descripción
Sistema de solicitudes automáticas cuando el stock está bajo.

### Funcionalidades

#### Creación Automática
- **Detección**: Cuando stock < mínimo
- **Notificación**: Email al administrador
- **Seguimiento**: Estado de la solicitud

#### Gestión Manual
- **Crear solicitud**: Manualmente desde interfaz
- **Actualizar estado**: Pendiente → En proceso → Recibida
- **Previsión de llegada**: Fecha estimada
- **Observaciones**: Notas adicionales

#### Público
- **Formulario público**: Desde web pública
- **Sin autenticación**: Accesible públicamente

### Archivos Relacionados
- **Backend**: `app/Http/Controllers/SolicitudReposicionController.php`
- **Frontend**: `resources/js/views/SolicitudesReposicion.vue`
- **Modelo**: `app/Models/SolicitudReposicion.php`

---

## Sistema de Notificaciones

### Descripción
Sistema completo de notificaciones por email y push.

### Funcionalidades

#### Notificaciones en Tiempo Real
- **Bell de notificaciones**: Icono con contador
- **Lista de notificaciones**: Panel desplegable
- **Marcar como leída**: Individual o todas
- **Auto-actualización**: Polling periódico

#### Notificaciones Push (PWA)
- **Suscripción**: VAPID keys
- **Envío**: Desde servidor
- **Recepción**: En dispositivo móvil
- **Acción**: Click abre la aplicación

#### Notificaciones por Email
- **SMTP configurable**: Múltiples configuraciones
- **Eventos configurables**: Qué eventos envían email
- **Destinatarios**: Configurables por evento
- **Prueba de conexión**: Desde interfaz

#### Configuración
- **Eventos**: Lista de eventos disponibles
- **Email/Push**: Activar/desactivar por evento
- **Destinatarios**: Selección de usuarios

### Archivos Relacionados
- **Backend**: 
  - `app/Http/Controllers/NotificationController.php`
  - `app/Http/Controllers/NotificacionController.php`
  - `app/Services/NotificationService.php`
  - `app/Services/PushNotificationService.php`
- **Frontend**: Componentes de notificaciones en layouts
- **Modelos**: `app/Models/Notificacion.php`, `app/Models/PushSubscription.php`

---

## PWA (Progressive Web App)

### Descripción
Aplicación web instalable en dispositivos móviles con funcionalidad offline.

### Funcionalidades

#### Instalación
- **Prompt de instalación**: Sugerencia al usuario
- **Manifest**: Configuración de PWA
- **Iconos**: Múltiples tamaños
- **Pantalla de inicio**: Personalizada

#### Service Worker
- **Caché de assets**: Archivos estáticos
- **Funcionamiento offline**: Caché de recursos
- **Actualización**: Detección de nuevas versiones

#### Funcionalidad Móvil
- **Firma móvil**: Uso de SSE desde móvil
- **Navegación táctil**: Optimizada para touch
- **Responsive**: Adaptado a móviles

### Archivos Relacionados
- **Manifest**: `public/manifest.json`
- **Service Worker**: `public/service-worker.js`
- **Componentes**: `resources/js/components/PWAInstallPrompt.vue`

---

## Sistema de Proyectos

### Descripción
Sistema de gestión de proyectos con tareas, comentarios y adjuntos.

### Funcionalidades

#### Proyectos
- **Crear proyecto**: Con código, nombre, descripción
- **Estados**: Planificación, En progreso, Pausado, Completado, Cancelado
- **Prioridades**: Baja, Media, Alta, Crítica
- **Responsable**: Asignación de responsable
- **Fechas**: Inicio, fin estimada, fin real
- **Progreso**: Porcentaje de completado
- **Archivado**: Soft delete

#### Tareas
- **Crear tarea**: Dentro de proyecto
- **Asignación**: Asignar a usuario
- **Estados**: Pendiente, En progreso, Completada, Cancelada
- **Completar/Reabrir**: Cambio de estado
- **Dependencias**: Relaciones entre tareas

#### Comentarios y Adjuntos
- **Comentarios**: En proyectos y tareas
- **Adjuntos**: Archivos asociados
- **Historial**: Seguimiento de cambios

### Archivos Relacionados
- **Backend**: `app/Http/Controllers/Proyectos/`
- **Frontend**: Vistas de proyectos
- **Modelos**: `app/Models/Proyectos/`
- **Base de datos**: Conexión separada `proyectos`

---

## Backups

### Descripción
Sistema de backup y restauración de base de datos.

### Funcionalidades

#### Crear Backup
- **Manual**: Desde interfaz de administración
- **mysqldump**: Comando de MySQL
- **Descarga**: Descarga directa del archivo SQL
- **Logging**: Registro de backups creados

#### Restaurar Backup
- **Subida de archivo**: Selección de archivo SQL
- **Validación**: Verificación de formato
- **Ejecución**: Restauración completa
- **Logging**: Registro de restauraciones

#### Resetear Sistema
- **Eliminación selectiva**: Solo datos operativos
- **Preserva**: Usuarios, configuración, maestros
- **Transaccional**: Rollback en caso de error
- **Logging**: Registro completo de operación

### Archivos Relacionados
- **Backend**: `app/Http/Controllers/BackupController.php`
- **Frontend**: Panel de configuración
- **Almacenamiento**: `storage/app/backups/`

---

## Configuración del Sistema

### Gestión de Usuarios
- Crear, editar, eliminar usuarios
- Asignar roles (admin/usuario)
- Asignar almacenes
- Activar/desactivar usuarios

### Gestión de Categorías
- Crear, editar, eliminar categorías
- Subir imágenes
- Ordenar categorías
- Asignar a provincias

### Gestión Geográfica
- Provincias, Sedes, Departamentos
- Marcar departamentos como almacenes
- Coordenadas geográficas

### Justificantes
- Crear justificantes para entradas/salidas
- Activar/desactivar
- Ordenar

### Campos Personalizados
- Crear campos globales
- Tipos: text, number, select, textarea, date, checkbox
- Configurar visibilidad pública

### SMTP
- Múltiples configuraciones SMTP
- Prueba de conexión
- Activar/desactivar

### Notificaciones
- Configurar eventos notificables
- Configurar destinatarios
- Activar email/push por evento
