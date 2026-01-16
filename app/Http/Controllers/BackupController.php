<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class BackupController extends Controller
{
    /**
     * Crear una copia de seguridad completa de la base de datos
     */
    public function crearBackup()
    {
        try {
            $database = env('DB_DATABASE');
            $username = env('DB_USERNAME');
            $password = env('DB_PASSWORD');
            $host = env('DB_HOST', '127.0.0.1');
            
            $fecha = date('Y-m-d_H-i-s');
            $filename = "backup_{$database}_{$fecha}.sql";
            $filepath = storage_path("app/backups/{$filename}");
            
            // Crear directorio si no existe
            if (!File::exists(storage_path('app/backups'))) {
                File::makeDirectory(storage_path('app/backups'), 0755, true);
            }
            
            // Construir comando mysqldump
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s --skip-comments --add-drop-table %s > %s 2>&1',
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($host),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );
            
            // Ejecutar comando
            exec($command, $output, $return_var);
            
            if ($return_var !== 0) {
                Log::error('Error al crear backup', [
                    'command' => $command,
                    'output' => $output,
                    'return_var' => $return_var
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear backup: ' . implode("\n", $output)
                ], 500);
            }
            
            // Verificar que el archivo se creó
            if (!File::exists($filepath) || File::size($filepath) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo de backup está vacío o no se creó'
                ], 500);
            }
            
            // Registrar en log
            Log::info('Backup creado exitosamente', [
                'filename' => $filename,
                'size' => File::size($filepath),
                'user_id' => auth()->id()
            ]);
            
            // Retornar archivo para descarga
            return response()->download($filepath, $filename)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Error al crear backup', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear backup: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Restaurar base de datos desde archivo SQL
     */
    public function restaurarBackup(Request $request)
    {
        try {
            $request->validate([
                'backup' => 'required|file|mimes:sql|max:102400' // Max 100MB
            ]);
            
            $database = env('DB_DATABASE');
            $username = env('DB_USERNAME');
            $password = env('DB_PASSWORD');
            $host = env('DB_HOST', '127.0.0.1');
            
            // Guardar archivo temporalmente
            $file = $request->file('backup');
            $filepath = $file->storeAs('backups/temp', 'restore_' . time() . '.sql');
            $fullpath = storage_path("app/{$filepath}");
            
            // Construir comando mysql
            $command = sprintf(
                'mysql --user=%s --password=%s --host=%s %s < %s 2>&1',
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($host),
                escapeshellarg($database),
                escapeshellarg($fullpath)
            );
            
            // Ejecutar comando
            exec($command, $output, $return_var);
            
            // Eliminar archivo temporal
            Storage::delete($filepath);
            
            if ($return_var !== 0) {
                Log::error('Error al restaurar backup', [
                    'output' => $output,
                    'return_var' => $return_var
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error al restaurar backup: ' . implode("\n", $output)
                ], 500);
            }
            
            // Registrar en log
            Log::info('Backup restaurado exitosamente', [
                'filename' => $file->getClientOriginalName(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Base de datos restaurada correctamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al restaurar backup', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar backup: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Resetear sistema - Eliminar todos los datos de prueba
     */
    public function resetSistema()
    {
        try {
            $eliminados = [];
            $omitidasNoExisten = [];

            $eliminarTablas = function (array $tablas, ?string $connection = null) use (&$eliminados, &$omitidasNoExisten) {
                try {
                    // Usar conexión por defecto si no se especifica
                    if ($connection) {
                        $schema = Schema::connection($connection);
                        $db = DB::connection($connection);
                    } else {
                        // Para la conexión por defecto, usar el nombre de la conexión por defecto
                        $defaultConnection = config('database.default');
                        $schema = Schema::connection($defaultConnection);
                        $db = DB::connection($defaultConnection);
                    }

                    // Probar la conexión antes de continuar
                    $db->getPdo();
                } catch (\Exception $e) {
                    Log::error("Error de conexión a BD " . ($connection ?: 'por defecto') . ": " . $e->getMessage());
                    throw $e;
                }

                foreach ($tablas as $tabla) {
                    try {
                        // Verificar si la tabla existe antes de intentar borrar
                        if (!$schema->hasTable($tabla)) {
                            $omitidasNoExisten[] = ($connection ? "{$connection}." : '') . $tabla;
                            $eliminados[($connection ? "{$connection}." : '') . $tabla] = 0;
                            continue;
                        }

                        // delete() (en vez de truncate) para minimizar problemas con FK en distintos motores/configs.
                        $eliminados[($connection ? "{$connection}." : '') . $tabla] = $db->table($tabla)->delete();
                    } catch (\Exception $e) {
                        Log::warning("Error al procesar tabla {$tabla} (" . ($connection ?: 'default') . "): " . $e->getMessage());
                        $eliminados[($connection ? "{$connection}." : '') . $tabla] = 0;
                        // No relanzar la excepción, continuar con las siguientes tablas
                    }
                }
            };

            DB::beginTransaction();
            
            // Intentar iniciar transacción en conexión proyectos si existe
            $proyectosDb = null;
            $proyectosTransactionStarted = false;
            try {
                $proyectosDb = DB::connection('proyectos');
                $proyectosDb->beginTransaction();
                $proyectosTransactionStarted = true;
            } catch (\Exception $e) {
                Log::warning('Conexión proyectos no disponible, omitiendo reset de proyectos: ' . $e->getMessage());
            }

            // DB principal: borrar SOLO datos operativos (manteniendo usuarios/config y maestros de inventario)
            $tablasOperativasPrincipal = [
                // Valores de campos personalizados (datos operativos, no la configuración)
                'custom_field_values',
                
                // Sesiones/logs/notificaciones
                'sesiones',
                'intentos_login',
                'registro_cambios',
                'notificaciones',
                'push_subscriptions',
                'checkmk_sync_logs',
                'impresoras_checkmk_sync',

                // Pedidos
                'pedidos_historial',
                'detalles_pedido',
                'pedidos',

                // Material/transacciones
                'material_firmas',
                'material_movimientos_historial',
                'material_movimiento_detalles',
                'material_movimientos',
                'material_existencias',
                'material_peticiones',
                'material_referencias',
                'solicitudes_reposicion',
            ];
            $eliminarTablas($tablasOperativasPrincipal, null);

            // DB proyectos (conexión 'proyectos'): borrar datos operativos de proyectos
            // Solo si la conexión está disponible
            if ($proyectosTransactionStarted) {
                $tablasOperativasProyectos = [
                    'tarea_dependencias',
                    'checklist_items',
                    'checklists',
                    'hitos',
                    'notificaciones',
                    'actividades',
                    'proyecto_miembro',
                    'equipo_miembro',
                    'equipos',
                    'etiquetables',
                    'etiquetas',
                    'adjuntos',
                    'comentarios',
                    'tareas',
                    'proyecto_ubicacion',
                    'ubicaciones',
                    'proyectos',
                ];
                $eliminarTablas($tablasOperativasProyectos, 'proyectos');
            }

            // Limpiar ficheros asociados
            $fotosPath = storage_path('app/public/fotos');
            if (File::exists($fotosPath)) {
                File::cleanDirectory($fotosPath);
            }

            $firmasPath = storage_path('app/firmas');
            if (File::exists($firmasPath)) {
                File::cleanDirectory($firmasPath);
            }

            $adjuntosProyectosPath = storage_path('app/proyectos/adjuntos');
            if (File::exists($adjuntosProyectosPath)) {
                File::cleanDirectory($adjuntosProyectosPath);
            }

            // Commit transacciones
            if ($proyectosTransactionStarted && $proyectosDb) {
                $proyectosDb->commit();
            }
            DB::commit();

            $totalEliminados = array_sum($eliminados);

            Log::warning('Sistema reseteado - Datos operativos eliminados', [
                'user_id' => auth()->id(),
                'eliminados' => $eliminados,
                'omitidas_no_existen' => $omitidasNoExisten,
                'total' => $totalEliminados
            ]);

            return response()->json([
                'success' => true,
                'message' => "Se eliminaron {$totalEliminados} registros correctamente",
                'detalles' => [
                    'eliminados_por_tabla' => $eliminados,
                    'tablas_omitidas_no_existen' => $omitidasNoExisten,
                ]
            ]);
            
        } catch (\Exception $e) {
            try {
                if (isset($proyectosTransactionStarted) && $proyectosTransactionStarted && isset($proyectosDb)) {
                    $proyectosDb->rollBack();
                }
            } catch (\Exception $ignored) {
                // Si no hay transacción en esta conexión, ignorar.
            }
            DB::rollBack();
            Log::error('Error al resetear sistema', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al resetear sistema: ' . $e->getMessage()
            ], 500);
        }
    }
}
