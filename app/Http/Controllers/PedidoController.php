<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Entidad;
use App\Models\RegistroCambio;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PedidoController extends Controller
{
    public function index(Request $request)
    {
        $query = Pedido::with(['detalles.entidad.tipoEntidad', 'impresora', 'usuarioCreador']);

        // Filtro por estado
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        $pedidos = $query->orderBy('fecha_pedido', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $pedidos,
        ]);
    }

    public function show($id)
    {
        $pedido = Pedido::with(['detalles.entidad.tipoEntidad', 'impresora', 'usuarioCreador'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $pedido,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numero_pedido' => 'required|string|unique:pedidos,numero_pedido',
            'fecha_pedido' => 'required|date',
            'detalles' => 'required|array|min:1',
            'detalles.*.entidad_id' => 'required|exists:entidades,id',
            'detalles.*.cantidad' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $pedido = Pedido::create([
                'numero_pedido' => $request->numero_pedido,
                'fecha_pedido' => $request->fecha_pedido,
                'estado' => 'pendiente',
                'notas' => $request->notas,
                'datos' => $request->datos,
                'impresora_id' => $request->impresora_id,
                'usuario_creador_id' => $request->user()->id,
                'usuario_solicitante' => $request->usuario_solicitante,
                'email_solicitante' => $request->email_solicitante,
                'telefono_solicitante' => $request->telefono_solicitante,
            ]);

            foreach ($request->detalles as $detalle) {
                DetallePedido::create([
                    'pedido_id' => $pedido->id,
                    'entidad_id' => $detalle['entidad_id'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'] ?? null,
                ]);
            }

            // Registrar actividad
            RegistroCambio::create([
                'entidad_id' => $pedido->id,
                'tipo_entidad' => Pedido::class,
                'accion' => 'crear',
                'datos_anteriores' => null,
                'datos_nuevos' => $pedido->toArray(),
                'usuario_id' => $request->user()->id,
                'ip' => $request->ip(),
            ]);

            DB::commit();

            // Enviar notificaciones
            try {
                $notificationService = new NotificationService();
                $pedido->load(['detalles.entidad', 'impresora']);
                $notificationService->notificarPeticionCreada($pedido);
            } catch (\Exception $e) {
                \Log::error('Error enviando notificación de pedido creado: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado correctamente',
                'data' => $pedido->load(['detalles.entidad', 'impresora']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function receive(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'fecha_recepcion' => 'required|date',
            'albaran' => 'sometimes|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $pedido = Pedido::with('detalles.entidad')->findOrFail($id);

        if ($pedido->estado !== 'pendiente') {
            return response()->json([
                'success' => false,
                'message' => 'El pedido ya ha sido procesado',
            ], 400);
        }

        DB::beginTransaction();
        
        try {
            // Actualizar stock de consumibles
            foreach ($pedido->detalles as $detalle) {
                $entidad = $detalle->entidad;
                $datos = $entidad->datos;
                
                if (isset($datos['stock_actual'])) {
                    $datos['stock_actual'] = (int)$datos['stock_actual'] + $detalle->cantidad;
                    $entidad->update(['datos' => $datos]);

                    // Registrar actividad de consumo
                    RegistroCambio::create([
                        'entidad_id' => $entidad->id,
                        'tipo_entidad' => 'entidad',
                        'accion' => 'consumir',
                        'datos_anteriores' => ['stock' => (int)$entidad->datos['stock_actual'] - $detalle->cantidad],
                        'datos_nuevos' => ['stock' => $datos['stock_actual']],
                        'usuario_id' => $request->user()->id,
                        'ip' => $request->ip(),
                    ]);
                }
            }

            // Subir albarán si existe
            $albaranPath = null;
            if ($request->hasFile('albaran')) {
                $albaranPath = $request->file('albaran')->store('uploads/albaranes', 'public');
            }

            // Actualizar pedido
            $pedido->update([
                'estado' => 'recibido',
                'fecha_recepcion' => $request->fecha_recepcion,
                'albaran_foto' => $albaranPath,
            ]);

            // Registrar actividad
            RegistroCambio::create([
                'entidad_id' => $pedido->id,
                'tipo_entidad' => 'pedido',
                'accion' => 'modificar',
                'datos_anteriores' => ['estado' => 'pendiente'],
                'datos_nuevos' => ['estado' => 'recibido', 'albaran' => $albaranPath],
                'usuario_id' => $request->user()->id,
                'ip' => $request->ip(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido recibido correctamente',
                'data' => $pedido->load(['detalles.entidad', 'impresora']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al recibir el pedido: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);

        // Eliminar albarán si existe
        if ($pedido->albaran_foto) {
            Storage::disk('public')->delete($pedido->albaran_foto);
        }

        $pedido->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pedido eliminado correctamente',
        ]);
    }
}
