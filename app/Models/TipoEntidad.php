<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoEntidad extends Model
{
    use HasFactory;

    protected $table = 'tipos_entidad';

    protected $fillable = [
        'nombre',
        'clave',
        'icono',
        'color',
        'orden',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];

    public function campos()
    {
        return $this->hasMany(Campo::class, 'tipo_entidad_id')->orderBy('orden');
    }

    public function entidades()
    {
        return $this->hasMany(Entidad::class, 'tipo_entidad_id');
    }
}
