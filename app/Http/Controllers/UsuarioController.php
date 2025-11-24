<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = Usuario::with('almacenes')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $usuarios,
        ]);
    }

    public function show($id)
    {
        $usuario = Usuario::with('almacenes')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $usuario,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => 'required|email|unique:usuarios,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,gestor,usuario',
            'almacenes' => 'array',
            'almacenes.*' => 'exists:departamentos,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $usuario = Usuario::create([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol' => $request->role,
            'activo' => true,
        ]);

        // Asignar almacenes si no es admin
        if ($request->role !== 'admin' && !empty($request->almacenes)) {
            error_log('DEBUG: Asignando almacenes al usuario ' . $usuario->id . ': ' . json_encode($request->almacenes));
            $result = $usuario->almacenes()->attach($request->almacenes);
            error_log('DEBUG: Resultado de attach: ' . ($result ? 'true' : 'false'));
            error_log('DEBUG: Almacenes asignados correctamente');
        } else {
            error_log('DEBUG: No se asignaron almacenes. Role: ' . $request->role . ', Almacenes: ' . json_encode($request->almacenes));
        }

        // Cargar el usuario con sus almacenes para la respuesta
        $usuario->load('almacenes');

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado correctamente',
            'data' => $usuario,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|string|max:100',
            'apellido' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:usuarios,email,' . $id,
            'password' => 'sometimes|string|min:6',
            'role' => 'sometimes|in:admin,gestor,usuario',
            'activo' => 'sometimes|boolean',
            'almacenes' => 'array',
            'almacenes.*' => 'exists:departamentos,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only(['nombre', 'apellido', 'email', 'role', 'activo']);
        if (isset($data['role'])) {
            $data['rol'] = $data['role'];
            unset($data['role']);
        }
        
        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $usuario->update($data);

        // Actualizar almacenes si no es admin
        if (isset($request->role) && $request->role !== 'admin') {
            $almacenes = $request->almacenes ?? [];
            $usuario->almacenes()->sync($almacenes);
        } elseif (isset($request->role) && $request->role === 'admin') {
            // Si se cambia a admin, limpiar asignaciones de almacenes
            $usuario->almacenes()->detach();
        }

        // Cargar el usuario con sus almacenes para la respuesta
        $usuario->load('almacenes');

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente',
            'data' => $usuario,
        ]);
    }

    public function destroy($id)
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado correctamente',
        ]);
    }
}
