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
        // PRIMERO: Limpiar todas las variables de entorno de MAIL para evitar interferencias
        // Esto asegura que Laravel use los valores de config() en lugar de env()
        $envVars = [
            'MAIL_HOST',
            'MAIL_PORT',
            'MAIL_USERNAME',
            'MAIL_PASSWORD',
            'MAIL_ENCRYPTION',
            'MAIL_FROM_ADDRESS',
            'MAIL_FROM_NAME',
            'MAIL_MAILER',
        ];
        
        foreach ($envVars as $var) {
            putenv($var);
            if (isset($_ENV[$var])) {
                unset($_ENV[$var]);
            }
            if (isset($_SERVER[$var])) {
                unset($_SERVER[$var]);
            }
        }
        
        // SEGUNDO: Configurar los valores en config() - estos tienen prioridad sobre env()
        $encryption = ($this->encryption === 'none' || empty($this->encryption)) ? null : $this->encryption;
        
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $this->host,
            'mail.mailers.smtp.port' => (int)$this->port,
            'mail.mailers.smtp.encryption' => $encryption,
            'mail.from.address' => $this->from_address,
            'mail.from.name' => $this->from_name,
        ]);

        // TERCERO: Configurar autenticación si hay username Y password
        if (!empty($this->username) && !empty($this->password)) {
            config([
                'mail.mailers.smtp.username' => $this->username,
                'mail.mailers.smtp.password' => $this->password,
            ]);
        } else {
            // Sin autenticación - desactivar completamente
            config([
                'mail.mailers.smtp.username' => null,
                'mail.mailers.smtp.password' => null,
            ]);
        }
        
        // CUARTO: Forzar recreación completa del mailer para que tome la nueva configuración
        // Limpiar todas las instancias relacionadas con mail
        app()->forgetInstance('mail.manager');
        app()->forgetInstance(\Illuminate\Contracts\Mail\Mailer::class);
        app()->forgetInstance(\Illuminate\Contracts\Mail\Factory::class);
        app()->forgetInstance('swift.mailer');
        app()->forgetInstance('swift.transport');
        
        // Limpiar cache de configuración si existe
        if (app()->bound('cache')) {
            app('cache')->forget('mail.config');
        }
    }
}
