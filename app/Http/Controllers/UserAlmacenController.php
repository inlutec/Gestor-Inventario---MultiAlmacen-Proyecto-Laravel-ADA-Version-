<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Departamento;
use Illuminate\Http\Request;

class UserAlmacenController extends Controller
{
    /**
     * Obtener los almacenes asignados a un usuario
     */
    public function index($userId)
    {
        $user = Usuario::findOrFail($userId);
        $almacenes = $user->almacenes()->with('sede')->get();
        
        return response()->json([
            'success' => true,
            'data' => $almacenes
        ]);
    }
    
    /**
     * Asignar almacenes a un usuario
     */
    public function store(Request $request, $userId)
    {
        $user = Usuario::findOrFail($userId);
        
        $validated = $request->validate([
            'almacen_ids' => 'required|array',
            'almacen_ids.*' => 'exists:departamentos,id',
        ]);
        
        // Sincronizar los almacenes del usuario
        $user->almacenes()->sync($validated['almacen_ids']);
        
        return response()->json([
            'success' => true,
            'message' => 'Almacenes asignados correctamente',
            'data' => $user->almacenes()->with('sede')->get()
        ]);
    }
    
    /**
     * Obtener todos los almacenes disponibles
     */
    public function almacenesDisponibles()
    {
        $almacenes = Departamento::where('es_almacen', 1)
            ->with(['sede.provincia'])
            ->get()
            ->map(function($departamento) {
                return [
                    'id' => $departamento->id,
                    'nombre' => $departamento->nombre,
                    'clave' => $departamento->clave,
                    'es_almacen' => $departamento->es_almacen,
                    'sede_id' => $departamento->sede_id,
                    'sede' => $departamento->sede,
                    'provincia_id' => $departamento->sede ? $departamento->sede->provincia_id : null,
                    'provincia_nombre' => $departamento->sede && $departamento->sede->provincia ? $departamento->sede->provincia->nombre : null,
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $almacenes
        ]);
    }
    
    /**
     * Obtener usuarios con sus almacenes asignados
     */
    public function usuariosConAlmacenes()
    {
        $usuarios = Usuario::with('almacenes.sede')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $usuarios
        ]);
    }
    
    /**
     * Obtener los almacenes del usuario autenticado
     */
    public function misAlmacenes()
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }
        
        // Si es administrador, devolver todos los almacenes
        if ($user->isAdmin()) {
            $almacenes = Departamento::where('es_almacen', 1)
                ->with(['sede.provincia'])
                ->get()
                ->map(function($departamento) {
                    return [
                        'id' => $departamento->id,
                        'nombre' => $departamento->nombre,
                        'clave' => $departamento->clave,
                        'es_almacen' => $departamento->es_almacen,
                        'sede_id' => $departamento->sede_id,
                        'sede' => $departamento->sede,
                        'provincia_id' => $departamento->sede ? $departamento->sede->provincia_id : null,
                        'provincia_nombre' => $departamento->sede && $departamento->sede->provincia ? $departamento->sede->provincia->nombre : null,
                    ];
                });
        } else {
            // Si no es administrador, devolver solo sus almacenes asignados
            $almacenes = $user->almacenes()->with(['sede.provincia'])->get()
                ->map(function($departamento) {
                    return [
                        'id' => $departamento->id,
                        'nombre' => $departamento->nombre,
                        'clave' => $departamento->clave,
                        'es_almacen' => $departamento->es_almacen,
                        'sede_id' => $departamento->sede_id,
                        'sede' => $departamento->sede,
                        'provincia_id' => $departamento->sede ? $departamento->sede->provincia_id : null,
                        'provincia_nombre' => $departamento->sede && $departamento->sede->provincia ? $departamento->sede->provincia->nombre : null,
                    ];
                });
        }
        
        return response()->json([
            'success' => true,
            'data' => $almacenes
        ]);
    }
}