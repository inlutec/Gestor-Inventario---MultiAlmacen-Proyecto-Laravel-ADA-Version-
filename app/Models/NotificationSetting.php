<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'evento',
        'notificar_usuario',
        'notificar_admin',
        'descripcion'
    ];

    protected $casts = [
        'notificar_usuario' => 'boolean',
        'notificar_admin' => 'boolean',
    ];

    /**
     * Verificar si un evento debe notificar a usuarios
     */
    public static function debeNotificarUsuario(string $evento): bool
    {
        $setting = self::where('evento', $evento)->first();
        return $setting ? $setting->notificar_usuario : false;
    }

    /**
     * Verificar si un evento debe notificar a administradores
     */
    public static function debeNotificarAdmin(string $evento): bool
    {
        $setting = self::where('evento', $evento)->first();
        return $setting ? $setting->notificar_admin : false;
    }

    /**
     * Obtener destinatarios para un evento
     */
    public static function obtenerDestinatarios(string $evento, $usuario = null, $admins = []): array
    {
        $destinatarios = [];
        
        if (self::debeNotificarUsuario($evento) && $usuario && $usuario->email) {
            $destinatarios[] = $usuario->email;
        }
        
        if (self::debeNotificarAdmin($evento) && count($admins) > 0) {
            foreach ($admins as $admin) {
                if ($admin->email) {
                    $destinatarios[] = $admin->email;
                }
            }
        }
        
        return array_unique($destinatarios);
    }
}
