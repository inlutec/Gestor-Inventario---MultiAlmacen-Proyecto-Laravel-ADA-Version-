<?php

namespace App\Http\Controllers;

use App\Models\SolicitudReposicion;
use App\Models\Entidad;
use App\Services\PushNotificationService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SolicitudReposicionController extends Controller
{
    protected $pushService;
    protected $notificationService;

    public function __construct(PushNotificationService $pushService, NotificationService $notificationService)
    {
        $this->pushService = $pushService;
        $this->notificationService = $notificationService;
    }

    /**
     * Lista todas las solicitudes de reposición
     */
    public function index(Request $request)
    {
        $query = SolicitudReposicion::with(['usuario', 'material.categoria', 'material.departamento.sede.provincia'])
            ->orderBy('fecha_solicitud', 'desc');

        // Aplicar filtro por almacén si se especifica
        // Manejar múltiples parámetros que pueden enviar los componentes
        $almacenIds = $request->get('almacen_ids', []);
        $almacenSeleccionado = $request->get('almacen_seleccionado');
        $almacenId = $request->get('almacen_id');
        
        // Prioridad: almacen_ids > almacen_seleccionado > almacen_id
        if (empty($almacenIds) && $almacenSeleccionado) {
            $almacenIds = [$almacenSeleccionado];
        } elseif (empty($almacenIds) && $almacenId) {
            $almacenIds = [$almacenId];
        }
        
        if (!empty($almacenIds)) {
            $query->whereHas('material.departamento', function ($q) use ($almacenIds) {
                $q->whereIn('id', $almacenIds);
            });
        }

        // Filtrar por estado si se especifica
        if ($request->has('estado') && $request->estado !== 'todos') {
            $query->where('estado', $request->estado);
        }

        // Filtrar por material si se especifica
        if ($request->has('entidad_id')) {
            $query->where('entidad_id', $request->entidad_id);
        }

        $solicitudes = $query->get()->map(function ($solicitud) {
            $material = $solicitud->material;
            $datos = $material->datos ?? [];
            
            return [
                'id' => $solicitud->id,
                'usuario' => [
                    'id' => $solicitud->usuario->id,
                    'nombre' => $solicitud->usuario->nombre,
                    'email' => $solicitud->usuario->email,
                    'telefono' => $solicitud->telefono_solicitante ?? $solicitud->usuario->telefono ?? null,
                ],
                'material' => [
                    'id' => $material->id,
                    'nombre' => $datos['nombre'] ?? $material->referencia ?? 'Sin nombre',
                    'referencia' => $datos['referencia'] ?? $material->referencia ?? null,
                    'descripcion' => $datos['descripcion'] ?? null,
                    'categoria' => $material->categoria->nombre ?? null,
                ],
                'cantidad_solicitada' => $solicitud->cantidad_solicitada,
                'estado' => $solicitud->estado,
                'fecha_solicitud' => $solicitud->fecha_solicitud->format('Y-m-d H:i:s'),
                'fecha_notificacion' => $solicitud->fecha_notificacion ? $solicitud->fecha_notificacion->format('Y-m-d H:i:s') : null,
                'prevision_llegada' => $solicitud->prevision_llegada ? $solicitud->prevision_llegada->format('Y-m-d') : null,
                'prevision_llegada_texto' => $solicitud->prevision_llegada ? $solicitud->prevision_llegada->format('F Y') : null,
                'notas' => $solicitud->notas,
                'motivo' => $solicitud->notas, // Las notas son el motivo de la solicitud
            ];
        });

        return response()->json($solicitudes);
    }

    /**
     * Crea una nueva solicitud de reposición
     */
    public function store(Request $request)
    {
        $request->validate([
            'entidad_id' => 'required|exists:entidades,id',
            'cantidad_solicitada' => 'integer|min:1',
            'telefono_solicitante' => 'nullable|string|max:20',
            'notas' => 'nullable|string|max:500',
        ]);

        $usuario = Auth::user();

        // Verificar si ya existe una solicitud pendiente para este material y usuario
        $solicitudExistente = SolicitudReposicion::where('usuario_id', $usuario->id)
            ->where('entidad_id', $request->entidad_id)
            ->where('estado', 'pendiente')
            ->first();

        if ($solicitudExistente) {
            // Actualizar la cantidad, teléfono y notas si ya existe
            $solicitudExistente->cantidad_solicitada += ($request->cantidad_solicitada ?? 1);
            if ($request->telefono_solicitante) {
                $solicitudExistente->telefono_solicitante = $request->telefono_solicitante;
            }
            if ($request->notas) {
                $solicitudExistente->notas = $request->notas;
            }
            $solicitudExistente->save();
            
            return response()->json([
                'message' => 'Solicitud actualizada. Se ha sumado la cantidad solicitada.',
                'solicitud' => $solicitudExistente
            ], 200);
        }

        $solicitud = SolicitudReposicion::create([
            'usuario_id' => $usuario->id,
            'entidad_id' => $request->entidad_id,
            'cantidad_solicitada' => $request->cantidad_solicitada ?? 1,
            'estado' => 'pendiente',
            'fecha_solicitud' => now(),
            'notas' => $request->notas,
            'telefono_solicitante' => $request->telefono_solicitante,
        ]);

        return response()->json([
            'message' => 'Solicitud creada. Te avisaremos cuando haya stock disponible.',
            'solicitud' => $solicitud
        ], 201);
    }

    /**
     * Crea solicitud desde web pública (sin autenticación)
     */
    public function storePublico(Request $request)
    {
        $request->validate([
            'entidad_id' => 'required|exists:entidades,id',
            'cantidad_solicitada' => 'integer|min:1',
            'usuario_solicitante' => 'required|string|max:255',
            'email_solicitante' => 'required|email|max:255',
            'telefono_solicitante' => 'nullable|string|max:20',
            'notas' => 'nullable|string|max:500',
        ]);

        // Buscar o crear usuario basado en email
        $usuario = \App\Models\Usuario::firstOrCreate(
            ['email' => $request->email_solicitante],
            [
                'nombre' => $request->usuario_solicitante,
                'apellido' => '',
                'password' => bcrypt(Str::random(32)), // Password aleatorio
                'rol' => 'usuario',
            ]
        );

        // Verificar si ya existe una solicitud pendiente
        $solicitudExistente = SolicitudReposicion::where('usuario_id', $usuario->id)
            ->where('entidad_id', $request->entidad_id)
            ->where('estado', 'pendiente')
            ->first();

        if ($solicitudExistente) {
            // Actualizar la cantidad, teléfono y notas si ya existe
            $solicitudExistente->cantidad_solicitada += ($request->cantidad_solicitada ?? 1);
            if ($request->telefono_solicitante) {
                $solicitudExistente->telefono_solicitante = $request->telefono_solicitante;
            }
            if ($request->notas) {
                $solicitudExistente->notas = ($solicitudExistente->notas ? $solicitudExistente->notas . "\n" : '') . $request->notas;
            }
            $solicitudExistente->save();
            
            return response()->json([
                'message' => 'Solicitud actualizada. Se ha sumado la cantidad solicitada.',
                'solicitud' => $solicitudExistente
            ], 200);
        }

        $solicitud = SolicitudReposicion::create([
            'usuario_id' => $usuario->id,
            'entidad_id' => $request->entidad_id,
            'cantidad_solicitada' => $request->cantidad_solicitada ?? 1,
            'estado' => 'pendiente',
            'fecha_solicitud' => now(),
            'notas' => $request->notas,
            'telefono_solicitante' => $request->telefono_solicitante,
        ]);

        return response()->json([
            'message' => 'Solicitud creada. Te avisaremos cuando haya stock disponible.',
            'solicitud' => $solicitud
        ], 201);
    }

    /**
     * Verifica si un usuario tiene solicitud activa para un material
     */
    public function verificarSolicitud($entidadId)
    {
        $usuario = Auth::user();
        
        $solicitud = SolicitudReposicion::where('usuario_id', $usuario->id)
            ->where('entidad_id', $entidadId)
            ->where('estado', 'pendiente')
            ->first();

        return response()->json([
            'tiene_solicitud' => $solicitud !== null,
            'solicitud' => $solicitud
        ]);
    }

    /**
     * Actualiza la previsión de llegada
     */
    public function actualizarPrevision(Request $request, $id)
    {
        $request->validate([
            'prevision_llegada' => 'nullable|date',
            'notas' => 'nullable|string|max:500',
        ]);

        $solicitud = SolicitudReposicion::findOrFail($id);
        
        $solicitud->update([
            'prevision_llegada' => $request->prevision_llegada,
            'notas' => $request->notas,
        ]);

        return response()->json([
            'message' => 'Previsión actualizada correctamente',
            'solicitud' => $solicitud
        ]);
    }

    /**
     * Cancela una solicitud
     */
    public function cancelar($id)
    {
        $solicitud = SolicitudReposicion::findOrFail($id);
        
        $solicitud->update([
            'estado' => 'cancelado',
        ]);

        return response()->json([
            'message' => 'Solicitud cancelada',
            'solicitud' => $solicitud
        ]);
    }

    /**
     * Notifica que hay stock disponible
     */
    public function notificar($id)
    {
        $solicitud = SolicitudReposicion::with(['usuario', 'material'])->findOrFail($id);
        
        if ($solicitud->estado !== 'pendiente') {
            return response()->json([
                'error' => 'Solo se pueden notificar solicitudes pendientes'
            ], 400);
        }

        $solicitud->update([
            'estado' => 'notificado',
            'fecha_notificacion' => now(),
        ]);

        $material = $solicitud->material;
        $datos = $material->datos ?? [];
        $nombreMaterial = $datos['nombre'] ?? $material->referencia ?? 'el material solicitado';
        $referenciaVisible = $datos['referencia'] ?? $material->referencia ?? 'Sin referencia';
        $descripcionMaterial = $datos['descripcion'] ?? '';
        
        $titulo = '✅ Stock Disponible';
        $mensaje = "Ya hay stock de {$nombreMaterial}. ¡Haz tu pedido ahora!";

        // Enviar email al usuario
        try {
            $this->notificationService->notificarUsuario(
                $solicitud->usuario,
                'Stock Disponible - ' . $nombreMaterial,
                "stock_disponible",
                [
                    'usuario_nombre' => $solicitud->usuario->nombre,
                    'material_nombre' => $nombreMaterial,
                    'material_referencia' => $referenciaVisible,
                    'material_descripcion' => $descripcionMaterial,
                    'cantidad_solicitada' => $solicitud->cantidad_solicitada,
                    'prevision_llegada' => $solicitud->prevision_llegada ? $solicitud->prevision_llegada->format('d/m/Y') : null,
                    'notas' => $solicitud->notas,
                ]
            );
        } catch (\Exception $e) {
            \Log::warning("Error enviando email de notificación: " . $e->getMessage());
        }

        // Enviar notificación push al usuario
        try {
            $this->pushService->sendToUser(
                $solicitud->usuario_id,
                $titulo,
                $mensaje,
                [
                    'tipo' => 'stock_disponible',
                    'entidad_id' => $solicitud->entidad_id,
                    'solicitud_id' => $solicitud->id,
                ]
            );
        } catch (\Exception $e) {
            \Log::warning("Error enviando notificación push: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Usuario notificado correctamente',
            'solicitud' => $solicitud
        ]);
    }

    /**
     * Verifica automáticamente solicitudes cuando hay nueva entrada de stock
     */
    public static function verificarSolicitudesPendientes($entidadId)
    {
        $solicitudes = SolicitudReposicion::with(['usuario', 'material'])
            ->where('entidad_id', $entidadId)
            ->where('estado', 'pendiente')
            ->get();

        $pushService = app(PushNotificationService::class);

        foreach ($solicitudes as $solicitud) {
            $solicitud->update([
                'estado' => 'notificado',
                'fecha_notificacion' => now(),
            ]);

            try {
                $titulo = '✅ Stock Disponible';
                $mensaje = "Ya hay stock de {$solicitud->material->nombre}. ¡Haz tu pedido ahora!";
                
                $pushService->enviarNotificacionUsuario(
                    $solicitud->usuario_id,
                    $titulo,
                    $mensaje,
                    [
                        'tipo' => 'stock_disponible',
                        'entidad_id' => $solicitud->entidad_id,
                        'solicitud_id' => $solicitud->id,
                    ]
                );
            } catch (\Exception $e) {
                \Log::warning("Error enviando notificación push: " . $e->getMessage());
            }
        }

        return count($solicitudes);
    }
}
