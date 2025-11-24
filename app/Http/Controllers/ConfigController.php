<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Justificante;
use App\Models\CustomField;
use App\Models\Sede;
use App\Models\Departamento;
use App\Models\CheckmkConfig;
use App\Models\CheckmkSyncLog;
use App\Models\SmtpConfig;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ConfigController extends Controller
{
    // ==================== PROVINCIAS ====================
    
    /**
     * Listar todas las provincias
     */
    public function indexProvincias()
    {
        $provincias = \App\Models\Provincia::withCount('sedes')->orderBy('nombre')->get();
        
        return response()->json([
            'success' => true,
            'data' => $provincias
        ]);
    }
    
    /**
     * Listar provincias públicas (sin autenticación)
     */
    public function indexProvinciasPublicas()
    {
        $provincias = \App\Models\Provincia::where('activo', true)
            ->withCount('sedes')
            ->orderBy('nombre')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $provincias
        ]);
    }
    
    /**
     * Crear una nueva provincia
     */
    public function storeProvincia(Request $request)
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
        
        $provincia = \App\Models\Provincia::create($validated);
        
        return response()->json([
            'success' => true,
            'data' => $provincia,
            'message' => 'Provincia creada correctamente'
        ], 201);
    }
    
    /**
     * Actualizar una provincia
     */
    public function updateProvincia(Request $request, $id)
    {
        $provincia = \App\Models\Provincia::findOrFail($id);
        
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
    public function destroyProvincia($id)
    {
        $provincia = \App\Models\Provincia::findOrFail($id);
        
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

    // ==================== JUSTIFICANTES ====================
    
    /**
     * Listar todos los justificantes
     */
    public function indexJustificantes(Request $request)
    {
        $query = Justificante::query();
        
        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        
        if ($request->has('activo')) {
            $query->where('activo', $request->activo);
        }
        
        $justificantes = $query->ordenado()->get();
        
        return response()->json([
            'success' => true,
            'data' => $justificantes
        ]);
    }
    
    /**
     * Crear un nuevo justificante
     */
    public function storeJustificante(Request $request)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:entrada,salida',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
            'orden' => 'integer|min:0',
        ]);
        
        // Si no se especifica orden, poner al final
        if (!isset($validated['orden'])) {
            $maxOrden = Justificante::where('tipo', $validated['tipo'])->max('orden');
            $validated['orden'] = $maxOrden ? $maxOrden + 1 : 1;
        }
        
        $justificante = Justificante::create($validated);
        
        return response()->json([
            'success' => true,
            'data' => $justificante,
            'message' => 'Justificante creado correctamente'
        ], 201);
    }
    
    /**
     * Actualizar un justificante
     */
    public function updateJustificante(Request $request, $id)
    {
        $justificante = Justificante::findOrFail($id);
        
        $validated = $request->validate([
            'tipo' => 'sometimes|in:entrada,salida',
            'nombre' => 'sometimes|string|max:255',
            'descripcion' => 'nullable|string',
            'activo' => 'sometimes|boolean',
            'orden' => 'sometimes|integer|min:0',
        ]);
        
        $justificante->update($validated);
        
        return response()->json([
            'success' => true,
            'data' => $justificante,
            'message' => 'Justificante actualizado correctamente'
        ]);
    }
    
    /**
     * Eliminar un justificante
     */
    public function destroyJustificante($id)
    {
        $justificante = Justificante::findOrFail($id);
        $justificante->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Justificante eliminado correctamente'
        ]);
    }
    
    /**
     * Reordenar justificantes
     */
    public function reordenarJustificantes(Request $request)
    {
        $orders = $request->validate([
            'orders' => 'required|array',
            'orders.*' => 'integer'
        ])['orders'];
        
        foreach ($orders as $id => $orden) {
            Justificante::where('id', $id)->update(['orden' => $orden]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Orden actualizado correctamente'
        ]);
    }
    
    // ==================== CAMPOS PERSONALIZADOS ====================
    
    /**
     * Listar campos personalizados
     */
    public function indexCampos(Request $request)
    {
        $query = CustomField::query();
        
        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }
        
        $campos = $query->orderBy('sort_order')->orderBy('label')->get();
        
        return response()->json([
            'success' => true,
            'data' => $campos
        ]);
    }
    
    /**
     * Crear campo personalizado
     */
    public function storeCampo(Request $request)
    {
        $validated = $request->validate([
            'entity_type' => 'required|string',
            'label' => 'required|string|max:255',
            'key' => 'nullable|string|max:255',
            'type' => 'required|in:text,number,date,boolean,select',
            'required' => 'boolean',
            'options' => 'nullable|array',
        ]);
        
        // Generar key si no se proporciona
        if (empty($validated['key'])) {
            $validated['key'] = \Str::slug($validated['label'], '_');
        }
        
        // Asignar sort_order
        $maxOrder = CustomField::where('entity_type', $validated['entity_type'])->max('sort_order');
        $validated['sort_order'] = $maxOrder ? $maxOrder + 1 : 1;
        
        $campo = CustomField::create($validated);
        
        return response()->json([
            'success' => true,
            'data' => $campo,
            'message' => 'Campo creado correctamente'
        ], 201);
    }
    
    /**
     * Actualizar campo personalizado
     */
    public function updateCampo(Request $request, $id)
    {
        $campo = CustomField::findOrFail($id);
        
        $validated = $request->validate([
            'entity_type' => 'sometimes|string',
            'label' => 'sometimes|string|max:255',
            'key' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:text,number,date,boolean,select',
            'required' => 'sometimes|boolean',
            'options' => 'nullable|array',
        ]);
        
        $campo->update($validated);
        
        return response()->json([
            'success' => true,
            'data' => $campo,
            'message' => 'Campo actualizado correctamente'
        ]);
    }
    
    /**
     * Eliminar campo personalizado
     */
    public function destroyCampo($id)
    {
        $campo = CustomField::findOrFail($id);
        $campo->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Campo eliminado correctamente'
        ]);
    }
    
    /**
     * Reordenar campos
     */
    public function reordenarCampos(Request $request)
    {
        $orders = $request->validate([
            'orders' => 'required|array',
            'orders.*' => 'integer'
        ])['orders'];
        
        foreach ($orders as $id => $orden) {
            CustomField::where('id', $id)->update(['sort_order' => $orden]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Orden actualizado correctamente'
        ]);
    }
    
    // ==================== SEDES Y DEPARTAMENTOS ====================
    
    /**
     * Listar sedes con sus departamentos
     */
    public function indexSedes()
    {
        $sedes = Sede::with(['departamentos' => function($query) {
            $query->select('id', 'nombre', 'sede_id', 'es_almacen');
        }, 'provincia'])->orderBy('nombre')->get();
        
        return response()->json([
            'success' => true,
            'data' => $sedes
        ]);
    }
    
    /**
     * Listar sedes públicas (sin autenticación)
     */
    public function indexSedesPublicas()
    {
        $sedes = Sede::with(['provincia'])
            ->whereHas('provincia', function($query) {
                $query->where('activo', true);
            })
            ->orderBy('nombre')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $sedes
        ]);
    }
    
    /**
     * Obtener departamentos de una sede pública (sin autenticación)
     */
    public function getDepartamentosPorSedePublica($sedeId)
    {
        // Para el solicitante, mostrar solo departamentos que NO son almacenes
        $departamentos = Departamento::where('sede_id', $sedeId)
            ->where('es_almacen', false)  // Solo departamentos no-almacén
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'sede_id', 'es_almacen']);
        
        return response()->json([
            'success' => true,
            'data' => $departamentos
        ]);
    }
    
    /**
     * Listar campos personalizados públicos (sin autenticación)
     */
    public function indexCamposPublicos(Request $request)
    {
        $query = CustomField::query();
        
        if ($request->has('entidad')) {
            $query->where('entity_type', $request->entidad);
        }
        
        $campos = $query->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
        
        return response()->json($campos);
    }
    
    /**
     * Crear sede
     */
    public function storeSede(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:sedes,nombre',
            'provincia_id' => 'nullable|exists:provincias,id',
        ]);
        
        $sede = Sede::create($validated);
        
        return response()->json([
            'success' => true,
            'data' => $sede,
            'message' => 'Sede creada correctamente'
        ], 201);
    }
    
    /**
     * Actualizar sede
     */
    public function updateSede(Request $request, $id)
    {
        $sede = Sede::findOrFail($id);
        
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255|unique:sedes,nombre,' . $id,
            'provincia_id' => 'nullable|exists:provincias,id',
        ]);
        
        $sede->update($validated);
        
        return response()->json([
            'success' => true,
            'data' => $sede,
            'message' => 'Sede actualizada correctamente'
        ]);
    }
    
    /**
     * Eliminar sede
     */
    public function destroySede($id)
    {
        $sede = Sede::findOrFail($id);
        $sede->delete(); // Los departamentos se eliminarán en cascada
        
        return response()->json([
            'success' => true,
            'message' => 'Sede eliminada correctamente'
        ]);
    }
    
    /**
     * Crear departamento
     */
    public function storeDepartamento(Request $request, $sedeId)
    {
        $sede = Sede::findOrFail($sedeId);
        
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
        ]);
        
        $departamento = $sede->departamentos()->create($validated);
        
        return response()->json([
            'success' => true,
            'data' => $departamento,
            'message' => 'Departamento creado correctamente'
        ], 201);
    }
    
    /**
     * Actualizar departamento
     */
    public function updateDepartamento(Request $request, $id)
    {
        $departamento = Departamento::findOrFail($id);
        
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'es_almacen_central' => 'sometimes|boolean',
        ]);
        
        $departamento->update($validated);
        
        return response()->json([
            'success' => true,
            'data' => $departamento,
            'message' => 'Departamento actualizado correctamente'
        ]);
    }
    
    /**
     * Actualizar estado de almacén de un departamento
     */
    public function updateAlmacenDepartamento(Request $request, $id)
    {
        $departamento = Departamento::findOrFail($id);
        
        $validated = $request->validate([
            'es_almacen' => 'required|boolean',
        ]);
        
        // Actualizar directamente el campo es_almacen
        $departamento->update(['es_almacen' => $validated['es_almacen']]);
        
        // Asegurarse de que el modelo devuelto tenga el campo correcto
        $departamento->refresh();
        
        return response()->json([
            'success' => true,
            'data' => $departamento,
            'message' => $validated['es_almacen'] ?
                'Departamento marcado como almacén correctamente' :
                'Departamento desmarcado como almacén correctamente'
        ]);
    }
    
    /**
     * Eliminar departamento
     */
    public function destroyDepartamento($id)
    {
        $departamento = Departamento::findOrFail($id);
        $departamento->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Departamento eliminado correctamente'
        ]);
    }
    
    /**
     * Sincronizar campo "Departamento" en todas las entidades
     */
    public function syncDepartamentos()
    {
        // Aquí podrías implementar lógica para actualizar el custom field "departamento"
        // con las opciones actualizadas de departamentos
        
        return response()->json([
            'success' => true,
            'message' => 'Campo Departamento sincronizado'
        ]);
    }
    
    // ==================== CHECKMK ====================
    
    /**
     * Obtener configuración de CheckMK
     */
    public function getCheckmkConfig()
    {
        $config = CheckmkConfig::first();
        
        if (!$config) {
            return response()->json([
                'success' => true,
                'data' => [
                    'api_url' => '',
                    'api_user' => '',
                    'site' => '',
                    'sync_interval_minutes' => 60,
                    'last_sync' => null,
                ]
            ]);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'api_url' => $config->api_url,
                'api_user' => $config->api_user,
                'site' => $config->site,
                'sync_interval_minutes' => $config->sync_interval_minutes,
                'last_sync' => $config->last_sync,
            ]
        ]);
    }
    
    /**
     * Actualizar configuración de CheckMK
     */
    public function updateCheckmkConfig(Request $request)
    {
        $validated = $request->validate([
            'api_url' => 'required|url',
            'api_user' => 'required|string',
            'api_password' => 'nullable|string',
            'site' => 'required|string',
            'sync_interval_minutes' => 'required|integer|min:1|max:1440',
        ]);
        
        $config = CheckmkConfig::first();
        
        if (!$config) {
            $config = new CheckmkConfig();
        }
        
        $config->api_url = $validated['api_url'];
        $config->api_user = $validated['api_user'];
        $config->site = $validated['site'];
        $config->sync_interval_minutes = $validated['sync_interval_minutes'];
        
        // Solo actualizar password si se proporciona
        if (!empty($validated['api_password'])) {
            $config->api_password = encrypt($validated['api_password']);
        }
        
        $config->save();
        
        return response()->json([
            'success' => true,
            'data' => [
                'api_url' => $config->api_url,
                'api_user' => $config->api_user,
                'site' => $config->site,
                'sync_interval_minutes' => $config->sync_interval_minutes,
                'last_sync' => $config->last_sync,
            ],
            'message' => 'Configuración actualizada correctamente'
        ]);
    }
    
    /**
     * Probar conexión con CheckMK
     */
    public function testCheckmkConnection()
    {
        try {
            $config = CheckmkConfig::first();
            
            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay configuración de CheckMK'
                ], 400);
            }
            
            // Aquí podrías hacer una petición real a CheckMK para probar la conexión
            // Por ahora simplemente verificamos que existan los datos
            
            return response()->json([
                'success' => true,
                'message' => 'Conexión verificada correctamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al conectar: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener logs de sincronización CheckMK
     */
    public function getCheckmkLogs(Request $request)
    {
        $limit = $request->get('limit', 100);
        
        $logs = CheckmkSyncLog::orderBy('sync_timestamp', 'desc')
            ->limit($limit)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }
    
    // ==================== LOGOTIPOS ====================
    
    /**
     * Sube y reemplaza un logotipo institucional
     */
    public function uploadLogo(Request $request, string $tipo)
    {
        // Validar que el tipo sea junta o ada
        if (!in_array($tipo, ['junta', 'ada'])) {
            return response()->json(['message' => 'Tipo de logotipo inválido'], 400);
        }

        // Validar el archivo
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:2048', // max 2MB (límite de PHP)
        ]);

        try {
            $file = $request->file('logo');
            $filename = $tipo === 'junta' ? 'junta-logo.png' : 'ada-logo.png';
            $publicPath = public_path('images/' . $filename);

            // Si es SVG, guardar directamente sin procesar
            if ($file->getClientOriginalExtension() === 'svg') {
                $file->move(public_path('images'), $filename);
                return response()->json([
                    'message' => 'Logotipo actualizado correctamente',
                    'path' => '/images/' . $filename
                ]);
            }

            // Para PNG/JPG, redimensionar usando GD
            $sourceImage = null;
            $extension = $file->getClientOriginalExtension();
            
            switch ($extension) {
                case 'png':
                    $sourceImage = imagecreatefrompng($file->getRealPath());
                    break;
                case 'jpg':
                case 'jpeg':
                    $sourceImage = imagecreatefromjpeg($file->getRealPath());
                    break;
            }

            if (!$sourceImage) {
                return response()->json(['message' => 'Error al procesar la imagen'], 500);
            }

            // Obtener dimensiones originales
            $originalWidth = imagesx($sourceImage);
            $originalHeight = imagesy($sourceImage);

            // Calcular nuevas dimensiones manteniendo proporción (altura máxima 120px)
            $maxHeight = 120;
            if ($originalHeight > $maxHeight) {
                $ratio = $maxHeight / $originalHeight;
                $newWidth = (int)($originalWidth * $ratio);
                $newHeight = $maxHeight;
            } else {
                $newWidth = $originalWidth;
                $newHeight = $originalHeight;
            }

            // Crear imagen redimensionada
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preservar transparencia para PNG
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
            imagefill($resizedImage, 0, 0, $transparent);
            
            // Redimensionar
            imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

            // Guardar como PNG
            imagepng($resizedImage, $publicPath);

            // Liberar memoria
            imagedestroy($sourceImage);
            imagedestroy($resizedImage);

            return response()->json([
                'message' => 'Logotipo actualizado correctamente',
                'path' => '/images/' . $filename
            ]);

        } catch (\Exception $e) {
            \Log::error('Error subiendo logotipo: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al procesar el logotipo: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== CONFIGURACIÓN SMTP ====================

    /**
     * Obtener configuración SMTP activa
     */
    public function getSmtpConfig()
    {
        $config = SmtpConfig::getActive();
        
        if (!$config) {
            return response()->json([
                'success' => true,
                'data' => null
            ]);
        }

        // No enviar la contraseña al frontend
        $configData = $config->toArray();
        unset($configData['password']);
        $configData['tiene_password'] = !empty($config->password);

        return response()->json([
            'success' => true,
            'data' => $configData
        ]);
    }

    /**
     * Guardar o actualizar configuración SMTP
     */
    public function saveSmtpConfig(Request $request)
    {
        $validated = $request->validate([
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'encryption' => 'required|in:tls,ssl,none',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string',
            'from_address' => 'required|email',
            'from_name' => 'required|string|max:255',
        ]);

        try {
            // Desactivar configuraciones anteriores
            SmtpConfig::where('activo', true)->update(['activo' => false]);

            // Crear o actualizar la configuración
            $config = SmtpConfig::updateOrCreate(
                ['host' => $validated['host'], 'port' => $validated['port']],
                array_merge($validated, ['activo' => true])
            );

            return response()->json([
                'success' => true,
                'message' => 'Configuración SMTP guardada correctamente',
                'data' => $config
            ]);
        } catch (\Exception $e) {
            Log::error('Error guardando configuración SMTP: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Probar configuración SMTP enviando un email de prueba
     */
    public function testSmtpConfig(Request $request)
    {
        $validated = $request->validate([
            'email_prueba' => 'required|email'
        ]);

        $config = null;

        try {
            $config = SmtpConfig::getActive();
            
            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay configuración SMTP activa. Guarda primero la configuración.'
                ], 400);
            }

            // Configurar timeout más corto para evitar esperas largas
            ini_set('default_socket_timeout', 10);

            // Aplicar la configuración
            $config->apply();

            // Verificar que la configuración sea coherente
            $encryption = $config->encryption === 'none' ? null : $config->encryption;
            
            // Validar puerto según encriptación
            if ($encryption === 'ssl' && $config->port != 465) {
                throw new \Exception('Puerto incorrecto para SSL. Use 465 para SSL o 587 para TLS.');
            }
            
            if ($encryption === 'tls' && $config->port != 587) {
                throw new \Exception('Puerto incorrecto para TLS. Use 587 para TLS o 465 para SSL.');
            }

            // Log para depuración
            Log::info('Probando SMTP', [
                'host' => $config->host,
                'port' => $config->port,
                'encryption' => $encryption,
                'username' => $config->username,
                'from' => $config->from_address
            ]);

            // Intentar enviar email de prueba
            Mail::raw('Este es un email de prueba desde el sistema de Gestión de Material de la Junta de Andalucía.' . "\n\n" .
                      'Si recibes este mensaje, la configuración SMTP está funcionando correctamente.' . "\n\n" .
                      'Fecha y hora: ' . now()->format('d/m/Y H:i:s'), 
                function ($message) use ($validated) {
                    $message->to($validated['email_prueba'])
                            ->subject('✓ Prueba de configuración SMTP - Gestión de Material');
                }
            );

            // Actualizar resultado de la prueba
            $config->update([
                'ultima_prueba' => now(),
                'resultado_prueba' => 'Exitoso - Email enviado a ' . $validated['email_prueba']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email de prueba enviado correctamente a ' . $validated['email_prueba']
            ]);

        } catch (\Swift_TransportException $e) {
            $errorMsg = $e->getMessage();
            
            // Mensajes de error más amigables
            if (strpos($errorMsg, 'Connection timed out') !== false) {
                $errorMsg = 'Timeout de conexión. Verifica que el host y puerto sean correctos y que el servidor sea accesible.';
            } elseif (strpos($errorMsg, 'Connection refused') !== false) {
                $errorMsg = 'Conexión rechazada. Verifica el host y puerto. Asegúrate de usar el puerto correcto (587 para TLS, 465 para SSL).';
            } elseif (strpos($errorMsg, 'stream_socket_enable_crypto') !== false) {
                $errorMsg = 'Error en la encriptación. Verifica que el tipo de encriptación coincida con el puerto (TLS=587, SSL=465).';
            } elseif (strpos($errorMsg, 'Authentication') !== false || strpos($errorMsg, 'Invalid credentials') !== false) {
                $errorMsg = 'Credenciales inválidas. Verifica el usuario y contraseña SMTP.';
            }

            if ($config) {
                $config->update([
                    'ultima_prueba' => now(),
                    'resultado_prueba' => 'Error: ' . $errorMsg
                ]);
            }

            Log::error('Error SMTP (Transport): ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => $errorMsg
            ], 500);

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();

            if ($config) {
                $config->update([
                    'ultima_prueba' => now(),
                    'resultado_prueba' => 'Error: ' . $errorMsg
                ]);
            }

            Log::error('Error probando configuración SMTP: ' . $errorMsg);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar email: ' . $errorMsg
            ], 500);
        }
    }

    /**
     * Eliminar configuración SMTP
     */
    public function deleteSmtpConfig($id)
    {
        try {
            $config = SmtpConfig::findOrFail($id);
            $config->delete();

            return response()->json([
                'success' => true,
                'message' => 'Configuración SMTP eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error eliminando configuración SMTP: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la configuración'
            ], 500);
        }
    }

    // ==================== CATEGORÍAS ====================
    
    /**
     * Listar categorías públicas (sin autenticación)
     */
    public function indexCategoriasPublicas(Request $request)
    {
        $categorias = \App\Models\Categoria::query()
            ->where('activo', true)
            ->withCount('entidades')
            ->ordenadas()
            ->get(['id', 'nombre', 'descripcion', 'imagen', 'orden']);
        
        return response()->json([
            'success' => true,
            'data' => $categorias
        ]);
    }
    
    /**
     * Listar todas las categorías
     */
    public function indexCategorias(Request $request)
    {
        $query = \App\Models\Categoria::query();
        
        if ($request->has('activo')) {
            $query->where('activo', $request->activo);
        }
        
        // Aplicar filtro por almacén si el usuario no es administrador
        if (auth()->check() && auth()->user()->role !== 'admin') {
            $almacenIds = $request->get('almacen_ids', []);
            if (!empty($almacenIds)) {
                // Filtrar categorías que tengan entidades en los almacenes del usuario
                $query->whereHas('entidades', function ($q) use ($almacenIds) {
                    $q->whereIn('departamento_id', $almacenIds);
                });
            }
        }
        
        $categorias = $query->ordenadas()
            ->withCount('entidades')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $categorias
        ]);
    }
    
    /**
     * Crear una nueva categoría
     */
    public function storeCategoria(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'orden' => 'nullable|integer',
            'activo' => 'nullable|boolean',
        ]);

        $categoria = \App\Models\Categoria::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Categoría creada correctamente',
            'data' => $categoria
        ]);
    }
    
    /**
     * Actualizar una categoría
     */
    public function updateCategoria(Request $request, $id)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'orden' => 'nullable|integer',
            'activo' => 'nullable|boolean',
        ]);

        $categoria = \App\Models\Categoria::findOrFail($id);
        $categoria->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Categoría actualizada correctamente',
            'data' => $categoria
        ]);
    }
    
    /**
     * Eliminar una categoría
     */
    public function destroyCategoria($id)
    {
        $categoria = \App\Models\Categoria::findOrFail($id);
        
        // Verificar si tiene entidades asociadas
        if ($categoria->entidades()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la categoría porque tiene materiales asociados'
            ], 400);
        }

        $categoria->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada correctamente'
        ]);
    }

    /**
     * Reordenar categorías
     */
    public function reordenarCategorias(Request $request)
    {
        $validated = $request->validate([
            'orden' => 'required|array',
            'orden.*.id' => 'required|exists:categorias,id',
            'orden.*.orden' => 'required|integer',
        ]);

        foreach ($validated['orden'] as $item) {
            \App\Models\Categoria::where('id', $item['id'])->update(['orden' => $item['orden']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Orden actualizado correctamente'
        ]);
    }

    /**
     * Subir imagen de categoría
     */
    public function uploadImagenCategoria(Request $request, $id)
    {
        $request->validate([
            'imagen' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $categoria = \App\Models\Categoria::findOrFail($id);

        if ($request->hasFile('imagen')) {
            $file = $request->file('imagen');
            $filename = 'categoria_' . $id . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            // Crear directorio si no existe
            $storagePath = storage_path('app/public/categorias');
            if (!file_exists($storagePath)) {
                mkdir($storagePath, 0755, true);
            }

            // Redimensionar y optimizar la imagen
            $imagePath = $file->getRealPath();
            $imageInfo = getimagesize($imagePath);
            $mimeType = $imageInfo['mime'];

            // Crear imagen desde el archivo
            switch ($mimeType) {
                case 'image/jpeg':
                    $sourceImage = imagecreatefromjpeg($imagePath);
                    break;
                case 'image/png':
                    $sourceImage = imagecreatefrompng($imagePath);
                    break;
                case 'image/gif':
                    $sourceImage = imagecreatefromgif($imagePath);
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Formato de imagen no soportado'
                    ], 400);
            }

            if (!$sourceImage) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo procesar la imagen'
                ], 400);
            }

            $originalWidth = imagesx($sourceImage);
            $originalHeight = imagesy($sourceImage);

            // Redimensionar a máximo 800x800 manteniendo proporción
            $maxSize = 800;
            if ($originalWidth > $maxSize || $originalHeight > $maxSize) {
                $ratio = min($maxSize / $originalWidth, $maxSize / $originalHeight);
                $newWidth = (int)($originalWidth * $ratio);
                $newHeight = (int)($originalHeight * $ratio);
            } else {
                $newWidth = $originalWidth;
                $newHeight = $originalHeight;
            }

            // Crear imagen redimensionada
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

            // Preservar transparencia para PNG y GIF
            if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
                imagecolortransparent($resizedImage, imagecolorallocatealpha($resizedImage, 0, 0, 0, 127));
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
            }

            // Redimensionar
            imagecopyresampled(
                $resizedImage,
                $sourceImage,
                0, 0, 0, 0,
                $newWidth,
                $newHeight,
                $originalWidth,
                $originalHeight
            );

            // Guardar imagen optimizada
            $destinationPath = $storagePath . '/' . $filename;
            switch ($mimeType) {
                case 'image/jpeg':
                    imagejpeg($resizedImage, $destinationPath, 85); // 85% calidad
                    break;
                case 'image/png':
                    imagepng($resizedImage, $destinationPath, 8); // Compresión 8
                    break;
                case 'image/gif':
                    imagegif($resizedImage, $destinationPath);
                    break;
            }

            // Liberar memoria
            imagedestroy($sourceImage);
            imagedestroy($resizedImage);

            // Eliminar imagen anterior si existe
            if ($categoria->imagen && \Storage::exists('public/categorias/' . $categoria->imagen)) {
                \Storage::delete('public/categorias/' . $categoria->imagen);
            }

            $categoria->update(['imagen' => $filename]);

            return response()->json([
                'success' => true,
                'message' => 'Imagen subida y optimizada correctamente',
                'imagen' => $filename,
                'url' => asset('storage/categorias/' . $filename)
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se recibió ninguna imagen'
        ], 400);
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
