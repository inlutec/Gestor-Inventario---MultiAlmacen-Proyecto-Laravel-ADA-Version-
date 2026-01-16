<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaterialMovimiento;
use App\Models\MaterialMovimientoDetalle;
use App\Models\MaterialFirma;
use App\Models\Entidad;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class MaterialMovimientoController extends Controller
{
    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Listar movimientos de material
     */
    public function index(Request $request)
    {
        try {
            $query = MaterialMovimiento::with([
                'usuario',
                'detalles',
                'firmas',
                'pedido.sede',
                'pedido.departamento',
                'pedido.usuarioCreador',
                'pedido.usuarioAprobador',
                'pedido.detalles.entidad',
                'pedido.historial' => function($q) {
                    $q->where('accion', 'comentario')
                      ->with('usuario:id,nombre,email')
                      ->orderBy('fecha', 'desc');
                },
                'origenDepartamento.sede',
                'destinoDepartamento.sede'
            ]);
            
            // Aplicar filtro por almacén si el usuario no es administrador
            if (auth()->check() && auth()->user()->role !== 'admin') {
                $almacenIds = $request->get('almacen_ids', []);
                if (!empty($almacenIds)) {
                    $query->whereHas('detalles.entidad', function ($q) use ($almacenIds) {
                        $q->whereIn('departamento_id', $almacenIds);
                    });
                }
            }
            
            // Filtros
            if ($request->filled('tipo')) {
                $query->where('tipo', $request->tipo);
            }
            
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }
            
            if ($request->filled('fecha_desde')) {
                $query->whereDate('fecha_movimiento', '>=', $request->fecha_desde);
            }
            
            if ($request->filled('fecha_hasta')) {
                $query->whereDate('fecha_movimiento', '<=', $request->fecha_hasta);
            }
            
            if ($request->filled('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('numero_documento', 'like', "%{$buscar}%")
                      ->orWhere('origen', 'like', "%{$buscar}%")
                      ->orWhere('destino', 'like', "%{$buscar}%");
                });
            }
            
            $movimientos = $query->orderBy('fecha_movimiento', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($mov) {
                    // Determinar el almacén principal del movimiento
                    $almacen = null;
                    if ($mov->destino_departamento_id && $mov->destinoDepartamento) {
                        $almacen = $mov->destinoDepartamento;
                    } elseif ($mov->origen_departamento_id && $mov->origenDepartamento) {
                        $almacen = $mov->origenDepartamento;
                    }
                    
                    // Determinar la justificación según el origen del movimiento
                    $justificacion = '';
                    if ($mov->pedido) {
                        // Si viene de un pedido/petición
                        if ($mov->pedido->tipo === 'peticion') {
                            $justificacion = 'Petición web pública';
                        } else {
                            $justificacion = 'Pedido interno';
                        }
                    } else {
                        // Si es un movimiento manual
                        $justificacion = $mov->observaciones ?: 'Regularización de stock';
                    }
                    
                    return [
                        'id' => $mov->id,
                        'tipo' => $mov->tipo,
                        'numero_documento' => $mov->numero_documento,
                        'fecha_movimiento' => $mov->fecha_movimiento,
                        'fecha_prevista_entrega' => $mov->fecha_prevista_entrega,
                        'fecha_entrega' => $mov->fecha_entrega,
                        'usuario' => $mov->usuario ? [
                            'id' => $mov->usuario->id,
                            'nombre' => $mov->usuario->nombre,
                            'apellido' => $mov->usuario->apellido ?? '',
                            'email' => $mov->usuario->email ?? ''
                        ] : null,
                        'almacen' => $almacen ? [
                            'id' => $almacen->id,
                            'nombre' => $almacen->nombre,
                            'sede' => $almacen->sede ? $almacen->sede->nombre : ''
                        ] : null,
                        'justificacion' => $justificacion,
                        'origen' => $mov->origen,
                        'destino' => $mov->destino,
                        'observaciones' => $mov->observaciones,
                        'estado' => $mov->estado,
                        'detalles' => $mov->detalles,
                        'firmas' => $mov->firmas,
                        'pedido' => $mov->pedido ? [
                            'id' => $mov->pedido->id,
                            'numero_pedido' => $mov->pedido->numero_pedido,
                            'tipo' => $mov->pedido->tipo,
                            'estado' => $mov->pedido->estado,
                            'comentarios_aprobacion' => $mov->pedido->comentarios_aprobacion,
                            'observaciones' => $mov->pedido->observaciones,
                            'notas' => $mov->pedido->notas,
                            'comentarios' => ($mov->pedido->historial && $mov->pedido->historial->isNotEmpty()) 
                                ? $mov->pedido->historial->map(function($entrada) {
                                    return [
                                        'id' => $entrada->id,
                                        'descripcion' => $entrada->descripcion,
                                        'fecha' => $entrada->fecha ? $entrada->fecha->format('d/m/Y H:i:s') : null,
                                        'fecha_relativa' => $entrada->fecha ? $entrada->fecha->diffForHumans() : null,
                                        'usuario' => $entrada->usuario ? [
                                            'nombre' => $entrada->usuario->nombre,
                                            'email' => $entrada->usuario->email
                                        ] : null
                                    ];
                                })->toArray() 
                                : []
                        ] : null,
                        'total_lineas' => $mov->detalles->count(),
                        'total_cantidad' => $mov->detalles->sum('cantidad'),
                        'tiene_firma_emisor' => $mov->firmaEmisor ? true : false,
                        'tiene_firma_receptor' => $mov->firmaReceptor ? true : false,
                        'enlace_publico' => $mov->enlace_publico,
                        'enlace_valido' => $mov->enlaceEsValido(),
                        'created_at' => $mov->created_at,
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $movimientos,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al listar movimientos de material', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los movimientos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener un movimiento específico
     */
    public function show($id)
    {
        try {
            $movimiento = MaterialMovimiento::with(['usuario', 'detalles.entidad', 'firmas'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $movimiento->id,
                    'tipo' => $movimiento->tipo,
                    'numero_documento' => $movimiento->numero_documento,
                    'fecha_movimiento' => $movimiento->fecha_movimiento,
                    'usuario_id' => $movimiento->usuario_id,
                    'usuario' => $movimiento->usuario ? $movimiento->usuario->nombre : null,
                    'origen' => $movimiento->origen,
                    'destino' => $movimiento->destino,
                    'observaciones' => $movimiento->observaciones,
                    'estado' => $movimiento->estado,
                    'enlace_publico' => $movimiento->enlace_publico,
                    'enlace_expira' => $movimiento->enlace_expira,
                    'enlace_valido' => $movimiento->enlaceEsValido(),
                    'detalles' => $movimiento->detalles->map(function($det) {
                        return [
                            'id' => $det->id,
                            'entidad_id' => $det->entidad_id,
                            'descripcion' => $det->descripcion,
                            'cantidad' => $det->cantidad,
                            'unidad' => $det->unidad,
                            'observaciones' => $det->observaciones,
                        ];
                    }),
                    'firmas' => $movimiento->firmas->map(function($firma) {
                        return [
                            'id' => $firma->id,
                            'tipo_firmante' => $firma->tipo_firmante,
                            'nombre' => $firma->nombre,
                            'apellidos' => $firma->apellidos,
                            'dni' => $firma->dni,
                            'firma_rubrica' => $firma->firma_rubrica,
                            'fecha_firma' => $firma->fecha_firma,
                            'ip_address' => $firma->ip_address,
                        ];
                    }),
                    'created_at' => $movimiento->created_at,
                    'updated_at' => $movimiento->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Movimiento no encontrado',
            ], 404);
        }
    }

    /**
     * Crear un nuevo movimiento
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tipo' => 'required|in:entrada,salida',
                'fecha_movimiento' => 'required|date',
                'origen' => 'nullable|string|max:255',
                'destino' => 'nullable|string|max:255',
                // Ubicación estructurada
                'origen_sede_id' => 'nullable|exists:sedes,id',
                'origen_departamento_id' => 'nullable|exists:departamentos,id',
                'destino_sede_id' => 'nullable|exists:sedes,id',
                'destino_departamento_id' => 'nullable|exists:departamentos,id',
                'observaciones' => 'nullable|string',
                'detalles' => 'required|array|min:1',
                'detalles.*.entidad_id' => 'required|exists:entidades,id',
                'detalles.*.descripcion' => 'required|string',
                'detalles.*.cantidad' => 'required|integer|min:1',
                'detalles.*.unidad' => 'required|string',
                'detalles.*.observaciones' => 'nullable|string',
            ]);

            // Reglas adicionales: ENTRADA requiere destino sede y departamento
            if ($validated['tipo'] === 'entrada') {
                if (empty($validated['destino_sede_id']) || empty($validated['destino_departamento_id'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Para entradas, el destino debe ser una sede y un departamento registrados',
                    ], 422);
                }
            }

            // Validar que todas las entidades sean de tipo pequeño material (tipo_entidad_id = 3)
            $entidadesIds = collect($validated['detalles'])->pluck('entidad_id')->unique()->values();
            $noPequenoMaterial = Entidad::whereIn('id', $entidadesIds)->where('tipo_entidad_id', '!=', 3)->count();
            if ($noPequenoMaterial > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden seleccionar artículos de pequeño material en este movimiento.',
                ], 422);
            }

            DB::beginTransaction();

            // Generar número de documento
            $numeroDocumento = MaterialMovimiento::generarNumeroDocumento($validated['tipo']);

            // Crear movimiento
            $movimiento = MaterialMovimiento::create([
                'tipo' => $validated['tipo'],
                'numero_documento' => $numeroDocumento,
                'fecha_movimiento' => $validated['fecha_movimiento'],
                'usuario_id' => auth()->id(),
                'origen_sede_id' => $validated['origen_sede_id'] ?? null,
                'origen_departamento_id' => $validated['origen_departamento_id'] ?? null,
                'origen' => $validated['origen'] ?? null,
                'destino_sede_id' => $validated['destino_sede_id'] ?? null,
                'destino_departamento_id' => $validated['destino_departamento_id'] ?? null,
                'destino' => $validated['destino'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'estado' => 'borrador',
            ]);

            // Crear detalles
            foreach ($validated['detalles'] as $detalle) {
                MaterialMovimientoDetalle::create([
                    'movimiento_id' => $movimiento->id,
                    'entidad_id' => $detalle['entidad_id'],
                    'descripcion' => $detalle['descripcion'],
                    'cantidad' => $detalle['cantidad'],
                    'unidad' => $detalle['unidad'],
                    'observaciones' => $detalle['observaciones'] ?? null,
                ]);
            }

            // Para movimientos de ENTRADA, generar enlace público automáticamente
            // Nota: Devolvemos enlace de SPA (no API) para que abra la pantalla pública de firma
            $enlacePublico = null;
            if ($validated['tipo'] === 'entrada') {
                $token = $movimiento->generarEnlacePublico(30); // 30 días de validez
                $movimiento->estado = 'pendiente_firma';
                $movimiento->save();
                // Generar enlace con hash routing para Vue Router
                $appDomain = \App\Models\AppConfig::getConfig()->app_domain ?? config('app.url');
                $enlacePublico = rtrim($appDomain, '/') . '/gestionmaterial/#/albaran/' . $token;
                
                // Verificar y notificar solicitudes pendientes de los materiales incluidos
                $entidadesIds = collect($validated['detalles'])->pluck('entidad_id')->unique();
                foreach ($entidadesIds as $entidadId) {
                    try {
                        \App\Http\Controllers\SolicitudReposicionController::verificarSolicitudesPendientes($entidadId);
                    } catch (\Exception $e) {
                        \Log::warning("Error verificando solicitudes pendientes para entidad {$entidadId}: " . $e->getMessage());
                    }
                }
            }

            // Registrar en historial de auditoría
            \App\Models\MaterialMovimientoHistorial::registrarCambio(
                $movimiento->id,
                'creado',
                "Movimiento de {$validated['tipo']} creado por " . auth()->user()->nombre,
                null,
                [
                    'numero_documento' => $numeroDocumento,
                    'tipo' => $validated['tipo'],
                    'cantidad_materiales' => count($validated['detalles']),
                    'origen' => $validated['origen'] ?? null,
                    'destino' => $validated['destino'] ?? null
                ]
            );

            // Enviar notificación por email de movimiento creado
            $movimientoConRelaciones = $movimiento->load(['usuario', 'detalles.entidad', 'proveedor']);
            $this->notificationService->notificarMovimientoCreado($movimientoConRelaciones);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Movimiento creado correctamente',
                'data' => $movimiento->fresh(['detalles']),
                'enlace_publico' => $enlacePublico,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear movimiento', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el movimiento: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar un movimiento (solo si está en borrador)
     */
    public function update(Request $request, $id)
    {
        try {
            $movimiento = MaterialMovimiento::findOrFail($id);

            // Si solo se actualiza fecha_prevista_entrega, permitirlo sin restricciones
            if ($request->has('fecha_prevista_entrega')) {
                $validated = $request->validate([
                    'fecha_prevista_entrega' => 'nullable|date',
                    'notificar_usuario' => 'nullable|boolean',
                ]);
                
                $fechaAnterior = $movimiento->fecha_prevista_entrega;
                $movimiento->update(['fecha_prevista_entrega' => $validated['fecha_prevista_entrega']]);

                // Registrar en historial de auditoría
                \App\Models\MaterialMovimientoHistorial::registrarCambio(
                    $movimiento->id,
                    'fecha_prevista_actualizada',
                    "Fecha prevista de entrega actualizada",
                    ['fecha_prevista_entrega' => $fechaAnterior],
                    ['fecha_prevista_entrega' => $validated['fecha_prevista_entrega']],
                    auth()->id()
                );

                // Enviar notificación si se solicita
                if ($request->input('notificar_usuario', false) && $validated['fecha_prevista_entrega']) {
                    Log::info('Intentando enviar notificación de fecha prevista', [
                        'movimiento_id' => $movimiento->id,
                        'notificar_usuario' => $request->input('notificar_usuario'),
                        'fecha' => $validated['fecha_prevista_entrega']
                    ]);
                    $this->notificationService->notificarFechaPrevistaEntrega($movimiento, $validated['fecha_prevista_entrega']);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Fecha prevista actualizada correctamente',
                    'data' => $movimiento->fresh(),
                ]);
            }

            // Para otras actualizaciones, solo permitir en borradores
            if ($movimiento->estado !== 'borrador') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden editar movimientos en borrador',
                ], 403);
            }

            $validated = $request->validate([
                'fecha_movimiento' => 'sometimes|date',
                'origen' => 'nullable|string|max:255',
                'destino' => 'nullable|string|max:255',
                'observaciones' => 'nullable|string',
                'detalles' => 'sometimes|array|min:1',
                'detalles.*.entidad_id' => 'required|exists:entidades,id',
                'detalles.*.descripcion' => 'required|string',
                'detalles.*.cantidad' => 'required|integer|min:1',
                'detalles.*.unidad' => 'required|string',
                'detalles.*.observaciones' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // Guardar datos anteriores para auditoría
            $datosAntes = $movimiento->toArray();
            
            // Actualizar movimiento
            $movimiento->update($validated);

            // Si se enviaron detalles, reemplazarlos
            if (isset($validated['detalles'])) {
                $movimiento->detalles()->delete();
                foreach ($validated['detalles'] as $detalle) {
                    MaterialMovimientoDetalle::create([
                        'movimiento_id' => $movimiento->id,
                        'entidad_id' => $detalle['entidad_id'],
                        'descripcion' => $detalle['descripcion'],
                        'cantidad' => $detalle['cantidad'],
                        'unidad' => $detalle['unidad'],
                        'observaciones' => $detalle['observaciones'] ?? null,
                    ]);
                }
            }

            // Registrar en historial de auditoría
            MaterialMovimientoHistorial::registrarCambio(
                $movimiento->id,
                'modificado',
                "Movimiento modificado en borrador",
                $datosAntes,
                $movimiento->fresh()->toArray(),
                auth()->id()
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Movimiento actualizado correctamente',
                'data' => $movimiento->fresh(['detalles']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar movimiento', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el movimiento: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar un movimiento (solo borradores)
     */
    public function destroy(Request $request, $id)
    {
        try {
            $movimiento = MaterialMovimiento::findOrFail($id);
            // Solo administradores pueden eliminar movimientos, independientemente del estado
            $esAdmin = $request->user() && method_exists($request->user(), 'isAdmin') ? $request->user()->isAdmin() : false;
            if (!$esAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado: se requiere rol administrador para eliminar movimientos',
                ], 403);
            }

            DB::beginTransaction();
            try {
                // Registrar en historial de auditoría ANTES de eliminar
                MaterialMovimientoHistorial::registrarCambio(
                    $movimiento->id,
                    'eliminado',
                    "Movimiento eliminado por administrador",
                    $movimiento->toArray(),
                    null,
                    auth()->id()
                );
                
                // Borrar firmas asociadas (si existe relación)
                if (method_exists($movimiento, 'firmas')) {
                    $movimiento->firmas()->delete();
                }
                // Borrar detalles asociados (libera FK entidad_id)
                if (method_exists($movimiento, 'detalles')) {
                    $movimiento->detalles()->delete();
                }
                // Borrar movimiento
                $movimiento->delete();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Error interno eliminando el movimiento: ' . $e->getMessage(),
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Movimiento eliminado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el movimiento',
            ], 500);
        }
    }

    /**
     * Generar enlace público para firma
     */
    public function generarEnlacePublico(Request $request, $id)
    {
        try {
            $movimiento = MaterialMovimiento::findOrFail($id);

            // Ahora tanto entradas como salidas pueden generar enlaces públicos
            $diasExpiracion = $request->input('dias_expiracion', 30);
            $token = $movimiento->generarEnlacePublico($diasExpiracion);

            // Cambiar estado a pendiente_firma
            $movimiento->estado = 'pendiente_firma';
            $movimiento->save();

            // Generar enlace con hash routing para Vue Router
            $appDomain = \App\Models\AppConfig::getConfig()->app_domain ?? config('app.url');
            $urlPublica = rtrim($appDomain, '/') . '/gestionmaterial/#/albaran/' . $token;

            return response()->json([
                'success' => true,
                'message' => 'Enlace público generado',
                'data' => [
                    'enlace' => $urlPublica,
                    'expira' => $movimiento->enlace_expira,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar enlace: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Firmar un movimiento
     * - Para SALIDAS: firma el emisor (quien entrega)
     * - Para ENTRADAS: firma el receptor (quien recibe)
     */
    public function firmarEmisor(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:100',
                'apellidos' => 'required|string|max:100',
                'dni' => 'nullable|string|max:20',
                'firma_rubrica' => 'required|string', // Base64
            ]);

            $movimiento = MaterialMovimiento::findOrFail($id);

            // Determinar el tipo de firmante según el tipo de movimiento
            if ($movimiento->tipo === 'entrada') {
                // Para ENTRADAS, el que firma desde el admin es el RECEPTOR
                $tipoFirmante = 'receptor';
                
                // Verificar que no tenga firma de receptor
                if ($movimiento->firmaReceptor) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Este movimiento ya tiene firma del receptor',
                    ], 400);
                }
            } else {
                // Para SALIDAS, el que firma desde el admin es el EMISOR
                $tipoFirmante = 'emisor';
                
                // Verificar que no tenga firma de emisor
                if ($movimiento->firmaEmisor) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Este movimiento ya tiene firma del emisor',
                    ], 400);
                }
            }

            MaterialFirma::create([
                'movimiento_id' => $movimiento->id,
                'tipo_firmante' => $tipoFirmante,
                'nombre' => $validated['nombre'],
                'apellidos' => $validated['apellidos'],
                'dni' => $validated['dni'] ?? null,
                'firma_rubrica' => $validated['firma_rubrica'],
                'ip_address' => $request->ip(),
                'fecha_firma' => now(),
            ]);

            // Actualizar estado según firmas requeridas
            $movimiento->refresh();
            $estadoAnterior = $movimiento->estado;
            $movimiento->estado = $movimiento->tieneFirmasCompletas() ? 'firmado' : 'pendiente_firma';
            $movimiento->save();

            // Registrar en historial de auditoría
            \App\Models\MaterialMovimientoHistorial::registrarCambio(
                $movimiento->id,
                'firmado_' . $tipoFirmante,
                "Firma de {$tipoFirmante} realizada por {$validated['nombre']} {$validated['apellidos']}",
                ['estado' => $estadoAnterior],
                [
                    'estado' => $movimiento->estado,
                    'tipo_firmante' => $tipoFirmante,
                    'nombre_firmante' => $validated['nombre'] . ' ' . $validated['apellidos'],
                    'dni' => $validated['dni'] ?? null
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Documento firmado correctamente',
            ]);

        } catch (\Exception $e) {
            Log::error('Error al firmar', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al firmar: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener inventario actual
     */
    public function inventario(Request $request)
    {
        try {
            // Obtener entidades de tipo "pequeño material" (tipo_entidad_id = 3)
            $query = Entidad::where('tipo_entidad_id', 3);
            
            // Aplicar filtro por almacén si se especifica
            $almacenIds = $request->get('almacen_ids', []);
            // Forzar a array si viene como string
            if (!is_array($almacenIds)) {
                $almacenIds = $almacenIds !== '' ? [$almacenIds] : [];
            }
            if (!empty($almacenIds)) {
                $query->porAlmacenes($almacenIds);
            }
            
            $materialesRaw = $query->get();
            \Log::info('Materiales obtenidos en inventario', [
                'count' => $materialesRaw->count(),
                'ids' => $materialesRaw->pluck('id'),
                'datos_null' => $materialesRaw->filter(function($m){ return !isset($m->datos); })->pluck('id'),
                'datos_type' => $materialesRaw->map(function($m){ return is_array($m->datos) ? 'array' : gettype($m->datos); })
            ]);
            $materiales = $materialesRaw->map(function($material) use ($almacenIds) {
                try {
                    $datos = $material->datos ?? [];
                    // ...existing code...
                } catch (\Throwable $e) {
                    \Log::error('Error en material inventario', [
                        'material_id' => $material->id ?? null,
                        'datos' => $material->datos ?? null,
                        'exception' => $e->getMessage()
                    ]);
                    return null;
                }
                    
                    // Calcular stock actual basado en movimientos
                    // Filtrar también por almacén en los movimientos si es necesario
                    $entradasQuery = MaterialMovimientoDetalle::whereHas('movimiento', function($q) {
                        $q->where('tipo', 'entrada')
                          ->whereIn('estado', ['firmado', 'entregado']);
                    })->where('entidad_id', $material->id);
                    
                    $salidasQuery = MaterialMovimientoDetalle::whereHas('movimiento', function($q) {
                        $q->where('tipo', 'salida')
                          ->whereIn('estado', ['firmado', 'entregado']);
                    })->where('entidad_id', $material->id);
                    
                    // Si hay filtros de almacén, aplicarlos también a los movimientos
                    if (!empty($almacenIds)) {
                        $entradasQuery->whereHas('movimiento', function($q) use ($almacenIds) {
                            $q->whereIn('destino_departamento_id', $almacenIds);
                        });
                        $salidasQuery->whereHas('movimiento', function($q) use ($almacenIds) {
                            $q->whereIn('origen_departamento_id', $almacenIds);
                        });
                    }
                    
                    $entradas = $entradasQuery->sum('cantidad');
                    $salidas = $salidasQuery->sum('cantidad');
                    
                    $stockActual = $entradas - $salidas;
                    
                    // Obtener ubicación específica del almacén si está filtrado
                    $ubicacion = $datos['ubicacion'] ?? '';
                    if (!empty($almacenIds) && count($almacenIds) === 1) {
                        // Si hay un solo almacén seleccionado, usar su ubicación específica
                        $almacenId = $almacenIds[0];
                        $ubicacionEspecifica = $material->ubicacionAlmacen($almacenId);
                        if ($ubicacionEspecifica) {
                            $ubicacion = $ubicacionEspecifica;
                        }
                    }
                    
                    return [
                        'id' => $material->id,
                        'nombre' => $datos['referencia'] ?? $datos['nombre'] ?? 'Sin nombre',
                        'descripcion' => $datos['descripcion'] ?? '',
                        'unidad' => $datos['unidad'] ?? 'unidad',
                        'stock_actual' => $stockActual,
                        'stock_minimo' => $datos['stock_minimo'] ?? 0,
                        'ubicacion' => $ubicacion,
                        'datos' => $datos,
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $materiales,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener inventario', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener inventario: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Descargar PDF del movimiento (requiere autenticación)
     */
    public function descargarPDF($id)
    {
        try {
            $movimiento = MaterialMovimiento::with(['detalles', 'firmas', 'usuario'])
                ->findOrFail($id);

            // Para usuarios autenticados, permitir descarga sin validar firmas
            // Esto permite a los admins descargar el PDF en cualquier estado
            
            $pdf = Pdf::loadView('pdf.albaran', [
                'movimiento' => $movimiento,
                'detalles' => $movimiento->detalles,
                'firmaEmisor' => $movimiento->firmaEmisor,
                'firmaReceptor' => $movimiento->firmaReceptor,
            ]);

            $filename = "justificante_{$movimiento->numero_documento}.pdf";

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Error al descargar PDF', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el PDF',
            ], 500);
        }
    }

    /**
     * Generar challenge para firma con certificado (antireplay)
     */
    public function challenge($id)
    {
        try {
            $mov = MaterialMovimiento::findOrFail($id);
            $nonce = bin2hex(random_bytes(16));
            $payload = $mov->id.'|'.$mov->numero_documento.'|'.$mov->fecha_movimiento.'|'.$nonce;
            $challenge = hash('sha256', $payload, true); // bytes
            $challengeB64 = base64_encode($challenge);
            // Guardar en cache 2 minutos
            Cache::put("mov:challenge:{$mov->id}:{$nonce}", $challengeB64, now()->addMinutes(2));
            return response()->json([
                'success' => true,
                'data' => [
                    'nonce' => $nonce,
                    'challenge' => $challengeB64,
                    'algoritmo' => 'SHA256',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>'No se pudo generar el challenge'],500);
        }
    }

    /**
     * Firmar con certificado digital (emisor/receptor según tipo movimiento)
     */
    public function firmarConCertificado(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nonce' => 'required|string',
                'firma' => 'required|string', // base64
                'certificado' => 'required|string', // PEM
                'algoritmo' => 'nullable|string',
            ]);

            $mov = MaterialMovimiento::findOrFail($id);

            // Determinar firmante
            $tipoFirmante = $mov->tipo === 'entrada' ? 'receptor' : 'emisor';
            if ($tipoFirmante === 'receptor' && $mov->firmaReceptor) {
                return response()->json(['success'=>false,'message'=>'Este movimiento ya tiene firma del receptor'],400);
            }
            if ($tipoFirmante === 'emisor' && $mov->firmaEmisor) {
                return response()->json(['success'=>false,'message'=>'Este movimiento ya tiene firma del emisor'],400);
            }

            // Recuperar challenge de cache
            $cacheKey = "mov:challenge:{$mov->id}:{$validated['nonce']}";
            $challengeB64 = Cache::pull($cacheKey); // consumir
            if (!$challengeB64) {
                return response()->json(['success'=>false,'message'=>'Challenge inválido o expirado'],400);
            }

            $firmaBin = base64_decode($validated['firma']);
            $certPem = $validated['certificado'];

            // Verificar firma con OpenSSL
            $ok = openssl_verify(base64_decode($challengeB64), $firmaBin, $certPem, OPENSSL_ALGO_SHA256);
            if ($ok !== 1) {
                return response()->json(['success'=>false,'message'=>'Firma inválida'],400);
            }

            // Parsear certificado
            $certRes = openssl_x509_read($certPem);
            $parsed = openssl_x509_parse($certRes);
            $subject = $parsed['subject'] ?? [];
            $issuer = $parsed['issuer'] ?? [];
            $cn = $subject['CN'] ?? '';
            $serialNumber = $subject['serialNumber'] ?? ($subject['2.5.4.5'] ?? null);
            $dni = $serialNumber ? preg_replace('/[^A-Za-z0-9]/', '', $serialNumber) : null;
            $partes = preg_split('/\s+/', trim($cn));
            $nombre = $partes ? array_shift($partes) : null;
            $apellidos = $partes ? implode(' ', $partes) : null;

            // Thumbprint del cert (SHA256 del DER)
            $exportedPem = null;
            openssl_x509_export($certRes, $exportedPem);
            // Convertir PEM->DER
            $der = null;
            if ($exportedPem) {
                $pemStr = preg_replace('/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s+/', '', $exportedPem);
                $der = base64_decode($pemStr, true);
            }
            $thumb = $der ? strtoupper(hash('sha256', $der)) : null;

            \App\Models\MaterialFirma::create([
                'movimiento_id' => $mov->id,
                'tipo_firmante' => $tipoFirmante,
                'tipo_firma' => 'certificado',
                'nombre' => $nombre,
                'apellidos' => $apellidos,
                'dni' => $dni,
                'cert_subject' => json_encode($subject),
                'cert_issuer' => json_encode($issuer),
                'cert_serial' => $parsed['serialNumberHex'] ?? ($parsed['serialNumber'] ?? null),
                'cert_thumbprint' => $thumb,
                'raw_cert_pem' => $certPem,
                'algoritmo' => 'RSA-SHA256',
                'challenge_hash' => $challengeB64,
                'ip_address' => $request->ip(),
                'fecha_firma' => now(),
            ]);

            $mov->refresh();
            $estadoAnterior = $mov->estado;
            $mov->estado = $mov->tieneFirmasCompletas() ? 'firmado' : 'pendiente_firma';
            $mov->save();

            // Registrar en historial de auditoría
            \App\Models\MaterialMovimientoHistorial::registrarCambio(
                $mov->id,
                'firmado_' . $tipoFirmante . '_certificado',
                "Firma con certificado digital de {$tipoFirmante} por {$nombre} {$apellidos} (DNI: {$dni})",
                ['estado' => $estadoAnterior],
                [
                    'estado' => $mov->estado,
                    'tipo_firmante' => $tipoFirmante,
                    'tipo_firma' => 'certificado',
                    'nombre' => $nombre,
                    'apellidos' => $apellidos,
                    'dni' => $dni,
                ],
                auth()->id()
            );

            return response()->json(['success'=>true,'message'=>'Documento firmado con certificado']);
        } catch (\Exception $e) {
            Log::error('Error firma certificado', ['e'=>$e->getMessage()]);
            return response()->json(['success'=>false,'message'=>'Error al firmar con certificado'],500);
        }
    }

    /**
     * Solicitar firma remota en dispositivo móvil
     */
    public function solicitarFirmaRemota(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'tipo_firma' => 'required|in:receptor,emisor',
                'session_id' => 'required|string',
            ]);

            $movimiento = MaterialMovimiento::with(['detalles.entidad'])->findOrFail($id);

            // Verificar que la sesión existe
            $session = Cache::get("firma_movil_session:{$validated['session_id']}");
            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión de firma móvil no encontrada. Asegúrate de que la página de firma está abierta.',
                ], 404);
            }

            // Verificar que no esté ya firmado
            $tipoFirma = $validated['tipo_firma'];
            if ($tipoFirma === 'receptor' && $movimiento->firmaReceptor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este movimiento ya tiene firma del receptor',
                ], 400);
            }
            if ($tipoFirma === 'emisor' && $movimiento->firmaEmisor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este movimiento ya tiene firma del emisor',
                ], 400);
            }

            // Preparar datos del movimiento para enviar al móvil
            $movimientoData = [
                'id' => $movimiento->id,
                'tipo' => $movimiento->tipo,
                'numero_albaran' => $movimiento->numero_documento,
                'fecha' => $movimiento->fecha_movimiento,
                'origen' => $movimiento->origen,
                'destino' => $movimiento->destino,
                'observaciones' => $movimiento->observaciones,
                'materiales' => $movimiento->detalles->map(function($detalle) {
                    return [
                        'codigo' => $detalle->entidad->codigo ?? '',
                        'nombre' => $detalle->entidad->nombre ?? '',
                        'cantidad' => $detalle->cantidad,
                    ];
                }),
            ];

            // Guardar solicitud en caché para que el SSE la envíe
            Cache::put("firma_movil_solicitud:{$validated['session_id']}", [
                'movimiento' => $movimientoData,
                'tipo_firma' => $tipoFirma,
                'timestamp' => now(),
            ], now()->addMinutes(10));

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de firma enviada al dispositivo móvil',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al solicitar firma remota', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al solicitar firma: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recibir firma desde dispositivo móvil (almacenar temporalmente)
     */
    public function firmarRemoto(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'tipo_firma' => 'required|in:receptor,emisor',
                'firma' => 'required|string', // Base64 del canvas
                'session_id' => 'required|string',
            ]);

            $movimiento = MaterialMovimiento::findOrFail($id);
            $tipoFirma = $validated['tipo_firma'];

            // Verificar que no esté ya firmado
            if ($tipoFirma === 'receptor' && $movimiento->firmaReceptor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este movimiento ya tiene firma del receptor',
                ], 400);
            }
            if ($tipoFirma === 'emisor' && $movimiento->firmaEmisor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este movimiento ya tiene firma del emisor',
                ], 400);
            }

            // Guardar firma temporalmente en caché (pendiente de confirmación)
            $cacheKey = "firma_remota_temp:{$validated['session_id']}:{$tipoFirma}:{$id}";
            Cache::put($cacheKey, [
                'firma_base64' => $validated['firma'],
                'movimiento_id' => $id,
                'tipo_firma' => $tipoFirma,
                'session_id' => $validated['session_id'],
                'timestamp' => now(),
            ], now()->addMinutes(10));

            return response()->json([
                'success' => true,
                'message' => 'Firma recibida y en espera de confirmación',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al recibir firma remota', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar firma: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verificar si hay firma pendiente de confirmación
     */
    public function verificarFirmaPendiente(Request $request, $id)
    {
        try {
            $sessionId = $request->query('session_id');
            $tipoFirma = $request->query('tipo_firma');

            if (!$sessionId || !$tipoFirma) {
                return response()->json([
                    'firma_recibida' => false,
                ]);
            }

            $cacheKey = "firma_remota_temp:{$sessionId}:{$tipoFirma}:{$id}";
            $firmaTemp = Cache::get($cacheKey);

            if ($firmaTemp) {
                return response()->json([
                    'firma_recibida' => true,
                    'firma_base64' => $firmaTemp['firma_base64'],
                ]);
            }

            return response()->json([
                'firma_recibida' => false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'firma_recibida' => false,
            ]);
        }
    }

    /**
     * Confirmar y guardar firma remota definitivamente
     */
    public function confirmarFirmaRemota(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'session_id' => 'required|string',
                'tipo_firma' => 'required|in:receptor,emisor',
                'datos_receptor' => 'sometimes|array',
                'datos_receptor.nombre' => 'sometimes|nullable|string',
                'datos_receptor.email' => 'sometimes|nullable|email',
                'datos_receptor.telefono' => 'sometimes|nullable|string',
                'datos_receptor.dni' => 'sometimes|nullable|string',
            ]);

            $movimiento = MaterialMovimiento::findOrFail($id);
            $tipoFirma = $validated['tipo_firma'];

            // Recuperar firma temporal
            $cacheKey = "firma_remota_temp:{$validated['session_id']}:{$tipoFirma}:{$id}";
            $firmaTemp = Cache::get($cacheKey);

            if (!$firmaTemp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Firma temporal no encontrada o expirada',
                ], 404);
            }

            // Obtener datos del receptor
            $datosReceptor = $validated['datos_receptor'] ?? [];
            $nombre = $datosReceptor['nombre'] ?? 'Receptor';
            $email = $datosReceptor['email'] ?? '';
            $telefono = $datosReceptor['telefono'] ?? '';
            $dni = $datosReceptor['dni'] ?? '';
            
            // Separar nombre y apellidos
            $nombreCompleto = explode(' ', $nombre, 2);
            $nombreFirma = $nombreCompleto[0] ?? $nombre;
            $apellidosFirma = $nombreCompleto[1] ?? '';

            // Guardar firma en storage/app/firmas
            $firmaBase64 = $firmaTemp['firma_base64'];
            $firmaData = explode(',', $firmaBase64);
            $firmaDecoded = base64_decode($firmaData[1] ?? $firmaData[0]);
            
            $filename = "firma_{$tipoFirma}_{$movimiento->id}_" . time() . '.png';
            $path = storage_path("app/firmas/{$filename}");
            
            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }
            
            file_put_contents($path, $firmaDecoded);

            // Crear registro de firma con datos del receptor
            MaterialFirma::create([
                'movimiento_id' => $movimiento->id,
                'tipo_firmante' => $tipoFirma,
                'tipo_firma' => 'rubrica',
                'nombre' => $nombreFirma,
                'apellidos' => $apellidosFirma,
                'dni' => $dni,
                'email' => $email,
                'telefono' => $telefono,
                'firma_rubrica' => $firmaBase64,
                'ip_address' => $request->ip(),
                'fecha_firma' => now(),
            ]);

            // Actualizar estado del movimiento
            $movimiento->refresh();
            $estadoAnterior = $movimiento->estado;
            $movimiento->estado = $movimiento->tieneFirmasCompletas() ? 'firmado' : 'pendiente_firma';
            $movimiento->save();

            // Registrar en historial de auditoría
            \App\Models\MaterialMovimientoHistorial::registrarCambio(
                $movimiento->id,
                'firmado_' . $tipoFirma . '_remoto',
                "Firma remota de {$tipoFirma} por {$nombreFirma} {$apellidosFirma}",
                ['estado' => $estadoAnterior],
                [
                    'estado' => $movimiento->estado,
                    'tipo_firmante' => $tipoFirma,
                    'tipo_firma' => 'rubrica_remota',
                    'nombre' => $nombreFirma,
                    'apellidos' => $apellidosFirma,
                    'email' => $email,
                ],
                auth()->id()
            );

            // Eliminar firma temporal
            Cache::forget($cacheKey);

            return response()->json([
                'success' => true,
                'message' => 'Firma confirmada y guardada correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al confirmar firma remota', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar firma: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marcar movimiento como entregado
     */
    public function marcarEntregado(Request $request, $id)
    {
        try {
            $movimiento = MaterialMovimiento::with(['detalles.entidad', 'firmas'])->findOrFail($id);
            
            // Verificar que el movimiento esté firmado
            if ($movimiento->estado !== 'firmado') {
                return response()->json([
                    'success' => false,
                    'message' => 'El movimiento debe estar firmado antes de marcarlo como entregado',
                ], 400);
            }

            // Verificar que no esté ya entregado
            if ($movimiento->fecha_entrega) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este movimiento ya ha sido marcado como entregado',
                ], 400);
            }

            // Marcar como entregado
            $movimiento->fecha_entrega = now();
            $movimiento->entregado_por = $request->user()->id;
            $movimiento->estado = 'entregado';
            $movimiento->save();

            // Registrar en historial de auditoría
            \App\Models\MaterialMovimientoHistorial::registrarCambio(
                $movimiento->id,
                'entregado',
                "Movimiento marcado como entregado por " . $request->user()->nombre,
                ['estado' => 'firmado'],
                [
                    'estado' => 'entregado',
                    'fecha_entrega' => now()->format('d/m/Y H:i:s'),
                    'entregado_por' => $request->user()->nombre
                ]
            );

            // Enviar notificación por email de entrega
            $movimiento->load(['usuario', 'detalles.entidad', 'usuarioEntrega']);
            $this->notificationService->notificarMovimientoEntregado($movimiento);

            // Cargar relación del usuario que marcó como entregado
            $movimiento->load('usuarioEntrega');

            return response()->json([
                'success' => true,
                'message' => 'Movimiento marcado como entregado correctamente',
                'movimiento' => $movimiento,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al marcar como entregado', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar como entregado: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener historial de auditoría de un movimiento
     */
    public function obtenerHistorialAuditoria($id)
    {
        $movimiento = MaterialMovimiento::findOrFail($id);
        
        $historial = $movimiento->historial()
            ->with('usuario:id,nombre,email')
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(function($entrada) {
                return [
                    'id' => $entrada->id,
                    'accion' => $entrada->accion,
                    'descripcion' => $entrada->descripcion,
                    'usuario' => $entrada->usuario ? [
                        'nombre' => $entrada->usuario->nombre,
                        'email' => $entrada->usuario->email
                    ] : null,
                    'fecha' => $entrada->fecha->format('d/m/Y H:i:s'),
                    'fecha_relativa' => $entrada->fecha->diffForHumans(),
                    'datos_antes' => $entrada->datos_antes,
                    'datos_despues' => $entrada->datos_despues,
                    'ip_address' => $entrada->ip_address
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $historial
        ]);
    }

    /**
     * Descargar PDF sin firmar (para firmar con Autofirma)
     * Accesible desde enlace público
     */
    public function descargarPDFSinFirmar($token)
    {
        try {
            $movimiento = MaterialMovimiento::where('enlace_publico', $token)
                ->with(['detalles.entidad', 'usuario', 'origenSede', 'destinoSede', 'firmas'])
                ->first();

            if (!$movimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Enlace no válido',
                ], 404);
            }

            if (!$movimiento->enlaceEsValido()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El enlace ha expirado o no es válido',
                ], 403);
            }

            // Obtener las firmas
            $firmaEmisor = $movimiento->firmas->where('tipo_firmante', 'emisor')->first();
            $firmaReceptor = $movimiento->firmas->where('tipo_firmante', 'receptor')->first();

            $pdf = Pdf::loadView('pdf.albaran-sin-firmar', [
                'movimiento' => $movimiento,
                'detalles' => $movimiento->detalles,
                'firmaEmisor' => $firmaEmisor,
                'firmaReceptor' => $firmaReceptor,
            ]);

            $filename = "documento_{$movimiento->numero_documento}_sin_firmar.pdf";

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Error al descargar PDF sin firmar', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el PDF',
            ], 500);
        }
    }

    /**
     * Subir PDF firmado con Autofirma
     * Accesible desde enlace público
     */
    public function subirPDFFirmado(Request $request, $token)
    {
        try {
            $validated = $request->validate([
                'pdf_firmado' => 'required|file|mimes:pdf|max:10240', // 10MB
                'tipo_firmante' => 'required|in:emisor,receptor',
                'nombre' => 'required|string|max:100',
                'apellidos' => 'required|string|max:100',
                'dni' => 'nullable|string|max:20',
            ]);

            $movimiento = MaterialMovimiento::where('enlace_publico', $token)
                ->with(['firmas'])
                ->first();

            if (!$movimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Enlace no válido',
                ], 404);
            }

            if (!$movimiento->enlaceEsValido()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El enlace ha expirado o no es válido',
                ], 403);
            }

            $tipoFirmante = $validated['tipo_firmante'];

            // Verificar que no exista ya esta firma
            $firmaExistente = $movimiento->firmas()
                ->where('tipo_firmante', $tipoFirmante)
                ->first();

            if ($firmaExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una firma de ' . $tipoFirmante . ' para este documento',
                ], 400);
            }

            DB::beginTransaction();

            // Guardar el archivo PDF firmado
            $file = $request->file('pdf_firmado');
            $filename = sprintf(
                'firmas/%s_%s_%s.pdf',
                $movimiento->numero_documento,
                $tipoFirmante,
                time()
            );
            
            $path = $file->storeAs('public', $filename);

            // Extraer información del certificado del PDF (opcional)
            $certificadoInfo = $this->extraerInfoCertificado($file->getRealPath());

            // Crear registro de firma
            MaterialFirma::create([
                'movimiento_id' => $movimiento->id,
                'tipo_firmante' => $tipoFirmante,
                'nombre' => $validated['nombre'],
                'apellidos' => $validated['apellidos'],
                'dni' => $validated['dni'] ?? null,
                'firma_rubrica' => null, // No hay imagen de rúbrica en este caso
                'pdf_firmado_path' => $filename,
                'metodo_firma' => 'certificado_digital',
                'certificado_info' => $certificadoInfo ? json_encode($certificadoInfo) : null,
                'ip_address' => $request->ip(),
                'fecha_firma' => now(),
            ]);

            // Actualizar estado del movimiento
            $movimiento->refresh();
            $estadoAnterior = $movimiento->estado;
            $movimiento->estado = $movimiento->tieneFirmasCompletas() ? 'firmado' : 'pendiente_firma';
            $movimiento->save();

            // Registrar en historial de auditoría
            \App\Models\MaterialMovimientoHistorial::registrarCambio(
                $movimiento->id,
                'firmado_' . $tipoFirmante,
                "Firma digital de {$tipoFirmante} subida por {$validated['nombre']} {$validated['apellidos']} (Certificado Digital)",
                ['estado' => $estadoAnterior],
                [
                    'estado' => $movimiento->estado,
                    'tipo_firmante' => $tipoFirmante,
                    'metodo' => 'certificado_digital',
                    'nombre_firmante' => $validated['nombre'] . ' ' . $validated['apellidos'],
                    'dni' => $validated['dni'] ?? null,
                    'certificado' => $certificadoInfo
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Documento firmado correctamente con certificado digital',
                'estado_movimiento' => $movimiento->estado
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al subir PDF firmado', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el PDF firmado: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Anular firma (solo para administradores)
     */
    public function anularFirma(Request $request, $movimientoId, $firmaId)
    {
        try {
            $movimiento = MaterialMovimiento::findOrFail($movimientoId);
            $firma = MaterialFirma::where('id', $firmaId)
                ->where('movimiento_id', $movimientoId)
                ->firstOrFail();

            DB::beginTransaction();

            $tipoFirmante = $firma->tipo_firmante;
            $nombreFirmante = $firma->nombre . ' ' . $firma->apellidos;

            // Eliminar archivo PDF si existe
            if ($firma->pdf_firmado_path) {
                Storage::disk('public')->delete($firma->pdf_firmado_path);
            }

            // Eliminar firma
            $firma->delete();

            // Actualizar estado del movimiento
            $estadoAnterior = $movimiento->estado;
            $movimiento->estado = $movimiento->tieneFirmasCompletas() ? 'firmado' : 'pendiente_firma';
            $movimiento->save();

            // Registrar en historial de auditoría
            \App\Models\MaterialMovimientoHistorial::registrarCambio(
                $movimiento->id,
                'firma_anulada',
                "Firma de {$tipoFirmante} anulada por " . auth()->user()->nombre . ". Firmante original: {$nombreFirmante}",
                ['estado' => $estadoAnterior],
                [
                    'estado' => $movimiento->estado,
                    'tipo_firmante_anulado' => $tipoFirmante,
                    'nombre_firmante_anulado' => $nombreFirmante,
                    'motivo' => $request->input('motivo', 'Firma incorrecta o inválida')
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Firma anulada correctamente',
                'estado_movimiento' => $movimiento->estado
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al anular firma', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al anular la firma: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extraer información del certificado del PDF firmado
     */
    private function extraerInfoCertificado($pdfPath)
    {
        try {
            // Intentar extraer información básica del PDF
            // Nota: Para extraer certificados completos se necesitaría una librería especializada
            $info = [
                'metodo' => 'certificado_digital',
                'fecha_extraccion' => now()->format('Y-m-d H:i:s'),
            ];

            // Aquí podrías integrar librerías como pdftk, o parsear con comandos del sistema
            // Por ahora devolvemos info básica
            return $info;

        } catch (\Exception $e) {
            Log::warning('No se pudo extraer info del certificado', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
