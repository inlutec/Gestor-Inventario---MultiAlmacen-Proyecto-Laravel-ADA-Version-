<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroCambio extends Model
{
    use HasFactory;

    protected $table = 'registro_cambios';

    protected $fillable = [
        'entidad_id',
        'tipo_entidad',
        'accion',
        'datos_anteriores',
        'datos_nuevos',
        'usuario_id',
        'ip',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Registrar un cambio en una entidad
     */
    public static function registrar($entidadTipo, $entidadId, $accion, $datosAnteriores = null, $datosNuevos = null, $comentario = null)
    {
        return self::create([
            'tipo_entidad' => $entidadTipo,
            'entidad_id' => $entidadId,
            'accion' => $accion,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => $datosNuevos,
            'usuario_id' => auth()->id(),
            'ip' => request()->ip(),
        ]);
    }
}
