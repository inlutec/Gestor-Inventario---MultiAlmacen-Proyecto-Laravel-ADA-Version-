# Documentación Backend

## Stack Tecnológico

- **Laravel 11**: Framework PHP
- **PHP 8.3+**: Lenguaje de programación
- **MySQL/MariaDB**: Base de datos
- **Laravel Sanctum**: Autenticación API
- **DomPDF**: Generación de PDFs
- **Guzzle**: Cliente HTTP

## Estructura de Directorios

```
app/
├── Console/
│   └── Commands/          # Comandos Artisan
├── Http/
│   ├── Controllers/        # Controladores HTTP
│   │   ├── Api/           # Controladores API específicos
│   │   └── Proyectos/     # Controladores de proyectos
│   ├── Middleware/         # Middleware personalizado
│   └── Kernel.php         # Registro de middleware
├── Models/                 # Modelos Eloquent
│   └── Proyectos/         # Modelos de proyectos
└── Services/              # Servicios de negocio
    ├── NotificationService.php
    └── PushNotificationService.php
```

## Controladores Principales

### AuthController
**Archivo**: `app/Http/Controllers/AuthController.php`

Maneja autenticación:
- `login()`: Iniciar sesión
- `logout()`: Cerrar sesión
- `me()`: Obtener usuario actual
- `checkSession()`: Verificar sesión

### EntidadController
**Archivo**: `app/Http/Controllers/EntidadController.php`

CRUD de materiales:
- `index()`: Listar entidades
- `show()`: Obtener entidad
- `store()`: Crear entidad
- `update()`: Actualizar entidad
- `destroy()`: Eliminar entidad
- `historialStock()`: Historial de stock
- `regularizarStock()`: Regularizar stock

### MaterialMovimientoController
**Archivo**: `app/Http/Controllers/MaterialMovimientoController.php`

Gestión de movimientos:
- `index()`: Listar movimientos
- `store()`: Crear movimiento
- `show()`: Obtener movimiento
- `update()`: Actualizar movimiento
- `destroy()`: Eliminar movimiento
- `firmarEmisor()`: Firmar como emisor
- `firmarRemoto()`: Solicitar firma remota
- `confirmarFirmaRemota()`: Confirmar firma desde móvil
- `descargarPDF()`: Generar PDF del albarán

### FirmaMovilController
**Archivo**: `app/Http/Controllers/FirmaMovilController.php`

Firma móvil con SSE:
- `stream()`: Stream SSE para conexión móvil
- `sesionesActivas()`: Listar sesiones activas

### DashboardController
**Archivo**: `app/Http/Controllers/DashboardController.php`

Estadísticas del dashboard:
- `index()`: Obtener estadísticas y KPIs

### MaterialPeticionController
**Archivo**: `app/Http/Controllers/MaterialPeticionController.php`

Gestión de peticiones:
- `store()`: Crear petición pública
- `aprobar()`: Aprobar petición
- `denegar()`: Denegar petición
- `materialesDisponibles()`: Materiales disponibles para peticiones

### ConfigController
**Archivo**: `app/Http/Controllers/ConfigController.php`

Configuración del sistema:
- Gestión de categorías
- Gestión de provincias, sedes, departamentos
- Gestión de justificantes
- Gestión de campos personalizados
- Configuración SMTP

### BackupController
**Archivo**: `app/Http/Controllers/BackupController.php`

Backups:
- `crearBackup()`: Crear backup de BD
- `restaurarBackup()`: Restaurar desde backup
- `resetSistema()`: Resetear sistema (¡CUIDADO!)

## Modelos Principales

### Entidad
**Archivo**: `app/Models/Entidad.php`

Modelo de materiales con:
- Relaciones: `tipoEntidad`, `categoria`, `departamento`
- Accesores: Cálculo de stock
- Mutadores: Procesamiento de datos JSON

### MaterialMovimiento
**Archivo**: `app/Models/MaterialMovimiento.php`

Modelo de movimientos con:
- Relaciones: `usuario`, `detalles`, `firmas`, `justificante`
- Estados: Enum de estados
- Scopes: Filtrado por tipo, estado, almacén

### MaterialFirma
**Archivo**: `app/Models/MaterialFirma.php`

Modelo de firmas con:
- Relaciones: `movimiento`
- Tipos: Emisor o Receptor

### Pedido
**Archivo**: `app/Models/Pedido.php`

Modelo de pedidos/peticiones con:
- Relaciones: `detalles`, `sede`, `departamento`
- Estados: Enum de estados

