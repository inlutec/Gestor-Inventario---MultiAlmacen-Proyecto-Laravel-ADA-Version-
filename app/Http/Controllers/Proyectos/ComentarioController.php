<?php

namespace App\Http\Controllers\Proyectos;

use App\Http\Controllers\Controller;
use App\Models\Proyectos\Comentario;
use App\Models\Proyectos\Actividad;
use App\Models\Proyectos\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ComentarioController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'comentable_type' => 'required|string',
            'comentable_id' => 'required|integer',
            'contenido' => 'required|string',
            'comentario_padre_id' => 'nullable|exists:comentarios,id',
        ]);

        DB::connection('proyectos')->beginTransaction();
        
        try {
            $validated['usuario_id'] = Auth::id();
            
            $comentario = Comentario::create($validated);

            // Registrar actividad
            Actividad::create([
                'activable_type' => $validated['comentable_type'],
                'activable_id' => $validated['comentable_id'],
                'usuario_id' => Auth::id(),
                'accion' => 'commented',
                'descripcion' => 'Agregó un comentario',
            ]);

            // Notificar a los miembros del proyecto
            $entidad = $comentario->comentable;
            if (method_exists($entidad, 'proyecto')) {
                $proyecto = $entidad->proyecto ?? $entidad;
                
                foreach ($proyecto->miembros as $miembro) {
                    if ($miembro->id != Auth::id() && $miembro->pivot->notificaciones) {
                        Notificacion::create([
                            'usuario_id' => $miembro->id,
                            'tipo' => 'nuevo_comentario',
                            'notificable_type' => get_class($comentario->comentable),
                            'notificable_id' => $comentario->comentable_id,
                            'mensaje' => Auth::user()->nombre . ' agregó un comentario',
                        ]);
                    }
                }
            }

            DB::connection('proyectos')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Comentario agregado exitosamente.',
                'comentario' => $comentario->load('usuario'),
            ]);
                
        } catch (\Exception $e) {
            DB::connection('proyectos')->rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar el comentario: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $comentario = Comentario::findOrFail($id);

        if ($comentario->usuario_id != Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para editar este comentario.',
            ], 403);
        }

        $validated = $request->validate([
            'contenido' => 'required|string',
        ]);

        try {
            $comentario->contenido = $validated['contenido'];
            $comentario->marcarComoEditado();

            return response()->json([
                'success' => true,
                'message' => 'Comentario actualizado exitosamente.',
                'comentario' => $comentario,
            ]);
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el comentario: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $comentario = Comentario::findOrFail($id);

        if ($comentario->usuario_id != Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para eliminar este comentario.',
            ], 403);
        }

        try {
            $comentario->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comentario eliminado exitosamente.',
            ]);
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el comentario: ' . $e->getMessage(),
            ], 500);
        }
    }
}
