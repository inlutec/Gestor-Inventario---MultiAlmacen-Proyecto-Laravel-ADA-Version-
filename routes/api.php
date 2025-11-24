<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EntidadController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\SedeController;
use App\Http\Controllers\MaterialMovimientoController;
use App\Http\Controllers\AlbaranPublicoController;
use App\Http\Controllers\MaterialPeticionController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\FirmaMovilController;
use App\Http\Controllers\BackupController;

/*
|--------------------------------------------------------------------------
| API Routes - Pequeño Material
|--------------------------------------------------------------------------
*/

// Rutas públicas
Route::post('/login', [AuthController::class, 'login']);

// Albaranes públicos (sin autenticación)
Route::get('/albaran/{token}', [AlbaranPublicoController::class, 'ver']);
Route::post('/albaran/{token}/firmar', [AlbaranPublicoController::class, 'firmar']);
Route::get('/albaran/{token}/pdf', [AlbaranPublicoController::class, 'descargarPDF']);
Route::get('/albaran/{token}/pdf-sin-firmar', [MaterialMovimientoController::class, 'descargarPDFSinFirmar']);
Route::post('/albaran/{token}/subir-pdf-firmado', [MaterialMovimientoController::class, 'subirPDFFirmado']);

// Peticiones públicas (sin autenticación)
Route::post('/peticiones', [MaterialPeticionController::class, 'store']);
Route::get('/materiales-disponibles', [MaterialPeticionController::class, 'materialesDisponibles']);

// Solicitudes de reposición públicas (sin autenticación, desde web pública)
Route::post('/solicitudes-reposicion-publicas', [\App\Http\Controllers\SolicitudReposicionController::class, 'storePublico']);

// Sedes y departamentos públicos (para formulario público de peticiones)
Route::get('/sedes-publicas', [SedeController::class, 'publicIndex']);
Route::get('/sedes-publicas/{id}/departamentos', function($id) {
    $departamentos = \App\Models\Departamento::where('sede_id', $id)->orderBy('nombre')->get(['id', 'sede_id', 'nombre', 'clave']);
    return response()->json($departamentos);
});

// Campos personalizados públicos (para formulario público de peticiones)
Route::get('/custom-fields-publicos', [CustomFieldController::class, 'index']);

// Firma móvil - SSE endpoint (sin autenticación, usa session_id)
Route::get('/firma-movil/stream', [FirmaMovilController::class, 'stream']);

// Firma móvil - Envío de firma desde dispositivo móvil (sin autenticación, valida session_id)
Route::post('/material-movimientos/{id}/firmar-remoto', [MaterialMovimientoController::class, 'firmarRemoto']);

// Almacenes disponibles (sin autenticación para selector en páginas públicas)
Route::get('/almacenes-disponibles', [\App\Http\Controllers\UserAlmacenController::class, 'almacenesDisponibles']);