### Usuario
**Archivo**: `app/Models/Usuario.php`

Modelo de usuarios con:
- Autenticación: Sanctum
- Relaciones: `almacenes` (many-to-many)

## Middleware

### CheckAdmin
**Archivo**: `app/Http/Middleware/CheckAdmin.php`

Verifica que el usuario tenga rol `admin`:
```php
public function handle(Request $request, Closure $next)
{
    if (auth()->user()->rol !== 'admin') {
        return response()->json(['message' => 'No autorizado'], 403);
    }
    return $next($request);
}
```

### FilterByAlmacen
**Archivo**: `app/Http/Middleware/FilterByAlmacen.php`

Filtra datos por almacenes asignados al usuario:
```php
public function handle(Request $request, Closure $next)
{
    $user = auth()->user();
    $almacenIds = $user->almacenes->pluck('id');
    
    $request->merge(['almacen_ids' => $almacenIds]);
    
    return $next($request);
}
```

## Servicios

### NotificationService
**Archivo**: `app/Services/NotificationService.php`

Servicio para enviar notificaciones:
- `enviarEmail()`: Enviar email
- `crearNotificacion()`: Crear notificación en BD
- `notificarUsuarios()`: Notificar a múltiples usuarios

### PushNotificationService
**Archivo**: `app/Services/PushNotificationService.php`

Servicio para push notifications:
- `enviarPush()`: Enviar notificación push
- `obtenerSuscripciones()`: Obtener suscripciones de usuario

## Rutas

### API Routes
**Archivo**: `routes/api.php`

Rutas API organizadas por:
- Públicas (sin autenticación)
- Autenticadas (requieren token)
- Admin (requieren rol admin)

### Web Routes
**Archivo**: `routes/web.php`

Rutas web para:
- Páginas públicas
- SPA catch-all
- Proyectos

## Validación

### Form Requests

Ejemplo de validación en controlador:
```php
$validated = $request->validate([
    'tipo' => 'required|in:entrada,salida',
    'fecha_movimiento' => 'required|date',
    'justificante_id' => 'required|exists:justificantes,id',
    'detalles' => 'required|array|min:1',
    'detalles.*.entidad_id' => 'required|exists:entidades,id',
    'detalles.*.cantidad' => 'required|integer|min:1',
]);
```

## Autenticación

### Sanctum

Configuración en `config/sanctum.php`:
- Tokens de API
- Stateful domains (si aplica)

Uso:
```php
// En controlador
Route::middleware('auth:sanctum')->group(function () {
    // Rutas protegidas
});
```

## Base de Datos

### Migraciones

Todas las migraciones en `database/migrations/`:
- Orden cronológico
- Nombres descriptivos
- Rollback implementado

### Seeders

Seeders en `database/seeders/`:
- `ProvinciaSeeder`: Provincias de Andalucía
- `DatabaseSeeder`: Seeder principal

## Generación de PDFs

### DomPDF

Uso en controladores:
```php
use Barryvdh\DomPDF\Facade\Pdf;

$pdf = Pdf::loadView('albaran', ['movimiento' => $movimiento]);
return $pdf->download('albaran.pdf');
```

## Caché

### Laravel Cache

Uso para sesiones SSE:
```php
Cache::put("firma_movil_session:{$sessionId}", $data, $expiration);
$session = Cache::get("firma_movil_session:{$sessionId}");
Cache::forget("firma_movil_session:{$sessionId}");
```

## Logging

### Laravel Log

```php
use Illuminate\Support\Facades\Log;

Log::info('Mensaje', ['data' => $data]);
Log::error('Error', ['exception' => $e]);
Log::warning('Advertencia', ['context' => $context]);
```

## Comandos Artisan

### Comandos Personalizados

Crear comando:
```bash
php artisan make:command NombreComando
```

Ejecutar:
```bash
php artisan nombre:comando
```

## Testing

### Pruebas Manuales

Antes de deploy:
1. Probar endpoints con Postman/curl
2. Verificar validaciones
3. Verificar permisos
4. Verificar logs

## Mejores Prácticas

1. **Validación**: Validar todos los inputs
2. **Autorización**: Verificar permisos en cada acción
3. **Logging**: Registrar acciones importantes
4. **Transacciones**: Usar DB transactions para operaciones críticas
5. **Código limpio**: Seguir PSR-12
6. **Documentación**: Comentar código complejo
7. **Seguridad**: Nunca exponer información sensible
