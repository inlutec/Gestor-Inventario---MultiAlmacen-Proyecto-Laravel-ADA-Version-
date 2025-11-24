<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Entidad;
use App\Models\Notificacion;
use App\Models\Usuario;
use App\Models\RegistroCambio;
use App\Services\PushNotificationService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialPeticionController extends Controller
{
    private $pushService;
    private $notificationService;

    public function __construct(PushNotificationService $pushService, NotificationService $notificationService)
    {
        $this->pushService = $pushService;
        $this->notificationService = $notificationService;
    }

    /**
     * Devuelve lista de materiales disponibles para peticiones públicas
     */
    public function materialesDisponibles(Request $request)
    {
        $usuarioId = $request->user() ? $request->user()->id : null;
        
        $materiales = Entidad::where('tipo_entidad_id', 3)
            ->where('visible_publico', true)
            ->select('id', 'referencia', 'datos', 'categoria_id', 'foto')
            ->get()
            ->map(function ($mat) use ($usuarioId) {
                $datos = is_array($mat->datos) ? $mat->datos : (json_decode($mat->datos, true) ?? []);
                
                // Calcular stock real desde material_movimiento_detalles
                $entradas = DB::table('material_movimiento_detalles as md')
                    ->join('material_movimientos as m', 'md.movimiento_id', '=', 'm.id')
                    ->where('md.entidad_id', $mat->id)
                    ->where('m.tipo', 'entrada')
                    ->whereIn('m.estado', ['firmado', 'entregado'])
                    ->sum('md.cantidad');
                
                $salidas = DB::table('material_movimiento_detalles as md')
                    ->join('material_movimientos as m', 'md.movimiento_id', '=', 'm.id')
                    ->where('md.entidad_id', $mat->id)
                    ->where('m.tipo', 'salida')
                    ->whereIn('m.estado', ['firmado', 'entregado'])
                    ->sum('md.cantidad');
                
                $stock_actual = $entradas - $salidas;
                
                // Verificar si el usuario tiene solicitud pendiente
                $tieneSolicitud = false;
                $previsionLlegada = null;
                
                if ($usuarioId) {
                    $solicitud = \App\Models\SolicitudReposicion::where('usuario_id', $usuarioId)
                        ->where('entidad_id', $mat->id)
                        ->where('estado', 'pendiente')
                        ->first();
                    
                    if ($solicitud) {
                        $tieneSolicitud = true;
                        $previsionLlegada = $solicitud->prevision_llegada;
                    }
                }
                
                // Si no tiene solicitud del usuario, buscar previsión general más próxima
                if (!$previsionLlegada) {
                    $solicitudGeneral = \App\Models\SolicitudReposicion::where('entidad_id', $mat->id)
                        ->where('estado', 'pendiente')
                        ->whereNotNull('prevision_llegada')
                        ->orderBy('prevision_llegada', 'asc')
                        ->first();
                    
                    if ($solicitudGeneral) {
                        $previsionLlegada = $solicitudGeneral->prevision_llegada;
                    }
                }
                
                return [
                    'id' => $mat->id,
                    'referencia' => $mat->referencia ?: ($datos['referencia'] ?? ''),
                    'nombre' => $datos['nombre'] ?? $datos['descripcion'] ?? 'Sin nombre',
                    'descripcion' => $datos['observaciones'] ?? $datos['descripcion'] ?? '',
                    'stock_actual' => $stock_actual,
                    'unidad' => $datos['unidad'] ?? 'ud',
                    'categoria_id' => $mat->categoria_id,
                    'foto' => $mat->foto,
                    'tiene_solicitud' => $tieneSolicitud,
                    'prevision_llegada' => $previsionLlegada ? $previsionLlegada->format('Y-m-d') : null,
                    'prevision_llegada_texto' => $previsionLlegada ? $previsionLlegada->format('F Y') : null,
                ];
            })
            ->filter(function ($mat) {
                // Solo mostrar materiales con stock > 0 o todos si no hay restricción
                return true; // Mostrar todos para que se vea en la lista
            })
            ->sortByDesc('stock_actual')
            ->values();

        return response()->json($materiales);
    }

    /**
     * Listado de peticiones (para el panel interno)
     */
    public function index(Request $request)
    {
        $query = Pedido::with(['detalles.entidad.departamento.sede.provincia', 'usuarioAprobador'])
            ->where('tipo', 'peticion');

        // Aplicar filtro por almacén si se especifica
        // Manejar múltiples parámetros que pueden enviar los componentes
        $almacenIds = $request->get('almacen_ids', []);
        $almacenSeleccionado = $request->get('almacen_seleccionado');
        $almacenId = $request->get('almacen_id');
        
        // Prioridad: almacen_ids > almacen_seleccionado > almacen_id
        if (empty($almacenIds) && $almacenSeleccionado) {
            $almacenIds = [$almacenSeleccionado];
        } elseif (empty($almacenIds) && $almacenId) {
            $almacenIds = [$almacenId];
        }
        
        if (!empty($almacenIds)) {
            $query->whereHas('detalles.entidad.departamento', function ($q) use ($almacenIds) {
                $q->whereIn('id', $almacenIds);
            });
        }

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->whereDate('fecha', '>=', $request->fecha_hasta);
        }

        $peticiones = $query->orderBy('fecha', 'desc')->get()->map(function ($pedido) {
            $primerDetalle = $pedido->detalles->first();
            return [
                'id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'tipo' => $pedido->tipo,
                'estado' => $pedido->estado,
                'usuario_solicitante' => $pedido->usuario_solicitante,
                'email_solicitante' => $pedido->email_solicitante,
                'telefono_solicitante' => $pedido->telefono_solicitante,
                'sede_id' => $pedido->sede_id,
                'departamento_id' => $pedido->departamento_id,
                'justificacion' => $pedido->observaciones,
                'observaciones_admin' => $pedido->notas,
                'comentarios_aprobacion' => $pedido->comentarios_aprobacion,
                'aprobacion_parcial' => $pedido->aprobacion_parcial,
                'cantidad_aprobada' => $primerDetalle ? $primerDetalle->cantidad_aprobada : null,
                'fecha_aprobacion' => $pedido->fecha_aprobacion,
                'aprobada_por' => $pedido->usuarioAprobador,
                'movimiento_id' => $pedido->movimiento_id,
                'created_at' => $pedido->created_at,
                'updated_at' => $pedido->updated_at,
                // Datos del primer detalle para compatibilidad
                'entidad_id' => $primerDetalle ? $primerDetalle->entidad_id : null,
                'entidad' => $primerDetalle ? $primerDetalle->entidad : null,
                'cantidad' => $primerDetalle ? $primerDetalle->cantidad : 0,
                'unidad' => $primerDetalle ? $primerDetalle->unidad : 'ud',
                // Todos los detalles (múltiples materiales) - manejar caso vacío
                'materiales' => $pedido->detalles->map(function ($detalle) {
                    if (!$detalle->entidad) {
                        return null;
                    }
                    $datos = is_array($detalle->entidad->datos) ? $detalle->entidad->datos : (json_decode($detalle->entidad->datos, true) ?? []);
                    return [
                        'detalle_id' => $detalle->id,
                        'entidad_id' => $detalle->entidad_id,
                        'referencia' => $detalle->entidad->referencia ?? ($datos['referencia'] ?? ''),
                        'nombre' => $datos['nombre'] ?? ($datos['descripcion'] ?? 'Sin nombre'),
                        'cantidad_solicitada' => $detalle->cantidad,
                        'cantidad_aprobada' => $detalle->cantidad_aprobada,
                        'unidad' => $detalle->unidad ?? 'ud',
                        'entidad' => $detalle->entidad
                    ];
                })->filter(function ($item) {
                    return $item !== null;
                })
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $peticiones
        ]);
    }

    /**
     * Crear nueva petición (desde formulario público)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'materiales' => 'required|array|min:1',
            'materiales.*.material_id' => 'required|exists:entidades,id',
            'materiales.*.cantidad' => 'required|numeric|min:1',
            'materiales.*.unidad' => 'nullable|string|max:20',
            'justificacion' => 'nullable|string|max:1000',
            'usuario_solicitante' => 'required|string|max:255',
            'email_solicitante' => 'required|email|max:255',
            'telefono_solicitante' => 'nullable|string|max:20',
            'sede_id' => 'nullable|exists:sedes,id',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'campos_personalizados' => 'nullable|array'
        ]);

        try {
            DB::beginTransaction();

            // Crear el pedido (petición)
            $pedido = Pedido::create([
                'tipo' => 'peticion',
                'estado' => 'pendiente',
                'fecha' => now(),
                'fecha_pedido' => now(),
                'numero_pedido' => 'PET-' . now()->format('YmdHis') . '-' . rand(100, 999),
                'usuario_solicitante' => $validated['usuario_solicitante'],
                'email_solicitante' => $validated['email_solicitante'],
                'telefono_solicitante' => $validated['telefono_solicitante'] ?? null,
                'sede_id' => $validated['sede_id'] ?? null,
                'departamento_id' => $validated['departamento_id'] ?? null,
                'observaciones' => $validated['justificacion'],
                'datos_personalizados' => $validated['campos_personalizados'] ?? null,
            ]);

            // Crear detalles del pedido (múltiples materiales)
            foreach ($validated['materiales'] as $material) {
                DetallePedido::create([
                    'pedido_id' => $pedido->id,
                    'entidad_id' => $material['material_id'],
                    'cantidad' => $material['cantidad'],
                    'unidad' => $material['unidad'] ?? 'ud'
                ]);
            }

            // Crear notificación para los administradores
            $cantidadMateriales = count($validated['materiales']);
            $textoMateriales = $cantidadMateriales === 1 ? '1 material' : "{$cantidadMateriales} materiales";
            
            Notificacion::create([
                'tipo' => 'peticion_nueva',
                'titulo' => 'Nueva petición de material',
                'mensaje' => "Nueva petición de {$validated['usuario_solicitante']} ({$textoMateriales})",
                'datos' => [
                    'pedido_id' => $pedido->id,
                    'usuario' => $validated['usuario_solicitante'],
                    'email' => $validated['email_solicitante'],
                    'cantidad_materiales' => $cantidadMateriales
                ],
                'leido' => false
            ]);

            // Enviar notificación push a administradores
            $this->pushService->sendToAdmins(
                '🔔 Nueva petición de material',
                "Petición de {$validated['usuario_solicitante']}: {$textoMateriales} - {$validated['justificacion']}",
                [
                    'tag' => 'peticion-' . $pedido->id,
                    'url' => '/gestionmaterial/peticiones',
                    'requireInteraction' => true
                ]
            );

            // Registrar en historial
            \App\Models\PedidoHistorial::registrarCambio(
                $pedido->id,
                'creado',
                "Petición creada desde formulario público por {$validated['usuario_solicitante']}",
                null,
                [
                    'numero_pedido' => $pedido->numero_pedido,
                    'cantidad_materiales' => $cantidadMateriales,
                    'justificacion' => $validated['justificacion'],
                ]
            );

            // Enviar notificación por email
            $pedidoConRelaciones = $pedido->load(['detalles.entidad', 'sede', 'departamento']);
            $this->notificationService->notificarPeticionCreada($pedidoConRelaciones);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Petición enviada correctamente',
                'data' => $pedido
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la petición: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aprobar una petición (total o parcialmente)
     */
    public function aprobar(Request $request, $id)
    {
        $validated = $request->validate([
            'origen' => 'nullable|string|max:255',
            'destino' => 'nullable|string|max:255',
            'observaciones_admin' => 'nullable|string',
            'materiales' => 'nullable|array',
            'materiales.*.detalle_id' => 'required|exists:detalles_pedido,id',
            'materiales.*.cantidad_aprobada' => 'required|numeric|min:0',
            'comentarios_aprobacion' => 'nullable|string',
        ]);

        $pedido = Pedido::with('detalles.entidad')->findOrFail($id);
        
        // Obtener datos anteriores para el log
        $datosAnteriores = $pedido->toArray();
        
        DB::beginTransaction();
        try {
            $esAprobacionParcial = false;
            $comentarios = [];

            // Si se enviaron materiales específicos con cantidades, actualizar cada uno
            if (isset($validated['materiales']) && count($validated['materiales']) > 0) {
                foreach ($validated['materiales'] as $material) {
                    $detalle = DetallePedido::findOrFail($material['detalle_id']);
                    $cantidadSolicitada = $detalle->cantidad;
                    $cantidadAprobada = $material['cantidad_aprobada'];
                    
                    if ($cantidadAprobada < $cantidadSolicitada) {
                        $esAprobacionParcial = true;
                        $comentarios[] = "{$detalle->entidad->referencia}: aprobado {$cantidadAprobada} de {$cantidadSolicitada}";
                    }
                    
                    // Actualizar cantidad en detalle
                    $detalle->update([
                        'cantidad' => $cantidadAprobada
                    ]);
                }
            }

            // Actualizar pedido
            $pedido->update([
                'estado' => 'aprobado',
                'usuario_aprobador_id' => auth()->id(),
                'fecha_aprobacion' => now(),
                'aprobacion_parcial' => $esAprobacionParcial,
                'comentarios_aprobacion' => $validated['comentarios_aprobacion'] ?? null,
                'notas' => $validated['observaciones_admin'] ?? $pedido->notas,
            ]);

            // Registrar cambio
            $comentarioLog = $esAprobacionParcial 
                ? "Aprobación parcial: " . implode(', ', $comentarios)
                : 'Aprobación total';
            
            if ($validated['comentarios_aprobacion']) {
                $comentarioLog .= ". Comentario: " . $validated['comentarios_aprobacion'];
            }

            RegistroCambio::registrar(
                Pedido::class,
                $pedido->id,
                $esAprobacionParcial ? 'aprobado_parcial' : 'aprobado',
                $datosAnteriores,
                $pedido->fresh()->toArray(),
                $comentarioLog
            );

            // Crear movimiento de material en el histórico
            $movimiento = \App\Models\MaterialMovimiento::create([
                'tipo' => 'salida',
                'justificante_id' => $pedido->id, // ← Asociar al pedido original
                'numero_documento' => 'PET-' . str_pad($pedido->id, 6, '0', STR_PAD_LEFT),
                'fecha_movimiento' => now(),
                'origen' => $validated['origen'] ?? 'Almacén General',
                'destino' => $validated['destino'] ?? $pedido->usuario_solicitante,
                'observaciones' => "Petición #{$pedido->numero_pedido}: " . ($pedido->observaciones ?? ''),
                'estado' => 'pendiente_firma',
                'usuario_id' => auth()->id(),
            ]);

            // Crear detalles del movimiento con los materiales aprobados
            foreach ($pedido->detalles as $detalle) {
                // Obtener unidad del detalle, o del material si no está definida
                $unidad = $detalle->unidad;
                if (!$unidad) {
                    $datos = is_array($detalle->entidad->datos) ? $detalle->entidad->datos : (json_decode($detalle->entidad->datos, true) ?? []);
                    $unidad = $datos['unidad'] ?? 'ud';
                }
                
                // Obtener descripción del material
                $datos = is_array($detalle->entidad->datos) ? $detalle->entidad->datos : (json_decode($detalle->entidad->datos, true) ?? []);
                $descripcion = $datos['nombre'] ?? $datos['descripcion'] ?? $detalle->entidad->referencia ?? 'Material';
                
                \App\Models\MaterialMovimientoDetalle::create([
                    'movimiento_id' => $movimiento->id,
                    'entidad_id' => $detalle->entidad_id,
                    'cantidad' => $detalle->cantidad, // Ya actualizada con la cantidad aprobada
                    'unidad' => $unidad,
                    'descripcion' => $descripcion,
                ]);
            }

            // Vincular el movimiento al pedido
            $pedido->update(['movimiento_id' => $movimiento->id]);

            // Registrar en historial de auditoría
            \App\Models\MaterialMovimientoHistorial::registrarCambio(
                $movimiento->id,
                'creado_desde_peticion',
                "Movimiento creado desde petición web #{$pedido->numero_pedido} aprobada por " . auth()->user()->nombre,
                null,
                [
                    'numero_documento' => $movimiento->numero_documento,
                    'tipo' => $movimiento->tipo,
                    'estado' => $movimiento->estado,
                    'origen' => $movimiento->origen,
                    'destino' => $movimiento->destino,
                    'peticion_id' => $pedido->id,
                    'numero_peticion' => $pedido->numero_pedido,
                ],
                auth()->id()
            );

            // Notificar al solicitante
            if ($pedido->email_solicitante) {
                $usuario = Usuario::where('email', $pedido->email_solicitante)->first();
                if ($usuario) {
                    $titulo = $esAprobacionParcial ? '⚠️ Petición aprobada parcialmente' : '✅ Petición aprobada';
                    $mensaje = $esAprobacionParcial 
                        ? "Algunos materiales fueron aprobados con cantidades diferentes" 
                        : "Tu petición ha sido aprobada completamente";
                    
                    if ($validated['comentarios_aprobacion']) {
                        $mensaje .= ". Comentario: " . $validated['comentarios_aprobacion'];
                    }
                    
                    $this->pushService->sendToUser(
                        $usuario->id,
                        $titulo,
                        $mensaje,
                        [
                            'tag' => 'peticion-aprobada-' . $pedido->id,
                            'url' => '/gestionmaterial/peticiones'
                        ]
                    );
                }
            }

            // Registrar en historial de auditoría
            $descripcionHistorial = $esAprobacionParcial 
                ? "Petición aprobada parcialmente por " . auth()->user()->nombre
                : "Petición aprobada por " . auth()->user()->nombre;
            
            if ($validated['comentarios_aprobacion']) {
                $descripcionHistorial .= ". Comentario: " . $validated['comentarios_aprobacion'];
            }

            \App\Models\PedidoHistorial::registrarCambio(
                $pedido->id,
                'aprobado',
                $descripcionHistorial,
                [
                    'estado' => $datosAnteriores['estado'],
                ],
                [
                    'estado' => 'aprobado',
                    'aprobacion_parcial' => $esAprobacionParcial,
                    'comentarios' => $validated['comentarios_aprobacion'] ?? null,
                    'cambios_materiales' => $comentarios
                ]
            );

            // Enviar notificación por email de aprobación
            $pedidoConRelaciones = $pedido->load(['detalles.entidad', 'usuarioCreador', 'sede', 'departamento']);
            $this->notificationService->notificarPeticionAprobada(
                $pedidoConRelaciones, 
                auth()->user()->nombre ?? 'Administrador'
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $esAprobacionParcial ? 'Petición aprobada parcialmente' : 'Petición aprobada',
                'aprobacion_parcial' => $esAprobacionParcial
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Denegar una petición
     */
    public function denegar(Request $request, $id)
    {
        $validated = $request->validate([
            'observaciones_admin' => 'required|string|max:500'
        ]);

        $pedido = Pedido::findOrFail($id);
        
        $datosAnteriores = $pedido->toArray();
        
        DB::beginTransaction();
        try {
            $pedido->update([
                'estado' => 'denegado',
                'usuario_aprobador_id' => auth()->id(),
                'fecha_aprobacion' => now(),
                'notas' => $validated['observaciones_admin']
            ]);

            // Registrar cambio
            RegistroCambio::registrar(
                Pedido::class,
                $pedido->id,
                'denegado',
                $datosAnteriores,
                $pedido->fresh()->toArray(),
                "Petición denegada. Motivo: {$validated['observaciones_admin']}"
            );

            // Registrar en historial de auditoría
            \App\Models\PedidoHistorial::registrarCambio(
                $pedido->id,
                'rechazado',
                "Petición rechazada por " . auth()->user()->nombre . ". Motivo: {$validated['observaciones_admin']}",
                [
                    'estado' => $datosAnteriores['estado'],
                ],
                [
                    'estado' => 'denegado',
                    'motivo_rechazo' => $validated['observaciones_admin']
                ]
            );

            // Si tiene email de solicitante, notificar
            if ($pedido->email_solicitante) {
                $usuario = Usuario::where('email', $pedido->email_solicitante)->first();
                if ($usuario) {
                    $this->pushService->sendToUser(
                        $usuario->id,
                        '❌ Petición denegada',
                        "Tu petición ha sido denegada. Motivo: {$validated['observaciones_admin']}",
                        [
                            'tag' => 'peticion-denegada-' . $pedido->id,
                            'url' => '/gestionmaterial/peticiones'
                        ]
                    );
                }
            }

            // Enviar notificación por email de denegación
            $pedidoConRelaciones = $pedido->load(['detalles.entidad', 'usuarioCreador', 'sede', 'departamento']);
            $this->notificationService->notificarPeticionDenegada(
                $pedidoConRelaciones,
                auth()->user()->nombre ?? 'Administrador',
                $validated['observaciones_admin']
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Petición denegada'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al denegar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una petición
     */
    public function destroy($id)
    {
        $pedido = Pedido::findOrFail($id);
        
        RegistroCambio::registrar(
            Pedido::class,
            $pedido->id,
            'eliminado',
            $pedido->toArray(),
            null,
            'Petición eliminada'
        );
        
        $pedido->delete();

        return response()->json([
            'success' => true,
            'message' => 'Petición eliminada'
        ]);
    }

    /**
     * Obtener historial de cambios de una petición
     */
    public function historial($id)
    {
        $cambios = RegistroCambio::where(function($query) {
                $query->where('tipo_entidad', Pedido::class)
                      ->orWhere('tipo_entidad', 'pedido');
            })
            ->where('entidad_id', $id)
            ->with('usuario')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $cambios
        ]);
    }

    /**
     * Obtener historial de auditoría de una petición
     */
    public function obtenerHistorialAuditoria($id)
    {
        $pedido = Pedido::findOrFail($id);
        
        $historial = $pedido->historial()
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
}