// Dashboard
Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware('auth:sanctum')->middleware('filter.almacen');
Route::get('/dashboard/stats', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware('auth:sanctum')->middleware('filter.almacen');

// Rutas protegidas con autenticación
Route::middleware('auth:sanctum')->group(function () {
    // Autenticación
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/check-session', [AuthController::class, 'checkSession']);

    // Entidades (referencias comunes para todos los almacenes)
    Route::get('/entidades', [EntidadController::class, 'index']);
    Route::get('/entidades/{id}', [EntidadController::class, 'show']);
    Route::post('/entidades', [EntidadController::class, 'store']);
    Route::put('/entidades/{id}', [EntidadController::class, 'update']);
    Route::delete('/entidades/{id}', [EntidadController::class, 'destroy']);
    Route::post('/entidades/{id}/upload-photo', [EntidadController::class, 'uploadPhoto']);
    Route::post('/entidades/{id}/upload-foto-material', [EntidadController::class, 'uploadFotoMaterial']);
    Route::get('/entidades/{id}/historial-stock', [EntidadController::class, 'historialStock']);
    Route::post('/entidades/{id}/regularizar-stock', [EntidadController::class, 'regularizarStock']);
    Route::patch('/entidades/{id}/ubicacion', [EntidadController::class, 'updateUbicacion'])->middleware('filter.almacen');

    // Material - Movimientos (entradas/salidas/histórico)
    Route::get('/material-movimientos', [MaterialMovimientoController::class, 'index'])->middleware('filter.almacen');
    Route::get('/material-movimientos/inventario', [MaterialMovimientoController::class, 'inventario'])->middleware('filter.almacen');
    Route::get('/material-movimientos/{id}', [MaterialMovimientoController::class, 'show']);
    Route::post('/material-movimientos', [MaterialMovimientoController::class, 'store']);
    Route::put('/material-movimientos/{id}', [MaterialMovimientoController::class, 'update']);
    Route::delete('/material-movimientos/{id}', [MaterialMovimientoController::class, 'destroy']);
    Route::post('/material-movimientos/{id}/generar-enlace', [MaterialMovimientoController::class, 'generarEnlacePublico']);
    Route::post('/material-movimientos/{id}/firmar-emisor', [MaterialMovimientoController::class, 'firmarEmisor']);
    Route::get('/material-movimientos/{id}/challenge', [MaterialMovimientoController::class, 'challenge']);
    Route::post('/material-movimientos/{id}/firmar-certificado', [MaterialMovimientoController::class, 'firmarConCertificado']);
    Route::get('/material-movimientos/{id}/pdf', [MaterialMovimientoController::class, 'descargarPDF']);
    Route::post('/material-movimientos/{id}/solicitar-firma-remota', [MaterialMovimientoController::class, 'solicitarFirmaRemota']);
    Route::get('/material-movimientos/{id}/verificar-firma-pendiente', [MaterialMovimientoController::class, 'verificarFirmaPendiente']);
    Route::post('/material-movimientos/{id}/confirmar-firma-remota', [MaterialMovimientoController::class, 'confirmarFirmaRemota']);
    Route::post('/material-movimientos/{id}/marcar-entregado', [MaterialMovimientoController::class, 'marcarEntregado']);
    Route::get('/material-movimientos/{id}/historial-auditoria', [MaterialMovimientoController::class, 'obtenerHistorialAuditoria']);
    Route::delete('/material-movimientos/{movimiento}/firmas/{firma}', [MaterialMovimientoController::class, 'anularFirma']);

    // Peticiones de material (gestión interna)
    Route::get('/peticiones', [MaterialPeticionController::class, 'index'])->middleware('filter.almacen');
    Route::post('/peticiones/{id}/aprobar', [MaterialPeticionController::class, 'aprobar']);
    Route::post('/peticiones/{id}/denegar', [MaterialPeticionController::class, 'denegar']);
    Route::get('/peticiones/{id}/historial', [MaterialPeticionController::class, 'historial']);
    Route::get('/peticiones/{id}/historial-auditoria', [MaterialPeticionController::class, 'obtenerHistorialAuditoria']);
    Route::delete('/peticiones/{id}', [MaterialPeticionController::class, 'destroy']);

    // Sedes públicas para selects (autenticados)
    Route::get('/sedes', [SedeController::class, 'publicIndex']);
    
    // Custom fields públicos (para formularios)
    Route::get('/custom-fields', [CustomFieldController::class, 'index']);
    
    // ==================== NOTIFICACIONES PUSH ====================
    Route::post('/notifications/subscribe', [NotificationController::class, 'subscribe']);
    Route::post('/notifications/unsubscribe', [NotificationController::class, 'unsubscribe']);
    Route::get('/notifications/vapid-public-key', [NotificationController::class, 'getPublicKey']);
    Route::post('/notifications/test', [NotificationController::class, 'testNotification']);
    Route::get('/notifications/stats', [NotificationController::class, 'stats']);
    
    // ==================== NOTIFICACIONES BELL ====================
    Route::get('/notificaciones', [\App\Http\Controllers\NotificacionController::class, 'index']);
    Route::post('/notificaciones/{id}/marcar-leida', [\App\Http\Controllers\NotificacionController::class, 'marcarLeida']);
    Route::post('/notificaciones/marcar-todas-leidas', [\App\Http\Controllers\NotificacionController::class, 'marcarTodasLeidas']);
    Route::get('/notificaciones/conteo', [\App\Http\Controllers\NotificacionController::class, 'conteoNoLeidas']);
    
    // ==================== GESTIÓN DE ALMACENES POR USUARIO ====================
    // Disponible para todos los usuarios autenticados (para que puedan ver sus almacenes asignados)
    Route::get('/mis-almacenes', [\App\Http\Controllers\UserAlmacenController::class, 'misAlmacenes']);
    
    // ==================== CONFIGURACIÓN ====================
    
    // Restringir acceso a configuración solo para administradores
    Route::middleware('check.admin')->group(function () {
        // Usuarios
        Route::get('/usuarios', [\App\Http\Controllers\UsuarioController::class, 'index']);
        Route::get('/usuarios/{id}', [\App\Http\Controllers\UsuarioController::class, 'show']);
        Route::post('/usuarios', [\App\Http\Controllers\UsuarioController::class, 'store']);
        Route::put('/usuarios/{id}', [\App\Http\Controllers\UsuarioController::class, 'update']);
        Route::delete('/usuarios/{id}', [\App\Http\Controllers\UsuarioController::class, 'destroy']);
        
        // Gestión de almacenes por usuario (solo admins pueden asignar almacenes)
        Route::get('/usuarios/{userId}/almacenes', [\App\Http\Controllers\UserAlmacenController::class, 'index']);
        Route::post('/usuarios/{userId}/almacenes', [\App\Http\Controllers\UserAlmacenController::class, 'store']);
        Route::get('/usuarios-con-almacenes', [\App\Http\Controllers\UserAlmacenController::class, 'usuariosConAlmacenes']);
        
        // Justificantes (solo modificación para admins)
        Route::post('/config/justificantes', [ConfigController::class, 'storeJustificante']);
        Route::put('/config/justificantes/{id}', [ConfigController::class, 'updateJustificante']);
        Route::delete('/config/justificantes/{id}', [ConfigController::class, 'destroyJustificante']);
        Route::patch('/config/justificantes/reordenar', [ConfigController::class, 'reordenarJustificantes']);
        
        // Campos personalizados
        Route::get('/config/campos', [ConfigController::class, 'indexCampos']);
        Route::post('/config/campos', [ConfigController::class, 'storeCampo']);
        Route::put('/config/campos/{id}', [ConfigController::class, 'updateCampo']);
        Route::delete('/config/campos/{id}', [ConfigController::class, 'destroyCampo']);
        Route::patch('/config/campos/reordenar', [ConfigController::class, 'reordenarCampos']);
        
        // Provincias
        Route::get('/config/provincias', [ConfigController::class, 'indexProvincias']);
        Route::post('/config/provincias', [ConfigController::class, 'storeProvincia']);
        Route::put('/config/provincias/{id}', [ConfigController::class, 'updateProvincia']);
        Route::delete('/config/provincias/{id}', [ConfigController::class, 'destroyProvincia']);
        
        // Sedes y departamentos
        Route::get('/config/sedes', [ConfigController::class, 'indexSedes']);
        Route::post('/config/sedes', [ConfigController::class, 'storeSede']);
        Route::put('/config/sedes/{id}', [ConfigController::class, 'updateSede']);
        Route::delete('/config/sedes/{id}', [ConfigController::class, 'destroySede']);
        Route::post('/config/sedes/{sedeId}/departamentos', [ConfigController::class, 'storeDepartamento']);
        Route::put('/config/departamentos/{id}', [ConfigController::class, 'updateDepartamento']);
        Route::patch('/config/departamentos/{id}/almacen', [ConfigController::class, 'updateAlmacenDepartamento']);
        Route::delete('/config/departamentos/{id}', [ConfigController::class, 'destroyDepartamento']);
        Route::post('/config/departamentos/sync', [ConfigController::class, 'syncDepartamentos']);
        
        // CheckMK
        Route::get('/config/checkmk', [ConfigController::class, 'getCheckmkConfig']);
        Route::put('/config/checkmk', [ConfigController::class, 'updateCheckmkConfig']);
        Route::post('/config/checkmk/test', [ConfigController::class, 'testCheckmkConnection']);
        Route::get('/config/checkmk/logs', [ConfigController::class, 'getCheckmkLogs']);
        
        // SMTP
        Route::get('/config/smtp', [ConfigController::class, 'getSmtpConfig']);
        Route::post('/config/smtp', [ConfigController::class, 'saveSmtpConfig']);
        Route::post('/config/smtp/test', [ConfigController::class, 'testSmtpConfig']);
        Route::delete('/config/smtp/{id}', [ConfigController::class, 'deleteSmtpConfig']);
        
        // Logotipos
        Route::post('/config/upload-logo/{tipo}', [ConfigController::class, 'uploadLogo']);
        
        // Configuración de Notificaciones por Email
        Route::get('/notification-settings', [\App\Http\Controllers\NotificationSettingController::class, 'index']);
        Route::put('/notification-settings/{id}', [\App\Http\Controllers\NotificationSettingController::class, 'update']);
        Route::post('/notification-settings/batch', [\App\Http\Controllers\NotificationSettingController::class, 'updateBatch']);
        
        // Solicitudes de reposición (solo modificación para admins)
        Route::post('/solicitudes-reposicion', [\App\Http\Controllers\SolicitudReposicionController::class, 'store'])->middleware('check.admin');
        Route::put('/solicitudes-reposicion/{id}', [\App\Http\Controllers\SolicitudReposicionController::class, 'update'])->middleware('check.admin');
        Route::delete('/solicitudes-reposicion/{id}', [\App\Http\Controllers\SolicitudReposicionController::class, 'destroy'])->middleware('check.admin');
        
        // Backup y Restore
        Route::get('/config/backup/crear', [BackupController::class, 'crearBackup']);
        Route::post('/config/backup/restaurar', [BackupController::class, 'restaurarBackup']);
        Route::post('/config/backup/reset-sistema', [BackupController::class, 'resetSistema']);
    });
    
    // Justificantes (lectura para todos los usuarios autenticados)
    Route::get('/config/justificantes', [ConfigController::class, 'indexJustificantes']);
    
    // Categorías (comunes para todos los almacenes) - disponible para todos los usuarios autenticados
    Route::get('/config/categorias', [ConfigController::class, 'indexCategorias']);
    Route::post('/config/categorias', [ConfigController::class, 'storeCategoria'])->middleware('check.admin');
    Route::put('/config/categorias/{id}', [ConfigController::class, 'updateCategoria'])->middleware('check.admin');
    Route::delete('/config/categorias/{id}', [ConfigController::class, 'destroyCategoria'])->middleware('check.admin');
    Route::patch('/config/categorias/reordenar', [ConfigController::class, 'reordenarCategorias'])->middleware('check.admin');
    Route::post('/config/categorias/{id}/upload-imagen', [ConfigController::class, 'uploadImagenCategoria'])->middleware('check.admin');
    
    // Solicitudes de reposición (lectura para todos los usuarios autenticados)
    Route::get('/solicitudes-reposicion', [\App\Http\Controllers\SolicitudReposicionController::class, 'index'])->middleware('filter.almacen');
    Route::get('/solicitudes-reposicion/{id}', [\App\Http\Controllers\SolicitudReposicionController::class, 'show']);
});
