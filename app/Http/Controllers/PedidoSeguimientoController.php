<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use Illuminate\Support\Facades\Log;

class PedidoSeguimientoController extends Controller
{
    /**
     * Ver pedido público por token de seguimiento (sin autenticación)
     */
    public function ver($token)
    {
        try {
            $pedido = Pedido::with([
                'detalles.entidad',
                'sede',
                'departamento',
                'historial' => function($query) {
                    $query->where('visible_publico', true)
                          ->with('usuario:id,nombre,email')
                          ->orderBy('fecha', 'desc');
                }
            ])
            ->where('token_seguimiento', $token)
            ->firstOrFail();

            // Verificar que el token sea válido
            if (!$pedido->tokenSeguimientoEsValido()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este enlace ha expirado o no es válido',
                ], 410);
            }

            // Preparar datos del pedido
            $datosPedido = [
                'id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'estado' => $pedido->estado,
                'fecha' => $pedido->fecha,
                'fecha_pedido' => $pedido->fecha_pedido,
                'usuario_solicitante' => $pedido->usuario_solicitante,
                'email_solicitante' => $pedido->email_solicitante,
                'telefono_solicitante' => $pedido->telefono_solicitante,
                'observaciones' => $pedido->observaciones,
                'sede' => $pedido->sede ? [
                    'nombre' => $pedido->sede->nombre,
                ] : null,
                'departamento' => $pedido->departamento ? [
                    'nombre' => $pedido->departamento->nombre,
                ] : null,
                'materiales' => $pedido->detalles->map(function($detalle) {
                    if (!$detalle->entidad) {
                        return null;
                    }
                    $datos = is_array($detalle->entidad->datos) 
                        ? $detalle->entidad->datos 
                        : (json_decode($detalle->entidad->datos, true) ?? []);
                    return [
                        'referencia' => $detalle->entidad->referencia ?? ($datos['referencia'] ?? ''),
                        'nombre' => $datos['nombre'] ?? ($datos['descripcion'] ?? 'Sin nombre'),
                        'cantidad_solicitada' => $detalle->cantidad,
                        'cantidad_aprobada' => $detalle->cantidad_aprobada,
                        'unidad' => $detalle->unidad ?? 'ud',
                    ];
                })->filter(function($item) {
                    return $item !== null;
                }),
                'historial' => $pedido->historial->map(function($entrada) {
                    $datos = [
                        'id' => $entrada->id,
                        'accion' => $entrada->accion,
                        'descripcion' => $entrada->descripcion,
                        'fecha' => $entrada->fecha->format('d/m/Y H:i:s'),
                        'fecha_relativa' => $entrada->fecha->diffForHumans(),
                        'usuario' => $entrada->usuario ? [
                            'nombre' => $entrada->usuario->nombre,
                        ] : null,
                    ];
                    
                    // Incluir datos de cambios si existen
                    if ($entrada->datos_antes) {
                        $datos['datos_antes'] = $entrada->datos_antes;
                    }
                    if ($entrada->datos_despues) {
                        $datos['datos_despues'] = $entrada->datos_despues;
                    }
                    
                    // Procesar información específica según la acción
                    if ($entrada->accion === 'aprobado' || $entrada->accion === 'aprobado_parcial') {
                        if (isset($entrada->datos_despues['comentarios'])) {
                            $datos['comentarios_aprobacion'] = $entrada->datos_despues['comentarios'];
                        }
                        if (isset($entrada->datos_despues['aprobacion_parcial'])) {
                            $datos['aprobacion_parcial'] = $entrada->datos_despues['aprobacion_parcial'];
                        }
                        if (isset($entrada->datos_despues['cambios_materiales'])) {
                            $datos['cambios_materiales'] = $entrada->datos_despues['cambios_materiales'];
                        }
                    }
                    
                    if ($entrada->accion === 'comentario') {
                        // Los comentarios ya están en la descripción
                        $datos['es_comentario'] = true;
                    }
                    
                    return $datos;
                }),
                'fecha_aprobacion' => $pedido->fecha_aprobacion,
                'comentarios_aprobacion' => $pedido->comentarios_aprobacion,
                'aprobacion_parcial' => $pedido->aprobacion_parcial,
            ];

            return response()->json([
                'success' => true,
                'data' => $datosPedido,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error al ver pedido público', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el pedido',
            ], 500);
        }
    }
}
