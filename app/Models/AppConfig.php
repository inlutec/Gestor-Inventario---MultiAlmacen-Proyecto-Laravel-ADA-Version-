<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppConfig extends Model
{
    use HasFactory;

    protected $table = 'app_config';

    protected $fillable = [
        'app_domain',
    ];

    /**
     * Obtener la configuración (siempre hay solo un registro)
     */
    public static function getConfig()
    {
        $config = self::first();
        if (!$config) {
            // Si no existe, crear con valores por defecto
            $config = self::create([
                'app_domain' => 'http://10.66.129.108'
            ]);
        }
        return $config;
    }

    /**
     * Obtener el dominio base de la aplicación
     */
    public static function getAppDomain()
    {
        $config = self::getConfig();
        return $config->app_domain ?? 'http://10.66.129.108';
    }
}
