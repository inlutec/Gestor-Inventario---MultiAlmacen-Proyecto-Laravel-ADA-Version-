<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campo extends Model
{
    use HasFactory;

    protected $table = 'campos';

    protected $fillable = [
        'tipo_entidad_id',
        'nombre',
        'clave',
        'tipo_dato',
        'opciones',
        'obligatorio',
        'mostrar_en_tabla',
        'orden',
    ];

    protected $casts = [
        'opciones' => 'array',
        'obligatorio' => 'boolean',
        'mostrar_en_tabla' => 'boolean',
        'orden' => 'integer',
    ];

    public function tipoEntidad()
    {
        return $this->belongsTo(TipoEntidad::class, 'tipo_entidad_id');
    }
}
