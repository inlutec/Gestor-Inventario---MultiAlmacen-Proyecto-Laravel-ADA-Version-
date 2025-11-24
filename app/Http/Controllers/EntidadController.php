<?php

namespace App\Http\Controllers;

use App\Models\Entidad;
use App\Models\TipoEntidad;
use App\Models\RegistroCambio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class EntidadController extends Controller
{
    public function index(Request $request)
    {
        $query = Entidad::with(['tipoEntidad', 'plano', 'usuarioCreador', 'departamento.sede.provincia']);

        // Aplicar filtro por almacén (para todos los usuarios, incluyendo admins si seleccionan un almacén)
        $almacenIds = $request->get('almacen_ids', []);
        if (!empty($almacenIds)) {
            $query->porAlmacenes($almacenIds);
        }

        // Filtro por tipo
        if ($request->has('tipo_entidad_id')) {
            $query->where('tipo_entidad_id', $request->tipo_entidad_id);
        }

        // Búsqueda
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereRaw("JSON_SEARCH(datos, 'one', ?) IS NOT NULL", ["%{$search}%"]);
            });
        }

        $entidades = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $entidades,
        ]);
    }

    public function show($id)
    {
        $entidad = Entidad::with(['tipoEntidad.campos', 'plano', 'usuarioCreador'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $entidad,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo_entidad_id' => 'required|exists:tipos_entidad,id',
            'datos' => 'required|array',
            'custom_fields' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $entidad = Entidad::create([
            'tipo_entidad_id' => $request->tipo_entidad_id,
            'datos' => $request->datos,
            'categoria_id' => $request->categoria_id,
            'visible_publico' => $request->visible_publico ?? true,
            'plano_id' => $request->plano_id,
            'posicion_x' => $request->posicion_x,
            'posicion_y' => $request->posicion_y,
            'fotos' => $request->fotos ?? [],
            'usuario_creador_id' => $request->user()->id,
        ]);

        // Guardar valores de campos personalizados (para impresoras u otras entidades)
        if ($request->filled('custom_fields')) {
            $this->guardarCamposPersonalizados('impresora', $entidad->id, $request->custom_fields);
        }

        // Registrar actividad
        $this->registrarActividad(
            'crear',
            'entidad',
            $entidad->id,
            null,
            $entidad->datos,
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Entidad creada correctamente',
            'data' => $entidad->load(['tipoEntidad', 'plano']),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $entidad = Entidad::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'datos' => 'sometimes|array',
            'custom_fields' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $datosAnteriores = $entidad->datos;

        $entidad->update([
            'datos' => $request->datos ?? $entidad->datos,
            'categoria_id' => $request->categoria_id ?? $entidad->categoria_id,
            'visible_publico' => $request->has('visible_publico') ? $request->visible_publico : $entidad->visible_publico,
            'plano_id' => $request->plano_id ?? $entidad->plano_id,
            'posicion_x' => $request->posicion_x ?? $entidad->posicion_x,
            'posicion_y' => $request->posicion_y ?? $entidad->posicion_y,
            'fotos' => $request->fotos ?? $entidad->fotos,
        ]);

        // Guardar valores de campos personalizados
        if ($request->filled('custom_fields')) {
            $this->guardarCamposPersonalizados('impresora', $entidad->id, $request->custom_fields);
        }

        // Registrar actividad
        $this->registrarActividad(
            'modificar',
            'entidad',
            $entidad->id,
            $datosAnteriores,
            $entidad->datos,
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Entidad actualizada correctamente',
            'data' => $entidad->load(['tipoEntidad', 'plano']),
        ]);
    }

    private function guardarCamposPersonalizados(string $entityType, int $entityId, array $values)
    {
        // Recuperar definiciones activas para el tipo
        $defs = \App\Models\CustomField::where('entity_type', $entityType)->where('active', true)->get()->keyBy('key');
        foreach ($values as $key => $value) {
            if (!isset($defs[$key])) continue; // Ignorar no definidos
            $field = $defs[$key];
            \App\Models\CustomFieldValue::updateOrCreate(
                ['field_id' => $field->id, 'entity_type' => $entityType, 'entity_id' => $entityId],
                ['value' => is_array($value) ? json_encode($value) : (string)$value]
            );
        }
    }

    public function destroy(Request $request, $id)
    {
        $entidad = Entidad::findOrFail($id);
        $datosAnteriores = $entidad->datos;

        // Eliminar fotos asociadas
        if ($entidad->fotos && is_array($entidad->fotos)) {
            foreach ($entidad->fotos as $foto) {
                Storage::disk('public')->delete($foto);
            }
        }

        $entidad->delete();

        // Registrar actividad
        $this->registrarActividad(
            'eliminar',
            'entidad',
            $id,
            $datosAnteriores,
            null,
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Entidad eliminada correctamente',
        ]);
    }

    public function uploadFotoMaterial(Request $request, $id)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $entidad = Entidad::findOrFail($id);
        
        $file = $request->file('foto');
        $extension = $file->getClientOriginalExtension();
        $filename = 'material_' . $id . '_' . time() . '.' . $extension;
        
        // Crear directorio si no existe
        $storagePath = storage_path('app/public/uploads/materiales');
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0775, true);
        }
        
        // Cargar imagen
        $sourcePath = $file->getRealPath();
        $imageInfo = getimagesize($sourcePath);
        
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            default:
                return response()->json(['error' => 'Formato de imagen no soportado'], 400);
        }
        
        // Calcular nuevas dimensiones manteniendo proporción
        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);
        $maxSize = 800;
        
        if ($originalWidth > $maxSize || $originalHeight > $maxSize) {
            $ratio = min($maxSize / $originalWidth, $maxSize / $originalHeight);
            $newWidth = round($originalWidth * $ratio);
            $newHeight = round($originalHeight * $ratio);
        } else {
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        }
        
        // Crear imagen redimensionada
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preservar transparencia para PNG y GIF
        if ($imageInfo['mime'] === 'image/png' || $imageInfo['mime'] === 'image/gif') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        // Guardar imagen optimizada
        $fullPath = $storagePath . '/' . $filename;
        
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                imagejpeg($resizedImage, $fullPath, 85);
                break;
            case 'image/png':
                imagepng($resizedImage, $fullPath, 8);
                break;
            case 'image/gif':
                imagegif($resizedImage, $fullPath);
                break;
        }
        
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
        
        // Eliminar imagen anterior si existe
        if ($entidad->foto) {
            $oldPath = storage_path('app/public/' . $entidad->foto);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
        
        // Actualizar entidad con nueva foto
        $relativePath = 'uploads/materiales/' . $filename;
        $entidad->update(['foto' => $relativePath]);

        return response()->json([
            'success' => true,
            'message' => 'Foto subida correctamente',
            'data' => ['foto' => $relativePath],
        ]);
    }

    public function uploadPhoto(Request $request, $id)
    {
        $request->validate([
            'photo' => 'required|image|max:5120', // 5MB max
        ]);

        $entidad = Entidad::findOrFail($id);
        
        $path = $request->file('photo')->store('uploads/impresoras', 'public');
        
        $fotos = $entidad->fotos ?? [];
        $fotos[] = $path;
        
        $entidad->update(['fotos' => $fotos]);

        return response()->json([
            'success' => true,
            'message' => 'Foto subida correctamente',
            'data' => ['path' => $path],
        ]);
    }

    public function historialStock(Request $request, $id)
    {
        try {
            $entidad = Entidad::findOrFail($id);
           
            // Obtener todos los movimientos de esta entidad
            $query = \DB::table('material_movimiento_detalles')
                ->join('material_movimientos', 'material_movimientos.id', '=', 'material_movimiento_detalles.movimiento_id')
                ->where('material_movimiento_detalles.entidad_id', $id)
                ->select(
                    'material_movimientos.id',
                    'material_movimientos.fecha_movimiento',
                    'material_movimientos.numero_documento',
                    'material_movimientos.tipo',
                    'material_movimientos.origen',
                    'material_movimientos.destino',
                    'material_movimientos.estado',
                    'material_movimientos.observaciones',
                    'material_movimientos.usuario_id',
                    'material_movimientos.destino_departamento_id',
                    'material_movimientos.origen_departamento_id',
                    'material_movimientos.justificante_id',
                    'material_movimiento_detalles.cantidad',
                    'material_movimiento_detalles.unidad'
                );
            
            // Aplicar filtro por almacén si se especifica
            $almacenIds = $request->get('almacen_ids', []);
            if (!empty($almacenIds)) {
                $query->where(function($q) use ($almacenIds) {
                    $q->whereIn('material_movimientos.destino_departamento_id', $almacenIds)
                      ->orWhereIn('material_movimientos.origen_departamento_id', $almacenIds);
                });
            }
            
            $movimientos = $query->orderBy('material_movimientos.fecha_movimiento', 'desc')->get();

            // Cargar información adicional
            $movimientos = $movimientos->map(function($mov) {
                // Obtener información del usuario
                $usuario = \DB::table('usuarios')->where('id', $mov->usuario_id)->first();
                
                // Determinar el almacén principal del movimiento
                $almacenId = null;
                if ($mov->destino_departamento_id) {
                    $almacenId = $mov->destino_departamento_id;
                } elseif ($mov->origen_departamento_id) {
                    $almacenId = $mov->origen_departamento_id;
                }
                
                $almacen = null;
                if ($almacenId) {
                    $almacen = \DB::table('departamentos')
                        ->join('sedes', 'sedes.id', '=', 'departamentos.sede_id')
                        ->where('departamentos.id', $almacenId)
                        ->select('departamentos.id', 'departamentos.nombre', 'sedes.nombre as sede')
                        ->first();
                }
                
                // Determinar la justificación según el origen del movimiento
                $justificacion = '';
                
                // Primero verificar si hay un pedido asociado (la relación es inversa: pedidos.movimiento_id)
                $pedido = \DB::table('pedidos')->where('movimiento_id', $mov->id)->first();
                
                if ($pedido) {
                    // Si viene de un pedido/petición
                    if (isset($pedido->tipo) && $pedido->tipo === 'peticion') {
                        $justificacion = 'Petición web pública';
                        // Si hay justificación en la petición (en observaciones), agregarla
                        if (!empty($pedido->observaciones)) {
                            $justificacion .= ': ' . $pedido->observaciones;
                        }
                    } else {
                        $justificacion = 'Pedido interno';
                        // Si hay observaciones en el pedido, agregarlas
                        if (!empty($pedido->notas)) {
                            $justificacion .= ': ' . $pedido->notas;
                        }
                    }
                } elseif ($mov->justificante_id) {
                    // Si tiene justificante, obtener su nombre
                    $justificante = \DB::table('justificantes')->where('id', $mov->justificante_id)->first();
                    if ($justificante) {
                        $justificacion = $justificante->nombre;
                        if (!empty($mov->observaciones)) {
                            $justificacion .= ': ' . $mov->observaciones;
                        }
                    } else {
                        $justificacion = $mov->observaciones ?: 'Regularización de stock';
                    }
                } else {
                    // Si es un movimiento manual sin justificante
                    $justificacion = $mov->observaciones ?: 'Regularización de stock';
                }
                
                return [
                    'id' => $mov->id,
                    'fecha' => $mov->fecha_movimiento,
                    'tipo' => $mov->tipo,
                    'cantidad' => $mov->cantidad,
                    'unidad' => $mov->unidad,
                    'estado' => $mov->estado,
                    'usuario' => $usuario ? [
                        'name' => $usuario->nombre,
                        'email' => $usuario->email
                    ] : null,
                    'almacen' => $almacen ? [
                        'nombre' => $almacen->nombre,
                        'sede' => $almacen->sede
                    ] : null,
                    'numero_documento' => $mov->numero_documento,
                    'origen' => $mov->origen,
                    'destino' => $mov->destino,
                    'observaciones' => $mov->observaciones,
                    'justificacion' => $justificacion
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $movimientos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function regularizarStock(Request $request, $id)
    {
        $validated = $request->validate([
            'stock_anterior' => 'required|numeric|min:0',
            'stock_nuevo' => 'required|numeric|min:0',
            'motivo' => 'required|string|min:10|max:500',
        ]);

        try {
            $entidad = Entidad::findOrFail($id);
            
            $stockAnterior = $validated['stock_anterior'];
            $stockNuevo = $validated['stock_nuevo'];
            $diferencia = $stockNuevo - $stockAnterior;

            if ($diferencia == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay diferencia en el stock',
                ], 400);
            }

            DB::beginTransaction();

            // Determinar tipo de movimiento según la diferencia
            $tipo = $diferencia > 0 ? 'entrada' : 'salida';
            $cantidadMovimiento = abs($diferencia);

            // Crear el movimiento de ajuste
            $movimiento = \App\Models\MaterialMovimiento::create([
                'tipo' => $tipo,
                'numero_documento' => 'AJUSTE-' . now()->format('Ymd-His'),
                'fecha_movimiento' => now(),
                'origen' => $tipo === 'entrada' ? 'Ajuste de Inventario' : 'Stock Físico',
                'destino' => $tipo === 'entrada' ? 'Stock Físico' : 'Ajuste de Inventario',
                'observaciones' => "Regularización de stock. Motivo: {$validated['motivo']}. Stock anterior: {$stockAnterior}, Stock nuevo: {$stockNuevo}",
                'estado' => 'firmado', // Los ajustes se consideran automáticamente firmados
                'usuario_id' => $request->user()->id,
            ]);

            // Obtener descripción del material
            $datos = is_array($entidad->datos) ? $entidad->datos : (json_decode($entidad->datos, true) ?? []);
            $descripcion = $datos['nombre'] ?? $datos['descripcion'] ?? $entidad->referencia ?? 'Material';
            $unidad = $datos['unidad'] ?? 'ud';

            // Crear detalle del movimiento
            \App\Models\MaterialMovimientoDetalle::create([
                'movimiento_id' => $movimiento->id,
                'entidad_id' => $entidad->id,
                'cantidad' => $cantidadMovimiento,
                'unidad' => $unidad,
                'descripcion' => $descripcion,
            ]);

            // Registrar en historial de auditoría
            \App\Models\MaterialMovimientoHistorial::registrarCambio(
                $movimiento->id,
                'ajuste_inventario',
                "Regularización de stock por " . $request->user()->nombre . ". Diferencia: " . ($diferencia > 0 ? '+' : '') . $diferencia,
                ['stock_anterior' => $stockAnterior],
                [
                    'stock_nuevo' => $stockNuevo,
                    'diferencia' => $diferencia,
                    'motivo' => $validated['motivo'],
                    'usuario' => $request->user()->nombre,
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock regularizado correctamente',
                'data' => [
                    'movimiento_id' => $movimiento->id,
                    'diferencia' => $diferencia,
                    'tipo' => $tipo,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al regularizar stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateUbicacion(Request $request, $id)
    {
        $validated = $request->validate([
            'ubicacion' => 'required|string|max:255',
            'almacen_id' => 'sometimes|nullable|exists:departamentos,id',
        ]);

        try {
            $entidad = Entidad::findOrFail($id);
            
            // Obtener almacén del middleware o del request
            $almacenId = $request->get('almacen_id') ?: $request->user()->almacen_seleccionado;
            
            // Si se especifica un almacén, verificar que el usuario tiene acceso
            if ($almacenId && !$request->user()->tieneAccesoAlmacen($almacenId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a este almacén',
                ], 403);
            }

            DB::beginTransaction();

            // Actualizar la ubicación en los datos JSON de la entidad
            $datos = is_array($entidad->datos) ? $entidad->datos : json_decode($entidad->datos, true) ?? [];
            $ubicacionAnterior = $datos['ubicacion'] ?? '';
            $datos['ubicacion'] = $validated['ubicacion'];
            
            $entidad->update(['datos' => $datos]);

            // Si hay un almacén específico, registrar la ubicación por almacén
            if ($almacenId) {
                // Actualizar o crear registro de ubicación por almacén
                \App\Models\EntidadUbicacion::updateOrCreate(
                    [
                        'entidad_id' => $entidad->id,
                        'almacen_id' => $almacenId,
                    ],
                    [
                        'ubicacion' => $validated['ubicacion'],
                        'usuario_id' => $request->user()->id,
                        'updated_at' => now(),
                    ]
                );
            }

            // Registrar actividad
            $this->registrarActividad(
                'modificar',
                'entidad',
                $entidad->id,
                ['ubicacion' => $ubicacionAnterior],
                ['ubicacion' => $validated['ubicacion']],
                $request
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ubicación actualizada correctamente',
                'data' => [
                    'ubicacion' => $validated['ubicacion'],
                    'almacen_id' => $almacenId,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la ubicación: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function registrarActividad($accion, $tipo, $entidadId, $datosAnteriores, $datosNuevos, Request $request)
    {
        RegistroCambio::create([
            'entidad_id' => $entidadId,
            'tipo_entidad' => $tipo,
            'accion' => $accion,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => $datosNuevos,
            'usuario_id' => $request->user()->id,
            'ip' => $request->ip(),
        ]);
    }
}
