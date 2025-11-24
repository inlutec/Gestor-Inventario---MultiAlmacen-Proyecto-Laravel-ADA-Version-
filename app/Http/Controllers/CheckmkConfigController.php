<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CheckmkConfig;
use App\Models\CheckmkSyncLog;
use Illuminate\Support\Facades\Log;

class CheckmkConfigController extends Controller
{
    /**
     * Obtener la configuración actual
     */
    public function getConfig()
    {
        try {
            $config = CheckmkConfig::first();
            
            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración no encontrada',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'api_url' => $config->api_url,
                    'api_user' => $config->api_user,
                    'site' => $config->site,
                    'sync_interval_minutes' => $config->sync_interval_minutes,
                    'last_sync' => $config->last_sync,
                    // No enviamos la password por seguridad
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener configuración CheckMK', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la configuración',
            ], 500);
        }
    }

    /**
     * Actualizar la configuración
     */
    public function updateConfig(Request $request)
    {
        try {
            $request->validate([
                'api_url' => 'required|url',
                'api_user' => 'required|string',
                'api_password' => 'nullable|string',
                'site' => 'required|string',
                'sync_interval_minutes' => 'required|integer|min:1|max:1440',
            ]);

            $config = CheckmkConfig::first();
            
            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración no encontrada',
                ], 404);
            }

            $config->api_url = $request->api_url;
            $config->api_user = $request->api_user;
            $config->site = $request->site;
            $config->sync_interval_minutes = $request->sync_interval_minutes;
            
            // Solo actualizar password si se proporciona
            if ($request->filled('api_password')) {
                $config->api_password = $request->api_password;
            }
            
            $config->save();

            // Actualizar el cron job con el nuevo intervalo
            $this->updateCronJob($request->sync_interval_minutes);

            return response()->json([
                'success' => true,
                'message' => 'Configuración actualizada correctamente',
                'data' => [
                    'api_url' => $config->api_url,
                    'api_user' => $config->api_user,
                    'site' => $config->site,
                    'sync_interval_minutes' => $config->sync_interval_minutes,
                    'last_sync' => $config->last_sync,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al actualizar configuración CheckMK', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la configuración: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener los logs de sincronización
     */
    public function getLogs(Request $request)
    {
        try {
            $limit = $request->input('limit', 100);
            $logs = CheckmkSyncLog::orderBy('sync_timestamp', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $logs,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener logs CheckMK', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los logs',
            ], 500);
        }
    }

    /**
     * Actualizar el intervalo del cron job
     */
    private function updateCronJob($minutes)
    {
        try {
            // Aquí puedes implementar la lógica para actualizar el cron
            // Por ahora, el sistema usará el valor de la BD cuando se ejecute
            Log::info("Intervalo de sincronización actualizado a {$minutes} minutos");
        } catch (\Exception $e) {
            Log::error('Error al actualizar cron job', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Test de conexión con CheckMK
     */
    public function testConnection()
    {
        try {
            $config = CheckmkConfig::first();
            
            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración no encontrada',
                ], 404);
            }

            // Intentar conectar con la API
            $client = new \GuzzleHttp\Client([
                'verify' => false,
                'timeout' => 10,
            ]);

            $response = $client->get($config->api_url . '/' . $config->site . '/check_mk/api/1.0/version', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $config->api_user . ' ' . $config->api_password,
                    'Accept' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                return response()->json([
                    'success' => true,
                    'message' => 'Conexión exitosa con CheckMK',
                    'version' => json_decode($response->getBody(), true),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se pudo conectar con CheckMK',
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage(),
            ], 500);
        }
    }
}
