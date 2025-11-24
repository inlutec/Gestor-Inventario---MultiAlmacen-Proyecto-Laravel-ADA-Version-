<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

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
            DB::beginTransaction();
            
            // Contador de registros eliminados
            $eliminados = [
                'custom_field_values' => 0,
                'custom_fields' => 0,
                'material_firmas' => 0,
                'material_movimiento_historial' => 0,
                'material_movimiento_lineas' => 0,
                'material_movimientos' => 0,
                'material_existencias' => 0,
                'material_peticiones' => 0,
                'material_referencias' => 0,
            ];
            
            // 1. Eliminar valores de campos personalizados
            $eliminados['custom_field_values'] = DB::table('custom_field_values')->delete();
            
            // 2. Eliminar campos personalizados
            $eliminados['custom_fields'] = DB::table('custom_fields')->delete();
            
            // 3. Eliminar firmas de movimientos
            $eliminados['material_firmas'] = DB::table('material_firmas')->delete();
            
            // 4. Eliminar historial de movimientos
            $eliminados['material_movimiento_historial'] = DB::table('material_movimiento_historial')->delete();
            
            // 5. Eliminar líneas de movimientos
            $eliminados['material_movimiento_lineas'] = DB::table('material_movimiento_lineas')->delete();
            
            // 6. Eliminar movimientos
            $eliminados['material_movimientos'] = DB::table('material_movimientos')->delete();
            
            // 7. Eliminar existencias
            $eliminados['material_existencias'] = DB::table('material_existencias')->delete();
            
            // 8. Eliminar peticiones públicas
            $eliminados['material_peticiones'] = DB::table('material_peticiones')->delete();
            
            // 9. Eliminar referencias de material
            $eliminados['material_referencias'] = DB::table('material_referencias')->delete();
            
            // 10. Eliminar fotos de material del storage
            $fotosPath = storage_path('app/public/fotos');
            if (File::exists($fotosPath)) {
                File::cleanDirectory($fotosPath);
            }
            
            // 11. Eliminar firmas del storage
            $firmasPath = storage_path('app/firmas');
            if (File::exists($firmasPath)) {
                File::cleanDirectory($firmasPath);
            }
            
            DB::commit();
            
            // Calcular total
            $totalEliminados = array_sum($eliminados);
            
            // Registrar en log
            Log::warning('Sistema reseteado - Todos los datos eliminados', [
                'user_id' => auth()->id(),
                'eliminados' => $eliminados,
                'total' => $totalEliminados
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Se eliminaron {$totalEliminados} registros correctamente",
                'detalles' => $eliminados
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al resetear sistema', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al resetear sistema: ' . $e->getMessage()
            ], 500);
        }
    }
}
