<?php

namespace App\Http\Controllers\Proyectos;

use App\Http\Controllers\Controller;
use App\Models\Proyectos\Tarea;
use App\Models\Proyectos\Proyecto;
use App\Models\Proyectos\Actividad;
use App\Models\Proyectos\Notificacion;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TareaController extends Controller
{
    public function index(Request $request)
    {
        $usuario = Auth::user();
        
        $query = Tarea::query()
            ->with(['proyecto', 'asignado', 'etiquetas']);

        // Filtros
        if ($request->filled('proyecto_id')) {
            $query->where('proyecto_id', $request->proyecto_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('asignado_a')) {
            if ($request->asignado_a === 'sin_asignar') {
                $query->whereNull('asignado_a');
            } elseif ($request->asignado_a === 'mis_tareas') {
                $query->where('asignado_a', $usuario->id);
            } else {
                $query->where('asignado_a', $request->asignado_a);
            }
        }

        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->prioridad);
        }

        if ($request->filled('retrasadas')) {
            $query->retrasadas();
        }

        $tareas = $query->orderBy('fecha_vencimiento')->paginate(50);

        $proyectos = Proyecto::activos()->orderBy('nombre')->get();

        return view('proyectos.tareas.index', compact('tareas', 'proyectos'));
    }

    public function misTareas()
    {
        $usuario = Auth::user();

        $tareasPendientes = Tarea::with(['proyecto', 'etiquetas'])
            ->asignadasA($usuario->id)
            ->pendientes()
            ->orderBy('fecha_vencimiento')
            ->get();

        $tareasRetrasadas = Tarea::with(['proyecto', 'etiquetas'])
            ->asignadasA($usuario->id)
            ->retrasadas()
            ->get();

        return view('proyectos.tareas.mis-tareas', compact('tareasPendientes', 'tareasRetrasadas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'proyecto_id' => 'required|exists:proyectos,id',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'nullable|in:pendiente,en_progreso,revision,completada,bloqueada,cancelada',
            'prioridad' => 'nullable|in:baja,media,alta,critica',
            'fecha_inicio' => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date',
            'horas_estimadas' => 'nullable|numeric|min:0',
            'asignado_a' => 'nullable|exists:usuarios,id',
            'tarea_padre_id' => 'nullable|exists:tareas,id',
        ]);

        DB::connection('proyectos')->beginTransaction();
        
        try {
            $validated['creado_por'] = Auth::id();
            $validated['estado'] = $validated['estado'] ?? 'pendiente';
            $validated['prioridad'] = $validated['prioridad'] ?? 'media';
            
            $tarea = Tarea::create($validated);

            // Actualizar progreso del proyecto
            $tarea->proyecto->actualizarProgreso();

            // Notificar al asignado
            if ($request->asignado_a) {
                Notificacion::create([
                    'usuario_id' => $request->asignado_a,
                    'tipo' => 'tarea_asignada',
                    'notificable_type' => Tarea::class,
                    'notificable_id' => $tarea->id,
                    'mensaje' => 'Se te ha asignado una nueva tarea: ' . $tarea->titulo,
                    'datos' => [
                        'proyecto' => $tarea->proyecto->nombre,
                    ],
                ]);
            }

            // Registrar actividad
            Actividad::create([
                'activable_type' => Tarea::class,
                'activable_id' => $tarea->id,
                'usuario_id' => Auth::id(),
                'accion' => 'created',
                'descripcion' => 'Creó la tarea',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::connection('proyectos')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Tarea creada exitosamente.',
                'tarea' => $tarea->load(['asignado', 'etiquetas']),
            ]);
                
        } catch (\Exception $e) {
            DB::connection('proyectos')->rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la tarea: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $tarea = Tarea::with([
            'proyecto',
            'asignado',
            'creador',
            'tareaPadre',
            'subTareas',
            'checklists.items',
            'comentarios.usuario',
            'adjuntos.usuario',
            'etiquetas',
            'actividades.usuario',
            'dependencias.dependeDe',
        ])->findOrFail($id);

        return view('proyectos.tareas.show', compact('tarea'));
    }

    public function update(Request $request, $id)
    {
        $tarea = Tarea::findOrFail($id);

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'nullable|in:pendiente,en_progreso,revision,completada,bloqueada,cancelada',
            'prioridad' => 'nullable|in:baja,media,alta,critica',
            'fecha_inicio' => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date',
            'horas_estimadas' => 'nullable|numeric|min:0',
            'horas_reales' => 'nullable|numeric|min:0',
            'asignado_a' => 'nullable|exists:usuarios,id',
        ]);

        DB::connection('proyectos')->beginTransaction();
        
        try {
            $datosAntiguos = $tarea->getAttributes();
            $asignadoAnterior = $tarea->asignado_a;
            
            $tarea->update($validated);

            // Si cambió el asignado, notificar
            if ($request->asignado_a && $request->asignado_a != $asignadoAnterior) {
                Notificacion::create([
                    'usuario_id' => $request->asignado_a,
                    'tipo' => 'tarea_reasignada',
                    'notificable_type' => Tarea::class,
                    'notificable_id' => $tarea->id,
                    'mensaje' => 'Se te ha reasignado la tarea: ' . $tarea->titulo,
                ]);
            }

            // Actualizar progreso del proyecto
            $tarea->proyecto->actualizarProgreso();

            // Registrar actividad
            Actividad::create([
                'activable_type' => Tarea::class,
                'activable_id' => $tarea->id,
                'usuario_id' => Auth::id(),
                'accion' => 'updated',
                'descripcion' => 'Actualizó la tarea',
                'datos_antiguos' => $datosAntiguos,
                'datos_nuevos' => $tarea->getAttributes(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::connection('proyectos')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Tarea actualizada exitosamente.',
                'tarea' => $tarea->load(['asignado', 'etiquetas']),
            ]);
                
        } catch (\Exception $e) {
            DB::connection('proyectos')->rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la tarea: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function completar($id)
    {
        $tarea = Tarea::findOrFail($id);

        DB::connection('proyectos')->beginTransaction();
        
        try {
            $tarea->completar(Auth::id());

            // Registrar actividad
            Actividad::create([
                'activable_type' => Tarea::class,
                'activable_id' => $tarea->id,
                'usuario_id' => Auth::id(),
                'accion' => 'completed',
                'descripcion' => 'Marcó la tarea como completada',
            ]);

            // Notificar al creador
            if ($tarea->creado_por && $tarea->creado_por != Auth::id()) {
                Notificacion::create([
                    'usuario_id' => $tarea->creado_por,
                    'tipo' => 'tarea_completada',
                    'notificable_type' => Tarea::class,
                    'notificable_id' => $tarea->id,
                    'mensaje' => Auth::user()->nombre . ' completó la tarea: ' . $tarea->titulo,
                ]);
            }

            DB::connection('proyectos')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Tarea completada exitosamente.',
            ]);
                
        } catch (\Exception $e) {
            DB::connection('proyectos')->rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al completar la tarea: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function reabrir($id)
    {
        $tarea = Tarea::findOrFail($id);

        DB::connection('proyectos')->beginTransaction();
        
        try {
            $tarea->reabrir();

            // Registrar actividad
            Actividad::create([
                'activable_type' => Tarea::class,
                'activable_id' => $tarea->id,
                'usuario_id' => Auth::id(),
                'accion' => 'reopened',
                'descripcion' => 'Reabrió la tarea',
            ]);

            DB::connection('proyectos')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Tarea reabierta exitosamente.',
            ]);
                
        } catch (\Exception $e) {
            DB::connection('proyectos')->rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al reabrir la tarea: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $tarea = Tarea::findOrFail($id);

        try {
            $proyecto = $tarea->proyecto;
            $tarea->delete();
            $proyecto->actualizarProgreso();

            return response()->json([
                'success' => true,
                'message' => 'Tarea eliminada exitosamente.',
            ]);
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la tarea: ' . $e->getMessage(),
            ], 500);
        }
    }
}
