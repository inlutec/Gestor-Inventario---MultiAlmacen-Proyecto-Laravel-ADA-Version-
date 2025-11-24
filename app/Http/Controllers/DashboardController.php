<?php

namespace App\Http\Controllers;

use App\Models\MaterialMovimiento;
use App\Models\MaterialMovimientoDetalle;
use App\Models\Pedido;
use App\Models\SolicitudReposicion;
use App\Models\Entidad;
use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Obtener datos del dashboard
     */
    public function index(Request $request)
    {
        try {
            // Obtener IDs de almacenes para filtrar
            $almacenIds = $request->get('almacen_ids', []);
            $almacenSeleccionado = $request->get('almacen_seleccionado');
            
            if (empty($almacenIds) && $almacenSeleccionado) {
                $almacenIds = [$almacenSeleccionado];
            }
            
            // También aceptar almacen_id para compatibilidad con el nuevo frontend
            if ($request->has('almacen_id') && $request->get('almacen_id')) {
                $almacenIds = [$request->get('almacen_id')];
            }

            // 1. Estadísticas generales
            $stats = $this->getEstadisticasGenerales($almacenIds);

            // 2. Pedidos pendientes de firma
            $pedidosPendientesFirma = $this->getPedidosPendientesFirma($almacenIds);

            // 3. Pedidos pendientes de entrega
            $pedidosPendientesEntrega = $this->getPedidosPendientesEntrega($almacenIds);

            // 4. Peticiones pendientes de aprobación
            $peticionesPendientesAprobacion = $this->getPeticionesPendientesAprobacion($almacenIds);

            // 5. Solicitudes de reposición pendientes
            $solicitudesReposicionPendientes = $this->getSolicitudesReposicionPendientes($almacenIds);

            // 6. Stock bajo o agotado
            $stockBajo = $this->getStockBajo($almacenIds);
            
            // 6.1. Stock crítico (sin stock)
            $stockCritico = $this->getStockCritico($almacenIds);

            // 7. Movimientos recientes
            $movimientosRecientes = $this->getMovimientosRecientes($almacenIds);
            
            // 7.1. Movimientos urgentes (pendientes más de 48h)
            $movimientosUrgentes = $this->getMovimientosUrgentes($almacenIds);

            // 8. Materiales más solicitados
            $materialesMasSolicitados = $this->getMaterialesMasSolicitados($almacenIds);
            
            // 8.1. Stock por categoría
            $stockPorCategoria = $this->getStockPorCategoria($almacenIds);

            // 9. Notificaciones no leídas
            $notificacionesNoLeidas = $this->getNotificacionesNoLeidas();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'pedidos_pendientes_firma' => $pedidosPendientesFirma,
                    'pedidos_pendientes_entrega' => $pedidosPendientesEntrega,
                    'peticiones_pendientes_aprobacion' => $peticionesPendientesAprobacion,
                    'solicitudes_reposicion_pendientes' => $solicitudesReposicionPendientes,
                    'stock_bajo' => $stockBajo,
                    'stock_critico' => $stockCritico,
                    'movimientos_recientes' => $movimientosRecientes,
                    'movimientos_urgentes' => $movimientosUrgentes,
                    'materiales_mas_solicitados' => $materialesMasSolicitados,
                    'stock_por_categoria' => $stockPorCategoria,
                    'notificaciones_no_leidas' => $notificacionesNoLeidas,
                    'pedidos_recientes' => $pedidosPendientesFirma->toArray(),
                    'actividad_reciente' => $this->getActividadReciente($almacenIds),
                    'impresoras_por_sede' => [], // Datos de ejemplo
                    'pedidos_por_mes' => [], // Datos de ejemplo
                    'top_consumibles' => [], // Datos de ejemplo
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del dashboard: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener estadísticas generales
     */
    private function getEstadisticasGenerales($almacenIds)
    {
        // Total de materiales
        $queryTotalMateriales = Entidad::where('tipo_entidad_id', 3);
        if (!empty($almacenIds)) {
            $queryTotalMateriales->porAlmacenes($almacenIds);
        }
        $totalMateriales = $queryTotalMateriales->count();

        // Total de entradas este mes
        $queryEntradasMes = MaterialMovimiento::where('tipo', 'entrada')
            ->whereMonth('fecha_movimiento', now()->month)
            ->whereYear('fecha_movimiento', now()->year)
            ->whereIn('estado', ['firmado', 'entregado']);
        
        if (!empty($almacenIds)) {
            $queryEntradasMes->whereHas('detalles.entidad', function($q) use ($almacenIds) {
                $q->whereIn('departamento_id', $almacenIds);
            });
        }
        $totalEntradasMes = $queryEntradasMes->count();

        // Total de salidas este mes
        $querySalidasMes = MaterialMovimiento::where('tipo', 'salida')
            ->whereMonth('fecha_movimiento', now()->month)
            ->whereYear('fecha_movimiento', now()->year)
            ->whereIn('estado', ['firmado', 'entregado']);
        
        if (!empty($almacenIds)) {
            $querySalidasMes->whereHas('detalles.entidad', function($q) use ($almacenIds) {
                $q->whereIn('departamento_id', $almacenIds);
            });
        }
        $totalSalidasMes = $querySalidasMes->count();

        // Total de peticiones este mes
        $queryPeticionesMes = Pedido::where('tipo', 'peticion')
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year);
        
        if (!empty($almacenIds)) {
            $queryPeticionesMes->whereHas('detalles.entidad.departamento', function($q) use ($almacenIds) {
                $q->whereIn('id', $almacenIds);
            });
        }
        $totalPeticionesMes = $queryPeticionesMes->count();

        // Total de solicitudes de reposición pendientes
        $querySolicitudesPendientes = SolicitudReposicion::where('estado', 'pendiente');
        if (!empty($almacenIds)) {
            $querySolicitudesPendientes->whereHas('material.departamento', function($q) use ($almacenIds) {
                $q->whereIn('id', $almacenIds);
            });
        }
        $totalSolicitudesPendientes = $querySolicitudesPendientes->count();

        // Agregar movimientos pendientes de firma a las estadísticas
        $queryMovimientosPendientes = MaterialMovimiento::whereIn('estado', ['pendiente', 'pendiente_firma']);
        if (!empty($almacenIds)) {
            $queryMovimientosPendientes->whereHas('detalles.entidad', function($q) use ($almacenIds) {
                $q->whereIn('departamento_id', $almacenIds);
            });
        }
        $movimientosPendientes = $queryMovimientosPendientes->get();

        return [
            'total_materiales' => $totalMateriales,
            'total_entradas_mes' => $totalEntradasMes,
            'total_salidas_mes' => $totalSalidasMes,
            'total_peticiones_mes' => $totalPeticionesMes,
            'total_solicitudes_pendientes' => $totalSolicitudesPendientes,
            'movimientos_pendientes_firma' => $movimientosPendientes->map(function($movimiento) {
                return [
                    'id' => $movimiento->id,
                    'numero_documento' => $movimiento->numero_documento,
                    'tipo' => $movimiento->tipo,
                    'estado' => $movimiento->estado,
                    'fecha_movimiento' => $movimiento->fecha_movimiento->format('Y-m-d H:i:s'),
                    'destino' => $movimiento->destino ?? '',
                ];
            }),
        ];
    }

    /**
     * Obtener pedidos pendientes de firma
     */
    private function getPedidosPendientesFirma($almacenIds)
    {
        $query = MaterialMovimiento::with(['detalles.entidad', 'usuario'])
            ->where('estado', 'pendiente_firma')
            ->orderBy('fecha_movimiento', 'desc')
            ->limit(10);

        if (!empty($almacenIds)) {
            $query->whereHas('detalles.entidad', function($q) use ($almacenIds) {
                $q->whereIn('departamento_id', $almacenIds);
            });
        }

        return $query->get()->map(function($movimiento) {
            return [
                'id' => $movimiento->id,
                'numero_documento' => $movimiento->numero_documento,
                'tipo' => $movimiento->tipo,
                'fecha_movimiento' => $movimiento->fecha_movimiento->format('Y-m-d H:i:s'),
                'usuario' => $movimiento->usuario ? $movimiento->usuario->nombre : 'N/A',
                'total_materiales' => $movimiento->detalles->count(),
                'materiales' => $movimiento->detalles->take(3)->map(function($detalle) {
                    $datos = $detalle->entidad->datos ?? [];
                    return [
                        'nombre' => $datos['nombre'] ?? $datos['descripcion'] ?? 'Sin nombre',
                        'cantidad' => $detalle->cantidad,
                        'unidad' => $detalle->unidad,
                    ];
                }),
            ];
        });
    }

    /**
     * Obtener pedidos pendientes de entrega
     */
    private function getPedidosPendientesEntrega($almacenIds)
    {
        $query = MaterialMovimiento::with(['detalles.entidad', 'usuario'])
            ->where('estado', 'firmado')
            ->whereNull('fecha_entrega')
            ->orderBy('fecha_movimiento', 'desc')
            ->limit(10);

        if (!empty($almacenIds)) {
            $query->whereHas('detalles.entidad', function($q) use ($almacenIds) {
                $q->whereIn('departamento_id', $almacenIds);
            });
        }

        return $query->get()->map(function($movimiento) {
            return [
                'id' => $movimiento->id,
                'numero_documento' => $movimiento->numero_documento,
                'tipo' => $movimiento->tipo,
                'fecha_movimiento' => $movimiento->fecha_movimiento->format('Y-m-d H:i:s'),
                'usuario' => $movimiento->usuario ? $movimiento->usuario->nombre : 'N/A',
                'total_materiales' => $movimiento->detalles->count(),
                'materiales' => $movimiento->detalles->take(3)->map(function($detalle) {
                    $datos = $detalle->entidad->datos ?? [];
                    return [
                        'nombre' => $datos['nombre'] ?? $datos['descripcion'] ?? 'Sin nombre',
                        'cantidad' => $detalle->cantidad,
                        'unidad' => $detalle->unidad,
                    ];
                }),
            ];
        });
    }

    /**
     * Obtener peticiones pendientes de aprobación
     */
    private function getPeticionesPendientesAprobacion($almacenIds)
    {
        $query = Pedido::with(['detalles.entidad', 'usuarioCreador'])
            ->where('tipo', 'peticion')
            ->where('estado', 'pendiente')
            ->orderBy('fecha', 'desc')
            ->limit(10);

        if (!empty($almacenIds)) {
            $query->whereHas('detalles.entidad.departamento', function($q) use ($almacenIds) {
                $q->whereIn('id', $almacenIds);
            });
        }

        return $query->get()->map(function($pedido) {
            return [
                'id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'fecha' => $pedido->fecha->format('Y-m-d H:i:s'),
                'usuario_solicitante' => $pedido->usuario_solicitante,
                'total_materiales' => $pedido->detalles->count(),
                'materiales' => $pedido->detalles->take(3)->map(function($detalle) {
                    $datos = $detalle->entidad->datos ?? [];
                    return [
                        'nombre' => $datos['nombre'] ?? $datos['descripcion'] ?? 'Sin nombre',
                        'cantidad' => $detalle->cantidad,
                        'unidad' => $detalle->unidad ?? 'ud',
                    ];
                }),
            ];
        });
    }

    /**
     * Obtener solicitudes de reposición pendientes
     */
    private function getSolicitudesReposicionPendientes($almacenIds)
    {
        $query = SolicitudReposicion::with(['usuario', 'material'])
            ->where('estado', 'pendiente')
            ->orderBy('fecha_solicitud', 'desc')
            ->limit(10);

        if (!empty($almacenIds)) {
            $query->whereHas('material.departamento', function($q) use ($almacenIds) {
                $q->whereIn('id', $almacenIds);
            });
        }

        return $query->get()->map(function($solicitud) {
            $datos = $solicitud->material->datos ?? [];
            return [
                'id' => $solicitud->id,
                'fecha_solicitud' => $solicitud->fecha_solicitud->format('Y-m-d H:i:s'),
                'usuario' => $solicitud->usuario->nombre,
                'material' => [
                    'nombre' => $datos['nombre'] ?? $datos['descripcion'] ?? 'Sin nombre',
                    'referencia' => $datos['referencia'] ?? 'Sin referencia',
                ],
                'cantidad_solicitada' => $solicitud->cantidad_solicitada,
                'prevision_llegada' => $solicitud->prevision_llegada ? $solicitud->prevision_llegada->format('Y-m-d') : null,
            ];
        });
    }

    /**
     * Obtener stock bajo o agotado
     */
    private function getStockBajo($almacenIds)
    {
        $query = Entidad::where('tipo_entidad_id', 3);
        
        if (!empty($almacenIds)) {
            $query->porAlmacenes($almacenIds);
        }

        $materiales = $query->get()->filter(function($material) {
            $datos = $material->datos ?? [];
            
            // Calcular stock actual
            $entradasQuery = MaterialMovimientoDetalle::whereHas('movimiento', function($q) {
                $q->where('tipo', 'entrada')
                  ->whereIn('estado', ['firmado', 'entregado']);
            })->where('entidad_id', $material->id);
            
            $salidasQuery = MaterialMovimientoDetalle::whereHas('movimiento', function($q) {
                $q->where('tipo', 'salida')
                  ->whereIn('estado', ['firmado', 'entregado']);
            })->where('entidad_id', $material->id);
            
            $entradas = $entradasQuery->sum('cantidad');
            $salidas = $salidasQuery->sum('cantidad');
            $stockActual = $entradas - $salidas;
            $stockMinimo = $datos['stock_minimo'] ?? 0;
            
            // Incluir si está agotado o por debajo del mínimo
            return $stockActual <= $stockMinimo;
        });

        return $materiales->map(function($material) {
            $datos = $material->datos ?? [];
            
            // Calcular stock actual
            $entradas = MaterialMovimientoDetalle::whereHas('movimiento', function($q) {
                $q->where('tipo', 'entrada')
                  ->whereIn('estado', ['firmado', 'entregado']);
            })->where('entidad_id', $material->id)->sum('cantidad');
            
            $salidas = MaterialMovimientoDetalle::whereHas('movimiento', function($q) {
                $q->where('tipo', 'salida')
                  ->whereIn('estado', ['firmado', 'entregado']);
            })->where('entidad_id', $material->id)->sum('cantidad');
            
            $stockActual = $entradas - $salidas;
            $stockMinimo = $datos['stock_minimo'] ?? 0;
            
            return [
                'id' => $material->id,
                'nombre' => $datos['nombre'] ?? $datos['descripcion'] ?? 'Sin nombre',
                'referencia' => $datos['referencia'] ?? 'Sin referencia',
                'stock_actual' => $stockActual,
                'stock_minimo' => $stockMinimo,
                'unidad' => $datos['unidad'] ?? 'ud',
                'estado_stock' => $stockActual <= 0 ? 'agotado' : ($stockActual <= $stockMinimo ? 'bajo' : 'normal'),
                'ubicacion' => $datos['ubicacion'] ?? '',
            ];
        })->sortBy('estado_stock')->values();
    }

    /**
     * Obtener movimientos recientes
     */
    private function getMovimientosRecientes($almacenIds)
    {
        $query = MaterialMovimiento::with(['detalles.entidad', 'usuario'])
            ->whereIn('estado', ['firmado', 'entregado'])
            ->orderBy('fecha_movimiento', 'desc')
            ->limit(10);

        if (!empty($almacenIds)) {
            $query->whereHas('detalles.entidad', function($q) use ($almacenIds) {
                $q->whereIn('departamento_id', $almacenIds);
            });
        }

        return $query->get()->map(function($movimiento) {
            return [
                'id' => $movimiento->id,
                'numero_documento' => $movimiento->numero_documento,
                'tipo' => $movimiento->tipo,
                'fecha_movimiento' => $movimiento->fecha_movimiento->format('Y-m-d H:i:s'),
                'usuario' => $movimiento->usuario ? $movimiento->usuario->nombre : 'N/A',
                'estado' => $movimiento->estado,
                'total_materiales' => $movimiento->detalles->count(),
            ];
        });
    }

    /**
     * Obtener materiales más solicitados
     */
    private function getMaterialesMasSolicitados($almacenIds)
    {
        $query = DB::table('detalles_pedido as dp')
            ->join('pedidos as p', 'dp.pedido_id', '=', 'p.id')
            ->join('entidades as e', 'dp.entidad_id', '=', 'e.id')
            ->select(
                'e.id',
                'e.referencia',
                'e.datos',
                DB::raw('COUNT(dp.id) as total_solicitudes'),
                DB::raw('SUM(dp.cantidad) as total_cantidad')
            )
            ->where('p.tipo', 'peticion')
            ->whereMonth('p.fecha', now()->month)
            ->whereYear('p.fecha', now()->year)
            ->groupBy('e.id', 'e.referencia', 'e.datos')
            ->orderBy('total_solicitudes', 'desc')
            ->limit(10);

        if (!empty($almacenIds)) {
            $query->join('departamentos as d', 'e.departamento', '=', 'd.nombre')
                  ->whereIn('d.id', $almacenIds);
        }

        $resultados = $query->get();

        return $resultados->map(function($item) {
            $datos = json_decode($item->datos, true) ?? [];
            $nombre = $datos['nombre'] ?? 'Sin nombre';
            $descripcion = $datos['descripcion'] ?? null;
            
            return [
                'id' => $item->id,
                'referencia' => $item->referencia ?? 'Sin referencia',
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'total_solicitudes' => $item->total_solicitudes,
                'total_cantidad' => $item->total_cantidad,
            ];
        });
    }

    /**
     * Obtener notificaciones no leídas
     */
    private function getNotificacionesNoLeidas()
    {
        if (!Auth::check()) {
            return [];
        }

        return Notificacion::where('leido', false)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($notificacion) {
                return [
                    'id' => $notificacion->id,
                    'tipo' => $notificacion->tipo,
                    'titulo' => $notificacion->titulo,
                    'mensaje' => $notificacion->mensaje,
                    'fecha' => $notificacion->created_at->format('Y-m-d H:i:s'),
                ];
            });
    }

    /**
     * Obtener stock crítico (materiales sin stock)
     */
    private function getStockCritico($almacenIds)
    {
        $query = Entidad::where('tipo_entidad_id', 3);
        
        if (!empty($almacenIds)) {
            $query->porAlmacenes($almacenIds);
        }

        $materiales = $query->get()->filter(function($material) {
            $datos = $material->datos ?? [];
            
            // Calcular stock actual
            $entradasQuery = MaterialMovimientoDetalle::whereHas('movimiento', function($q) {
                $q->where('tipo', 'entrada')
                  ->whereIn('estado', ['firmado', 'entregado']);
            })->where('entidad_id', $material->id);
            
            $salidasQuery = MaterialMovimientoDetalle::whereHas('movimiento', function($q) {
                $q->where('tipo', 'salida')
                  ->whereIn('estado', ['firmado', 'entregado']);
            })->where('entidad_id', $material->id);
            
            $entradas = $entradasQuery->sum('cantidad');
            $salidas = $salidasQuery->sum('cantidad');
            $stockActual = $entradas - $salidas;
            
            // Incluir solo si está agotado (stock <= 0)
            return $stockActual <= 0;
        });

        return $materiales->map(function($material) {
            $datos = $material->datos ?? [];
            
            // Calcular stock actual
            $entradas = MaterialMovimientoDetalle::whereHas('movimiento', function($q) {
                $q->where('tipo', 'entrada')
                  ->whereIn('estado', ['firmado', 'entregado']);
            })->where('entidad_id', $material->id)->sum('cantidad');
            
            $salidas = MaterialMovimientoDetalle::whereHas('movimiento', function($q) {
                $q->where('tipo', 'salida')
                  ->whereIn('estado', ['firmado', 'entregado']);
            })->where('entidad_id', $material->id)->sum('cantidad');
            
            $stockActual = $entradas - $salidas;
            $stockMinimo = $datos['stock_minimo'] ?? 0;
            
            return [
                'id' => $material->id,
                'nombre' => $datos['nombre'] ?? $datos['descripcion'] ?? 'Sin nombre',
                'referencia' => $datos['referencia'] ?? 'Sin referencia',
                'stock_actual' => $stockActual,
                'stock_minimo' => $stockMinimo,
                'unidad' => $datos['unidad'] ?? 'ud',
                'ubicacion' => $datos['ubicacion'] ?? '',
            ];
        })->values();
    }

    /**
     * Obtener movimientos urgentes (pendientes más de 48h)
     */
    private function getMovimientosUrgentes($almacenIds)
    {
        $hace48Horas = now()->subHours(48);
        
        $query = MaterialMovimiento::with(['detalles.entidad', 'usuario'])
            ->whereIn('estado', ['pendiente', 'pendiente_firma'])
            ->where('fecha_movimiento', '<', $hace48Horas)
            ->orderBy('fecha_movimiento', 'asc')
            ->limit(10);

        if (!empty($almacenIds)) {
            $query->whereHas('detalles.entidad', function($q) use ($almacenIds) {
                $q->whereIn('departamento_id', $almacenIds);
            });
        }

        return $query->get()->map(function($movimiento) {
            return [
                'id' => $movimiento->id,
                'numero_documento' => $movimiento->numero_documento,
                'tipo' => $movimiento->tipo,
                'estado' => $movimiento->estado,
                'fecha_movimiento' => $movimiento->fecha_movimiento->format('Y-m-d H:i:s'),
                'usuario' => $movimiento->usuario ? $movimiento->usuario->nombre : 'N/A',
                'horas_pendiente' => $movimiento->fecha_movimiento->diffInHours(now()),
                'total_materiales' => $movimiento->detalles->count(),
            ];
        });
    }

    /**
     * Obtener stock por categoría
     */
    private function getStockPorCategoria($almacenIds)
    {
        $query = Entidad::where('tipo_entidad_id', 3);
        
        if (!empty($almacenIds)) {
            $query->porAlmacenes($almacenIds);
        }

        $materiales = $query->get();
        $categorias = [];

        foreach ($materiales as $material) {
            $datos = $material->datos ?? [];
            $categoriaNombre = $datos['categoria'] ?? 'Sin categoría';
            
            // Calcular stock actual
            $entradas = MaterialMovimientoDetalle::whereHas('movimiento', function($q) {
                $q->where('tipo', 'entrada')
                  ->whereIn('estado', ['firmado', 'entregado']);
            })->where('entidad_id', $material->id)->sum('cantidad');
            
            $salidas = MaterialMovimientoDetalle::whereHas('movimiento', function($q) {
                $q->where('tipo', 'salida')
                  ->whereIn('estado', ['firmado', 'entregado']);
            })->where('entidad_id', $material->id)->sum('cantidad');
            
            $stockActual = $entradas - $salidas;
            $stockMinimo = $datos['stock_minimo'] ?? 0;

            if (!isset($categorias[$categoriaNombre])) {
                $categorias[$categoriaNombre] = [
                    'id' => count($categorias) + 1,
                    'nombre' => $categoriaNombre,
                    'total_materiales' => 0,
                    'stock_normal' => 0,
                    'stock_bajo' => 0,
                    'stock_critico' => 0,
                ];
            }

            $categorias[$categoriaNombre]['total_materiales']++;
            
            if ($stockActual <= 0) {
                $categorias[$categoriaNombre]['stock_critico']++;
            } elseif ($stockActual <= $stockMinimo) {
                $categorias[$categoriaNombre]['stock_bajo']++;
            } else {
                $categorias[$categoriaNombre]['stock_normal']++;
            }
        }

        return array_values($categorias);
    }

    /**
     * Obtener actividad reciente combinando movimientos, peticiones y otros eventos
     */
    private function getActividadReciente($almacenIds)
    {
        $actividades = collect();

        // 1. Movimientos recientes
        $movimientos = $this->getMovimientosRecientes($almacenIds);
        foreach ($movimientos as $movimiento) {
            $actividades->push([
                'id' => 'mov_' . $movimiento['id'],
                'tipo' => 'movimiento',
                'usuario' => [
                    'id' => null,
                    'nombre' => $movimiento['usuario'] ?? 'Sistema',
                ],
                'accion' => $movimiento['tipo'] === 'entrada' 
                    ? "registró una entrada de material ({$movimiento['numero_documento']})" 
                    : "registró una salida de material ({$movimiento['numero_documento']})",
                'created_at' => $movimiento['fecha_movimiento'],
            ]);
        }

        // 2. Peticiones recientes
        $peticiones = $this->getPeticionesPendientesAprobacion($almacenIds);
        foreach ($peticiones as $peticion) {
            $actividades->push([
                'id' => 'pet_' . $peticion['id'],
                'tipo' => 'peticion',
                'usuario' => [
                    'id' => null,
                    'nombre' => $peticion['usuario_solicitante'] ?? 'Usuario externo',
                ],
                'accion' => "solicitó material desde la web pública ({$peticion['numero_pedido']})",
                'created_at' => $peticion['fecha'],
            ]);
        }

        // 3. Solicitudes de reposición recientes
        $solicitudes = $this->getSolicitudesReposicionPendientes($almacenIds);
        foreach ($solicitudes as $solicitud) {
            $actividades->push([
                'id' => 'sol_' . $solicitud['id'],
                'tipo' => 'solicitud_reposicion',
                'usuario' => [
                    'id' => null,
                    'nombre' => $solicitud['usuario'] ?? 'Sistema',
                ],
                'accion' => "solicitó reposición de stock para {$solicitud['material']['nombre']}",
                'created_at' => $solicitud['fecha_solicitud'],
            ]);
        }

        // Ordenar por fecha descendente y limitar a 10
        return $actividades->sortByDesc('created_at')->take(10)->values()->toArray();
    }
}
