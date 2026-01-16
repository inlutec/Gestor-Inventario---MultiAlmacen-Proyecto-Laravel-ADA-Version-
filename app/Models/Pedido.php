<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos';

    protected $fillable = [
        'tipo',
        'numero_pedido',
        'fecha',
        'fecha_pedido',
        'fecha_recepcion',
        'estado',
        'notas',
        'observaciones',
        'albaran_foto',
        'datos',
        'datos_personalizados',
        'impresora_id',
        'usuario_creador_id',
        'usuario_solicitante',
        'email_solicitante',
        'telefono_solicitante',
        'sede_id',
        'departamento_id',
        'aprobacion_parcial',
        'comentarios_aprobacion',
        'usuario_aprobador_id',
        'fecha_aprobacion',
        'cantidad_aprobada',
        'movimiento_id',
        'token_seguimiento',
        'token_seguimiento_expira',
    ];

    protected $casts = [
        'datos' => 'array',
        'datos_personalizados' => 'array',
        'fecha' => 'date',
        'fecha_pedido' => 'date',
        'fecha_recepcion' => 'date',
        'fecha_aprobacion' => 'datetime',
        'aprobacion_parcial' => 'boolean',
        'token_seguimiento_expira' => 'datetime',
    ];

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class, 'pedido_id');
    }

    public function impresora()
    {
        return $this->belongsTo(Entidad::class, 'impresora_id');
    }

    public function usuarioCreador()
    {
        return $this->belongsTo(Usuario::class, 'usuario_creador_id');
    }

    public function usuarioAprobador()
    {
        return $this->belongsTo(Usuario::class, 'usuario_aprobador_id');
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }

    /**
     * Relación con el historial de cambios
     */
    public function historial()
    {
        return $this->hasMany(PedidoHistorial::class, 'pedido_id');
    }

    /**
     * Generar token de seguimiento público
     */
    public function generarTokenSeguimiento($diasExpiracion = 90)
    {
        $this->token_seguimiento = Str::random(64);
        $this->token_seguimiento_expira = now()->addDays($diasExpiracion);
        $this->save();
        
        return $this->token_seguimiento;
    }

    /**
     * Verificar si el token de seguimiento es válido
     */
    public function tokenSeguimientoEsValido(): bool
    {
        return $this->token_seguimiento &&
               $this->token_seguimiento_expira &&
               $this->token_seguimiento_expira->isFuture();
    }
}
