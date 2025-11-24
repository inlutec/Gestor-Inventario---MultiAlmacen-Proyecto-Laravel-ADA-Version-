<?php

namespace App\Http\Controllers\Proyectos;

use App\Http\Controllers\Controller;
use App\Models\Proyectos\Adjunto;
use App\Models\Proyectos\Actividad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdjuntoController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'adjuntable_type' => 'required|string',
            'adjuntable_id' => 'required|integer',
            'archivo' => 'required|file|max:51200', // 50MB
            'descripcion' => 'nullable|string',
        ]);

        try {
            $archivo = $request->file('archivo');
            
            $nombreOriginal = $archivo->getClientOriginalName();
            $extension = $archivo->getClientOriginalExtension();
            $nombreArchivo = Str::uuid() . '.' . $extension;
            
            // Guardar el archivo
            $ruta = $archivo->storeAs(
                'adjuntos/' . date('Y/m'),
                $nombreArchivo,
                'proyectos'
            );

            $adjunto = Adjunto::create([
                'adjuntable_type' => $validated['adjuntable_type'],
                'adjuntable_id' => $validated['adjuntable_id'],
                'nombre_original' => $nombreOriginal,
                'nombre_archivo' => $nombreArchivo,
                'ruta' => $ruta,
                'tipo_mime' => $archivo->getMimeType(),
                'tamano' => $archivo->getSize(),
                'subido_por' => Auth::id(),
                'descripcion' => $validated['descripcion'] ?? null,
            ]);

            // Registrar actividad
            Actividad::create([
                'activable_type' => $validated['adjuntable_type'],
                'activable_id' => $validated['adjuntable_id'],
                'usuario_id' => Auth::id(),
                'accion' => 'uploaded_file',
                'descripcion' => 'Subió el archivo: ' . $nombreOriginal,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Archivo subido exitosamente.',
                'adjunto' => $adjunto->load('usuario'),
            ]);
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir el archivo: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function descargar($id)
    {
        $adjunto = Adjunto::findOrFail($id);

        try {
            return Storage::disk('proyectos')->download(
                $adjunto->ruta,
                $adjunto->nombre_original
            );
        } catch (\Exception $e) {
            abort(404, 'Archivo no encontrado.');
        }
    }

    public function destroy($id)
    {
        $adjunto = Adjunto::findOrFail($id);

        if ($adjunto->subido_por != Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para eliminar este archivo.',
            ], 403);
        }

        try {
            // Eliminar el archivo del storage
            Storage::disk('proyectos')->delete($adjunto->ruta);
            
            $adjunto->delete();

            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado exitosamente.',
            ]);
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el archivo: ' . $e->getMessage(),
            ], 500);
        }
    }
}
