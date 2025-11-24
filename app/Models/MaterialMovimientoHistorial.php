<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialMovimientoHistorial extends Model
{
    protected $table = 'material_movimientos_historial';

    protected $fillable = [
        'movimiento_id',
        'usuario_id',
        'accion',
        'descripcion',
        'datos_antes',
        'datos_despues',
        'ip_address',
        'fecha'
    ];

    protected $casts = [
        'datos_antes' => 'array',
        'datos_despues' => 'array',
        'fecha' => 'datetime'
    ];

    public function movimiento()
    {
        return $this->belongsTo(MaterialMovimiento::class, 'movimiento_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Registrar un cambio en el historial de un movimiento
     */
    public static function registrarCambio(
        int $movimientoId,
        string $accion,
        string $descripcion,
        ?array $datosAntes = null,
        ?array $datosDespues = null,
        ?int $usuarioId = null
    ) {
        return self::create([
            'movimiento_id' => $movimientoId,
            'usuario_id' => $usuarioId ?? auth()->id(),
            'accion' => $accion,
            'descripcion' => $descripcion,
            'datos_antes' => $datosAntes,
            'datos_despues' => $datosDespues,
            'ip_address' => request()->ip(),
            'fecha' => now()
        ]);
    }
}
