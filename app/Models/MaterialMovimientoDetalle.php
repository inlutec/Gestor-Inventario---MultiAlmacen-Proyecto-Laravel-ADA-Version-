<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialMovimientoDetalle extends Model
{
    protected $table = 'material_movimiento_detalles';

    protected $fillable = [
        'movimiento_id',
        'entidad_id',
        'descripcion',
        'cantidad',
        'unidad',
        'observaciones',
    ];

    public function movimiento()
    {
        return $this->belongsTo(MaterialMovimiento::class, 'movimiento_id');
    }

    public function entidad()
    {
        return $this->belongsTo(Entidad::class, 'entidad_id');
    }
}
