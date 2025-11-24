<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    /**
     * Nota: Este servicio requiere la librería minishlink/web-push
     * Instalar con: composer require minishlink/web-push
     * 
     * También necesita claves VAPID en .env:
     * VAPID_PUBLIC_KEY=
     * VAPID_PRIVATE_KEY=
     * 
     * Para generar claves: php artisan tinker
     * > $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
     * > echo "VAPID_PUBLIC_KEY={$keys['publicKey']}\n";
     * > echo "VAPID_PRIVATE_KEY={$keys['privateKey']}\n";
     */

    private $webPush;
    private $enabled;

    public function __construct()
    {
        // Verificar si está configurado
        $this->enabled = class_exists('\Minishlink\WebPush\WebPush') 
            && env('VAPID_PUBLIC_KEY') 
            && env('VAPID_PRIVATE_KEY');

        if ($this->enabled) {
            try {
                $this->webPush = new \Minishlink\WebPush\WebPush([
                    'VAPID' => [
                        'subject' => env('APP_URL'),
                        'publicKey' => env('VAPID_PUBLIC_KEY'),
                        'privateKey' => env('VAPID_PRIVATE_KEY'),
                    ]
                ]);
            } catch (\Exception $e) {
                Log::error('[PushNotification] Error al inicializar WebPush: ' . $e->getMessage());
                $this->enabled = false;
            }
        }
    }

    public function sendToUser($userId, string $title, string $body, array $data = [])
    {
        if (!$this->enabled) {
            Log::warning('[PushNotification] Servicio deshabilitado - no se enviará notificación');
            return;
        }

        $subscriptions = PushSubscription::where('user_id', $userId)->get();

        foreach ($subscriptions as $sub) {
            $this->send($sub, $title, $body, $data);
        }
    }

    public function sendToAll(string $title, string $body, array $data = [])
    {
        if (!$this->enabled) {
            Log::warning('[PushNotification] Servicio deshabilitado - no se enviará notificación');
            return;
        }

        $subscriptions = PushSubscription::all();

        foreach ($subscriptions as $sub) {
            $this->send($sub, $title, $body, $data);
        }
    }

    public function sendToAdmins(string $title, string $body, array $data = [])
    {
        if (!$this->enabled) {
            Log::warning('[PushNotification] Servicio deshabilitado - no se enviará notificación');
            return;
        }

        $adminSubscriptions = PushSubscription::whereHas('usuario', function ($query) {
            $query->where('rol', 'admin');
        })->get();

        foreach ($adminSubscriptions as $sub) {
            $this->send($sub, $title, $body, $data);
        }
    }

    private function send($subscription, string $title, string $body, array $data = [])
    {
        if (!$this->enabled) return;

        try {
            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'icon' => '/gestionmaterial/images/icons/icon-192x192.png',
                'badge' => '/gestionmaterial/images/icons/icon-96x96.png',
                'tag' => $data['tag'] ?? 'default',
                'requireInteraction' => $data['requireInteraction'] ?? true,
                'data' => $data
            ]);

            $webPushSubscription = \Minishlink\WebPush\Subscription::create([
                'endpoint' => $subscription->endpoint,
                'keys' => [
                    'p256dh' => $subscription->public_key,
                    'auth' => $subscription->auth_token
                ]
            ]);

            $result = $this->webPush->sendOneNotification($webPushSubscription, $payload);

            if (!$result->isSuccess()) {
                Log::warning('[PushNotification] Fallo al enviar: ' . $result->getReason());
                
                // Si el endpoint expiró, eliminar suscripción
                if ($result->isSubscriptionExpired()) {
                    $subscription->delete();
                    Log::info('[PushNotification] Suscripción expirada eliminada');
                }
            }
        } catch (\Exception $e) {
            Log::error('[PushNotification] Error al enviar notificación: ' . $e->getMessage());
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
