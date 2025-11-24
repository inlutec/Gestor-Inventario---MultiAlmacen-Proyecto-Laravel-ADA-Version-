<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FirmaMovilController extends Controller
{
    /**
     * Mantiene una conexión SSE abierta para enviar solicitudes de firma en tiempo real
     */
    public function stream(Request $request)
    {
        $sessionId = $request->query('session');

        if (!$sessionId) {
            return response()->json(['error' => 'Session ID requerido'], 400);
        }

        // Registrar sesión activa en caché (expira en 24 horas)
        Cache::put("firma_movil_session:{$sessionId}", [
            'connected_at' => now(),
            'last_ping' => now(),
        ], now()->addHours(24));

        $response = new StreamedResponse(function () use ($sessionId) {
            // Configurar SSE
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no'); // Deshabilitar buffering de Nginx

            // Enviar ping inicial
            echo "data: " . json_encode(['tipo' => 'connected', 'session' => $sessionId]) . "\n\n";
            ob_flush();
            flush();

            // Mantener conexión abierta y enviar pings cada 15 segundos
            $startTime = time();
            $maxDuration = 3600; // 1 hora máximo

            while (time() - $startTime < $maxDuration) {
                // Verificar si hay solicitud de firma pendiente
                $solicitud = Cache::get("firma_movil_solicitud:{$sessionId}");

                if ($solicitud) {
                    // Enviar solicitud de firma al dispositivo
                    echo "data: " . json_encode([
                        'tipo' => 'solicitud_firma',
                        'movimiento' => $solicitud['movimiento'],
                        'tipo_firma' => $solicitud['tipo_firma'],
                        'timestamp' => now()->toIso8601String(),
                    ]) . "\n\n";
                    ob_flush();
                    flush();

                    // Eliminar solicitud de caché después de enviarla
                    Cache::forget("firma_movil_solicitud:{$sessionId}");
                }

                // Ping cada 15 segundos para mantener conexión
                echo "data: " . json_encode(['tipo' => 'ping', 'time' => now()->toIso8601String()]) . "\n\n";
                ob_flush();
                flush();

                // Actualizar last_ping
                $session = Cache::get("firma_movil_session:{$sessionId}");
                if ($session) {
                    $session['last_ping'] = now();
                    Cache::put("firma_movil_session:{$sessionId}", $session, now()->addHours(24));
                }

                sleep(15);

                // Verificar si la conexión sigue activa
                if (connection_aborted()) {
                    break;
                }
            }

            // Limpiar sesión al cerrar conexión
            Cache::forget("firma_movil_session:{$sessionId}");
        });

        return $response;
    }

    /**
     * Lista las sesiones activas de firma móvil
     */
    public function sesionesActivas()
    {
        $sesiones = [];
        $keys = Cache::get('firma_movil_sessions_list', []);

        foreach ($keys as $sessionId) {
            $sessionData = Cache::get("firma_movil_session:{$sessionId}");
            if ($sessionData) {
                $sesiones[] = [
                    'session_id' => $sessionId,
                    'connected_at' => $sessionData['connected_at'],
                    'last_ping' => $sessionData['last_ping'],
                ];
            }
        }

        return response()->json($sesiones);
    }
}
