<?php

namespace App\Http\Controllers;

use App\Models\Provincia;
use Illuminate\Http\Request;

class ProvinciaController extends Controller
{
    /**
     * Listar todas las provincias
     */
    public function index()
    {
        $provincias = Provincia::withCount('sedes')->orderBy('nombre')->get();
        
        return response()->json([
            'success' => true,
            'data' => $provincias
        ]);
    }
    
    /**
     * Crear una nueva provincia
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:provincias,nombre',
            'clave' => 'nullable|string|max:100|unique:provincias,clave',
            'activo' => 'boolean',
        ]);
        
        // Generar clave si no se proporciona
        if (!isset($validated['clave'])) {
            $validated['clave'] = $this->slugify($validated['nombre']);
        }
        
        // Por defecto activa
        $validated['activo'] = $validated['activo'] ?? true;
        
        $provincia = Provincia::create($validated);
        
        return response()->json([
            'success' => true,
            'data' => $provincia,
            'message' => 'Provincia creada correctamente'
        ], 201);
    }
    
    /**
     * Actualizar una provincia
     */
    public function update(Request $request, $id)
    {
        $provincia = Provincia::findOrFail($id);
        
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255|unique:provincias,nombre,' . $id,
            'clave' => 'sometimes|string|max:100|unique:provincias,clave,' . $id,
            'activo' => 'sometimes|boolean',
        ]);
        
        // Generar clave si se cambia el nombre y no se proporciona clave
        if (isset($validated['nombre']) && !isset($validated['clave'])) {
            $validated['clave'] = $this->slugify($validated['nombre']);
        }
        
        $provincia->update($validated);
        
        return response()->json([
            'success' => true,
            'data' => $provincia,
            'message' => 'Provincia actualizada correctamente'
        ]);
    }
    
    /**
     * Eliminar una provincia
     */
    public function destroy($id)
    {
        $provincia = Provincia::findOrFail($id);
        
        // Verificar si tiene sedes asignadas
        if ($provincia->sedes()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la provincia porque tiene sedes asignadas'
            ], 400);
        }
        
        $provincia->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Provincia eliminada correctamente'
        ]);
    }
    
    /**
     * Convertir texto a slug
     */
    private function slugify($text)
    {
        $t = strtolower(trim($text));
        $t = preg_replace('/[^a-z0-9]+/','-',$t);
        return trim($t,'-');
    }
}