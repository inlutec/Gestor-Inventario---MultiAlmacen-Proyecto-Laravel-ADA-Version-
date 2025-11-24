<?php

namespace App\Http\Controllers\Proyectos;

use App\Http\Controllers\Controller;
use App\Models\Proyectos\Proyecto;
use App\Models\Proyectos\Actividad;
use App\Models\Proyectos\Notificacion;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProyectoController extends Controller
{
    public function index(Request $request)
    {
        $usuario = Auth::user();
        
        $query = Proyecto::query()
            ->with(['responsable', 'ubicaciones', 'miembros', 'etiquetas'])
            ->activos();

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->prioridad);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('nombre', 'LIKE', "%{$buscar}%")
                  ->orWhere('codigo', 'LIKE', "%{$buscar}%")
                  ->orWhere('descripcion', 'LIKE', "%{$buscar}%");
            });
        }

        if ($request->filled('mis_proyectos')) {
            $query->delUsuario($usuario->id);
        }

        $proyectos = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('proyectos.index', compact('proyectos'));
    }

    public function dashboard()
    {
        $usuario = Auth::user();

        // Estadísticas generales
        $estadisticas = [
            'total_proyectos' => Proyecto::activos()->count(),
            'mis_proyectos' => Proyecto::activos()->delUsuario($usuario->id)->count(),
            'proyectos_progreso' => Proyecto::activos()->where('estado', 'en_progreso')->count(),
            'proyectos_retrasados' => Proyecto::activos()
                ->whereNotIn('estado', ['completado', 'cancelado'])
                ->whereNotNull('fecha_fin_estimada')
                ->where('fecha_fin_estimada', '<', now())
                ->count(),
        ];

        // Proyectos recientes del usuario
        $misProyectos = Proyecto::query()
            ->with(['responsable', 'tareas'])
            ->activos()
            ->delUsuario($usuario->id)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        // Actividad reciente
        $actividadesRecientes = Actividad::query()
            ->with(['usuario', 'activable'])
            ->whereHas('activable', function($q) use ($usuario) {
                $q->whereIn('id', $usuario->proyectos()->pluck('proyectos.id'));
            })
            ->recientes(20)
            ->get();

        // Notificaciones no leídas
        $notificacionesNoLeidas = Notificacion::where('usuario_id', $usuario->id)
            ->noLeidas()
            ->recientes()
            ->limit(10)
            ->get();

        return view('proyectos.dashboard', compact(
            'estadisticas',
            'misProyectos',
            'actividadesRecientes',
            'notificacionesNoLeidas'
        ));
    }

    public function show($id)
    {
        $proyecto = Proyecto::with([
            'responsable',
            'creador',
            'ubicaciones',
            'miembros',
            'equipos',
            'tareas.asignado',
            'tareas.etiquetas',
            'hitos',
            'comentarios.usuario',
            'adjuntos.usuario',
            'etiquetas',
            'actividades.usuario'
        ])->findOrFail($id);

        $usuario = Auth::user();
        
        if (!$proyecto->puedeVer($usuario)) {
            abort(403, 'No tienes permiso para ver este proyecto.');
        }

        // Estadísticas del proyecto
        $estadisticas = [
            'total_tareas' => $proyecto->tareas()->count(),
            'tareas_completadas' => $proyecto->tareas()->where('estado', 'completada')->count(),
            'tareas_pendientes' => $proyecto->tareas()->whereIn('estado', ['pendiente', 'en_progreso'])->count(),
            'tareas_retrasadas' => $proyecto->tareas()->retrasadas()->count(),
            'total_miembros' => $proyecto->miembros()->count() + $proyecto->equipos()->count(),
        ];

        return view('proyectos.show', compact('proyecto', 'estadisticas'));
    }

    public function create()
    {
        $usuarios = Usuario::orderBy('nombre')->get();
        
        return view('proyectos.create', compact('usuarios'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => 'nullable|string|unique:proyectos,codigo',
            'descripcion' => 'nullable|string',
            'estado' => 'required|in:planificacion,en_progreso,pausado,completado,cancelado',
            'prioridad' => 'required|in:baja,media,alta,critica',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin_estimada' => 'nullable|date|after_or_equal:fecha_inicio',
            'color' => 'nullable|string|max:7',
            'responsable_id' => 'nullable|exists:usuarios,id',
        ]);

        DB::connection('proyectos')->beginTransaction();
        
        try {
            $validated['creado_por'] = Auth::id();
            
            $proyecto = Proyecto::create($validated);

            // Agregar al creador como miembro con rol gestor
            $proyecto->miembros()->attach(Auth::id(), [
                'rol' => 'gestor',
                'notificaciones' => true,
            ]);

            // Si hay responsable diferente al creador, agregarlo
            if ($request->responsable_id && $request->responsable_id != Auth::id()) {
                $proyecto->miembros()->attach($request->responsable_id, [
                    'rol' => 'coordinador',
                    'notificaciones' => true,
                ]);

                // Crear notificación
                Notificacion::create([
                    'usuario_id' => $request->responsable_id,
                    'tipo' => 'proyecto_asignado',
                    'notificable_type' => Proyecto::class,
                    'notificable_id' => $proyecto->id,
                    'mensaje' => 'Has sido asignado como responsable del proyecto: ' . $proyecto->nombre,
                ]);
            }

            // Registrar actividad
            Actividad::create([
                'activable_type' => Proyecto::class,
                'activable_id' => $proyecto->id,
                'usuario_id' => Auth::id(),
                'accion' => 'created',
                'descripcion' => 'Creó el proyecto',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::connection('proyectos')->commit();

            return redirect()
                ->route('proyectos.show', $proyecto)
                ->with('success', 'Proyecto creado exitosamente.');
                
        } catch (\Exception $e) {
            DB::connection('proyectos')->rollBack();
            
            return back()
                ->withInput()
                ->with('error', 'Error al crear el proyecto: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $proyecto = Proyecto::findOrFail($id);
        $usuario = Auth::user();
        
        if (!$proyecto->puedeEditar($usuario)) {
            abort(403, 'No tienes permiso para editar este proyecto.');
        }

        $usuarios = Usuario::orderBy('nombre')->get();

        return view('proyectos.edit', compact('proyecto', 'usuarios'));
    }

    public function update(Request $request, $id)
    {
        $proyecto = Proyecto::findOrFail($id);
        $usuario = Auth::user();
        
        if (!$proyecto->puedeEditar($usuario)) {
            abort(403, 'No tienes permiso para editar este proyecto.');
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'required|in:planificacion,en_progreso,pausado,completado,cancelado',
            'prioridad' => 'required|in:baja,media,alta,critica',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin_estimada' => 'nullable|date',
            'fecha_fin_real' => 'nullable|date',
            'progreso' => 'nullable|numeric|min:0|max:100',
            'color' => 'nullable|string|max:7',
            'responsable_id' => 'nullable|exists:usuarios,id',
        ]);

        DB::connection('proyectos')->beginTransaction();
        
        try {
            $datosAntiguos = $proyecto->getAttributes();
            
            $proyecto->update($validated);

            // Registrar actividad
            Actividad::create([
                'activable_type' => Proyecto::class,
                'activable_id' => $proyecto->id,
                'usuario_id' => Auth::id(),
                'accion' => 'updated',
                'descripcion' => 'Actualizó el proyecto',
                'datos_antiguos' => $datosAntiguos,
                'datos_nuevos' => $proyecto->getAttributes(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::connection('proyectos')->commit();

            return redirect()
                ->route('proyectos.show', $proyecto)
                ->with('success', 'Proyecto actualizado exitosamente.');
                
        } catch (\Exception $e) {
            DB::connection('proyectos')->rollBack();
            
            return back()
                ->withInput()
                ->with('error', 'Error al actualizar el proyecto: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $proyecto = Proyecto::findOrFail($id);
        $usuario = Auth::user();
        
        if (!$proyecto->puedeEditar($usuario)) {
            abort(403, 'No tienes permiso para eliminar este proyecto.');
        }

        try {
            $proyecto->delete();

            return redirect()
                ->route('proyectos.index')
                ->with('success', 'Proyecto eliminado exitosamente.');
                
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Error al eliminar el proyecto: ' . $e->getMessage());
        }
    }

    public function archivar($id)
    {
        $proyecto = Proyecto::findOrFail($id);
        $usuario = Auth::user();
        
        if (!$proyecto->puedeEditar($usuario)) {
            abort(403, 'No tienes permiso para archivar este proyecto.');
        }

        $proyecto->archivado = true;
        $proyecto->save();

        Actividad::create([
            'activable_type' => Proyecto::class,
            'activable_id' => $proyecto->id,
            'usuario_id' => Auth::id(),
            'accion' => 'archived',
            'descripcion' => 'Archivó el proyecto',
        ]);

        return redirect()
            ->route('proyectos.index')
            ->with('success', 'Proyecto archivado exitosamente.');
    }
}
