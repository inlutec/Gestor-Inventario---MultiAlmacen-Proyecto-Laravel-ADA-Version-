<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\GeoAlmacenController;
use App\Http\Controllers\Proyectos\ProyectoController;
use App\Http\Controllers\Proyectos\TareaController;
use App\Http\Controllers\Proyectos\ComentarioController;
use App\Http\Controllers\Proyectos\AdjuntoController;

// ==================== RUTAS API PÚBLICAS (sin middleware Sanctum) ====================
// Estas rutas van en web.php con prefijo /api para evitar el middleware de Sanctum
Route::prefix('api')->group(function () {
    Route::get('/categorias-publicas', [ConfigController::class, 'indexCategoriasPublicas']);
    Route::get('/provincias', [ConfigController::class, 'indexProvinciasPublicas']);
    Route::get('/sedes-publicas', [ConfigController::class, 'indexSedesPublicas']);
    Route::get('/sedes-publicas/{sedeId}/departamentos', [ConfigController::class, 'getDepartamentosPorSedePublica']);
    Route::get('/custom-fields-publicos', [ConfigController::class, 'indexCamposPublicos']);
    Route::get('/almacenes-publicos', [GeoAlmacenController::class, 'almacenesPublicos']);
    Route::get('/almacenes-por-provincia', [GeoAlmacenController::class, 'almacenesPorProvincia']);
    Route::get('/sedes-por-provincia', [GeoAlmacenController::class, 'sedesPorProvincia']);
});

// ==================== RUTAS DE PROYECTOS ====================
Route::prefix('proyectos')->middleware(['auth:sanctum'])->name('proyectos.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [ProyectoController::class, 'dashboard'])->name('dashboard');
    
    // Proyectos
    Route::get('/', [ProyectoController::class, 'index'])->name('index');
    Route::get('/crear', [ProyectoController::class, 'create'])->name('create');
    Route::post('/', [ProyectoController::class, 'store'])->name('store');
    Route::get('/{id}', [ProyectoController::class, 'show'])->name('show');
    Route::get('/{id}/editar', [ProyectoController::class, 'edit'])->name('edit');
    Route::put('/{id}', [ProyectoController::class, 'update'])->name('update');
    Route::delete('/{id}', [ProyectoController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/archivar', [ProyectoController::class, 'archivar'])->name('archivar');
    
    // Tareas
    Route::prefix('tareas')->name('tareas.')->group(function () {
        Route::get('/', [TareaController::class, 'index'])->name('index');
        Route::get('/mis-tareas', [TareaController::class, 'misTareas'])->name('mis-tareas');
        Route::post('/', [TareaController::class, 'store'])->name('store');
        Route::get('/{id}', [TareaController::class, 'show'])->name('show');
        Route::put('/{id}', [TareaController::class, 'update'])->name('update');
        Route::delete('/{id}', [TareaController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/completar', [TareaController::class, 'completar'])->name('completar');
        Route::post('/{id}/reabrir', [TareaController::class, 'reabrir'])->name('reabrir');
    });
    
    // Comentarios
    Route::prefix('comentarios')->name('comentarios.')->group(function () {
        Route::post('/', [ComentarioController::class, 'store'])->name('store');
        Route::put('/{id}', [ComentarioController::class, 'update'])->name('update');
        Route::delete('/{id}', [ComentarioController::class, 'destroy'])->name('destroy');
    });
    
    // Adjuntos
    Route::prefix('adjuntos')->name('adjuntos.')->group(function () {
        Route::post('/', [AdjuntoController::class, 'store'])->name('store');
        Route::get('/{id}/descargar', [AdjuntoController::class, 'descargar'])->name('descargar');
        Route::delete('/{id}', [AdjuntoController::class, 'destroy'])->name('destroy');
    });
});

// Ruta específica para firmamovil - retorna la vista Blade de Laravel
Route::get('/firmamovil', function () {
    return view('firmamovil');
})->name('firmamovil');

// Ruta raíz - página de bienvenida pública
Route::get('/', function () {
    return view('app');
});

// Ruta de login nombrada para middleware de autenticación (SPA)
Route::get('/login', function () {
    return view('app');
})->name('login');

// Catch-all para Vue Router SPA (excluye rutas específicas, api y archivos .html)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '(?!proyectos.*|firmamovil|firma-pwa\.php|login|api\/.*|.*\.html).*');

