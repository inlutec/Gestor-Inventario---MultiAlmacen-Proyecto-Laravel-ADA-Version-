<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckmkConfig extends Model
{
    protected $table = 'checkmk_config';

    protected $fillable = [
        'api_url',
        'api_user',
        'api_password',
        'site',
        'sync_interval_minutes',
        'last_sync',
    ];

    protected $casts = [
        'last_sync' => 'datetime',
    ];

    /**
     * Obtener la configuración activa (siempre hay solo un registro)
     */
    public static function getConfig()
    {
        return self::first();
    }
}
