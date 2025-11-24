<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PushSubscription;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    private $pushService;

    public function __construct(PushNotificationService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * Suscribir dispositivo a notificaciones push
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'device_type' => 'nullable|string|in:android,ios,web'
        ]);

        $userId = Auth::id();

        try {
            PushSubscription::updateOrCreate(
                ['endpoint' => $validated['endpoint']],
                [
                    'user_id' => $userId,
                    'public_key' => $validated['keys']['p256dh'],
                    'auth_token' => $validated['keys']['auth'],
                    'device_type' => $request->input('device_type', 'web')
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Suscripción registrada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar suscripción: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Desuscribir dispositivo
     */
    public function unsubscribe(Request $request)
    {
        $endpoint = $request->input('endpoint');
        
        if (!$endpoint) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint requerido'
            ], 400);
        }

        try {
            $deleted = PushSubscription::where('endpoint', $endpoint)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Desuscripción exitosa',
                'deleted' => $deleted
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desuscribir: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener clave pública VAPID para suscripción
     */
    public function getPublicKey()
    {
        $publicKey = env('VAPID_PUBLIC_KEY');

        if (!$publicKey) {
            return response()->json([
                'success' => false,
                'message' => 'Clave pública VAPID no configurada'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'publicKey' => $publicKey
        ]);
    }

    /**
     * Enviar notificación de prueba al usuario actual
     */
    public function testNotification(Request $request)
    {
        $userId = Auth::id();

        if (!$this->pushService->isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Servicio de notificaciones no configurado. Instale minishlink/web-push y configure claves VAPID.'
            ], 500);
        }

        try {
            $this->pushService->sendToUser(
                $userId,
                'Notificación de prueba',
                'Esta es una notificación de prueba desde Gestión de Material',
                [
                    'url' => '/gestionmaterial/',
                    'tag' => 'test-notification'
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Notificación de prueba enviada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar notificación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de suscripciones (solo admin)
     */
    public function stats()
    {
        $user = Auth::user();

        if ($user->rol !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $total = PushSubscription::count();
        $byDevice = PushSubscription::selectRaw('device_type, COUNT(*) as count')
            ->groupBy('device_type')
            ->get();
        $byUser = PushSubscription::selectRaw('user_id, COUNT(*) as devices')
            ->groupBy('user_id')
            ->having('devices', '>', 1)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_subscriptions' => $total,
                'by_device' => $byDevice,
                'users_multiple_devices' => $byUser->count()
            ]
        ]);
    }
}
