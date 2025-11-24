<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Guardar suscripción push del usuario
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|url',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        // Aquí deberías guardar la suscripción en la base de datos
        // Por ahora solo logueamos
        Log::info('Nueva suscripción push', [
            'user_id' => $user->id,
            'endpoint' => $validated['endpoint'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Suscripción registrada correctamente'
        ]);
    }

    /**
     * Cancelar suscripción push
     */
    public function unsubscribe(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        Log::info('Suscripción push cancelada', [
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Suscripción cancelada'
        ]);
    }

    /**
     * Enviar notificación de prueba
     */
    public function test(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        // Esta es solo una respuesta de ejemplo
        // En producción, aquí enviarías la notificación real usando Web Push
        
        return response()->json([
            'success' => true,
            'message' => 'Notificación enviada',
            'data' => [
                'title' => 'Notificación de prueba',
                'body' => '¡Las notificaciones funcionan correctamente!',
                'icon' => '/images/icons/icon-192x192.png',
            ]
        ]);
    }
}
