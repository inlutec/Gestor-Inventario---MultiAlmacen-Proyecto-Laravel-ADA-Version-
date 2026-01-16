<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoHistorial extends Model
{
    use HasFactory;

    protected $table = 'pedidos_historial';

    protected $fillable = [
        'pedido_id',
        'usuario_id',
        'accion',
        'descripcion',
        'datos_antes',
        'datos_despues',
        'ip_address',
        'visible_publico',
        'fecha',
    ];

    protected $casts = [
        'datos_antes' => 'array',
        'datos_despues' => 'array',
        'fecha' => 'datetime',
        'visible_publico' => 'boolean',
    ];

    /**
     * Relación con el pedido
     */
    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    /**
     * Relación con el usuario que realizó la acción
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Registrar un cambio en el historial
     */
    public static function registrarCambio($pedidoId, $accion, $descripcion, $datosAntes = null, $datosDespues = null, $usuarioId = null, $visiblePublico = false)
    {
        return self::create([
            'pedido_id' => $pedidoId,
            'usuario_id' => $usuarioId ?? auth()->id(),
            'accion' => $accion,
            'descripcion' => $descripcion,
            'datos_antes' => $datosAntes,
            'datos_despues' => $datosDespues,
            'ip_address' => request()->ip(),
            'visible_publico' => $visiblePublico,
            'fecha' => now(),
        ]);
    }
}
