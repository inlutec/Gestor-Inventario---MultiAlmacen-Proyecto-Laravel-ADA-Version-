<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MaterialMovimiento extends Model
{
    protected $table = 'material_movimientos';

    protected $fillable = [
        'tipo',
        'numero_documento',
        'fecha_movimiento',
        'usuario_id',
        'origen_sede_id',
        'origen_departamento_id',
        'origen',
        'destino_sede_id',
        'destino_departamento_id',
        'destino',
        'observaciones',
        'estado',
        'enlace_publico',
        'enlace_expira',
        'fecha_entrega',
        'entregado_por',
        'fecha_prevista_entrega',
    ];

    protected $casts = [
        'fecha_movimiento' => 'datetime',
        'enlace_expira' => 'datetime',
        'fecha_entrega' => 'datetime',
        'fecha_prevista_entrega' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function usuarioEntrega()
    {
        return $this->belongsTo(Usuario::class, 'entregado_por');
    }

    public function detalles()
    {
        return $this->hasMany(MaterialMovimientoDetalle::class, 'movimiento_id');
    }

    public function firmas()
    {
        return $this->hasMany(MaterialFirma::class, 'movimiento_id');
    }

    public function firmaEmisor()
    {
        return $this->hasOne(MaterialFirma::class, 'movimiento_id')->where('tipo_firmante', 'emisor');
    }

    public function firmaReceptor()
    {
        return $this->hasOne(MaterialFirma::class, 'movimiento_id')->where('tipo_firmante', 'receptor');
    }

    public function origenSede()
    {
        return $this->belongsTo(Sede::class, 'origen_sede_id');
    }

    public function origenDepartamento()
    {
        return $this->belongsTo(\App\Models\Departamento::class, 'origen_departamento_id');
    }

    public function destinoSede()
    {
        return $this->belongsTo(Sede::class, 'destino_sede_id');
    }

    public function destinoDepartamento()
    {
        return $this->belongsTo(\App\Models\Departamento::class, 'destino_departamento_id');
    }

    public function historial()
    {
        return $this->hasMany(MaterialMovimientoHistorial::class, 'movimiento_id')->orderBy('fecha', 'desc');
    }

    public function pedido()
    {
        // Relación inversa: pedidos.movimiento_id apunta a este movimiento
        return $this->hasOne(Pedido::class, 'movimiento_id');
    }

    /**
     * Determina si el documento ya cuenta con todas las firmas requeridas
     * - ENTRADA: requiere firma del receptor
     * - SALIDA: requiere firma del emisor y del receptor
     */
    public function tieneFirmasCompletas(): bool
    {
        if ($this->tipo === 'entrada') {
            return (bool) $this->firmaReceptor;
        }
        // Salida: si el destino es una sede/departamento conocido => requiere emisor + receptor
        $destConocido = $this->destino_sede_id || $this->destino_departamento_id;
        if ($destConocido) {
            return (bool) ($this->firmaEmisor && $this->firmaReceptor);
        }
        // Si el destino no es conocido, basta con receptor
        return (bool) $this->firmaReceptor;
    }

    /**
     * Generar número de documento automático
     */
    public static function generarNumeroDocumento($tipo)
    {
        $prefix = $tipo === 'entrada' ? 'ENT' : 'SAL';
        $year = date('Y');
        $month = date('m');
        
        $ultimo = self::where('tipo', $tipo)
            ->where('numero_documento', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('numero_documento', 'desc')
            ->first();
        
        if ($ultimo) {
            $numero = (int)substr($ultimo->numero_documento, -4) + 1;
        } else {
            $numero = 1;
        }
        
        return sprintf("%s-%s%s-%04d", $prefix, $year, $month, $numero);
    }

    /**
     * Generar enlace público
     */
    public function generarEnlacePublico($diasExpiracion = 7)
    {
        $this->enlace_publico = Str::random(64);
        $this->enlace_expira = now()->addDays($diasExpiracion);
        $this->save();
        
        return $this->enlace_publico;
    }

    /**
     * Verificar si el enlace es válido
     */
    public function enlaceEsValido()
    {
        return $this->enlace_publico && 
               $this->enlace_expira && 
               $this->enlace_expira->isFuture() &&
               $this->estado !== 'cancelado';
    }
}
