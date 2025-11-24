<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Entidad;

class ConsumibleController extends Controller
{
    /**
     * Listar todos los consumibles
     */
    public function index()
    {
        try {
            // Tipo de entidad 2 = Consumibles
            $consumibles = Entidad::where('tipo_entidad_id', 2)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($entidad) {
                    $datos = $entidad->datos ?? [];
                    return [
                        'id' => $entidad->id,
                        'referencia' => $datos['referencia'] ?? 'Sin referencia',
                        'tipo' => $datos['tipo'] ?? '',
                        'marca' => $datos['marca'] ?? '',
                        'modelo' => $datos['modelo'] ?? '',
                        'color' => $datos['color'] ?? null,
                        'ubicacion' => $datos['ubicacion'] ?? '',
                        'stock_actual' => (int)($datos['stock_actual'] ?? 0),
                        'stock_minimo' => (int)($datos['stock_minimo'] ?? 0),
                        'custom_fields' => $entidad->custom_fields ?? [],
                        'created_at' => $entidad->created_at,
                        'updated_at' => $entidad->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $consumibles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener consumibles: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear un nuevo consumible
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'referencia' => 'required|string|max:255',
                'tipo' => 'required|string',
                'marca' => 'required|string|max:255',
                'modelo' => 'required|string|max:255',
                'stock_actual' => 'required|integer|min:0',
                'stock_minimo' => 'required|integer|min:0',
            ]);

            $entidad = new Entidad();
            $entidad->tipo_entidad_id = 2; // Consumibles
            $entidad->datos = [
                'referencia' => $request->referencia,
                'tipo' => $request->tipo,
                'marca' => $request->marca,
                'modelo' => $request->modelo,
                'color' => $request->color,
                'ubicacion' => $request->ubicacion,
                'stock_actual' => $request->stock_actual,
                'stock_minimo' => $request->stock_minimo,
            ];
            $entidad->custom_fields = $request->custom_fields ?? [];
            $entidad->save();

            return response()->json([
                'success' => true,
                'message' => 'Consumible creado correctamente',
                'data' => $entidad,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear consumible: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener un consumible específico
     */
    public function show($id)
    {
        try {
            $entidad = Entidad::where('tipo_entidad_id', 2)
                ->where('id', $id)
                ->firstOrFail();

            $datos = $entidad->datos ?? [];

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $entidad->id,
                    'referencia' => $datos['referencia'] ?? '',
                    'tipo' => $datos['tipo'] ?? '',
                    'marca' => $datos['marca'] ?? '',
                    'modelo' => $datos['modelo'] ?? '',
                    'color' => $datos['color'] ?? null,
                    'ubicacion' => $datos['ubicacion'] ?? '',
                    'stock_actual' => (int)($datos['stock_actual'] ?? 0),
                    'stock_minimo' => (int)($datos['stock_minimo'] ?? 0),
                    'custom_fields' => $entidad->custom_fields ?? [],
                    'created_at' => $entidad->created_at,
                    'updated_at' => $entidad->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Consumible no encontrado',
            ], 404);
        }
    }

    /**
     * Actualizar un consumible
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'referencia' => 'required|string|max:255',
                'tipo' => 'required|string',
                'marca' => 'required|string|max:255',
                'modelo' => 'required|string|max:255',
                'stock_actual' => 'required|integer|min:0',
                'stock_minimo' => 'required|integer|min:0',
            ]);

            $entidad = Entidad::where('tipo_entidad_id', 2)
                ->where('id', $id)
                ->firstOrFail();

            $entidad->datos = [
                'referencia' => $request->referencia,
                'tipo' => $request->tipo,
                'marca' => $request->marca,
                'modelo' => $request->modelo,
                'color' => $request->color,
                'ubicacion' => $request->ubicacion,
                'stock_actual' => $request->stock_actual,
                'stock_minimo' => $request->stock_minimo,
            ];
            $entidad->custom_fields = $request->custom_fields ?? [];
            $entidad->save();

            return response()->json([
                'success' => true,
                'message' => 'Consumible actualizado correctamente',
                'data' => $entidad,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar consumible: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar un consumible
     */
    public function destroy($id)
    {
        try {
            $entidad = Entidad::where('tipo_entidad_id', 2)
                ->where('id', $id)
                ->firstOrFail();

            $entidad->delete();

            return response()->json([
                'success' => true,
                'message' => 'Consumible eliminado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar consumible: ' . $e->getMessage(),
            ], 500);
        }
    }
}
