<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sesion extends Model
{
    protected $table = 'sesiones';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'usuario_id',
        'ip',
        'fecha_expiracion',
        'activa',
    ];

    protected $casts = [
        'fecha_expiracion' => 'datetime',
        'activa' => 'boolean',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
