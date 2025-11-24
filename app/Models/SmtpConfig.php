<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SmtpConfig extends Model
{
    protected $table = 'smtp_config';

    protected $fillable = [
        'provider',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'from_address',
        'from_name',
        'activo',
        'ultima_prueba',
        'resultado_prueba',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'ultima_prueba' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    // Encriptar password al guardar
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = Crypt::encryptString($value);
        }
    }

    // Desencriptar password al leer
    public function getPasswordAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    // Obtener configuración activa
    public static function getActive()
    {
        return static::where('activo', true)->first();
    }

    // Aplicar configuración SMTP a Laravel
    public function apply()
    {
        // Configurar los valores en config
        config([
            'mail.mailers.smtp.host' => $this->host,
            'mail.mailers.smtp.port' => $this->port,
            'mail.mailers.smtp.encryption' => $this->encryption === 'none' ? null : $this->encryption,
            'mail.from.address' => $this->from_address,
            'mail.from.name' => $this->from_name,
        ]);

        // Solo configurar autenticación si hay username Y password
        if (!empty($this->username) && !empty($this->password)) {
            config([
                'mail.mailers.smtp.username' => $this->username,
                'mail.mailers.smtp.password' => $this->password,
            ]);
        } else {
            // Sin autenticación - desactivar completamente
            config([
                'mail.mailers.smtp.username' => '',
                'mail.mailers.smtp.password' => '',
            ]);
            
            // También limpiar variables de entorno para evitar que se usen
            putenv('MAIL_USERNAME=');
            putenv('MAIL_PASSWORD=');
        }
        
        // Forzar recreación del mailer para que tome la nueva configuración
        app()->forgetInstance('mail.manager');
        app()->forgetInstance(\Illuminate\Contracts\Mail\Mailer::class);
    }
}
